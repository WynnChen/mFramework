<?php
use mFramework\Http\Response;
use mFramework\Map;

// handle测试用
class Handle
{

	public function headerHandle($response)
	{}

	public function bodyHandle($response)
	{}
}

class ResponseTest extends PHPUnit\Framework\TestCase
{

	public function testAutoResponseSwitch()
	{
		$response = new response();
		// 默认true
		$this->assertTrue($response->isAutoResponseEnabled());
		// 设置：
		$response->enableAutoResponse();
		$this->assertTrue($response->isAutoResponseEnabled());
		$response->disableAutoResponse();
		$this->assertFalse($response->isAutoResponseEnabled());
		$response->enableAutoResponse();
		$this->assertTrue($response->isAutoResponseEnabled());
		// 不是切换哦
		$response->enableAutoResponse();
		$this->assertTrue($response->isAutoResponseEnabled());
	}

	public function testHeaderAccess()
	{
		$response = new Response();
		$this->assertNull($response->getHeader('Content-Length'));
		$response->setHeader('Content-Length', 12345);
		$this->assertSame(12345, $response->getHeader('Content-Length'));
		$response->clearHeaders();
		$this->assertNull($response->getHeader('Content-Length'));
		$response->setHeader('Content-Length', 12345);
		$response->setHeader('Content-Length', null);
		$this->assertNull($response->getHeader('Content-Length'));
	}

	public function testHeaderOutput()
	{
		$response = new Response();
		// 输出测试：
		$response->setHeader('Content-Length', 12345);
		$response->setHeader('tt', array('a','b'));
		// 接管了之后每一段都是用|结尾。response
		$this->expectOutputString('HTTP/1.1 200 OK|Content-Length: 12345|tt: a|tt: b|');
		$response->setBodyHandle(function () {});
		$response->response();
	}

	public function testCookiesAccess()
	{
		$response = new Response();
		$response->setCookie('live', 'good');
		$this->assertEquals(array('value' => 'good','expire' => 0,'path' => '/','domain' => null,'secure' => false,'httponly' => false), $response->getCookie('live'));
		$response->setCookie('foo', 'bar', '2015-12-12 23:00:00');
		$this->assertEquals(array('value' => 'bar','expire' => strtotime('2015-12-12 23:00:00'),'path' => '/','domain' => null,'secure' => false,'httponly' => false), $response->getCookie('foo'));
		$this->assertNull($response->getCookie('nonexists'));
	}

	public function testCookiesOutput()
	{
		$response = new Response();
		// 输出测试：最前面有状态码的一行header。
		$response->setCookie('foo', 'bar', '2015-12-12 23:00:00');
		$this->expectOutputString('HTTP/1.1 200 OK|foo*' . implode('*', $response->getCookie('foo')));
		$response->setBodyHandle(function () {});
		$response->response();
	}

	public function testBodyAccess()
	{
		$response = new Response();
		$this->assertNull($response->getBody());
		$response->setBody('bodystring');
		$this->assertSame('bodystring', $response->getBody());
		$response->setBody(null);
		$this->assertNull($response->getBody());
		$response->setBody('bodystring');
	}

	public function testBodyOutput()
	{
		$response = new Response();
		$response->setBody('bodystring');
		// 输出测试
		$this->expectOutputString('bodystring');
		$response->setHeaderHandle(function () {}); // 跳过
		$response->response();
	}

	public function testResponseCode()
	{
		$response = new Response();
		$response->setResponseCode(300);
		$this->assertEquals(300, $response->getResponseCode());
		$response->setResponseCode(404, 'something wrong');
		$this->assertEquals(array('code' => 404,'msg' => 'something wrong'), $response->getResponseCode(true));
	}

	public function testResponseCodeOutput()
	{
		$response = new Response();
		$response->setResponseCode(404, 'something wrong');
		$this->expectOutputString('HTTP/1.1 404 something wrong|');
		$response->response();
	}

	public function testHandles()
	{
		$response = new Response();
		$handle_object = $this->createMock('Handle');
		$response->setHeaderHandle(array($handle_object,'headerHandle'));
		$response->setBodyHandle(array($handle_object,'bodyHandle'));
		
		$handle_object->expects($this->once())
			->method('headerHandle')
			->with($this->identicalTo($response));
		$handle_object->expects($this->once())
			->method('bodyHandle')
			->with($this->identicalTo($response));
		$response->response();
	}

	public function testIsResponsed()
	{
		$response = new Response();
		$this->assertFalse($response->isResponsed());
		$this->expectOutputRegex('/.*/'); // 在测试结果里处理掉输出。
		$response->response();
		$this->assertTrue($response->isResponsed());
	}

	public function testRedirect()
	{
		$response = new Response();
		$response->redirect('new', 301, 'page moved');
		$this->expectOutputString('HTTP/1.1 301 page moved|Location: new|');
		$response->response();
	}

	public function testNotFound()
	{
		$response = new Response();
		$response->notFound();
		$this->expectOutputString('HTTP/1.1 404 Not Found|');
		$response->response();
	}
}

