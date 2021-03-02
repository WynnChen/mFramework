<?php
namespace mFramework\Html\Widget\Form;

use mFramework\Html;

/**
 *
 * Html input span
 *
 * @package mFramework
 * @author Wynn Chen
 */
class CheckboxSpan extends InputSpan
{
	public function __construct($name, $value, $label, $id = null)
	{
		parent::__construct('checkbox', $name, $value, $label, $id);
	}
}
