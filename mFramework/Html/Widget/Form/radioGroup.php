<?php
namespace mFramework\Html\Widget\Form;

use mFramework\Html;
use mFramework\Html\Element;

class RadioGroup extends Element
{
	/**
	 *
	 * @param string $name
	 *			name属性值
	 * @param array $info
	 *			[value内容 => 显示label内容]
	 * @param string $value
	 *			初始选中值，可以为null
	 * @return Html\Element
	 */
	public function __construct($name, $info, $required = false, $value = null)
	{
		parent::__construct('span');
		$this->set('class', 'radio_group');
		foreach ($info as $key => $display) {
			$radio = InputSpan::create('radio', $name, $key, $display)->checked((($value !== null) and ((string)$key == (string)$value)));
			if ($required) {
				$radio->input->required('raquired');
			}
			$this->append($radio);
		}
	}
}