<?php
/**
 * mFramework 示例项目
 * 
 * 初始配置文件
 */
use mFramework\ClassLoader;
use mFramework\Database\Connection;

// mFramework 初始化
require __DIR__ . '/mFramework/mFramework.php';

// autoload 不同 namespace 前缀映射到路径的配置
ClassLoader::getInstance()
	->addPrefixHandles([
		'Model' => ClassLoader::baseDirHandle(__DIR__ . '/Model'),
		'' => ClassLoader::baseDirHandle(__DIR__ . '/_myApp')
	]);

// 数据库配置，示例.
Connection::set('default', ['type' => 'mysql','host' => '127.0.0.1','port' => '3306','dbname' => 'myApp','username' => 'user','password' => 'pwd','charset' => 'utf8','options' => array(PDO::ATTR_PERSISTENT => true, // <- 永久连接，视服务器实际环境定是否使用。
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)]) // 错误处理模式
;

