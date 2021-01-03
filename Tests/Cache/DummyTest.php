<?php
use mFramework\Cache\Dummy;

class DummyTest extends PHPUnit\Framework\TestCase
{
	protected $backend;
	
	protected function setUp():void
	{
		$this->backend = new Dummy();
	}
	protected function tearDown():void
	{
		$this->backend->clear();
	}
	
	public function testAccess()
	{
		$backend = $this->backend;
		
		$this->assertTrue($backend->set('key', 'value'));
		$this->assertNull($backend->get('key'));
		
	}
	
	public function testTTL()
	{
		$backend = $this->backend;
		
		$this->assertTrue($backend->set('key', 'value', 2));
		
		$this->assertNull($backend->get('key'));
	}
	
	public function testHas()
	{
		$backend = $this->backend;
		$backend->set('key', 'value');
		$this->assertFalse($backend->has('key'));
		$backend->set('a', 'b', 2);
		$this->assertFalse($backend->has('a'));
	}
		
	public function testDel()
	{
		$backend = $this->backend;
		$backend->set('key', 'value');
		$this->assertTrue($backend->del('key'));
		$this->assertFalse($backend->has('key'));
		$this->assertNull($backend->get('key'));
		
		$this->assertTrue($backend->del('foo'));
	}
	
	public function testClear()
	{
		$backend = $this->backend;
		$backend->set('key', 'value');
		$backend->set('a', 'value');
		$backend->set('b', 'value');
		
		$backend->clear();
		$this->assertFalse($backend->has('key'));
		$this->assertFalse($backend->has('a'));
		$this->assertFalse($backend->has('b'));
		
	}
	
	
}
