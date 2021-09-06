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
	/**
	 * 是主键
	 */
	const IS_PK = 1 << 0;
	/**
	 * 只读。
	 */
	const IS_READ_ONLY = 1 << 1;
	/**
	 * 自增长。隐含 IS_READ_ONLY
	 */
	const IS_AUTO_INC = 1 << 2;
	/**
	 * 字段同时是索引，意味着允许用 ::selectByXxxx(),得到resultSet
	 */
	const IS_INDEX = 1 << 3;
	/**
	 * 字段是唯一索引，意味着允许用 ::selectByXxxx(),得到单条记录
	 * 隐含 IS_INDEX
	 */
	const IS_UNIQUE = 1 << 4;

	/**
	 * Field constructor.
	 * @param int $flags 本类各种 IS_XXX 的集合。
	 * @param string|null $key 意味着这个字段指向一个表，可以用 ::getXxxx() 得到对应的实例。值为对应model类的类名
	 */
	public function __construct(private int $flags = 0, private ?string $key = null)
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

	public function isIndex():bool
	{
		return (bool)($this->flags & Field::IS_INDEX);
	}

	public function isUnique():bool
	{
		return (bool)($this->flags & Field::IS_UNIQUE);
	}

}
