<?php
declare(strict_types=1);

namespace mFramework;

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
 */
class Session
{

	static private function requireStarted()
	{
		if (session_status() != PHP_SESSION_ACTIVE) {
			throw new SessionException('Session not started.');
		}
	}

	static private function requireNotStarted()
	{
		if (session_status() == PHP_SESSION_ACTIVE) {
			throw new SessionException('Session has been started.');
		}
	}

	static private function requireUseCookies()
	{
		if (!ini_get("session.use_cookies")) {
			throw new SessionException('Require session use cookies.');
		}
	}

	/**
	 * 设置session的相关cookie参数。
	 * 必须在Session::start()及其他任何session_start()或包装之前调用才有效。
	 *
	 * @param int $lifetime
	 * @param string|null $domain
	 * @param string $path
	 * @param bool $secure
	 * @param bool $httponly
	 *
	 * @return bool 操作是否成功
	 * @throws SessionException
	 */
	static public function setCookieParams(int $lifetime = 0,
										   ?string $domain = null,
										   string $path = '/',
										   bool $secure = false,
										   bool $httponly = true):bool
	{
		self::requireNotStarted();
		self::requireUseCookies();
		return session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
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
	 * @return array
	 * @throws SessionException
	 * @see session_get_cookie_params()
	 *
	 */
	static public function getCookieParams(): array
	{
		self::requireUseCookies();
		return session_get_cookie_params();
	}

	/**
	 * 是否已经开始？
	 *
	 * @return boolean
	 */
	static public function isStarted(): bool
	{
		return session_status() === PHP_SESSION_ACTIVE;
	}

	/**
	 * 开始 Session 。
	 * 所有对 session 的操作都需要开始了才有意义。
	 * 由于一般情况下 session id 通过 cookie 传递，因此需要在有任何实际输出之前执行。
	 * 本方法不对以上情况进行判定。
	 *
	 * 重复调用会忽略。
	 *
	 * 本方法只是简单封装 session_start()。
	 *
	 * @return bool
	 */
	static public function start(): bool
	{
		if (self::isStarted()) {
			return true;
		}
		return session_start();
	}

	/**
	 * 设置 session 。
	 *
	 * @param string $name
	 * @param mixed $value
	 * @throws SessionException
	 */
	static public function save(string $name, mixed $value = null):void
	{
		self::requireStarted();
		$_SESSION[$name] = $value;
	}

	/**
	 * 取session内容
	 *
	 * 注意对应的值如果是object，需要有对应class的声明载入
	 *
	 * @param string $name session变量名
	 * @param mixed $default 默认值
	 * @return mixed $value 实际值，不存在的name返回默认值。
	 * @throws SessionException
	 */
	static public function load(string $name, mixed $default = null):mixed
	{
		self::requireStarted();
		return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
	}

	/**
	 * 即 isset($_SESSION[$name])
	 *
	 * @param string $name
	 * @return bool
	 * @throws SessionException
	 */
	static public function exists(string $name):bool
	{
		self::requireStarted();
		return isset($_SESSION[$name]);
	}

	/**
	 * unset($_SESSION[$name])
	 *
	 * @param string $name
	 * @return void
	 * @throws SessionException
	 */
	static public function delete(string $name):void
	{
		self::requireStarted();
		unset($_SESSION[$name]);
	}

	/**
	 * 获取crumb，如果没有就同时初始化个新的。
	 *
	 * @return string 生成的crumb
	 * @throws SessionException
	 */
	static public function getCrumb():string
	{
		self::requireStarted();
		$key = '_CRUMB_' . session_id();
		if (empty($_SESSION[$key])) {
			$_SESSION[$key] = md5(microtime());
		}
		
		return $_SESSION[$key];
	}

	/**
	 * 清除 crumb 。
	 *
	 * @throws SessionException
	 */
	static public function resetCrumb():void
	{
		self::requireStarted();
		$key = '_CRUMB_' . session_id();
		unset($_SESSION[$key]);
	}

	/**
	 * 清除掉所有session内的变量，但维持session本身的工作状态。
	 *
	 * @throws SessionException
	 */
	static public function reset():void
	{
		self::requireStarted();
		$_SESSION = array();
	}

	/**
	 * 对 session_id()的封装
	 *
	 * @return string
	 * @throws SessionException
	 */
	static public function getId():string
	{
		self::requireStarted();
		return session_id();
	}

	/**
	 * 对 session_id()的封装
	 *
	 *
	 * @param string
	 * @return string 当前（被覆盖掉的）id，如果没有的话为空字符串''
	 * @throws SessionException
	 */
	static public function setId($id):string
	{
		self::requireNotStarted();
		return session_id($id);
	}

	/**
	 * 重新生成session id
	 *
	 * @param bool $delete_old_data 是否同时删掉现在的session数据？
	 * @return bool 操作是否成功
	 * @throws SessionException
	 */
	static public function regenerateId(bool $delete_old_data = false):bool
	{
		self::requireStarted();
		
		if ($delete_old_data) {
			return session_regenerate_id(false);
		}
		
		// crumb要特殊处理下：
		$key = '_CRUMB_' . session_id();
		$crumb = self::load($key);
		$result = session_regenerate_id(true);
		if ($crumb !== null) {
			self::delete($key);
			$key = '_CRUMB_' . session_id(); // 新的
			self::save($key, $crumb);
		}
		return $result;
	}

	/**
	 * 必须 session start 了之后方才有效，否则触发报错。
	 *
	 * destroy不会影响 $_SESSION数组的运作（但内部数据清空），也不影响相应的cookies等。
	 * 相当于关闭session之后删除session的存储数据。
	 * destroy之后需要重新start才能继续使用session，之前的数据消失。
	 *
	 * @return boolean
	 * @throws SessionException
	 */
	static public function destroy():bool
	{
		self::requireStarted();
		$_SESSION = array();
		return session_destroy();
	}

	/**
	 * 对session_write_close()的封装。
	 * 尽快显式调用这个有助于降低session带来的并发瓶颈。
	 * session进入关闭状态。
	 *
	 * @throws SessionException
	 */
	static public function commit(): bool
	{
		self::requireStarted();
		return session_write_close();
	}

	/**
	 * 杀掉指定 session id 的session.
	 * 目标session可以不是当前访问者的session。
	 *
	 * @codeCoverageIgnore
	 *
	 * @param string $session_id			
	 */
	static public function killById(string $session_id): void
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
