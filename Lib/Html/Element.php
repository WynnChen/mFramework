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
 * element
 *
 * @package mFramework
 * @author Wynn Chen
 *		
 */
class Element extends \DOMElement implements \ArrayAccess
{
	use NodeTrait;

	/**
	 * 建立新的element，一般不直接调用，而通过Html::tag() 的模式来解决。
	 * 本方法的实现主要为了使得本类可以正常继承，帮助某些widget实现扩展。
	 *
	 * @param string $tag			
	 */
	public function __construct($tag, ...$children)
	{
		try {
			$doc = Document::getCurrent();
		} catch (Document\NoCurrentDocumentException $e) {
			throw new Document\NoCurrentDocumentException('create a Document before create any Element.');
		}
		
		parent::__construct($tag);
		
		// 直接new出来的element不能修改，下面两行将其与document关联起来，变为可修改：
		$doc->append($this);
		$this->parentNode->removeChild($this);
		// ok，可写了.
		
		$this->append(...$children);
	}

	/**
	 * 动态方法set/get/remove元素属性。
	 * $node->attr('var'); //设置属性attr为var
	 * $node->attr(null); //彻底remove掉attr属性。
	 * $var = $node->attr(); //获取当前的attr属性值。
	 *
	 * @param string $name			
	 * @param array $args			
	 *
	 * @return string|Element
	 */
	public function __call($name, $args)
	{
		// 没有参数。 $var = $node->attr()的用法。
		if (!$args) {
			return $this->getAttribute($name);
		}
		
		// 有参数
		if ($args[0] === null) {
			$this->removeAttribute($name);
		} else {
			$this->setAttribute($name, $args[0]);
		}
		return $this;
	}

	/**
	 * set arrtibute
	 *
	 * @param string $attribute			
	 * @param string $value			
	 */
	public function set($attribute, $value)
	{
		$this->setAttribute($attribute, $value);
		return $this;
	}

	public function del($attribute)
	{
		$this->removeAttribute($attribute);
		return $this;
	}

	/**
	 * get attribute
	 */
	public function get($attribute)
	{
		return $this->getAttribute($attribute);
	}

	public function offsetExists($attribute)
	{
		return (bool)$this->getAttribute($attribute);
	}

	public function offsetGet($attribute)
	{
		return $this->getAttribute($attribute);
	}

	public function offsetSet($attribute, $value)
	{
		$this->setAttribute($attribute, $value);
	}

	public function offsetUnset($attribute)
	{
		$this->removeAttribute($attribute);
	}

	/**
	 *
	 * 元素具有class $class?
	 *
	 * @param string $class			
	 * @return bool
	 */
	public function hasClass($class)
	{
		return in_array($class, explode(' ', $this->getAttribute('class')));
	}

	/**
	 * 给元素添加class,允许一次添加多个，尝试添加已存在的class会自动去重。
	 *
	 * @param string ...$class			
	 * @return Element $this
	 */
	public function addClass(...$class)
	{
		$classes = array_unique(array_merge(explode(' ', $this->getAttribute('class')), $class));
		$this->class(trim(implode(' ', $classes)));
		return $this;
	}

	/**
	 * 给元素去掉class，允许一次去掉多个，尝试去掉不存在的class不会出错。
	 *
	 * @param string $class			
	 * @return Element $this
	 */
	public function removeClass(...$class)
	{
		$classes = array_diff(explode(' ', $this->getAttribute('class')), $class);
		$this->class(trim(implode(' ', $classes))); // 如果弄完变成没有了。
		return $this;
	}
}
