<?php
/**
 * mFramework 示例项目
 *
 * 主入口文件
 * 
 * @author Wynn Chen <wynn.chen@outlook.com>
 */
require realpath('../..') . DIRECTORY_SEPARATOR . 'bootstrap.php';


use mFramework\Application;
use mFramework\Middleware\AutoStartSessionMiddleware;

try {
	// 这里加上了一个自动启动 Session 的中间件。
	(new Application())->addMiddleware(new AutoStartSessionMiddleware())->run();
} catch (Exception $e) {
	throw $e;
//	header("HTTP/1.0 404 Not Found");
//	echo 'Not Found.';
}