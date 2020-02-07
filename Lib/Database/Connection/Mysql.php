<?php
/**
 * mFramework - a mini PHP framework
 * 
 * @package   mFramework
 * @version   v5
 * @copyright 2009-2016 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework\Database\Connection;

/**
 * MySQL数据库配置需要包含如下内容:
 * 'host' => 数据库服务器主机
 * 'port' => 端口，一般为3306
 * 'dbname' => 数据库名
 * 'username' => 用户名
 * 'password' => 密码
 * 'charset' => 字符集，PHP程序这端使用的，不是Mysql数据库内的。一般utf8
 * 'options' => array, 配置选项。PDO::MYSQL_ATTR_INIT_COMMAND无需配置，强制执行。
 *
 * 注意：没有执行setAttribute(PDO::ATTR_EMULATE_PREPARES, false)，由外部程序自行决定。
 * 
 * @package mFramework
 * @author Wynn Chen
 */
class Mysql extends \mFramework\Database\Connection
{

	public function __construct($config)
	{
		// 建立数据库连接
		$options = $config['options'];
		$options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
		$options[\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES {$config['charset']}";
		// charset需要PHP5.3.6及以上。
		parent::__construct("mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}", $config['username'], $config['password'], $options);
		// $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); 放到外部进行。
	}

	public function enclose($identifier)
	{
		return '`' . $identifier . '`';
	}
}