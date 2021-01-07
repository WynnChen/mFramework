<?php
/**
 * mFramework - a mini PHP framework
 * 
 * @package   mFramework
 * @version   v5
 * @copyright 2009-2016 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework\Http;

use \mFramework\Map;

/**
 * HTTP Response
 *
 * 本Class的基本响应模型：
 * 将response分成response code, header, body,cookie这4个部分看待，
 * 分别有相应的方法组来维护。
 * 在最终response()时，根据这4个部分的信息生成最终的 http response 发回。
 * response code 的抽象模型是code与msg两个字段，
 * header 看成是关联数组， body 则视为字符串。
 * 最终 response code 与 header 信息将用于生成相应的 http response header。
 *
 * 如果有必要，可以跳过这个标准模式直接接管响应处理，调用
 * setHeaderHandle($callback)与setBodyHandle($callback),
 * 将在适当的时候（response()中）调用这两个callback分别负责发送header与body。
 * 如果需要处理response code和cookie，都要在 header handle 中进行。
 *
 * 如果再有必要，可以直接用disableAutoResponse()关闭对response()的自动调用，
 * 然后自行在恰当的时候处理输出。注意这种情况下本class无法正确判断是否已经发送响应，需要自行处理。
 *
 * 本class同时持有View管理，在response()中会尝试进行render()，
 * 这个特性可以用disableAutoRender()关闭。
 *
 * auto response： 如果启用，框架会在恰当的时候（运行的末端，并且response()未被调用过）调用response()。
 * auto render： 如果启用，response()中会自动在必要时调用render()
 *
 *
 * @package mFramework
 * @author Wynn Chen
 */
class Response
{

	/**
	 * 按照HTTP/1.1， RFC 2616。
	 * http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.3.3
	 *
	 * @var array
	 */
	private static $status_code_info = array(100 => 'Continue',101 => 'Switching Protocols',200 => 'OK',201 => 'Created',202 => 'Accepted',203 => 'Non-Authoritative Information',204 => 'No Content',205 => 'Reset Content',206 => 'Partial Content',300 => 'Multiple Choices',301 => 'Moved Permanently',302 => 'Found',303 => 'See Other',304 => 'Not Modified',305 => 'Use Proxy',307 => 'Temporary Redirect',400 => 'Bad Request',401 => 'Unauthorized',402 => 'Payment Required',403 => 'Forbidden',404 => 'Not Found',405 => 'Method Not Allowed',406 => 'Not Acceptable',407 => 'Proxy Authentication Required',408 => 'Request Timeout',409 => 'Conflict',410 => 'Gone',411 => 'Length Required',412 => 'Precondition Failed',413 => 'Request Entity Too Large',414 => 'Request-URI Too Long',415 => 'Unsupported Media Type',416 => 'Requested Range Not Satisfiable',417 => 'Expectation Failed',500 => 'Internal Server Error',501 => 'Not Implemented',502 => 'Bad Gateway',503 => 'Service Unavailable',504 => 'Gateway Timeout',505 => 'HTTP Version Not Supported');

	private $header = array();

	private $body = null;

	private $cookies = array();

	private $response_code = 200;

	private $response_code_msg = 'OK';

	private $auto_response = true;

	private $sent = false;

	protected $header_handle;

	protected $body_handle;

	public function __construct()
	{
		$this->data = new Map();
		$this->header_handle = array($this,'sendHeader');
		$this->body_handle = array($this,'sendBody');
	}

	/**
	 * 禁止自动发送响应
	 */
	public function disableAutoResponse()
	{
		$this->auto_response = false;
	}

	/**
	 * 允许自动发送响应
	 */
	public function enableAutoResponse()
	{
		$this->auto_response = true;
	}

	/**
	 * 自动响应开着？
	 *
	 * @return boolean
	 */
	public function isAutoResponseEnabled()
	{
		return $this->auto_response;
	}

	/**
	 * cookie设置
	 * 注意name相同时将会覆盖前一个设置。
	 *
	 * @param string $name
	 *			名称
	 * @param string $value
	 *			值
	 * @param int|string $time
	 *			持续时间，如果是int，为timestamp。如果是string，那么将用strtotime()解析为绝对值。
	 * @param string $path			
	 * @param string $domain			
	 * @param bool $secure			
	 * @param bool $httponly			
	 */
	public function setCookie($name, $value, $time = null, $path = '/', $domain = null, $secure = null, $httponly = null)
	{
		if ($time !== null and is_string($time)) {
			$time = strtotime($time);
		}
		$this->cookies[$name] = array('value' => $value,'expire' => $time,'path' => $path,'domain' => $domain,'secure' => $secure,'httponly' => $httponly);
	}

	/**
	 * 获取将要发送的cookie设置，如果已经设置过，为
	 * array(
	 * 'value' => $value,
	 * 'expires' => $time,
	 * 'path' => $path,
	 * 'domain' => $domain,
	 * 'secure' => $secure,
	 * 'httponly' => $httponly
	 * );
	 * 否则为null。
	 * 注意不要和request中的cookie混淆。
	 *
	 * @param string $name			
	 * @return array|null
	 */
	public function getCookie($name)
	{
		if (isset($this->cookies[$name])) {
			return $this->cookies[$name];
		} else {
			return null;
		}
	}

