<?php
/**
 * mFramework - a mini PHP framework
 *
 * @package   mFramework
 * @copyright 2009-2020 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework\Dispatcher;

use mFramework\Dispatcher;
use SplDoublyLinkedList;

/**
 * dispatchers doublelinkedlist
 *
 * 接受添加多个dispatcher，dispatch() 时遍历并在第一个成功的dispatch处终止。
 *
 * @author Wynn
 *		
 */
class DispatchersList extends SplDoublyLinkedList implements Dispatcher
{
	CONST MODE_STACK = SplDoublyLinkedList::IT_MODE_LIFO;
	CONST MODE_QUEUE = SplDoublyLinkedList::IT_MODE_FIFO;

	public function dispatch(string $action):string|false
	{
		foreach ($this as $dispatcher) {
			$result = $dispatcher->dispatch($action);
			if ($result) {
				return $result;
			}
		}
		return false;
	}
}