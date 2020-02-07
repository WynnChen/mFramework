<?php
use \mFramework\Database\Connection;
use \mFramework\Utility\Paginator;

class MyClassForDatabaseTest
{

	public $a;

	public $b;

	public function __construct($a, $b)
	{
		$this->a = $a;
		$this->b = $b;
		$this->id = 100;
	}
}

class DatabaseConnectionQueryTest extends DatabaseTestCase
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

	protected function setUp()
	{
		parent::setUp(); // 非常重要，会调用getDataSet()准备数据库。
		$this->config = array('type' => 'mysql','host' => $GLOBALS['DB_HOST'],'port' => $GLOBALS['DB_PORT'],'dbname' => $GLOBALS['DB_DBNAME'],'username' => $GLOBALS['DB_USER'],'password' => $GLOBALS['DB_PASSWD'],'charset' => 'utf8','options' => null);
		$this->con = Connection::create($this->config);
	}

	public function testSelectObject()
	{
		$this->assertEquals(2, $this->getConnection()
			->getRowCount('blog'));
		
		$result = $this->con->selectObjects('ArrayObject', 'select * from blog');
		$this->assertInstanceOf('mFramework\Database\ResultSet', $result);
		$this->assertInstanceOf('ArrayObject', $result->firstRow());
		$this->assertEquals('good', $result->firstRow()->heading);
		
		// 第四参数测试
		$result = $this->con->selectObjects('ArrayObject', 'select * from blog', null, new Paginator(1, 2));
		$this->assertEquals('bed', $result->firstRow()->heading);
		
		// 第三参数测试
		$result = $this->con->selectObjects('ArrayObject', 'select * from blog where heading = ?', array('bed'));
		$this->assertEquals('lala', $result->firstRow()->abstract);
		
		// 第五第六参数测试
		// 默认先填充属性construct
		$result = $this->con->selectObjects('MyClassForDatabaseTest', 'select * from blog', null, null, [8,9]);
		$this->assertEquals(100, $result->firstRow()->id);
		$this->assertEquals(8, $result->firstRow()->a);
		$this->assertEquals(9, $result->firstRow()->b);
		// 这样反过来
		$result = $this->con->selectObjects('MyClassForDatabaseTest', 'select * from blog', null, null, [10,11], true);
		$this->assertEquals(1, $result->firstRow()->id);
		$this->assertEquals(10, $result->firstRow()->a);
		$this->assertEquals(11, $result->firstRow()->b);
		
		$this->expectException('mFramework\Database\QueryException');
		$this->con->selectObjects('ArrayObject', 'select * from xxx');
	}

	public function testSelectFetchPropsLate()
	{
		$this->assertEquals(2, $this->getConnection()
			->getRowCount('blog'));
		
		$result = $this->con->selectObjects('ArrayObject', 'select * from blog');
		$this->assertInstanceOf('mFramework\Database\ResultSet', $result);
		$this->assertInstanceOf('ArrayObject', $result->firstRow());
		$this->assertEquals('good', $result->firstRow()->heading);
		
		$result = $this->con->selectObjects('ArrayObject', 'select * from blog', null, new Paginator(1, 2));
		$this->assertEquals('bed', $result->firstRow()->heading);
		
		$result = $this->con->selectObjects('ArrayObject', 'select * from blog where heading = ?', array('bed'));
		$this->assertEquals('lala', $result->firstRow()->abstract);
		
		$this->expectException('mFramework\Database\QueryException');
		$this->con->selectObjects('ArrayObject', 'select * from xxx');
	}

	public function testSelect()
	{
		$result = $this->con->select('select * from blog');
		$this->assertInstanceOf('mFramework\Database\ResultSet', $result);
		$this->assertInstanceOf('mFramework\Map', $result->firstRow());
		$this->assertEquals('good', $result->firstRow()->heading);
	}

	public function testSelectSingleValue()
	{
		$result = $this->con->SelectSingleValue('select count(*) from blog');
		$this->assertEquals(2, $result);
		$result = $this->con->SelectSingleValue('select count(*) from blog where id > ?', [1]);
		$this->assertEquals(1, $result);
		$this->expectException('mFramework\Database\QueryException');
		$result = $this->con->SelectSingleValue('select count(*) from blog where super > ?', [1]);
	}

	public function testExecute()
	{
		$sql = 'insert into blog (heading, abstract, body) values ("new heading", "new abstract", "new body information!!")';
		$this->con->execute($sql);
		$this->assertEquals(3, $this->getConnection()
			->getRowCount('blog'));
		$sql = 'insert into blog (heading, abstract, body) values (?,?,?)';
		$this->con->execute($sql, ["new heading","new abstract","new body information!!"]);
		$this->assertEquals(4, $this->getConnection()
			->getRowCount('blog'));
		
		$this->con->execute('delete from blog where id > ?', [2]);
		$this->assertEquals(2, $this->getConnection()
			->getRowCount('blog'));
		
		$this->expectException('mFramework\Database\QueryException');
		$this->con->execute('delete from blog where t > ?', [2]);
	}
}
