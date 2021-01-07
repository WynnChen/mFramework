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

/**
 * 对Session的一些易用性封装，基本上是对session_*()函数的封装。
 * 可以混合使用或不使用本类执行session操作，结果互通。
 *
 * 部分方法仅仅是基于完整性的考虑而存在，仅仅是对对应函数的简单封装。
 * 对于此类方法，使用类方法可以加强代码可读性，直接调用函数则运行更快。
 * 属于此类情况的方法在各方法的文档中有所标注，开发人员自行判定。
 *
 * 注意PHP本身可以设置禁用session，本类不负责判定，假设session正常启用。
 *
 * @package mFramework
 * @author Wynn Chen
 */
class Session
{

	private function __construct()
	{}

	private function __clone()
	{}

	public function __sleep()
	{}

	public function __wakeup()
	{}

	static private function requireStarted()
	{
		if (session_status() != PHP_SESSION_ACTIVE) {
			throw new Session\NotStartedException();
		}
	}

	static private function requireNotStarted()
	{
		if (session_status() == PHP_SESSION_ACTIVE) {
			throw new Session\HasBeenStartedException();
		}
	}

	static private function requireUseCookies()
	{
		if (!ini_get("session.use_cookies")) {
			throw new Session\NotUseCookiesException();
		}
	}

	/**
	 * 设置session的相关cookie参数。
	 * 必须在Session::start()及其他任何session_start()或包装之前调用才有效。
	 *
	 * @param int $lifetime			
	 * @param string $domain			
	 * @param string $path			
	 * @param bool $secure			
	 * @param bool $httponly			
	 *
	 * @throws Session\hasBeenStartedException 若session已经开始则抛出。
	 */
	static public function setCookieParams($lifetime = 0, $domain = null, $path = '/', $secure = false, $httponly = true)
	{
		self::requireNotStarted();
		self::requireUseCookies();
		session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
	}

	/**
	 * 获取session的相关cookie参数。
	 * 返回关联数组
	 * "lifetime" - The lifetime of the cookie in seconds.
	 * "path" - The path where information is stored.
	 * "domain" - The domain of the cookie.
	 * "secure" - The cookie should only be sent over secure connections.
	 * "httponly" - The cookie can only be accessed through the HTTP protocol.
	 *
	 * 本方法是对session_get_cookie_params()的简单封装。
	 *
	 * @see session_get_cookie_params()
	 *
	 * @throws Session\NotStartedException
	 * @return array
	 */
	static public function getCookieParams()
	{
		self::requireUseCookies();
		return session_get_cookie_params();
	}

	/**
	 * 是否已经开始？
	 *
	 * @return boolean
	 */
	static public function isStarted()
	{
		return session_status() == PHP_SESSION_ACTIVE;
	}

	/**
	 * 开始Session。
	 * 所有对session的操作都需要开始了才有意义。
	 * 由于一般情况下 session id 通过cookie传递，因此需要在有任何实际输出之前执行。
	 * 本方法不对以上情况进行判定。
	 *
	 * 重复调用会忽略。
	 *
	 * 本方法只是简单封装session_start()。
	 *
	 * @throws Session\NotStartedException
	 * @return session是否正确开始了？
	 */
	static public function start()
	{
		if (self::isStarted()) {
			return true;
		}
		return session_start();
	}

	/**
	 * 设置session。
	 *
	 * @param string $name			
	 * @param mixed $value			
	 * @throws Session\NotStartedException
	 */
	static public function save($name, $value = null)
	{
		self::requireStarted();
		$_SESSION[$name] = $value;
	}

	/**
	 * 取session内容
	 *
	 * 注意对应的值如果是object，需要有对应class的声明载入
	 *
	 * @param sring $name
	 *			session变量名
	 * @param mixed $default
	 *			默认值
	 * @throws Session\NotStartedException
	 * @return mixed $value 实际值，不存在的name返回默认值。
	 */
	static public function load($name, $default = null)
	{
		self::requireStarted();
		return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
	}

	/**
	 * 即isset($_SESSION[$name])
	 *
	 * @param string $name			
	 * @throws Session\NotStartedException
	 * @return bool
	 */
	static public function exists($name)
	{
		self::requireStarted();
		return isset($_SESSION[$name]);
	}

