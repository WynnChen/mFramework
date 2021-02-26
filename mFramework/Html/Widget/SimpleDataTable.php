<?php
namespace mFramework\Html\Widget;

use mFramework\Html;
use mFramework\Html\Element;
use mFramework\Html\Fragment;

class SimpleDataTable extends Element
{
	private Element $thead;
	private Element $tbody;

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
	 * @param array|null $headers TH内容，按顺序列出即可。
	 * @param mixed $data 准备处理的数据，任何允许foreach的类型均可。
	 * @param callable|null $callback 回调函数，在处理数据的时候使用。
	 * @param string|Element|Fragment|null $empty
	 * @throws Html\Exception
	 */
	public function __construct(?array $headers, ?iterable $data = null, ?callable $callback = null, string|Element|Fragment|null $empty = null)
	{
		parent::__construct('table');
		$this->addClass('data');
		$this->thead = $thead = Html::thead()->appendTo($this);
		$this->tbody = $tbody = Html::tbody()->appendTo($this);

		// th建立：
		$tr = Html::tr()->appendTo($thead);
		foreach ($headers as $info) {
			// 考虑要不要用<th>包装起来：
			if (($info instanceof Element) and ($info->tagName == 'th')) {
				$tr->append($info);
			} else {
				$tr->append(Html::th($info));
			}
		}
		
		// 内容部分：
		if ($data) {
			$row_no = 0;
			foreach ($data as $key => $item) {
				$row_no++;
				$tr = Html::tr()->appendTo($tbody);
				// 不要用  $callback($tr,$item,$i)的用法，增加灵活度。
				call_user_func($callback, $tr, $item, $key, $row_no);
			}
		}
		else{
			$tbody->addClass('empty');
			$tr = Html::tr(Html::div($empty ?: '无相关数据。'))->colspan(count($headers))->appendTo($tbody);
		}
	}
}