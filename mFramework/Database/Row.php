<?php
declare(strict_types=1);

namespace mFramework\Database;


use mFramework\Map;

/**
 * 默认的数据库查询结果object，基本上就是Map。
 *
 */
class Row extends Map
{
	/**
	 * 建立数据结果集迭代器
	 *
	 * @param bool $fetch
	 */
	public function __construct(bool $fetch = false)
	{
		parent::__construct($this);
	}
}
