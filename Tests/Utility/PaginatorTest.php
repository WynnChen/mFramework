<?php
use mFramework\Utility\Paginator;

class PaginatorTest extends PHPUnit\Framework\TestCase
{

	protected $paginator;

	protected function setUp()
	{
		$this->paginator = new Paginator(10, 2, 89);
	}

	public function testNew()
	{
		$p = $this->paginator;
		
		$this->assertEquals(10, $p->getItemsPerPage());
		$this->assertEquals(89, $p->getTotalItems());
		$this->assertEquals(2, $p->getCurrentPage());
		$this->assertEquals(9, $p->getTotalPages());
	}

	public function testChangeItemsPerPage()
	{
		$p = $this->paginator;
		
		$obj = $p->setItemsPerPage(5);
		$this->assertSame($p, $obj);
		$this->assertEquals(18, $p->getTotalPages());
		$p->setItemsPerPage(10);
		$this->assertEquals(9, $p->getTotalPages());
		$p->setItemsPerPage(-5);
		$this->assertEquals(1, $p->getItemsPerPage());
	}

	public function testChangeTotalItems()
	{
		$p = $this->paginator;
		
		$obj = $p->setTotalItems(54);
		$this->assertSame($p, $obj);
		$this->assertEquals(6, $p->getTotalPages());
		$p->setTotalItems(12);
		$this->assertEquals(2, $p->getTotalPages());
		// edge case
		$p->setTotalItems(0);
		$this->assertEquals(1, $p->getTotalPages());
		
		$p->setTotalItems(-4.5);
		$this->assertEquals(0, $p->getTotalItems());
	}

	public function testChangeItemsPerPageAndTotalItems()
	{
		$p = $this->paginator;
		
		$p->setTotalItems(54);
		$p->setItemsPerPage(12);
		$this->assertEquals(5, $p->getTotalPages());
	}

	public function testCurrentPage()
	{
		$p = $this->paginator;
		
		$obj = $p->setCurrentPage(3);
		$this->assertSame($p, $obj);
		$this->assertEquals(3, $p->getCurrentPage());
		
		$obj = $p->setCurrentPage(-100);
		$this->assertEquals(1, $p->getCurrentPage());
	}

	public function testValid()
	{
		$p = $this->paginator;
		$this->assertTrue($p->valid());
		$p->setCurrentPage(10);
		$this->assertEquals(10, $p->getCurrentPage());
		$this->assertFalse($p->valid());
		$p->setCurrentPage(4);
		$this->assertTrue($p->valid());
		$p->setTotalItems(20);
		$this->assertFalse($p->valid());
		$p->setItemsPerPage(3);
		$this->assertTrue($p->valid());
	}

	public function testPrevPageAndNextPage()
	{
		$p = $this->paginator;
		$this->assertTrue($p->hasPrevPage());
		$this->assertTrue($p->hasNextPage());
		
		$result = $p->nextPage();
		$this->assertEquals(3, $result);
		$this->assertEquals(3, $p->getCurrentPage());
		$result = $p->prevPage();
		$this->assertEquals(2, $result);
		$this->assertEquals(2, $p->getCurrentPage());
		
		// edge case
		$p->setCurrentPage(1);
		$this->assertFalse($p->hasPrevPage());
		$result = $p->prevPage();
		$this->assertFalse($result);
		$this->assertEquals(1, $p->getCurrentPage());
		// edge case
		$p->setCurrentPage($p->getTotalPages());
		$this->assertFalse($p->hasNextPage());
		$result = $p->nextPage();
		$this->assertFalse($result);
		$this->assertEquals($p->getTotalPages(), $p->getCurrentPage());
	}
}
