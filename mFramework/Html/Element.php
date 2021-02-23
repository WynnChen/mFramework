<?php
declare(strict_types=1);

namespace mFramework\Html;

/**
 * element
 */
class Element extends \DOMElement implements \ArrayAccess
{
	use NodeTrait;

	/**
	 * 允许用 Class::create($a, ...) 替代  new Class($a, ...)
	 * @param mixed ...$args
	 * @return static
	 * @throws Exception
	 */
	static public function create(...$args):static
	{
		return new static(...$args);
	}

	/**
	 * 建立新的 element，一般不直接调用，而通过 Html::tag() 的模式来解决。
	 * 本方法的实现主要为了使得本类可以正常继承，帮助某些 widget 实现扩展。
	 *
	 * @param string $tag
	 * @param Element|Fragment|Text|string|int|float ...$children
	 * @throws Exception
	 */
	public function __construct(string $tag, Element|Fragment|Text|string|int|float ...$children)
	{
		try {
			$doc = Document::getCurrent();
		} catch (Exception $e) {
			throw new Exception('create a Document before create any Element.');
		}

		$node = $doc->createElement($tag);

		parent::__construct($tag);
		// 直接new出来的element不能修改
		$doc->appendChild($this); //这里不能用append()，必须appendChild()
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
	 * @param array|null $args
	 *
	 * @return string|static
	 */
	public function __call(string $name, ?array $args): static|string
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

	public function get(string $attribute): string
	{
		return $this->getAttribute($attribute);
	}

	public function set(string $attribute, string|int|float|null $value):static
	{
		if($value === null){
			$this->removeAttribute($attribute);
		}
		else{
			$this->setAttribute($attribute, $value);
		}
		return $this;
	}

	public function delete(string $attribute):static
	{
		$this->removeAttribute($attribute);
		return $this;
	}

	public function offsetExists(mixed $offset):bool
	{
		return (bool)$this->getAttribute($offset);
	}

	public function offsetGet(mixed $offset)
	{
		return $this->getAttribute($offset);
	}

	public function offsetSet(mixed $offset, $value):void
	{
		$this->setAttribute($offset, $value);
	}

	public function offsetUnset($offset)
	{
		$this->removeAttribute($offset);
	}

	/**
	 *
	 * 元素具有 class $class?
	 *
	 * @param string $class
	 * @return bool
	 */
	public function hasClass(string $class):bool
	{
		return in_array($class, explode(' ', $this->getAttribute('class')));
	}

	/**
	 * 给元素添加class,允许一次添加多个，尝试添加已存在的class会自动去重。
	 *
	 * @param string ...$class			
	 * @return Element $this
	 */
	public function addClass(?string ...$class):static
	{
		$classes = array_merge(explode(' ', $this->getAttribute('class')), (array)$class);
		$this->setAttribute('class', trim(implode(' ', $classes)));
		return $this;
	}

	/**
	 * 给元素去掉class，允许一次去掉多个，尝试去掉不存在的class不会出错。
	 *
	 * @param string $class			
	 * @return Element $this
	 */
	public function removeClass(string ...$class):static
	{
		$classes = array_diff(explode(' ', $this->getAttribute('class')), $class);
		$this->class(trim(implode(' ', $classes))); // 如果弄完变成没有了。
		return $this;
	}

	/**
	 * 清除所有子元素
	 * @return $this
	 */
	public function clear():static
	{
		while ($this->firstElementChild){
			$this->firstElementChild->remove();
		}
		return $this;
	}

}
