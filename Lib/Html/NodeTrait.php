<?php
/**
 * mFramework - a mini PHP framework
 * 
 * Require PHP 7 since v4.0
 *
 * @package   mFramework
 * @version   4.0
 * @copyright 2009 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework\Html;

/**
 *
 * node trait
 *
 * @package mFramework
 * @author Wynn Chen
 *		
 */
trait NodeTrait
{

	/**
	 * 自动包装$node。标量转为DOMText，DOMNode维持原样，其他的抛异常。
	 *
	 * @param
	 *			$node
	 * @return DOMNode
	 */
	private function prepareNode($node)
	{
		if (is_scalar($node) or $node === null) {
			$node = new Text($node); // 马上就要被append了，直接new无所谓。
		}
		if (!$node instanceof \DOMNode) { // 因为不保证传来的是什么，只能用这个。
			throw new InvalidNodeException();
		}
		return $node;
	}

	/**
	 * 保证必须有父节点
	 *
	 * @param
	 *			DOMNode 检测节点，不指定的话检测$this
	 * @return DOMNode $this->parentNode
	 */
	private function requireParent(\DOMNode $node = null)
	{
		$node = $node ?: $this;
		$parent = $node->parentNode;
		if (!$parent) {
			throw new NeedParentException('need parent node .');
		}
		return $parent;
	}
	
	/**
	 * 可以用 ::create() 代替 new
	 * @param unknown ...$args
	 * @return unknown
	 */
	public static function create(...$args)
	{
		return new static(...$args);
	}

	/**
	 * 往本节点下附加子节点，数量不限。
	 * 添加的节点在最前面。
	 * 节点内容可以是Node或标量，标量及null自动转换为Text节点。
	 *
	 * 完成之后新节点的顺序和参数顺序是一样的：
	 * 如果 $div 是 <div><br/></div>， $input 是 <input/>，$img 是 <img/>
	 * 进行 $div->$prepend($input, $img) 之后，
	 * 结果是： <div><input/><img/><br/></div>
	 *
	 * @param ...$children DOMNode|scalar			
	 * @return self $this
	 */
	public function prepend(...$children)
	{
		$ref = $this->firstChild;
		foreach ($children as $node) {
			$this->insertBefore($this->prepareNode($node), $ref);
		}
		return $this;
	}

	/**
	 * 往本节点下附加子节点，数量不限。
	 * 添加的节点在最后面。
	 * 节点内容可以是Node或标量，标量及null自动转换为Text节点。
	 *
	 * 新节点出现顺序与参数顺序一样。
	 *
	 * @param ...$children DOMNode|scalar			
	 * @return self $this
	 */
	public function append(...$children)
	{
		foreach ($children as $node) {
			$this->appendChild($this->prepareNode($node));
		}
		return $this;
	}

	/**
	 * 将本节点附加到$node下，成为第一个子节点。
	 * 实际上要求$node是使用了本trait的对象。
	 *
	 * @param DOMNode $node			
	 * @return self $this
	 */
	public function prependTo(\DOMNode $node)
	{
		$node->prepend($this); // document有容器机制
		return $this;
	}

	/**
	 * 将本节点附加到$node下，成为最后一个子节点。
	 * 实际上要求$node是使用了本trait的对象。
	 *
	 * @param DOMNode $node			
	 * @return self $this
	 */
	public function appendTo(\DOMNode $node)
	{
		$node->append($this); // document有容器机制
		return $this;
	}
	
	// 这个方法废弃，这个效果可以这样实现： $node->append(...$this->childNodes)
	// 唯一的副作用是$node要求要了一点，不能只是原生的DOMNode了。
	// /**
	// * 把本节点的子节点全部附加过去。
	// */
	// public function appendChildrenTo(\DOMNode $node)
	
	/**
	 * 将另外的节点插在本节点之前
	 * 可以一次插多个，完成后的顺序与参数顺序一致。
	 *
	 * @param ...$nodes DOMNode|scalar			
	 * @throws NeedParentException
	 * @return self $this
	 */
	public function beforeMe(...$nodes)
	{
		$parent = $this->requireParent();
		// parent不一定是Html\Element之类的，所以不能直接$parent->append()之类;
		foreach ($nodes as $node) {
			$parent->insertBefore($this->prepareNode($node), $this);
		}
		return $this;
	}

	/**
	 * 将另外的节点插在本节点之前
	 * 可以一次插多个，完成后的顺序与参数顺序一致。
	 *
	 * @param ...$nodes DOMNode|scalar			
	 * @throws NeedParentException 本结点必须有父节点
	 * @return self $this
	 */
	public function afterMe(...$nodes)
	{
		$parent = $this->requireParent();
		$ref = $this->nextSibling;
		foreach ($nodes as $node) {
			$parent->insertBefore($this->prepareNode($node), $ref);
		}
		return $this;
	}

	/**
	 * 把本节点插到指定节点之前。
	 *
	 * @param $node DOMNode
	 *			指定参照节点
	 * @throws NeedParentException 指定参照节点必须有父节点
	 * @return self $this
	 */
	public function injectBefore($node)
	{
		$parent = $this->requireParent($node);
		$parent->insertBefore($this, $node);
		return $this;
	}

	/**
	 * 把本节点插到指定节点之后。
	 *
	 * @param $node DOMNode
	 *			指定参照节点
	 * @throws NeedParentException 指定参照节点必须有父节点
	 * @return self $this
	 */
	public function injectAfter($node)
	{
		$parent = $this->requireParent($node);
		$parent->insertBefore($this, $node->nextSibling);
		return $this;
	}

	/**
	 * 从文档DOM树中移除本节点
	 *
	 * @return self $this
	 */
	public function remove()
	{
		try {
			$parent = $this->requireParent();
			$parent->removeChild($this);
		} catch (NeedParentException $e) {
			// 没有父节点不用处理。
		}
		return $this;
	}

	/**
	 * 用本节点去替换掉另外一个节点在DOM树中的位置。
	 *
	 * @param DOMNode $node
	 *			将被替换的节点
	 * @throws NeedParentException 指定参照节点必须有父节点
	 * @return self
	 */
	public function replace(\DOMNode $node)
	{
		$parent = $this->requireParent($node);
		$parent->replaceChild($this, $node);
		return $this;
	}

	/**
	 * 清空子节点
	 *
	 * @return self
	 */
	public function clear()
	{
		while ($this->firstChild) {
			$this->removeChild($this->firstChild);
		}
		return $this;
	}

	/**
	 * 本节点对应的XML/HTML表示。
	 *
	 * @return string;
	 */
	public function __toString()
	{
		return $this->ownerDocument->saveXML($this);
	}
}