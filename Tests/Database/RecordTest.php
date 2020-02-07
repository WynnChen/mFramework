<?php
use \mFramework\Database\Connection;
use \mFramework\Utility\Paginator;

class Model_Blog extends \mFramework\Database\Record
{

	protected static $connection = 'blog_test';

	protected static $table = 'blog';

	protected static $fields = array('id' => self::DATATYPE_INT,'heading' => self::DATATYPE_STRING,'abstract' => self::DATATYPE_STRING,'body' => self::DATATYPE_STRING);

	protected static $auto_inc = 'id';

	protected static $pk = ['id'];

	protected static $ignore_on_write = ['abstract'];

	public $before_write = false;

	protected function beforeWrite()
	{
		parent::beforeWrite();
		$this->before_write = true;
	}
}

class Model_Blog_dummy extends \mFramework\Database\Record
{

	protected static $connection = array(
		// 'w' => 'blog_test_write', //故意不配置
		'r' => 'blog_test_read');

	protected static $table = 'blog';

	protected static $fields = array('id' => self::DATATYPE_STRING,'heading' => self::DATATYPE_FLOAT,'abstract' => self::DATATYPE_NULL,'body' => self::DATATYPE_BOOL);

	protected static $auto_inc = 'id';

	protected static $pk = ['id'];
}

class Model_Blog_nopk extends \mFramework\Database\Record
{

	protected static $connection = 'blog_test';

	protected static $table = 'blog';

	protected static $fields = array('id' => self::DATATYPE_INT,'heading' => self::DATATYPE_STRING,'abstract' => self::DATATYPE_STRING,'body' => self::DATATYPE_STRING);
}

class Model_Blog_onlypk extends \mFramework\Database\Record
{

	protected static $connection = 'blog_test';

	protected static $table = 'blog';

	protected static $fields = array('id' => self::DATATYPE_INT,'heading' => self::DATATYPE_STRING,'abstract' => self::DATATYPE_STRING,'body' => self::DATATYPE_STRING);

	protected static $auto_inc = 'id';

	protected static $pk = ['id','heading','abstract','body'];
}

class Model_Blog_allIgnore extends \mFramework\Database\Record
{

	protected static $connection = 'blog_test';

	protected static $table = 'blog';

	protected static $fields = array('id' => self::DATATYPE_INT,'heading' => self::DATATYPE_STRING,'abstract' => self::DATATYPE_STRING,'body' => self::DATATYPE_STRING);

	protected static $auto_inc = 'id';

	protected static $pk = ['id'];

	protected static $ignore_on_write = ['abstract','heading','body'];
}

class DatabaseRecordTest extends DatabaseTestCase
{

	protected $con;

	protected $config;

	protected $fixture;

	/**
	 *
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
	 */
	public function getDataSet()
	{
		return $this->fixture = $this->createMySQLXMLDataSet(__DIR__ . DIRECTORY_SEPARATOR . 'fixture.xml');
	}

	protected function setUp()
	{
		parent::setUp(); // 非常重要，会调用getDataSet()准备数据库。
		$this->config = array('type' => 'mysql','host' => $GLOBALS['DB_HOST'],'port' => $GLOBALS['DB_PORT'],'dbname' => $GLOBALS['DB_DBNAME'],'username' => $GLOBALS['DB_USER'],'password' => $GLOBALS['DB_PASSWD'],'charset' => 'utf8','options' => null);
		$this->con = Connection::create($this->config);
		Connection::set('blog_test', $this->con);
		Connection::set('blog_test_read', $this->con);
		Connection::set('blog_test_write', $this->con);
	}

	public function testCon()
	{
		// 测试下读写分离：
		$blog = Model_Blog_dummy::SelectByPk(1); // 读连接。
												 // 写连接没配置，要异常：
		$this->expectException('mFramework\Database\ConnectionException', 'No such connection mode config. [w]');
		$blog->update();
	}

	public function testTable()
	{
		$this->assertEquals('`blog`', Model_Blog::table(true));
		$this->assertEquals('blog', Model_Blog::table(false));
		$this->assertEquals(Model_Blog::table(true), Model_Blog::table()); // default
	}

	public function testGetPk()
	{
		$this->assertEquals(['`id`'], Model_Blog::getPk(true));
		$this->assertEquals(['id'], Model_Blog::getPk(false));
		$this->assertEquals(Model_Blog::getPk(true), Model_Blog::getPk()); // default
	}

