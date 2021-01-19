<?php
/**
 * mFramework
 */

/**
 * 框架主入口文件。
 *
 * 应用程序初始化一般只要：
 *
 * require $path.'mFramework.php'; //框架的所有主要的准备工作
 *
 * ClassLoader::getInstance()
 * ->setPrefixHandle($prefix, $handle); //配置自有的classloader信息
 *
 * (new mFramework\application())->run(); //初始化app然后运行之。
 *
 */
namespace mFramework;

require __DIR__ . '/mFramework/ClassLoader.php';

// 将框架本身注册好autoload
ClassLoader::getInstance()
	->addNamespace('mFramework', ClassLoader::baseDirHandle(__DIR__. '/mFramework'))
	->register();
