<?php
namespace mFramework\Html\Element;

use \mFramework\Html;

/**
 *
 * Html input element
 *
 * @package mFramework
 * @author Wynn Chen
 */
class Input extends \mFramework\Html\Element
{
	public function __construct($name = null, $type = null, $value = null)
	{
		parent::__construct('input');
		$name and $this->setAttribute('name', $name);
		$type and $this->setAttribute('type', $type);
		$value and $this->setAttribute('value', $value);
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
			$this->setAttribute('required', 'required');
		}
		else{
			$this->removeAttribute('required');
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
			$this->setAttribute('checked', 'checked');
		}
		else{
			$this->removeAttribute('checked');
		}
		return $this;
	}

	public function readonly($readonly = true)
	{
		if($readonly){
			$this->setAttribute('readonly', 'readonly');
		}
		else{
			$this->removeAttribute('$readonly');
		}
		return $this;
	}

}