	public function testSelectByPk()
	{
		$blog = Model_Blog::SelectByPk(1);
		$this->assertInstanceOf('Model_Blog', $blog);
		// 没有pk的要异常
		$this->expectException('mFramework\Database\QueryException', 'No PK info. Model_Blog_nopk');
		$blog = Model_Blog_nopk::SelectByPk(1);
	}

	public function testTypeFormat()
	{
		$blog = Model_Blog_dummy::SelectByPk(1);
		$this->assertTrue(is_string($blog->id));
		$this->assertTrue(is_float($blog->heading));
		$this->assertTrue(is_null($blog->abstract));
		$this->assertTrue(is_bool($blog->body));
	}

	public function testGetPkValues()
	{
		$blog = Model_Blog::SelectByPk(1);
		$this->assertSame(['id' => 1], $blog->getPkValues());
	}

	public function testGetValuesCopy()
	{
		$blog = Model_Blog::SelectByPk(1);
		$this->assertSame($blog->getArrayCopy(), $blog->getValuesArray()); // 没有额外污染
	}

	public function testSelectAll()
	{
		$blogs = Model_Blog::selectAll();
		$count = 0;
		foreach ($blogs as $blog) {
			$count++;
			$this->assertInstanceOf('Model_Blog', $blog);
			$this->assertGreaterThan(0, $blog->id);
		}
		$this->assertEquals($this->getConnection()
			->getRowCount('blog'), $count);
	}

	public function testSelectAll2()
	{
		$p = new Paginator(1);
		$blogs = Model_Blog::selectAll($p);
		$this->assertEquals($this->getConnection()
			->getRowCount('blog'), $p->getTotalItems());
	}

	public function testSelectAllWithSort()
	{
		$p = new Paginator(1);
		$blogs = Model_Blog::selectAll($p, ['id' => true]);
		$this->assertEquals(2, $blogs->firstRow()->id);
		
		$blogs = Model_Blog::selectAll($p, ['id' => 'DESC']);
		$this->assertEquals(2, $blogs->firstRow()->id);
		
		$blogs = Model_Blog::selectAll($p, ['id' => 'DesC']);
		$this->assertEquals(2, $blogs->firstRow()->id);
		
		$blogs = Model_Blog::selectAll(null, ['id' => 'DESC']);
		$this->assertEquals(2, $blogs->firstRow()->id);
		
		$this->expectException('mFramework\Database\QueryException', 'ORDER BY info invalid.');
		$blogs = Model_Blog::selectAll(null, ['id' => 'mysort']);
	}

	public function testSelectRandom()
	{
		$blogs = Model_Blog::selectRandom($this->getConnection()->getRowCount('blog'));
		$count = 0;
		foreach ($blogs as $blog) {
			$count++;
			$this->assertInstanceOf('Model_Blog', $blog);
			$this->assertGreaterThan(0, $blog->id);
		}
		$this->assertEquals($this->getConnection()
			->getRowCount('blog'), $count);
		
		$blogs = Model_Blog::selectRandom(1);
		$count = 0;
		foreach ($blogs as $blog) {
			$count++;
			$this->assertInstanceOf('Model_Blog', $blog);
			$this->assertGreaterThan(0, $blog->id);
		}
		$this->assertEquals(1, $count);
	}

	public function testCountAll()
	{
		$this->assertEquals($this->getConnection()
			->getRowCount('blog'), Model_Blog::countAll());
	}

	public function testInsert()
	{
		$blog = new Model_Blog();
		$blog->heading = 'new';
		$blog->abstract = 'newabstract';
		$blog->body = 'newbody';
		$result = $blog->insert();
		$this->assertEquals(1, $result); // 写入了一行
		$this->assertTrue($blog->before_write); // beforewrite读取过了
		$this->assertGreaterThan(0, $blog->id); // pk更新过来了
		$this->assertTrue(is_int($blog->id));
		
		$except = $this->createMySQLXMLDataSet(__DIR__ . DIRECTORY_SEPARATOR . 'fixtureAfterInsert.xml')->getTable('blog');
		
		$this->assertTablesEqual($except, $this->getConnection()
			->createQueryTable('blog', 'SELECT * FROM blog'));
		
		// 除了autoinc之外全部字段都标为忽略写入是会出问题的：
		$this->expectException('mFramework\Database\QueryException', 'Insert need at least one col.');
		$blog = new Model_Blog_allIgnore();
		$blog->heading = 'new blog';
		$blog->insert();
	}

