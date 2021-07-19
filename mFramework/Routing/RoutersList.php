<?php
declare(strict_types=1);
namespace mFramework\Routing;


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
	 * @return Request
	 * @throws RouteException
	 */
	public function route(Request $request):Request
	{
		foreach ($this as $route) {
			try {
				return $route->route($request);
			}
			catch (RouteException){
				//继续下一个
			}
		}
		throw new RouteException('router列表内无成功匹配');
	}
}