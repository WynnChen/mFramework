<?php
use mFramework\ClassLoader;

// 测试用，避免实际的include行为，只检查文件名对不对。
class ClassLoaderForTest extends ClassLoader
{
	public ?string $last_file;

	protected function includeFile(string $file): void
	{
		$this->last_file = $file;
	}

	public function loadClass(string $class): void
	{
		$this->last_file = null; //清除cache
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
 * @author Wynn Chen
 */
class ClassLoaderTest extends PHPUnit\Framework\TestCase
{
	protected ClassLoader $loader;

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
	 * 测试直接指定映射
	 */
	public function testClassMap()
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
		
		$this->assertEquals($map, $loader->getClassMap());
		
	}
   

	/**
	 * 检查目录映射规则
	 */
	public function testBaseDirHandleBaseDir()
	{
		$handle = ClassLoader::baseDirHandle('path/');
		$this->assertEquals('path' . DIRECTORY_SEPARATOR . 'someClass.php', $handle('someClass'));
		$this->assertEquals('path' . DIRECTORY_SEPARATOR . 'ns' . DIRECTORY_SEPARATOR . 'myClass.php', $handle('ns\myClass'));
		$this->assertEquals('path' . DIRECTORY_SEPARATOR . 'ns' . DIRECTORY_SEPARATOR . '_class.php', $handle('ns\_class'));
	}

	/**
	 * 自定义后缀
	 */
	public function testBaseDirHandlePostfix()
	{
		$handle = ClassLoader::baseDirHandle('path/', '.cls.php');
		$this->assertEquals('path' . DIRECTORY_SEPARATOR . 'some_class.cls.php', $handle('some_class'));
		$this->assertEquals('path' . DIRECTORY_SEPARATOR . 'ns' . DIRECTORY_SEPARATOR . 'class.cls.php', $handle('ns\class'));
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
		$result = $loader->addPrefixHandles($map);
		$this->assertSame($loader, $result);
		
		// 尝试重新覆盖
		$loader->addPrefixHandles(['ns' => function () {}]);
		
	
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
		
		$this->assertEquals($map, $loader->getPrefixHandles());
	}	

	public function testPriority()
	{
		$loader = new ClassLoaderForTest();
		$handles = $this->createMock('fooHandles');
		
		$loader->addPrefixHandles(['ns' => array($handles,'handle')]);
		
		$map = array('ns/class_b' => '../../.\..path');
		
		$loader->addClassMap($map);
		
		$loader->loadClass('ns/class_b');
		$this->assertEquals('../../.\..path', $loader->last_file);
	}
}