	public function testDelete()
	{
		$count = $this->getConnection()->getRowCount('blog');
		$blog = Model_Blog::SelectByPk(1);
		$this->assertInstanceOf('Model_Blog', $blog);
		$result = $blog->delete();
		$this->assertEquals(1, $result); // del了一行
		
		$except = $this->createMySQLXMLDataSet(__DIR__ . DIRECTORY_SEPARATOR . 'fixtureAfterDelete.xml')->getTable('blog');
		
		$this->assertTablesEqual($except, $this->getConnection()
			->createQueryTable('blog', 'SELECT * FROM blog'));
		// 没有pk又没有autoinc的无法默认删除
		$this->expectException('mFramework\Database\QueryException', 'delete need a col for WHERE.');
		$blog = Model_Blog_nopk::selectAll()->firstRow();
		$blog->delete();
	}

	public function testUpdate()
	{
		$blog = Model_Blog::SelectByPk(1);
		$blog->heading = 'somenew';
		$result = $blog->update();
		$this->assertEquals(1, $result); // 改了一行
		$this->assertTrue($blog->before_write); // beforewrite读取过了
		
		$except = $this->createMySQLXMLDataSet(__DIR__ . DIRECTORY_SEPARATOR . 'fixtureAfterUpdate.xml')->getTable('blog');
		$this->assertTablesEqual($except, $this->getConnection()
			->createQueryTable('blog', 'SELECT * FROM blog'));
		
		// 没有pk又没有autoinc的无法自行更新
		$this->expectException('mFramework\Database\QueryException', 'Update need a col for WHERE.');
		$blog = Model_Blog_nopk::selectAll()->firstRow();
		$blog->update();
	}

	public function testUpdate2()
	{
		$blog = Model_Blog::SelectByPk(1);
		$blog->heading = 'somenew';
		$blog->asbstract = 'somenew';
		$result = $blog->update();
		$this->assertEquals(1, $result); // 改了一行
		$this->assertTrue($blog->before_write); // beforewrite读取过了
		
		$except = $this->createMySQLXMLDataSet(__DIR__ . DIRECTORY_SEPARATOR . 'fixtureAfterUpdate.xml')->getTable('blog');
		$this->assertTablesEqual($except, $this->getConnection()
			->createQueryTable('blog', 'SELECT * FROM blog'));
		
		// 除了pk没东西了的：
		$this->expectException('mFramework\Database\QueryException', 'Update need a col to update.');
		$blog = Model_Blog_onlypk::selectAll()->firstRow();
		$blog->update();
	}

	public function testUpdateWith()
	{
		$blog = Model_Blog::SelectByPk(1);
		$blog->heading = 'somenew';
		$blog->body = 'xxxxx';
		$result = $blog->update('heading');
		$this->assertEquals(1, $result); // 改了一行
		$this->assertTrue($blog->before_write); // beforewrite读取过了
		
		$except = $this->createMySQLXMLDataSet(__DIR__ . DIRECTORY_SEPARATOR . 'fixtureAfterUpdate.xml')->getTable('blog');
		$this->assertTablesEqual($except, $this->getConnection()
			->createQueryTable('blog', 'SELECT * FROM blog'));
	}

	public function testUpdateWith2()
	{
		$blog = Model_Blog::SelectByPk(1);
		$blog->heading = 'somenew';
		$blog->abstract = 'xxxxx';
		$result = $blog->update('heading', 'abstract');
		$this->assertEquals(1, $result); // 改了一行
		$this->assertTrue($blog->before_write); // beforewrite读取过了
		
		$except = $this->createMySQLXMLDataSet(__DIR__ . DIRECTORY_SEPARATOR . 'fixtureAfterUpdate.xml');
		$except = new \PHPUnit\DbUnit\DataSet\ReplacementDataSet($except);
		$except->addFullReplacement('abs', 'xxxxx');
		$this->assertTablesEqual($except->getTable('blog'), $this->getConnection()
			->createQueryTable('blog', 'SELECT * FROM blog'));
	}

	public function testUpdateWithout()
	{
		$blog = Model_Blog::SelectByPk(1);
		$blog->heading = 'somenew';
		$blog->body = 'xxxxx';
		$result = $blog->updateWithout('body');
		$this->assertEquals(1, $result); // 改了一行
		$this->assertTrue($blog->before_write); // beforewrite读取过了
		
		$except = $this->createMySQLXMLDataSet(__DIR__ . DIRECTORY_SEPARATOR . 'fixtureAfterUpdate.xml')->getTable('blog');
		$this->assertTablesEqual($except, $this->getConnection()
			->createQueryTable('blog', 'SELECT * FROM blog'));
	}
}
