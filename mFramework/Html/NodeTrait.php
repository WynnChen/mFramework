<?php
declare(strict_types=1);

namespace mFramework\Html;

/**
 * Used by Document Element Fragment etc.
 * 应当只用于HTML模块内部。
 */
trait NodeTrait
{
	/**
	 * 允许可以用 ::create() 代替 new,方便点
	 * @param mixed ...$args
	 * @return static
	 */
	public static function create(...$args): static
	{
		return new static(...$args);
	}

	/**
	 * 本节点对应的XML/HTML表示。
	 *
	 * @return string;
	 */
	public function __toString(): string
	{
		return $this->ownerDocument->saveXML($this);
	}
}