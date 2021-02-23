<?php
/**
 * mFramework - a mini PHP framework
 * 
 * Require PHP 7 since v4.0
 *
 * @package   mFramework
 * @version   4.1
 * @copyright 2009 - 2016 Wynn Chen
 * @author    Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework\Widget\Form;

use \mFramework\Html;
use mFramework\Html\Element;

/**
 *
 * Html input span
 *
 * @package mFramework
 * @author Wynn Chen
 */
class InputSpan extends Element
{
	public $input;
	public $label;
	
	public function __construct($type, $name, $value, $label, $id = null)
	{
		parent::__construct('span');
		if (!$id) {
			$id = $name . '_' . mt_rand(0, 99) . uniqid();
		}
		$this->addClass($type);
		$this->input = Html::input($name, $type)->id($id)->value($value)->appendTo($this);
		$this->label = Html::label($label)->for($id)->addClass('follow')->appendTo($this);
	}
	
	/**
	 * 设置此input的required属性。
	 * 
	 * @param string $required
	 */
	public function required($required = true)
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
	 * @param string $checked
	 */
	public function checked($checked = true)
	{
		if($checked){
			$this->input->setAttribute('checked', 'checked');
		}
		else{
			$this->input->removeAttribute('checked');
		}
		return $this;
	}

}
