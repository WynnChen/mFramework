<?php
/**
 * mFramework - a mini PHP framework
 * 
 * @package   mFramework
 * @version   v5
 * @copyright 2009-2016 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework\Dispatcher;

/**
 * dispatchers doublelinkedlist
 *
 * 接受添加多个dispatcher，dispatch() 时遍历并在第一个成功的dispatch处终止。
 *
 * @author Wynn
 *		
 */
class DispatchersList extends \SplDoublyLinkedList implements \mFramework\Dispatcher
{

	CONST MODE_STACK = \SplDoublyLinkedList::IT_MODE_LIFO;

	CONST MODE_QUEUE = \SplDoublyLinkedList::IT_MODE_FIFO;

	public function dispatch(string $action)
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