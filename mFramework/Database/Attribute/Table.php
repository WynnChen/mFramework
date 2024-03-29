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
	public function __construct(private array|string $connection,
								private string $name,
								private ?array $orderBy=null,
								private bool $immutable=false
	)
	{}

	/**
	 * @return array|string
	 */
	public function getConnection(): array|string
	{
		return $this->connection;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return array|null
	 */
	public function getOrderBy(): ?array
	{
		return $this->orderBy;
	}

	/**
	 * immutable 的表默认不能调用update()相关方法。
	 * @return bool
	 */
	public function isImmutable() : bool
	{
		return $this->immutable;
	}

}