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
 * Sqlite数据库配置要求：
 * 'file' => 数据库文件名，或 :memory:
 * 
 * 
 * 
 * @package mFramework
 * @author Wynn Chen
 */
class Sqlite extends \mFramework\Database\Connection
{

	public function __construct($config)
	{
		parent::__construct('sqlite:'.$config['file']);
	}

	/**
	 * 使用sqlite标识符的标准用法。
	 * 
	 * {@inheritDoc}
	 * @see \mFramework\Database\Connection::enclose()
	 */
	public function enclose($identifier)
	{
		return '"' . $identifier . '"';
	}
}