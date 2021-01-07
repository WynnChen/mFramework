<?php
use mFramework\Router\DefaultRouter;
use mFramework\Http\Request;

class DefaultRouterTest extends PHPUnit\Framework\TestCase
{

	public function data()
	{
		return array(
			// url, action, params
			array('/blog/post/some-thing.xml','blog/post',array('ext' => 'xml','input' => 'some-thing','' => 'some-thing')),
			array('/blog/post/something.xml','blog/post',array('ext' => 'xml','input' => 'something','' => 'something')),
			array('/blog/post/something.xml/','blog/post',array('ext' => 'xml','input' => 'something','' => 'something')),
			array('/blog/post/something','blog/post/something',array('ext' => null,'input' => null,'' => null)),
			array('/blog/post/something/','blog/post/something',array('ext' => null,'input' => null,'' => null)),
			array('/something/','something',array('ext' => null,'input' => null,'' => null)),
			array('/something','something',array('ext' => null,'input' => null,'' => null)),
			array('/something.html/','',array('ext' => 'html','input' => 'something','' => 'something')),
			array('/something.html','',array('ext' => 'html','input' => 'something','' => 'something')),
			array('something.html','',array('ext' => 'html','input' => 'something','' => 'something')),
			array('/','',array('ext' => null,'input' => null,'' => null)),
			array('\\','',array('ext' => null,'input' => null,'' => null))); // IIS下 / 会解析为 \
	}

	/**
	 * @dataProvider data
	 */
	public function testRouter($url, $action, $params)
	{
		$router = new DefaultRouter();
		$_SERVER['REQUEST_URI'] = $url;
		$request = new Request();
		$return_action = $router->route($request);
		$this->assertSame($action, $return_action);
		$this->assertEquals($params, $request->getAllParameters());
	}

	/**
	 * @dataProvider data
	 */
	public function testReverseRoute($url, $action, $params)
	{
		$url = rtrim($url, '/'); // 最后的/无效。
		if ($url == '\\' or $url == '') { // ''是 '/'被处理后出来的。
			$url = '/';
		}
		if ($url == 'something.html') {
			$url = '/something.html';
		}
		$router = new DefaultRouter();
		$this->assertEquals($url, $router->reverseRoute($action, [$params[''],$params['ext']]));
	}

	public function testReverseRoute2()
	{
		$url = '/some/abc.html';
		$action = 'some';
		$params = array('input' => 'abc','ext' => 'html'); // input只有一个元素的时候可以不声明数组
		$router = new DefaultRouter();
		$this->assertEquals($url, $router->reverseRoute($action, $params));
	}
}
