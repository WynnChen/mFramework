<?php
use mFramework\Dispatcher\DefaultDispatcher;

class DefaultDispatcherTest extends PHPUnit\Framework\TestCase
{

	public function data(): array
	{
		return array(
			// action, resut action
			array('','index'), // 默认的
array('some','some'),array('blog/post','blog_post'),array('blog/post/something','blog_post_something'));
	}

	/**
	 * @dataProvider data
	 * @param $action
	 * @param $class
	 */
	public function testDispatch($action, $class)
	{
		$dispatcher = new DefaultDispatcher();
		$this->assertEquals($class.'Action', $dispatcher->dispatch($action));
	}

	public function testDefaultAction()
	{
		$dispatcher = new DefaultDispatcher('default');
		$this->assertEquals('defaultAction', $dispatcher->dispatch(''));
		$dispatcher = new DefaultDispatcher('some/index');
		$this->assertEquals('some_indexAction', $dispatcher->dispatch(''));
	}
}
