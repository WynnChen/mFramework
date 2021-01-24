<?php
declare(strict_types=1);

namespace mFramework\Database\Attribute;

use Attribute;
use mFramework\Database\Record;

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

	public function __construct(int $flags = 0)
	{}
}
