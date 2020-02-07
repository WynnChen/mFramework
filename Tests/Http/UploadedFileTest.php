<?php
use mFramework\Http\UploadedFile;

class UploadedFileTest extends PHPUnit\Framework\TestCase
{

	public function testSingleFile()
	{
		$_FILES['single'] = array('name' => 'file.ext','type' => 'image/jpg','size' => 1048576,'tmp_name' => '/tmp/phpeZwESU','error' => 0);
		$uploaded_file = new UploadedFile('single');
		$this->assertFalse($uploaded_file->isMultiple());
		$this->assertFalse($uploaded_file->isEmpty());
		$this->assertFalse($uploaded_file->hasError());
		$this->assertEquals(1, count($uploaded_file));
		
		// 数组访问
		$this->assertEquals('file.ext', $uploaded_file['name']);
		$this->assertTrue(isset($uploaded_file['name']));
		// 下面正常应该用不到
		unset($uploaded_file['name']);
		$this->assertFalse(isset($uploaded_file['name']));
		$uploaded_file['name'] = 'abc.ext';
		$this->assertEquals('abc.ext', $uploaded_file['name']);
		
		$uploaded_file['name'] = 'file.ext';
		
		// 属性访问
		$this->assertEquals('file.ext', $uploaded_file->name);
		$this->assertTrue(isset($uploaded_file->name));
		// 下面正常应该用不到
		unset($uploaded_file->name);
		$this->assertFalse(isset($uploaded_file->name));
		$uploaded_file->name = 'abc.ext';
		$this->assertEquals('abc.ext', $uploaded_file->name);
	}

	public function testNonexistFile()
	{
		$uploaded_file = new UploadedFile('nonexist'); //不要重复
		$this->assertFalse($uploaded_file->isMultiple());
		$this->assertTrue($uploaded_file->isEmpty());
		$this->assertTrue($uploaded_file->hasError());
		$this->assertEquals(0, count($uploaded_file));
	}

	public function testEmptyFile()
	{
		$_FILES['single'] = array('name' => 'file.ext','type' => 'image/jpg','size' => 0,'tmp_name' => '/tmp/phpeZwESU','error' => 4); // NO FILE !
		
		$uploaded_file = new UploadedFile('single');
		$this->assertEquals('file.ext', $uploaded_file['name']);
		$this->assertFalse($uploaded_file->isMultiple());
		$this->assertTrue($uploaded_file->isEmpty());
		$this->assertTrue($uploaded_file->hasError());
		$this->assertEquals(1, count($uploaded_file));
	}

	public function testMultipleFiles()
	{
		$_FILES['multiple'] = array('name' => array('foo.txt','bar.js'),'type' => array('text/plain','text/json'),'tmp_name' => array('/tmp/phpYzdqkD','/tmp/phpeEwEWG'),'error' => array(0,2),'size' => array(123,456));
		
		$uploaded_file = new UploadedFile('multiple');
		
		// 多重判定
		$this->assertTrue($uploaded_file->isMultiple());
		
		// 当前文件是第一个的信息
		$this->assertEquals('foo.txt', $uploaded_file->name);
		
		// 迭代器相关接口：
		$this->assertEquals($uploaded_file, $uploaded_file->current());
		$this->assertEquals(0, $uploaded_file->key());
		$this->assertTrue($uploaded_file->valid());
		$this->assertEquals('foo.txt', $uploaded_file->name);
		$uploaded_file->next();
		$this->assertEquals(1, $uploaded_file->key());
		$this->assertTrue($uploaded_file->valid());
		$this->assertEquals('bar.js', $uploaded_file->name);
		$uploaded_file->next();
		$this->assertFalse($uploaded_file->valid());
		$uploaded_file->rewind();
		$this->assertEquals(0, $uploaded_file->key());
		$this->assertTrue($uploaded_file->valid());
		
		// seek
		$uploaded_file->seek(1);
		$this->assertEquals(1, $uploaded_file->key());
		$this->assertEquals('bar.js', $uploaded_file->name);
		$uploaded_file->seek(0);
		$this->assertEquals(0, $uploaded_file->key());
		$this->assertEquals('foo.txt', $uploaded_file->name);
		// countable
		$this->assertEquals(2, $uploaded_file->count());
	}

	public function testGetFileContents()
	{
		$_FILES['single'] = array('name' => 'file.ext','type' => 'image/jpg','size' => 1048576,'tmp_name' => '/tmp/phpeZwESU','error' => 0);
		$uploaded_file = new UploadedFile('single');
		$this->assertSame(false, $uploaded_file->getFileContents());
	}

	public function data()
	{
		$t = array(UPLOAD_ERR_INI_SIZE => '上传的文件尺寸超过系统允许上限。',UPLOAD_ERR_FORM_SIZE => '上传的文件尺寸超过表单允许上限。',UPLOAD_ERR_PARTIAL => '文件只有部分完成上传。',UPLOAD_ERR_NO_FILE => '没有上传文件。',UPLOAD_ERR_NO_TMP_DIR => '找不到临时目录。',UPLOAD_ERR_CANT_WRITE => '无法写入磁盘。',UPLOAD_ERR_EXTENSION => '某个功能模块阻止了文件上传。',UploadedFile::MOVE_ERR_MAKE_DIR => '无法创建目标目录。',UploadedFile::MOVE_ERR_MOVE_FILE => '无法写入目标文件。',UploadedFile::MOVE_ERR_NOT_UPLOADED_FILE => '上传文件信息有问题。'); // 可能是攻击。
		
		$array = [];
		foreach ($t as $code => $msg) {
			$array[] = [$code,$msg];
		}
		return $array;
	}

	/**
	 * @dataProvider data
	 */
	public function testGetErrorMsg($code, $msg)
	{
		$this->assertEquals($msg, UploadedFile::getErrorMsg($code));
	}

	public function testGetErrorMsg2()
	{
		$this->assertEquals('未知错误', UploadedFile::getErrorMsg(1294));
		$this->assertEquals('未知错误', UploadedFile::getErrorMsg(071));
		$this->assertEquals('未知错误', UploadedFile::getErrorMsg(144));
	}
}
