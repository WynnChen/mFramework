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
class RadioSpan extends InputSpan
{
	public function __construct($name, $value, $label, $id = null)
	{
		parent::__construct('radio', $name, $value, $label, $id);
	}
}
