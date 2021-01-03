<?php
use mFramework\Dispatcher\DefaultDispatcher;
use mFramework\Dispatcher\DispatchersList;

class DispatchersListTest extends PHPUnit\Framework\TestCase
{

	public function testDispatcherList()
	{
		$dispatcher_a = new DefaultDispatcher('default');
		$dispatcher_b = new DefaultDispatcher('some/index');
		$dispatcher = new DispatchersList();
		$dispatcher->push($dispatcher_a);
		$dispatcher->push($dispatcher_b);
		
		$dispatcher->setIteratorMode(DispatchersList::MODE_STACK);
		$this->assertEquals(['some_indexAction','some_indexView'], $dispatcher->dispatch(''));
		
		$dispatcher->setIteratorMode(DispatchersList::MODE_QUEUE);
		$this->assertEquals(['defaultAction','defaultView'], $dispatcher->dispatch(''));
		
		// 空的就直接false呢
		$dispatcher = new DispatchersList();
		$this->assertFalse($dispatcher->dispatch('xyz'));
	}
}
