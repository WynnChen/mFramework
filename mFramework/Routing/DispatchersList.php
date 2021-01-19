<?php
namespace mFramework\Routing;

use mFramework\Http\Request;
use mFramework\Http\Response;
use SplDoublyLinkedList;

/**
 * dispatchers doublelinkedlist
 *
 * 接受添加多个dispatcher，dispatch() 时遍历并在第一个成功的dispatch处终止。
 *
 *
 */
class DispatchersList extends SplDoublyLinkedList implements DispatcherInterface
{
	CONST MODE_STACK = SplDoublyLinkedList::IT_MODE_LIFO;
	CONST MODE_QUEUE = SplDoublyLinkedList::IT_MODE_FIFO;

	/**
	 * @param Request $request
	 * @return Response
	 * @throws DispatchException
	 */
	public function handle(Request $request):Response
	{
		foreach ($this as $dispatcher) {
			try{
			return $dispatcher->handle($request);
			}
			catch (DispatchException $e){
				//继续下一个
			}
		}
		throw new DispatchException('dispatch列表全部失败。');
	}
}