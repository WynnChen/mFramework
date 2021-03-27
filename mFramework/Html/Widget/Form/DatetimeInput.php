<?php
namespace mFramework\Html\Widget\Form;

use mFramework\Html;
use mFramework\Html\Element;

/**
 *
 */
class DatetimeInput extends Element
{
	public $date_input;
	public $time_input;

	/**
	 * DatetimeInput 注意提供的 name是个整体索引，实际的input内容是 * name[date] 和 name[time]。
	 * 当前各个主流浏览器对于datetime-local 和time的一些问题，单独封装一下好使用。
	 *
	 * @param string $name
	 * @param string $value YYYY-mm-dd HH:ii:ss 格式
	 * @throws Html\Exception
	 */
	public function __construct(string $name, ?string $value = null)
	{
		parent::__construct('span');
		$this->addClass('datetime', 'input_span');

		$this->date_input = Html::input($name.'[date]', 'date')->appendTo($this);
		$this->time_input = Html::input($name.'[time]', 'time')->step(1)->appendTo($this);//step来强行变成秒格式。

		$this->value($value);
	}

	public function value(string $value): static
	{
		if(!$value){
			return $this;
		}
		else{
			if($value == 'now'){
				$value = date('Y-m-d H:i:s');
			}
			$info = explode(' ', $value, 2);
			if(count($info) < 2){
				$date = $value;
				$time = null;
			}
			else{
				list($date, $time) = $info;
			}

		}

		$this->date_input->value($date);
		$this->time_input->value($time);
		return $this;
	}

	public function set(string $attribute, float|int|string|null $value): static
	{
		if($attribute == 'value'){
			return $this->value($value);
		}
		else{
			return parent::set($attribute, $value);
		}
	}
	public function offsetSet(mixed $attribute, $value): Void
	{
		if($attribute == 'value'){
			$this->value($value);
		}
		else{
			parent::offsetSet($attribute, $value);
		}
	}

	/**
	 * 设置此input的required属性。
	 *
	 * @param bool $required
	 * @return $this
	 */
	public function required(bool $required = true):self
	{
		if($required){
			$this->date_input->setAttribute('required', 'required');
			$this->time_input->setAttribute('required', 'required');
			$this->addClass('required');
		}
		else{
			$this->date_input->setAttribute('required', 'required');
			$this->time_input->removeAttribute('required');
			$this->removeClass('required');
		}
		return $this;
	}

	/**
	 * 设置此input的checked属性。注意对于某些类型的input无效。
	 *
	 * @param bool $checked
	 * @return $this
	 */
	public function checked(bool $checked = true):self
	{
		if($checked){
			$this->date_input->setAttribute('checked', 'checked');
			$this->time_input->setAttribute('checked', 'checked');
			$this->addClass('checked');
		}
		else{
			$this->date_input->removeAttribute('checked');
			$this->time_input->removeAttribute('checked');
			$this->removeClass('checked');
		}
		return $this;
	}

	/**
	 *
	 * @param bool $disabled
	 */
	public function disabled(bool $disabled = true):self
	{
		if($disabled){
			$this->date_input->setAttribute('disabled', 'disabled');
			$this->time_input->setAttribute('disabled', 'disabled');
			$this->addClass('disabled');
		}
		else{
			$this->date_input->removeAttribute('disabled');
			$this->time_input->removeAttribute('disabled');
			$this->removeClass('disabled');
		}
		return $this;
	}

}
