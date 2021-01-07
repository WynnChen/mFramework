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
	public function __construct($name, $type = null)
	{
		parent::__construct('input');
		$this->setAttribute('name', $name);
		$type and $this->setAttribute('type', $type);
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

}
