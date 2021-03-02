<?php
namespace mFramework\Html\Widget\Form;

use mFramework\Html;
use mFramework\Html\Element;

/**
 *
 * Html input span
 *
 * @package mFramework
 * @author Wynn Chen
 */
abstract class InputSpan extends Element
{
	public $input;
	public $label;
	
	public function __construct($type, $name, $value, $label, $id = null)
	{
		parent::__construct('span');
		if (!$id) {
			$id = $name . '_' . mt_rand(0, 99) . uniqid();
		}
		$this->addClass($type, 'input_span');
		$this->input = Html::input($name, $type)->id($id)->value($value)->appendTo($this);
		$this->label = Html::label($label)->for($id)->addClass('follow')->appendTo($this);
	}

	/**
	 * 设置此input的required属性。
	 *
	 * @param bool $required
	 * @return $this
	 */
	public function required(bool $required = true)
	{
		if($required){
			$this->input->setAttribute('required', 'required');
		}
		else{
			$this->input->removeAttribute('required');
		}
		return $this;
	}

	/**
	 * 设置此input的checked属性。注意对于某些类型的input无效。
	 *
	 * @param bool $checked
	 * @return $this
	 */
	public function checked(bool $checked = true)
	{
		if($checked){
			$this->input->setAttribute('checked', 'checked');
		}
		else{
			$this->input->removeAttribute('checked');
		}
		return $this;
	}

	/**
	 *
	 * @param bool $disabled
	 * @return InputSpan
	 */
	public function disabled(bool $disabled = true)
	{
		if($disabled){
			$this->input->setAttribute('disabled', 'disabled');
		}
		else{
			$this->input->removeAttribute('disabled');
		}
		return $this;
	}

}
