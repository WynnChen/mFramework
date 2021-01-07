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
 * HTTP Request
 *
 * @package mFramework
 * @author Wynn Chen
 */
class Request
{

	const METHOD_HEAD = 'HEAD';

	const METHOD_GET = 'GET';

	const METHOD_POST = 'POST';

	const METHOD_PUT = 'PUT';

	const METHOD_PATCH = 'PATCH';

	const METHOD_DELETE = 'DELETE';

	const METHOD_OPTIONS = 'OPTIONS';

	/**
	 * 记录额外的输入参数。
	 * 一般route来解析生成。
	 * 随后可以用getParameter()方法读取。
	 *
	 * @var array|ArrayAccess
	 */
	protected $parameters;

	protected $uri;

	public function __construct($action = '/', $parameters = array(), $uri = null)
	{
		$this->action = $action;
		$this->parameters = $parameters;
		if ($uri === null) {
			$this->uri = self::getUri();
		}
	}

	/**
	 * 获取额外的输入参数
	 *
	 * @param number $index			
	 * @param string $default			
	 * @return mixed 获取指定$key的对应值
	 */
	public function getParameter($key = null, $default = null)
	{
		if (!isset($this->parameters[$key])) {
			return $default;
		}
		
		return $this->parameters[$key];
	}

	/**
	 * 获取整个附加参数数组
	 *
	 * @return array
	 */
	public function getAllParameters()
	{
		return $this->parameters;
	}

	/**
	 * 写入参数信息。
	 *
	 * @param string $key			
	 * @param mixed $value			
	 */
	public function setParameter($key, $value)
	{
		$this->parameters[$key] = $value;
	}

	/**
	 * 一次性写入多个参数。相同key的将被覆盖（包括数字key）
	 *
	 * @param array|arrayAccess $parameter_array			
	 */
	public function setParameters($parameter_array)
	{
		$this->parameters = $parameter_array + $this->parameters; // 要覆盖，顺序很重要。
	}

	/**
	 * 清除现有的所有参数。
	 */
	public function clearParameters()
	{
		$this->parameters = array();
	}
	
	// 下面是http相关的各种内容
	
	/**
	 * 返回的内容为字符串，为免错误，可以直接用 self::METHOD_* 常量来辅助对比
	 *
	 * @return string 请求方法名
	 */
	public function getMethod()
	{
		return $_SERVER['REQUEST_METHOD'];
	}
	
	// 由于GET/POST最常用，专门给出方便一点的判定方法
	
	/**
	 * 当前请求是GET方法？
	 *
	 * @return boolean
	 */
	public function isGet()
	{
		return $_SERVER['REQUEST_METHOD'] === 'GET';
	}

	/**
	 *
	 * 当前请求是POST方法？
	 *
	 * @return boolean
	 */
	public function isPost()
	{
		return $_SERVER['REQUEST_METHOD'] === 'POST';
	}

	/**
	 * 获取$_ENV数据
	 *
	 * @param number $index			
	 * @param string $default			
	 * @return mixed 有指定$key为对应值/默认值，否则为整个数组
	 */
	public static function getEnv($key = null, $default = null)
	{
		if ($key === null) {
			return new Map($_ENV);
		}
		
		if (!isset($_ENV[$key]) or $_ENV[$key] === '') {
			return $default;
		}
		
		return $_ENV[$key];
	}

	/**
	 * 获取$_SERVER数据
	 *
	 * @param number $index			
	 * @param string $default			
	 * @return mixed 有指定$key为对应值/默认值，否则为整个数组
	 */
	public static function getServer($key = null, $default = null)
	{
		if ($key === null) {
			return new Map($_SERVER);
		}
		
		if (!isset($_SERVER[$key]) or $_SERVER[$key] === '') {
			return $default;
		}
		
		return $_SERVER[$key];
	}

	/**
	 * 获取$_GET数据
	 *
	 * @param number $index			
	 * @param string $default			
	 * @return mixed 有指定$key为对应值/默认值，否则为整个数组
	 */
	public static function getQuery($key = null, $default = null)
	{
		if ($key === null) {
			return new Map($_GET);
		}
		
		if (!isset($_GET[$key]) or $_GET[$key] === '') {
			return $default;
		}
		
		return $_GET[$key];
	}

	/**
	 * 获取$_POST数据
	 *
	 * @param number $index			
	 * @param string $default			
	 * @return mixed 有指定$key为对应值/默认值，否则为整个数组
	 */
	public static function getPost($key = null, $default = null)
	{
		if ($key === null) {
			return new Map($_POST);
		}
		
		// 通常的表单中不填写时提交的值是空字符串
		if (!isset($_POST[$key]) or $_POST[$key] === '') {
			return $default;
		}
		
		return $_POST[$key];
	}

	/**
	 * 获取$_COOKIE数据
	 *
	 * @param number $index			
	 * @param string $default			
	 * @return mixed 有指定$key为对应值/默认值，否则为整个数组
	 */
	public static function getCookie($key = null, $default = null)
	{
		if ($key === null) {
			return new Map($_COOKIE);
		}
		
		if (!isset($_COOKIE[$key]) or $_COOKIE[$key] === '') {
			return $default;
		}
		return $_COOKIE[$key];
	}

	/**
	 * 获取上传文件信息
	 *
	 * @param string $key			
	 * @return UploadedFile
	 */
	public static function getUploadedFile($key)
	{
		return new UploadedFile($key);
	}

	/**
	 * 尝试获取客户端IP
	 *
	 * @return string 客户端IP
	 */
	public static function getIp()
	{
		if (isset($_SERVER['X_FORWARDED_FOR'])) {
			return $_SERVER['X_FORWARDED_FOR'];
		}
		if (isset($_SERVER['CLIENT_IP'])) {
			return $_SERVER['CLIENT_IP'];
		}
		if (isset($_SERVER['REMOTE_ADDR'])) {
			return $_SERVER['REMOTE_ADDR'];
		}
		
		return null;
	}

	/**
	 * 尝试获取url。为/开始的路径或者全URI，视参数flag
	 *
	 * @return unknown|NULL
	 */
	public static function getUri($complete = false)
	{
		$uri = null;
		if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
			$uri = $_SERVER['HTTP_X_ORIGINAL_URL'];
		} elseif (isset($_SERVER['UNENCODED_URL'])) {
			$uri = $_SERVER['UNENCODED_URL'];
		} elseif (isset($_SERVER['REQUEST_URI'])) {
			$uri = $_SERVER['REQUEST_URI'];
		}
		
		if ($complete) {
			$uri = ((@$_SERVER['HTTPS'] and $_SERVER['HTTPS'] != 'off') ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] . $uri;
		}
		return $uri;
	}
}