	/**
	 * unset($_SESSION[$name])
	 *
	 * @param string $name			
	 * @throws Session\NotStartedException
	 * @return bool
	 */
	static public function delete($name)
	{
		self::requireStarted();
		unset($_SESSION[$name]);
	}

	/**
	 * 获取crumb，如果没有就同时初始化个新的。
	 *
	 * @throws Session\NotStartedException
	 * @return string 生成的crumb
	 */
	static public function getCrumb()
	{
		self::requireStarted();
		$key = '_CRUMB_' . session_id();
		if (empty($_SESSION[$key])) {
			$_SESSION[$key] = md5(microtime());
		}
		
		return $_SESSION[$key];
	}

	/**
	 * 清除crumb。
	 *
	 * @throws Session\NotStartedException
	 */
	static public function resetCrumb()
	{
		self::requireStarted();
		$key = '_CRUMB_' . session_id();
		unset($_SESSION[$key]);
	}

	/**
	 * 清除掉所有session内的变量，但维持session本身的工作状态。
	 *
	 * @throws Session\NotStartedException
	 */
	static public function reset()
	{
		self::requireStarted();
		$_SESSION = array();
	}

	/**
	 * 对 session_id()的封装
	 *
	 * @throws Session\NotStartedException
	 * @return string
	 */
	static public function getId()
	{
		self::requireStarted();
		return session_id();
	}

	/**
	 * 对 session_id()的封装
	 *
	 *
	 * @param
	 *			string
	 * @throws Session\HasBeenStartedException
	 * @return bool 操作是否成功
	 */
	static public function setId($id)
	{
		self::requireNotStarted();
		return session_id($id);
	}

	/**
	 * 重新生成session id
	 *
	 * @throws Session\NotStartedException
	 * @param $delete_old_data 是否同时删掉现在的session数据？			
	 * @return bool 操作是否成功
	 */
	static public function regenerateId($delete_old_data = false)
	{
		self::requireStarted();
		
		if ($delete_old_data) {
			return session_regenerate_id(false);
		}
		
		// crumb要特殊处理下：
		$key = '_CRUMB_' . session_id();
		$crumb = self::load($key);
		session_regenerate_id(true);
		if ($crumb !== null) {
			self::delete($key);
			$key = '_CRUMB_' . session_id(); // 新的
			self::save($key, $crumb);
		}
	}

	/**
	 * 必须 session start 了之后方才有效，否则触发报错。
	 *
	 * destroy不会影响 $_SESSION数组的运作（但内部数据清空），也不影响相应的cookies等。
	 * 相当于关闭session之后删除session的存储数据。
	 * destroy之后需要重新start才能继续使用session，之前的数据消失。
	 *
	 * @throws Session\NotStartedException
	 * @return boolean
	 */
	static public function destroy()
	{
		self::requireStarted();
		$_SESSION = array();
		session_destroy();
	}

	/**
	 * 对session_write_close()的封装。
	 * 尽快显式调用这个有助于降低session带来的并发瓶颈。
	 * session进入关闭状态。
	 *
	 * @throws Session\NotStartedException
	 */
	static public function commit()
	{
		self::requireStarted();
		session_write_close();
	}

	/**
	 * 杀掉指定 session id 的session.
	 * 目标session可以不是当前访问者的session。
	 *
	 * @codeCoverageIgnore
	 *
	 * @param string $session_id			
	 */
	static public function killById($session_id)
	{
		$id = session_id();
		if ($id == $session_id) {
			session_start();
			session_destroy();
			return;
		}
		
		if (self::isStarted()) {
			session_write_close();
			session_id($session_id);
			session_start();
			session_destroy();
			session_id($id);
			session_start();
		} else {
			session_id($session_id);
			session_start();
			session_destroy();
		}
	}
}
namespace mFramework\Http\Session;

class Exception extends \mFramework\Exception
{}

class NotStartedException extends Exception
{}

class HasBeenStartedException extends Exception
{}

class NotUseCookiesException extends Exception
{}
