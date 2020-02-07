<?php
use mFramework\Cache\Wincache;

/**
 * @requires extension wincache
 *
 */
class WincacheTest extends PHPUnit\Framework\TestCase
{
	/**
	 *
	 * @var Wincache
	 */
	protected $backend;
	
	protected $t;
	
	protected function setUp()
	{
		if (!ini_get('wincache.enablecli')) {
			$this->markTestSkipped('wincache.enablecli is OFF.');
		}
		
		$this->backend = new Wincache();
	}
	protected function tearDown()
	{
		$this->backend->clear();
	}
	
	public function testAccess()
	{
		$backend = $this->backend;
		
		$this->assertTrue($backend->set('key', 'value'));
		$this->assertEquals('value', $backend->get('key'));
		
	}
	
	public function testTTL()
	{
		$backend = $this->backend;
		
		$this->assertTrue($backend->set('key', 'value', 2));
		sleep(1);
		$this->assertEquals('value', $backend->get('key'));
		sleep(2);
		$this->assertNull($backend->get('key'));
	}
	
	public function testHas()
	{
		$backend = $this->backend;
		$backend->set('key', 'value');
		$this->assertTrue($backend->has('key'));
		$backend->set('a', 'b', 1);
		sleep(2);
		$this->assertFalse($backend->has('a'));
	}
		
	public function testDel()
	{
		$backend = $this->backend;
		$backend->set('key', 'value');
		$this->assertTrue($backend->has('key'));
		$this->assertTrue($backend->del('key'));
		$this->assertFalse($backend->has('key'));
		$this->assertFalse($backend->del('key'));
		$this->assertNull($backend->get('key'));
		
		$this->assertFalse($backend->del('foo'));
	}
	
	public function testClear()
	{
		$backend = $this->backend;
		$backend->set('key', 'value');
		$backend->set('a', 'value');
		$backend->set('b', 'value');
		
		$this->assertTrue($backend->has('key'));
		
		$backend->clear();
		$this->assertFalse($backend->has('key'));
		$this->assertFalse($backend->has('a'));
		$this->assertFalse($backend->has('b'));
		
	}
	
	
}
