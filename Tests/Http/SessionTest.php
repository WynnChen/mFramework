<?php
use mFramework\Http\Session;

class SessionTest extends PHPUnit\Framework\TestCase
{

	protected function setUp()
	{
		// 因为命令行没有cookies。
		ini_set('session.use_cookies', 0);
		session_cache_limiter(false);
	}

	public function testNotStart()
	{
		$this->expectException('mFramework\\Http\\Session\\NotStartedException');
		Session::load('');
	}

	public function testNotStart2()
	{
		$this->expectException('mFramework\\Http\\Session\\NotStartedException');
		Session::save('a', 'value');
	}

	public function testNotStart3()
	{
		$this->expectException('mFramework\\Http\\Session\\NotStartedException');
		Session::delete('a');
	}

	public function testNotStart4()
	{
		$this->expectException('mFramework\\Http\\Session\\NotStartedException');
		Session::exists('a');
	}

	public function testNotStart5()
	{
		$this->expectException('mFramework\\Http\\Session\\NotStartedException');
		Session::getCrumb();
	}

	public function testNotStart6()
	{
		$this->expectException('mFramework\\Http\\Session\\NotStartedException');
		Session::resetCrumb();
	}

	public function testNotStart7()
	{
		$this->expectException('mFramework\\Http\\Session\\NotStartedException');
		Session::reset();
	}

	public function testNotStart8()
	{
		$this->expectException('mFramework\\Http\\Session\\NotStartedException');
		Session::getId();
	}

	public function testNotStart9()
	{
		$this->expectException('mFramework\\Http\\Session\\NotStartedException');
		Session::regenerateId();
	}

	public function testNotStart10()
	{
		$this->expectException('mFramework\\Http\\Session\\NotStartedException');
		Session::destroy();
	}

	public function testNotStart11()
	{
		$this->expectException('mFramework\\Http\\Session\\NotStartedException');
		Session::commit();
	}

	public function testSession()
	{
		Session::start();
		$this->assertFalse(Session::exists('key'));
		Session::save('key', 'value');
		$this->assertEquals('value', Session::load('key'));
		$this->assertTrue(Session::exists('key'));
		Session::delete('key');
		$this->assertFalse(Session::exists('key'));
		Session::save('key', 'abc');
		Session::reset();
		$this->assertFalse(Session::exists('key'));
		Session::destroy();
		$this->expectException('mFramework\\Http\\Session\\NotStartedException');
		$this->assertFalse(Session::exists('key'));
	}

	public function testCookieParams()
	{
		$this->expectException('mFramework\\Http\\Session\\NotUseCookiesException');
		Session::setCookieParams(100, '.jylt.me', '/ps', true, false);
	}

	public function testCookieParams1()
	{
		$this->expectException('mFramework\\Http\\Session\\NotUseCookiesException');
		Session::getCookieParams();
	}

	public function testCookieParams2()
	{
		ini_set('session.use_cookies', 1);
		session_cache_limiter(false);
		Session::setCookieParams(100, '.jylt.me', '/ps', true, false);
		$this->assertEquals(['lifetime' => 100,'path' => '/ps','domain' => '.jylt.me','secure' => true,'httponly' => false], Session::getCookieParams());
		ini_set('session.use_cookies', 0);
	}

	public function testCookieParams3()
	{
		Session::start();
		$this->expectException('mFramework\\Http\\Session\\HasBeenStartedException');
		Session::setCookieParams(100, '.jylt.me', '/ps', true, false);
	}

	public function testSessionId()
	{
		Session::destroy(); // 上一个的遗留
		Session::setId('someidstring');
		Session::start();
		$this->assertEquals('someidstring', Session::getId());
	}

	public function testRegenerateId()
	{
		Session::start();
		$crumb = Session::getCrumb();
		$this->assertEquals($crumb, Session::getCrumb());
		Session::regenerateId(); // 不影响数据
		$this->assertEquals($crumb, Session::getCrumb());
		Session::regenerateId(true); // 影响数据
		$this->assertNotEquals($crumb, Session::getCrumb());
	}

	public function testCrumb()
	{
		Session::start();
		$crumb = Session::getCrumb();
		$this->assertEquals($crumb, Session::getCrumb());
		Session::resetCrumb();
		$this->assertNotEquals($crumb, Session::getCrumb());
	}

	public function testCommit()
	{
		Session::start();
		Session::save('key', 'value');
		Session::commit();
		$this->assertFalse(Session::isStarted());
	}
}
