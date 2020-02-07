<?php
use mFramework\Application;
use mFramework\Middleware;
use mFramework\Http\Request;
use mFramework\Http\Response;
use mFramework\Dispatcher\DefaultDispatcher;
use mFramework\Router\DefaultRouter;

class middlewareA extends Middleware
{

	public function call(Application $app)
	{
		echo 'a';
		$this->next->call($app);
		echo 'a';
	}
}

class middlewareB extends Middleware
{

	public function call(Application $app)
	{
		echo 'b';
		// $this->next->call(); //跳过app。
		echo 'b';
	}
}

class myAction extends \mFramework\Action
{

	protected function run(\mFramework\Http\Request $request, \mFramework\Http\Response $response)
	{
		$this->disableView();
		echo 'good';
	}
}

class notAction
{}

class vAction extends \mFramework\Action
{

	protected function run(\mFramework\Http\Request $request, \mFramework\Http\Response $response)
	{
		$this->setView('zView');
	}
}

// render()测试用。
class zView implements \mFramework\View
{

	public function renderResponse(Response $response, \mFramework\Map $data)
	{}
}

class ApplicationTest extends PHPUnit\Framework\TestCase
{

	public function setUp()
	{
		$class = new ReflectionClass('mFramework\Application');
		$prop = $class->getProperty('app');
		$prop->setAccessible(true);
		$prop->setValue(null);
	}

	public function testGetInstance()
	{
		// 取得的是第一个。
		$app = new Application();
		$this->assertSame($app, Application::getApp());
		$app = new Application('name');
		$this->assertNotSame($app, Application::getApp());
	}

	public function testGetInstance2()
	{
		$this->expectException('mFramework\Application\NoApplicationException');
		Application::getApp();
	}

	public function testAppName()
	{
		$app = new Application('myName');
		$this->assertSame('myName', $app->getAppName());
		$this->assertSame($app, Application::getApp('myName'));
	}

	public function testMiddleware()
	{
		$app = new Application('t-1'); // 每次都要用不同的名字。
		$m1 = new middlewareA();
		$m2 = new middlewareB();
		$this->assertSame($app, $app->addMiddleware($m2));
		$app->addMiddleware($m1); // 逆序包围。
		$this->expectOutputString('abba');
		$app->run();
	}

	public function testMiddleware2()
	{
		$app = new Application('t_2');
		$m1 = new middlewareA();
		$m2 = new middlewareB();
		$app->addMiddleware($m1)->addMiddleware($m2); // 逆序包围。
		$this->assertSame($m1, $m2->getNextMiddleware()); // 检查链表
		$this->assertSame($app, $m1->getNextMiddleware()); // 检查链表
		$this->expectOutputString('bb'); // 中途中止了
		$app->run();
	}

	public function testRouterAccess()
	{
		$app = new Application('t-3');
		$router = new DefaultRouter();
		$this->assertSame($app, $app->setRouter($router));
		$this->assertSame($router, $app->getRouter());
	}

	public function testDispatcherAccess()
	{
		$app = new Application('t-4');
		$dispatcher = new DefaultDispatcher();
		$this->assertSame($app, $app->setDispatcher($dispatcher));
		$this->assertSame($dispatcher, $app->getDispatcher());
	}

	public function testRequestAccess()
	{
		$app = new Application('t-5');
		$request = new Request();
		$this->assertSame($app, $app->setRequest($request));
		$this->assertSame($request, $app->getRequest());
	}

	public function testResponseAccess()
	{
		$app = new Application('t-6');
		$response = new Response();
		$this->assertSame($app, $app->setResponse($response));
		$this->assertSame($response, $app->getResponse());
	}

	public function testRun()
	{
		$app = new Application('t-7');
		try {
			$app->run();
		} catch (\mFramework\Application\ActionClassNotFoundException $e) {
			// 默认配置
			$request = $app->getRequest();
			$response = $app->getResponse();
			$dispatcher = $app->getDispatcher();
			$router = $app->getRouter();
			$this->assertInstanceOf('\mFramework\Http\Request', $request);
			$this->assertInstanceOf('\mFramework\Http\Response', $response);
			$this->assertInstanceOf('\mFramework\Router\DefaultRouter', $router);
			$this->assertInstanceOf('\mFramework\Dispatcher\DefaultDispatcher', $dispatcher);
		}
	}

	public function testRun2()
	{
		$app = new Application('t-8');
		$router = $this->createMock('\mFramework\Router\DefaultRouter');
		$router->method('route')->willReturn(false);
		$app->setRouter($router);
		$this->expectException('mFramework\Application\RouteFailException');
		$app->run();
	}
	public function testRun4()
	{
		$app = new Application('t-8');
		$router = $this->createMock('\mFramework\Router\DefaultRouter');
		$router->method('route')->willReturn('my');
		$app->setRouter($router);
		$this->expectOutputString('goodHTTP/1.1 200 OK|');
		$app->run();
	}

	public function testRun3()
	{
		$app = new Application('t-9');
		$dispatcher = $this->createMock('\mFramework\Dispatcher\DefaultDispatcher');
		$dispatcher->method('dispatch')->willReturn(false);
		$app->setDispatcher($dispatcher);
		$this->expectException('mFramework\Application\DispatchFailException');
		$app->run();
	}



	public function testRun5()
	{
		$app = new Application('t-11');
		$router = $this->createMock('\mFramework\Router\DefaultRouter');
		$router->method('route')->willReturn('not');
		$app->setRouter($router);
		$this->expectException('mFramework\Application\ActionClassInvalidException');
		$app->run();
	}

	public function testRun6()
	{
		$app = new Application('t-12');
		$router = $this->createMock('\mFramework\Router\DefaultRouter');
		$router->method('route')->willReturn('v');
		$app->setRouter($router);
		$this->expectOutputString('HTTP/1.1 200 OK|'); // 还有atuoresponse带来的header
		$app->run();
	}
}