<?php
use mFramework\Router\DefaultRouter;
use mFramework\Router\RoutersList;
use mFramework\Router;
use mFramework\Http\Request;

class dummyRouter implements Router
{

	public function route(Request $request)
	{
		return 'dummy';
	}

	public function reverseRoute($action, $params = null, $query = [], $fragment = null)
	{
		return null;
	}
}

class RoutersListTest extends PHPUnit\Framework\TestCase
{

	public function testRouterList()
	{
		$router = new DefaultRouter();
		$_SERVER['REQUEST_URI'] = 'abc/xyz.html';
		$request = new Request();
		
		$router_a = new DefaultRouter();
		$router_b = new dummyRouter();
		$router = new RoutersList();
		$router->push($router_a);
		$router->push($router_b);
		
		$router->setIteratorMode(RoutersList::MODE_STACK);
		$this->assertEquals('dummy', $router->route($request));
		
		$router->setIteratorMode(RoutersList::MODE_QUEUE);
		$this->assertEquals('abc', $router->route($request));
		
		// 空的就直接false呢
		$router = new RoutersList();
		$this->assertFalse($router->route($request));
	}
}
