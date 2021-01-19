<?php

//基础工作
namespace
{
//ob_start();

//	include 'dbunit-4.0.0.phar';
	define('MFROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'mFramework' . DIRECTORY_SEPARATOR);

	spl_autoload_register(function ($class) {
		if (str_starts_with($class, 'mFramework\\')) {
			// mFramework的相关类
			require MFROOT . substr($class, strlen('mFramework\\')) . '.php';
		} else {
			// 测试用到的相关类
			$file = __DIR__ . '/' . substr($class, strlen('Test\\')) . '.php';
			if (is_file($file)) {
				require $file;
			}
		}
		return false;
	});
}
//特定模块
namespace mFramework
{
	// session测试用
	function session_start(): bool
	{
		if (ini_get('session.use_cookies')) {
			return true;
		}
		return \session_start();
	}
}
