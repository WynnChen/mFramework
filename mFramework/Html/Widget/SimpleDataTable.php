<?php
/**
 * mFramework - a mini PHP framework
*
* @package   mFramework
* @version   v5
* @copyright 2009-2016 Wynn Chen
* @author	Wynn Chen <wynn.chen@outlook.com>
*/
namespace mFramework\Html\Widget;

use mFramework\Html;

class SimpleDataTable
{

	/**
	 *
	 * callback函数的调用原型为： function($tr, $item, $key, $index)
	 * 其中$tr为本行的<tr>元素
	 * $key与$item 为 foreach($data as $key=>$item) 对应内容。
	 * $index为行数编号，第一行为1。
	 *
	 * $header中的内容如果已经是TH，就直接使用，否则自动封装。
	 * 此特性有助于为TH作预处理（加class，id，之类。）
	 *
	 * @param array $headers
	 *			TH内容，按顺序列出即可。
	 * @param mixed $data
	 *			准备处理的数据，任何允许foreach的类型均可。
	 * @param function $callback
	 *			回调函数，在处理数据的时候使用。
	 */
	public static function create($headers, $data = null, $callback = null)
	{
		$table = Html::table($thead = Html::thead(), $tbody = Html::tbody())->addClass('data');
		$table->thead = $thead;
		$table->tbody = $tbody;
		
		// TH部分
		$tr = Html::tr()->appendTo($thead);
		foreach ($headers as $info) {
			// 考虑要不要用<th>包装起来：
			if (($info instanceof \DOMElement) and ($info->tagName == 'th')) {
				$tr->append($info);
			} else {
				$tr->append(Html::th($info));
			}
		}
		
		// 内容部分：
		if ($data) {
			$i = 0;
			foreach ($data as $key => $item) {
				$i++;
				$tr = Html::tr()->appendTo($tbody);
				call_user_func($callback, $tr, $item, $key, $i); // update: 废弃$callback($tr,$item,$i)的用法，增加灵活度。
			}
		}
		return $table;
	}
}