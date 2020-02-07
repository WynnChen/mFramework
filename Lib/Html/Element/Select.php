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
use mFramework\Html\Element;

/**
 *
 * Html select element
 *
 * @package mFramework
 * @author Wynn Chen
 */
class Select extends Element
{
	public function __construct($name, ...$content)
	{
		parent::__construct('select', ...$content);
		$this->setAttribute('name', $name);
	}
	
	/**
	 * 设置此元素的required属性。
	 * 
	 * @param string $required
	 * @return self
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
}
