<?php
use mFramework\ClassLoader;

// 测试用，避免实际的include行为，只检查文件名对不对。
class ClassLoaderForTest extends ClassLoader
{

	public $last_file;

	protected function includeFile(string $file): bool
	{
		$this->last_file = $file;
		return true;
	}
	
	public function loadClass(string $class)
	{
		$this->last_file = null;
		parent::loadClass($class);
	}
}


// 测试prefix用的
class fooHandles
{
	public function handle($relative_class, $prefix)
	{}
}



/**
 * 实际测试。
 * @author wynn
 *
 */
class ClassLoaderTest extends PHPUnit\Framework\TestCase
{

	/**
	 *
	 * @var ClassLoader
	 */
	protected $loader;

	protected function setUp():void
	{
		$this->loader = new ClassLoaderForTest();
	}

	/**
	 * 单件测试
	 */
	public function testGetInstance()
	{
		$loader = ClassLoader::getInstance();
		$this->assertInstanceOf('mFramework\ClassLoader', $loader);
		
		$loader_2 = ClassLoader::getInstance();
		$this->assertSame($loader, $loader_2);
	}

	/**
	 * 注册相关测试
	 */
	public function testRegister()
	{
		$loader = $this->loader;
		$this->assertFalse($loader->isRegistered());
		$t = $loader->register();
		// 操作结果和状态信息应当一致
		$this->assertSame($loader->isRegistered(), $t);
		// 重复注册应当不会有问题
		$loader->register();
		$this->assertTrue($loader->isRegistered());
		
		// 取消注册
		$t = $loader->unregister();
		// 状态信息应当一致
		$this->assertNotSame($loader->isRegistered(), $t);
		// 重复取消注册也应当ok
		$loader->unregister();
		$this->assertFalse($loader->isRegistered());
	}


	/**
	 * 测试直接指定映射
	 */
	public function testClassFiles()
	{
		$loader = $this->loader;
		$map = array('class_a' => 'path\to\class.php','ns/class_b' => '../../.\..path');
		$append_map = array('class_a' => 'new\path\to\class.php');
		// 返回值应该是本体
		$result = $loader->addClassMap($map);
		$this->assertSame($loader, $result);
		// 应该正常设置了
		foreach ($map as $class => $file) {
			$loader->loadClass($class);
			$this->assertEquals($file, $loader->last_file);
		}
		// 没指定的还是不行
		$loader->loadClass('some_other_class');
		$this->assertNull($loader->last_file);
		// 再次指定：不改写
		$loader->addClassMap($append_map);
		$loader->loadClass('class_a');
		$this->assertEquals('path\to\class.php', $loader->last_file); // 不覆盖。
		
		$this->assertEquals($map, $loader->getClassMapping());
		
	}
   

	/**
	 * 检查目录映射规则
	 */
	public function testBaseDirHandleBaseDir()
	{
		$loader = $this->loader;
		$handle = ClassLoader::baseDirHandle('path/');
		$this->assertEquals('path' . DIRECTORY_SEPARATOR . 'someClass.php', $handle('someClass'));
		$this->assertEquals('path' . DIRECTORY_SEPARATOR . 'some' . DIRECTORY_SEPARATOR . 'class.php', $handle('some_class'));
		$this->assertEquals('path' . DIRECTORY_SEPARATOR . 'ns' . DIRECTORY_SEPARATOR . 'myClass.php', $handle('ns\myClass'));
		$this->assertEquals('path' . DIRECTORY_SEPARATOR . 'ns' . DIRECTORY_SEPARATOR . 'my' . DIRECTORY_SEPARATOR . 'class.php', $handle('ns\my_class'));
		$this->assertEquals('path' . DIRECTORY_SEPARATOR . 'ns' . DIRECTORY_SEPARATOR . '_class.php', $handle('ns\_class'));
		$this->assertEquals('path' . DIRECTORY_SEPARATOR . 'ns' . DIRECTORY_SEPARATOR . '__class.php', $handle('ns\__class'));
		$this->assertEquals('path' . DIRECTORY_SEPARATOR . '_class.php', $handle('_class'));
		$this->assertEquals('path' . DIRECTORY_SEPARATOR . '__class.php', $handle('__class'));
		$this->assertEquals('path' . DIRECTORY_SEPARATOR . 'some' . DIRECTORY_SEPARATOR . '_class.php', $handle('some__class'));
	}

	/**
	 * 自定义后缀
	 */
	public function testBaseDirHandlePostfix()
	{
		$loader = $this->loader;
		$handle = ClassLoader::baseDirHandle('path/', '.cls.php');
		$this->assertEquals('path' . DIRECTORY_SEPARATOR . 'some' . DIRECTORY_SEPARATOR . 'class.cls.php', $handle('some_class'));
		$this->assertEquals('path' . DIRECTORY_SEPARATOR . 'ns' . DIRECTORY_SEPARATOR . '_class.cls.php', $handle('ns\_class'));
	}

   
	/**
	 * 一次设置多个前缀
	 */
	public function testPrefixHandles()
	{
		$loader = $this->loader;
	
		$handles = $this->createMock('fooHandles');
	
		$map = array('' => array($handles,'handle'),'ns' => array($handles,'handle'));
	
		// 返回应该是本体
		$result = $loader->addNamespace($map);
		$this->assertSame($loader, $result);
		
		// 尝试重新覆盖
		$loader->addNamespace(['ns' => function () {}]);
		
	
		// 测试调用，顺便测试覆盖无效。
		$handles->expects($this->exactly(4))
		->method('handle')
		->will($this->returnArgument(0))
		->withConsecutive(array($this->equalTo('class_a'),$this->equalTo('')), array($this->equalTo('class_b'),$this->equalTo('')), array($this->equalTo('class_c'),$this->equalTo('ns')), array($this->equalTo('class_d'),$this->equalTo('ns')));
		// 测试不同ns下的调用
	
		$loader->loadClass('class_a');
		$loader->loadClass('class_b');
		$loader->loadClass('ns\class_c');
		$loader->loadClass('ns\class_d');
		
		$this->assertEquals($map, $loader->getNamespace());
	}	

	public function testPriority()
	{
		$loader = new ClassLoaderForTest();
		$handles = $this->createMock('fooHandles');
		
		$loader->addNamespace(['ns' => array($handles,'handle')]);
		
		$map = array('ns/class_b' => '../../.\..path');
		
		$loader->addClassMap($map);
		
		$loader->loadClass('ns/class_b');
		$this->assertEquals('../../.\..path', $loader->last_file);
	}
}
