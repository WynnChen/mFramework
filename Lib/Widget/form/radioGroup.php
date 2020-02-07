<?php
namespace mFramework\Widget\Form;

use mFramework\Html;
use mFramework\Html\Element;

class RadioGroup extends Element
{
	public function __construct()
	{
		parent::__construct('span');
	}
	
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
	public static function create($name, $info, $required = false, $value = null)
	{
		$box = new self();
		$box->set('class', 'radio_group');
		foreach ($info as $key => $display) {
			$radio = InputSpan::create('radio', $name, $key, $display)->checked((($value !== null) and ((string)$key == (string)$value)));
			if ($required) {
				$radio->input->required('raquired');
			}
			$box->append($radio);
		}
		return $box;
	}
}