<?php
use \mFramework\Database\Connection;
use \mFramework\Utility\Paginator;

class DatabaseConnectionTest extends DatabaseTestCase
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

	public function testMysqlConnection()
	{
		$this->assertInstanceOf('\mFramework\Database\Connection\Mysql', $this->con);
	}

	public function testEnclose()
	{
		$this->assertEquals('`table`', $this->con->enclose('table'));
	}

	public function testUnsupportTypeException()
	{
		$this->expectException('mFramework\Database\Connection\UnsupportTypeException');
		Connection::create(['type' => 'someNewDatabase']);
	}

	public function testPdoException()
	{
		$this->expectException('mFramework\Database\ConnectionException');
		$config = array('type' => 'mysql','host' => '127.0.0.1','port' => '3306','dbname' => 'test','username' => 'test','password' => 'test','charset' => 'inexistscharset','options' => null);
		Connection::create($config);
	}

	public function testConnectionAccess()
	{
		Connection::set('index_name', $this->con);
		$this->assertSame($this->con, Connection::get('index_name'));
		Connection::set('myname', $this->config);
		$this->assertInstanceOf('\mFramework\Database\Connection', Connection::get('myname'));
		//重复设置无效
		$this->assertFalse(\mFramework\Database\Connection::set('myname', $this->con));

	}

	public function testTypeMissException()
	{
		Connection::set('name', []);
		$this->expectException('mFramework\Database\Connection\TypeMissException');
		Connection::get('name');
	}

	public function testNameNotFoundException()
	{
		Connection::set('nameB', $this->config);
		$this->expectException('mFramework\Database\Connection\NameNotFoundException');
		Connection::get('nameA');
	}


}
