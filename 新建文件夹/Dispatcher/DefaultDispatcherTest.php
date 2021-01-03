<?php
use mFramework\Dispatcher\DefaultDispatcher;

class DefaultDispatcherTest extends PHPUnit\Framework\TestCase
{

	public function data()
	{
		return array(
			// action, resut action
			array('','index'), // 默认的
array('some','some'),array('blog/post','blog_post'),array('blog/post/something','blog_post_something'));
	}

	/**
	 * @dataProvider data
	 */
	public function testDispatch($action, $class)
	{
		$dispatcher = new DefaultDispatcher();
		$this->assertEquals([$class.'Action', $class.'View' ], $dispatcher->dispatch($action));
	}

	public function testDefaultAction()
	{
		$dispatcher = new DefaultDispatcher('default');
		$this->assertEquals(['defaultAction', 'defaultView'], $dispatcher->dispatch(''));
		$dispatcher = new DefaultDispatcher('some/index');
		$this->assertEquals(['some_indexAction', 'some_indexView'], $dispatcher->dispatch(''));
	}
}
