<?php
namespace mFramework\Html\Widget\Form;

use mFramework\Html;
use mFramework\Html\Element;

class RadioGroup extends Element
{
	/**
	 * @var array[InputSpan]
	 */
	private array $items = [];

	/**
	 *
	 * @param string $name name属性值
	 * @param array $info [value内容 => 显示label内容]
	 * @param mixed $value 初始选中值，可以为null
	 * @throws Html\Exception
	 */
	public function __construct(string $name, array $info, mixed $value = null)
	{
		parent::__construct('span', '');
		$this->set('class', 'radio_group');
		foreach ($info as $key => $display) {
			$this->items[$key] = $radio = RadioSpan::create($name, $key, $display)->appendTo($this);
			if(($value !== null) and ((string)$key == (string)$value)){
				$radio->checked();
			}
		}
	}

	/**
	 * 设置此input的required属性。
	 *
	 * @param string $required
	 */
	public function required(bool $required = true):self
	{
		foreach ($this->items as $input_span){
			$input_span->required($required);
		}
		$this->addClass('required');
		return $this;
	}

	public function disabled(bool $disabled = true):self
	{
		foreach ($this->items as $input_span){
			$input_span->disabled($disabled);
		}
		$this->addClass('disabled');
		return $this;
	}
}