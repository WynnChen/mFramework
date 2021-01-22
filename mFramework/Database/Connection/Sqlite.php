<?php
declare(strict_types=1);

namespace mFramework\Database\Connection;

use mFramework\Database\Connection;

/**
 * Sqlite数据库配置要求：
 * 'file' => 数据库文件名，或 :memory:
 * 
 * 
 * 
 * @package mFramework
 * @author Wynn Chen
 */
class Sqlite extends Connection
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
	public function enclose(string $identifier):string
	{
		return '"' . $identifier . '"';
	}
}