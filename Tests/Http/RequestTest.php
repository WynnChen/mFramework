<?php
use mFramework\Http\Request;

class RequestTest extends PHPUnit\Framework\TestCase
{

	public function testGetMethod()
	{
		$r = new Request();
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->assertEquals('GET', $r->getMethod());
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->assertEquals('POST', $r->getMethod());
	}
	
	// 只有GET和POST有简便的is方法。
	public function testIsGet()
	{
		$r = new Request();
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->assertTrue($r->isGet());
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->assertFalse($r->isGet());
	}

	public function testIsPost()
	{
		$r = new Request();
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->assertFalse($r->isPost());
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->assertTrue($r->isPost());
	}
	
	// 额外附加的参数
	public function testParameterAccess()
	{
		$input = array('abc' => 'efg','blank' => '');
		$r = new Request('/path', $input);
		
		$this->assertEquals('efg', $r->getParameter('abc')); // normal get
		
		$this->assertNull($r->getParameter('some')); // 不存在的字段
		$this->assertEquals('', $r->getParameter('blank')); // 不会对''进行额外处理
															
		// 带默认值
		$this->assertEquals('kk', $r->getParameter('some', 'kk'));
		$this->assertEquals('efg', $r->getParameter('abc', 'default'));
		
		// 整个：
		$this->assertEquals($input, $r->getAllParameters());
		
		// 写入
		$r->setParameter('some', 'string');
		$this->assertEquals('string', $r->getParameter('some'));
		
		// 简洁方案：
		$r->setParameter('', 'defaultstring');
		$this->assertEquals('defaultstring', $r->getParameter()); // 快捷
																  
		// 清除
		$r->clearParameters();
		$this->assertEquals(array(), $r->getAllParameters());
		
		// 一次多个：
		$r->setParameters($input);
		$this->assertEquals($input, $r->getAllParameters());
	}
	
	// 各种超全局变量的获取测试
	public function testGetQuery()
	{
		$_GET = array('abc' => 'efg','blank' => '');
		$r = new Request();
		
		$this->assertEquals('efg', $r->getQuery('abc')); // normal get
		
		$this->assertNull($r->getQuery('some')); // 不存在的字段
		$this->assertNull($r->getQuery('blank')); // '' 也视为没有（即对应字段没有填写）
												  
		// 带默认值
		$this->assertEquals('kk', $r->getQuery('some', 'kk'));
		$this->assertEquals('efg', $r->getQuery('abc', 'default'));
		
		// 整个：
		$info = $r->getQuery();
		$this->assertInstanceOf('mFramework\Map', $info);
		$this->assertEquals($_GET, $info->getArrayCopy());
	}

	public function testGetPost()
	{
		$_POST = array('abc' => 'efg','blank' => '');
		$r = new Request();
		
		$this->assertEquals('efg', $r->getPost('abc')); // normal get
		
		$this->assertNull($r->getPost('some')); // 不存在的字段
		$this->assertNull($r->getPost('blank')); // '' 也视为没有（即对应字段没有填写）
												 
		// 带默认值
		$this->assertEquals('kk', $r->getPost('some', 'kk'));
		$this->assertEquals('efg', $r->getPost('abc', 'default'));
		
		// 整个：
		$info = $r->getPost();
		$this->assertInstanceOf('mFramework\Map', $info);
		$this->assertEquals($_POST, $info->getArrayCopy());
	}

	public function testGetCookie()
	{
		$_COOKIE = array('abc' => 'efg','blank' => '');
		$r = new Request();
		
		$this->assertEquals('efg', $r->getCookie('abc')); // normal get
		
		$this->assertNull($r->getCookie('some')); // 不存在的字段
		$this->assertNull($r->getCookie('blank')); // '' 也视为没有（即对应字段没有填写）
												   
		// 带默认值
		$this->assertEquals('kk', $r->getCookie('some', 'kk'));
		$this->assertEquals('efg', $r->getCookie('abc', 'default'));
		
		// 整个：
		$info = $r->getCookie();
		$this->assertInstanceOf('mFramework\Map', $info);
		$this->assertEquals($_COOKIE, $info->getArrayCopy());
	}

	public function testGetServer()
	{
		$_SERVER = array('abc' => 'efg','blank' => '');
		$r = new Request();
		
		$this->assertEquals('efg', $r->getServer('abc')); // normal get
		
		$this->assertNull($r->getServer('some')); // 不存在的字段
		$this->assertNull($r->getServer('blank')); // '' 也视为没有（即对应字段没有填写）
												   
		// 带默认值
		$this->assertEquals('kk', $r->getServer('some', 'kk'));
		$this->assertEquals('efg', $r->getServer('abc', 'default'));
		
		// 整个：
		$info = $r->getServer();
		$this->assertInstanceOf('mFramework\Map', $info);
		$this->assertEquals($_SERVER, $info->getArrayCopy());
	}

	public function testGetEnv()
	{
		$_ENV = array('abc' => 'efg','blank' => '');
		$r = new Request();
		
		$this->assertEquals('efg', $r->getEnv('abc')); // normal get
		
		$this->assertNull($r->getEnv('some')); // 不存在的字段
		$this->assertNull($r->getEnv('blank')); // '' 也视为没有（即对应字段没有填写）
												
		// 带默认值
		$this->assertEquals('kk', $r->getEnv('some', 'kk'));
		$this->assertEquals('efg', $r->getEnv('abc', 'default'));
		
		// 整个：
		$info = $r->getEnv();
		$this->assertInstanceOf('mFramework\Map', $info);
		$this->assertEquals($_ENV, $info->getArrayCopy());
	}
	
	// $_FILES 比较特别
	public function testGetUploadedFile()
	{
		$_FILES = array('userfile' => array('name' => 'file.ext','type' => 'image/jpg','size' => 1048576,'tmp_name' => '/tmp/phpeZwESU','error' => 0),'multiple' => array('name' => array('foo.txt','bar.txt'),'type' => array('text/plain','text/plain'),'tmp_name' => array('/tmp/phpYzdqkD','/tmp/phpeEwEWG'),'error' => array(0,0),'size' => array(123,456)));
		$r = new Request();
		// 正常
		$this->assertInstanceOf('mFramework\Http\UploadedFile', $r->getUploadedFile('userfile'));
		// 不存在的索引
		$this->assertInstanceOf('mFramework\Http\UploadedFile', $r->getUploadedFile('nonexistsfile'));
		// 多重文件
		$this->assertInstanceOf('mFramework\Http\UploadedFile', $r->getUploadedFile('multiple'));
	}

	public function testGetIp()
	{
		$r = new Request();
		$this->assertNull($r->getIp());
		$_SERVER['REMOTE_ADDR'] = 'a';
		$this->assertEquals('a', $r->getIp());
		$_SERVER['CLIENT_IP'] = 'b';
		$this->assertEquals('b', $r->getIp());
		$_SERVER['X_FORWARDED_FOR'] = 'c';
		$this->assertEquals('c', $r->getIp());
	}

	public function testGetUri()
	{
		$r = new Request();
		$this->assertNull($r->getUri());
		$_SERVER['REQUEST_URI'] = 'a';
		$this->assertEquals('a', $r->getUri());
		$_SERVER['UNENCODED_URL'] = 'b';
		$this->assertEquals('b', $r->getUri());
		$_SERVER['HTTP_X_ORIGINAL_URL'] = 'c';
		$this->assertEquals('c', $r->getUri());
	}
}

