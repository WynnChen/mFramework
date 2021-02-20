<?php
declare(strict_types=1);

namespace mFramework\Database\Attribute;

use Attribute;

/**
 * Class Field
 * Record的子类用来配置字段信息.
 *
 * @package mFramework\Database\Attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Field {
	const IS_PK = 1 << 0;
	const IS_READ_ONLY = 1 << 1;
	CONST IS_AUTO_INC = 1 << 2;

	public function __construct(private int $flags = 0)
	{}

	public function isPk():bool
	{
		return (bool)($this->flags & Field::IS_PK);
	}

	public function isAutoInc():bool
	{
		return (bool)($this->flags & Field::IS_AUTO_INC);
	}

	public function isReadOnly():bool
	{
		return (bool)($this->flags & Field::IS_READ_ONLY);
	}

}
