<?php
//基础工作
namespace
{
	include 'dbunit-4.0.0.phar';
	define('MFROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Lib' . DIRECTORY_SEPARATOR);

	spl_autoload_register(function ($class) {
		if (strpos($class, 'mFramework\\') === 0) {
			// mFramework的相关类
			require MFROOT . substr($class, strlen('mFramework\\')) . '.php';
		} else {
			// 测试用到的相关类
			$file = __DIR__ . '/' . $class . '.php';
			if (is_file($file)) {
				require $file;
			}
		}
		return false;
	});
}
//特定模块
namespace mFramework\Http
{
	// 接管一下这两个函数：
	function setcookie($name, $value = null, $expire = null, $path = null, $domain = null, $httponly = null)
	{
		echo implode('*', func_get_args());
	}

	function header($header)
	{
		echo $header, '|';
	}
	
	// session测试用
	function session_start()
	{
		if (ini_get('session.use_cookies')) {
			return true;
		}
		return \session_start();
	}
}
