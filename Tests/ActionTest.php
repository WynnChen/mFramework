<?php
use mFramework\Action;
use mFramework\Application;
use mFramework\Http\Request;
use mFramework\Http\Response;

// setView()测试用。
class sView implements \mFramework\View
{

	public function renderResponse(Response $response, \mFramework\Map $data)
	{}
}
// setView()测试用。
class aView implements \mFramework\View
{

	public function renderResponse(Response $response, \mFramework\Map $data)
	{}
}

class newAction extends Action
{

	protected function run(Request $request, Response $response)
	{}

	public function getInfo()
	{
		return [$this->getRequest(),$this->getResponse(),$this->getApp()];
	}
}

class ActionTest extends PHPUnit\Framework\TestCase
{

	private $action, $app, $request, $response;

	protected function setUp()
	{
		$this->app = new Application('forActionTest' . microtime());
		$this->request = new Request();
		$this->response = new Response();
		$this->action = new newAction($this->request, $this->response, $this->app);
	}

	public function testNew()
	{
		$action = $this->action;
		$this->assertEquals([$this->request,$this->response,$this->app], $action->getInfo());
	}

	public function testSetView()
	{
		$action = $this->action;
		$this->assertFalse($action->isViewEnabled()); // 默认false
		$action->setView('someView'); // ok
		$this->assertTrue($action->isViewEnabled()); // setView会打开auto render
		$action->setView(new sView()); // ok
		$action->setView('aView'); // 有效类名
		$action->getView(); // 在这里会被实例化。
		$this->assertInstanceOf('aView', $action->getView());
	}

	/**
	 * 类型不对的对象不能set
	 */
	public function testSetView2()
	{
		$action = $this->action;
		$this->expectException('mFramework\\Action\\InvalidViewException');
		$action->setView(new \SplMaxHeap());
	}

	/**
	 * 不存在的类名延迟到getview()时异常
	 */
	public function testSetView3()
	{
		$action = $this->action;
		$action->setView('nonexistclass');
		$this->expectException('mFramework\\Action\\InvalidViewException');
		$action->getView();
	}

	/**
	 * 类型不对的类名延迟到getview()时异常
	 */
	public function testSetView4()
	{
		$action = $this->action;
		$action->setView('SPLMaxHeap');
		$this->expectException('mFramework\\Action\\InvalidViewException');
		$action->getView();
	}

	public function testViewSwitch()
	{
		$action = $this->action;
		// 默认false
		$this->assertFalse($action->isViewEnabled());
		// 设置：
		$action->enableView();
		$this->assertTrue($action->isViewEnabled());
		$action->disableView();
		$this->assertFalse($action->isViewEnabled());
		$action->enableView();
		$this->assertTrue($action->isViewEnabled());
		// 不是切换哦
		$action->enableView();
		$this->assertTrue($action->isViewEnabled());
	}

	public function testDataAccess()
	{
		$action = $this->action;
		$action->assign('key', 'somestring');
		$action->assign('key', 'otherstring');
		$data = $action->getData();
		$this->assertSame('otherstring', $data->key);
		$this->assertEquals(1, count($data));
	}
}
