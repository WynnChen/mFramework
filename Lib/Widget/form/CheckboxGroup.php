<?php
namespace mFramework\Widget\Form;

use mFramework\Html;

class CheckboxGroup
{

	/**
	 * 其内的所有checkbox都会用 "$name[$key]" 这样的格式做为name属性，即一数组
	 * post中取得的内容是一个数组，包含了所有被checked的项目。每个项目的 value为 $info 数组的key
	 * 注意$key的格式，不能包含[]，以及其他可能影响解析的内容.如果使用了用户输入内容，尤其需要慎重。
	 *
	 * @param string $name
	 *			name属性值
	 * @param array $info
	 *			[value内容 => 显示label内容]
	 * @param array $checked_values
	 *			初始选中值，可以为null
	 * @return Html\Element
	 */
	public static function create($name, $info, array $checked_values = null)
	{
		$box = Html::span()->set('class', 'checkbox_group');
		$items = [];
		foreach ($info as $key => $display) {
			$items[$key] = Html::checkbox($name . '[' . $key . ']', $key, $display)->appendTo($box);
		}
		if ($checked_values) {
			foreach ($checked_values as $value) {
				if (isset($items[$value])) {
					$items[$value]->checked('checked');
				}
			}
		}
		return $box;
	}
}