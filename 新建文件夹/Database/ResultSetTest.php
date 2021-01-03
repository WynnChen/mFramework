<?php
use \mFramework\Database\Connection;
use \mFramework\Utility\Paginator;

class DatabaseResultSetTest extends DatabaseTestCase
{

	protected $con;

	protected $config;

	/**
	 *
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
	 */
	public function getDataSet()
	{
		return $this->createMySQLXMLDataSet(__DIR__ . DIRECTORY_SEPARATOR . 'fixture.xml');
	}

	protected function setUp():void
	{
		parent::setUp(); // 非常重要，会调用getDataSet()准备数据库。
		$this->config = array('type' => 'mysql','host' => $GLOBALS['DB_HOST'],'port' => $GLOBALS['DB_PORT'],'dbname' => $GLOBALS['DB_DBNAME'],'username' => $GLOBALS['DB_USER'],'password' => $GLOBALS['DB_PASSWD'],'charset' => 'utf8','options' => null);
		$this->con = Connection::create($this->config);
	}

	public function testResultSet()
	{
		$result = $this->con->select('select * from blog');
		$this->assertTrue($result->hasRows());
		$result = $this->con->select('select * from blog where 1 > 100');
		$this->assertFalse($result->hasRows());
	}

	public function testIterator()
	{
		$result = $this->con->select('select * from blog');
		$count = 0;
		foreach ($result as $key => $row) {
			$count++;
			$this->assertTrue(is_string($row->heading));
		}
		$this->assertEquals($this->getConnection()
			->getRowCount('blog'), $count);
		// mysql只能迭代一次
		$count = 0;
		foreach ($result as $row) {
			$count++;
			$this->assertTrue(is_string($row->heading));
		}
		$this->assertNotEquals($this->getConnection()
			->getRowCount('blog'), $count);
	}

	public function testFullIterator()
	{
		$result = $this->con->select('select * from blog');
		$result = $result->getArray();
		$count = 0;
		foreach ($result as $key => $row) {
			$count++;
			$this->assertTrue(is_string($row->heading));
		}
		$this->assertEquals($this->getConnection()
			->getRowCount('blog'), $count);
	}
}
