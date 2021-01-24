<?php
declare(strict_types=1);

namespace mFramework\Database\Attribute;

use Attribute;

/**
 * Class Table
 * Record 的子类的 attribute 来配置表连接信息。
 *
 * @package mFramework\Database\Attribute
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Table {
	public function __construct(array|string $connection, string $name, ?array $orderBy=null)
	{}
}
