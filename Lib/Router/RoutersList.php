<?php
/**
 * mFramework - a mini PHP framework
 * 
 * @package   mFramework
 * @version   v5
 * @copyright 2009-2016 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework\Router;

/**
 * Routers doublelinkedlist
 *
 * 接受添加多个router，route()时遍历便在第一个成功的router终止。
 *
 * @author Wynn
 *		
 */
class RoutersList extends \SplDoublyLinkedList implements \mFramework\Router
{

	CONST MODE_STACK = \SplDoublyLinkedList::IT_MODE_LIFO;

	CONST MODE_QUEUE = \SplDoublyLinkedList::IT_MODE_FIFO;

	private $current = null;

	public function route(\mFramework\Http\Request $request)
	{
		foreach ($this as $route) {
			$result = $route->route($request);
			if ($result !== false) {
				$current = $route;
				return $result;
			}
		}
		return false;
	}
}