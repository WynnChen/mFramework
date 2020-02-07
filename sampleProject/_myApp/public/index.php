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
use mFramework\Application\ActionClassNotFoundException;

try {
	// 这里加上了一个自动启动 Session 的中间件。
	(new Application('myApp'))->addMiddleware(new mFramework\Middleware\AutoStartSession())->run();
} catch (ActionClassNotFoundException $e) {
	header("HTTP/1.0 404 Not Found");
	echo 'Not Found.';
}