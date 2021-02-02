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
	 * 本节点对应的XML/HTML表示。
	 *
	 * @return string;
	 */
	public function __toString(): string
	{
		return $this->ownerDocument->saveXML($this);
	}

	public function append(...$nodes): void
	{
		if(!$nodes){
			return;
		}

		foreach ($nodes as $key => &$node) {
			if(is_scalar($node)){
				$node = (string)$node;
			}
			if($node === null){
				unset($nodes[$key]);
			}
		}
		if($nodes){ //要求至少一个参数。
			parent::append(...$nodes);
		}
	}

	public function prepend(...$nodes): void
	{
		if(!$nodes){
			return;
		}

		foreach ($nodes as $key => &$node) {
			if(is_scalar($node)){
				$node = (string)$node;
			}
			if($node === null){
				unset($nodes[$key]);
			}
		}

		parent::prepend(...$nodes);
	}

	public function appendTo($node):static
	{
		$node->append($this);
		return $this;
	}

	public function prependTo($node):static
	{
		$node->prepend($this);
		return $this;
	}

}