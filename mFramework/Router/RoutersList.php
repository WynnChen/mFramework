<?php
/**
 * mFramework
 */
namespace mFramework\Router;

use mFramework\Http\Request;
use SplDoublyLinkedList;

/**
 * Routers,doublelinkedlist
 *
 * 接受添加多个router，route()时遍历便在第一个成功的router终止。
 *
 * @author Wynn
 *		
 */
class RoutersList extends SplDoublyLinkedList implements RouterInterface
{

	CONST MODE_STACK = SplDoublyLinkedList::IT_MODE_LIFO;
	CONST MODE_QUEUE = SplDoublyLinkedList::IT_MODE_FIFO;

	/**
	 * @param Request $request
	 * @return string|false
	 */
	public function route(Request $request):string|false
	{
		foreach ($this as $route) {
			$result = $route->route($request);
			if ($result !== false) {
				return $result;
			}
		}
		return false;
	}
}