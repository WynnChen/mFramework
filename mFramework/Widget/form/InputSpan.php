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
	
	public function __construct()
	{
		parent::__construct('span');
	}
	
	public static function create($type, $name, $value, $label, $id = null)
	{
		$span = new self();
		if (!$id) {
			$id = $name . '_' . mt_rand(0, 99) . uniqid();
		}
		$span->addClass($type);
		$span->input = Html::input($name, $type)->id($id)->value($value)->appendTo($span);
		$span->label = Html::label($label)->for($id)->addClass('follow')->appendTo($span);
		return $span;
	}
	
	/**
	 * 设置此input的required属性。
	 * 
	 * @param string $required
	 * @return \mFramework\Html\Element\Input
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
	 * @return \mFramework\Html\Element\Input
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