	/**
	 * 设置一个header信息
	 * 注意name一样的情况下会覆盖之前设置的。
	 * 如果需要在最终发送中发送多个同类header信息，使用数组作为$content的信息。
	 * 设置为null等于清除此header
	 * 状态码不受此影响，需要使用setResponseCode()方法。
	 *
	 *
	 * 注意如果直接调用header()函数，不受此方法影响。
	 *
	 *
	 * @param string $name			
	 * @param string|array $content			
	 */
	public function setHeader($name, $content)
	{
		$this->header[$name] = $content;
	}

	/**
	 * 取一个header，如果没有设置过则为null
	 *
	 * @param string $name			
	 * @return string|null
	 */
	public function getHeader($name)
	{
		return isset($this->header[$name]) ? $this->header[$name] : null;
	}

	/**
	 * 清除所有已经设置的header
	 */
	public function clearHeaders()
	{
		$this->header = array();
	}

	/**
	 * 设置body。设为null即为清除
	 *
	 * @param string $body			
	 */
	public function setBody($body)
	{
		$this->body = is_null($body) ? null : (string)$body;
	}

	/**
	 * 获取body
	 *
	 * @return string|null
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * 设置返回状态码
	 *
	 * @param int $code
	 *			状态码
	 * @param string $msg
	 *			对应的文字信息，为null时会尝试自动设置
	 */
	public function setResponseCode($code, $msg = null)
	{
		$code = (int)$code;
		if ($msg === null and isset(self::$status_code_info[$code])) {
			$msg = self::$status_code_info[$code];
		}
		$this->response_code = $code;
		$this->response_code_msg = $msg;
	}

	/**
	 * 返回状态码信息
	 *
	 * @param boolean $with_msg
	 *			是否需要取得相应的文本信息
	 * @return string|array 返回的信息
	 */
	public function getResponseCode($with_msg = false)
	{
		if ($with_msg) {
			return array('code' => $this->response_code,'msg' => $this->response_code_msg);
		} else {
			return $this->response_code;
		}
	}

	/**
	 * 快捷方式：响应为一个url跳转。
	 * 注意在action中不能额外指定view。
	 *
	 * 如果需要实现复杂的方案，不要调用本方法，自行在action处理。
	 *
	 * @param string $url			
	 * @param int $code
	 *			状态码，应当为3xx或201系列。
	 *			$param string $msg
	 */
	public function redirect($url, $code = 302, $msg = null)
	{
		$this->setResponseCode($code, $msg);
		$this->setHeader('Location', $url);
		$this->setBody(null);
	}

	/**
	 * 快捷方式：响应为404.
	 * 注意在action中不能额外指定view。
	 *
	 * 如果需要实现自定义页面等，不要调用本方法，自行在action处理。
	 */
	public function notFound()
	{
		$this->setResponseCode(404);
		$this->setBody(null);
	}

	/**
	 * 设置header handle
	 * handle函数的唯一参数是本resposne
	 *
	 * @param callable $callback			
	 * @return callable 之前的handle
	 */
	public function setHeaderHandle(Callable $callback)
	{
		$old = $this->header_handle;
		$this->header_handle = $callback;
		return $old;
	}

	/**
	 * 设置body handle
	 * handle函数的唯一参数是本resposne
	 *
	 * @param callable $callback			
	 * @return callable 之前的handle
	 */
	public function setBodyHandle(Callable $callback)
	{
		$old = $this->body_handle;
		$this->body_handle = $callback;
		return $old;
	}

	/**
	 * 尝试发送响应，根据本对象内的heder与body信息。
	 * 如果$this->rendered == false 同时 $this->auto_render == true 会尝试调用render()
	 * 如果 $this->auto_response == true，本方法不用手工调用，框架会在适当的时候触发调用。
	 *
	 * 在某些特定情况下可能需要跳过response()并手工处理响应，比如直接发送文件下载，等。
	 * 可以在action或view中调用disableAtuoResponse(),并另行处理。
	 *
	 * 注意重复调用response()可能会出现错误（例如header发送失败等），调用方自行判断。
	 */
	public function response()
	{
		$this->sent = true;
		$c = $this->header_handle;
		$c($this);
		$c = $this->body_handle;
		$c($this);
	}

	/**
	 * 是否已经response()过了。
	 *
	 * @return boolean
	 */
	public function isResponsed()
	{
		return $this->sent;
	}
	
	// 默认header handle。是在内部所以不需要考虑参数了。
	protected function sendHeader()
	{
		// 响应状态码：
		$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
		header($protocol . ' ' . $this->response_code . ' ' . $this->response_code_msg);
		
		// 其他header
		foreach ($this->header as $name => $content) {
			if (is_array($content) or $content instanceof \Traversable) {
				foreach ($content as $line) {
					header($name . ': ' . $line, false);
				}
			} else {
				header($name . ': ' . $content);
			}
		}
		// cookie
		foreach ($this->cookies as $name => $info) {
			setcookie($name, $info['value'], $info['expire'], $info['path'], $info['domain'], $info['secure'], $info['httponly']);
		}
	}

	protected function sendBody()
	{
		echo $this->body;
	}
}

