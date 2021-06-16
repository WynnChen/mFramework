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
use mFramework\Utility\Paginator;

class pageNav
{

	public static function create(Paginator $paginator, $linkTemplate, $visibleRange = 5, $pageSymbol = '_page_')
	{
		$block = Html::div($div = Html::div()->addClass('nav'), Html::div(Html::span('共 ' . $paginator->getTotalItems() . ' 项')->addClass('items'), ' / ', Html::span('每页 ' . $paginator->getItemsPerPage() . ' 项')->addClass('perpage'))->addClass('inf'))->addClass('pageNav');
		
		// 上一页
		if ($paginator->hasPrevPage()) {
			$div->append(Html::alink(str_replace($pageSymbol, $paginator->getCurrentPage() - 1, $linkTemplate), '<')->set('class', 'prev')
				->set('title', '上一页'), ' ');
		}
		
		// 先确定范围内页面
		$start = $paginator->getCurrentPage() - $visibleRange;
		$end = $paginator->getCurrentPage() + $visibleRange;

		// 端点修正:
		if ($start < 1) {
			$start = 1;
		}
		if ($end > $paginator->getTotalPages()) {
			$end = $paginator->getTotalPages();
		}
		
		// 临近端点修正：
		// if($visibleRange > 1){
		// if($start == 2){ $start -= 1; $end -= 1; }
		// if($end == $paginator->pages() - 1){ $start += 1; $end += 1; }
		// }
		// 必要的话第一页
		if ($start > 1) {
			$div->append(Html::alink(str_replace($pageSymbol, 1, $linkTemplate), 1)->set('class', 'first')
				->set('title', '第1页'), ' ');
		}
		// 必要的话 ...
		if ($start > 2) {
			$div->append(Html::span('...')->set('class', 'more'));
		}
		
		// 中间的
		for ($page = $start; $page <= $end; $page++) {
			if ($page == $paginator->getCurrentPage()) {
				$div->append(Html::em($page)->set('class', 'current'), ' ');
			} else {
				$div->append(Html::alink(str_replace($pageSymbol, $page, $linkTemplate), $page)->set('class', 'item')
					->set('title', '第' . $page . '页'), ' ');
			}
		}
		
		// 后面：
		if ($end + 1 < $paginator->getTotalPages()) {
			$div->append(Html::span('...')->set('class', 'more'), ' ');
		}
		// 最后一页
		if ($end < $paginator->getTotalPages()) {
			$div->append(Html::alink(str_replace($pageSymbol, $paginator->getTotalPages(), $linkTemplate), $paginator->getTotalPages())->set('class', 'last')
				->set('title', '第' . $paginator->getTotalPages() . '页'), ' ');
		}
		
		// 下一页
		if ($paginator->hasNextPage()) {
			$div->append(Html::alink(str_replace($pageSymbol, $paginator->getCurrentPage() + 1, $linkTemplate), '>')->set('class', 'next')
				->set('title', '下一页'), ' ');
		}
		
		return $block;
	}
}