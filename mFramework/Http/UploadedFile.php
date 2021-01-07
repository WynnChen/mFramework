<?php
/**
 * mFramework - a mini PHP framework
 * 
 * @package   mFramework
 * @version   v5
 * @copyright 2009-2016 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework\Http;

use \mFramework\Map;

/**
 * 代表经由 HTTP POST 方式上传的文件之相关封装
 *
 * 用POST字段名初始化：
 * $info = new UploadFile($key);
 *
 * 完成之后对象具备两个功能：
 * 1. 对象是一个迭代器，迭代当前关键字对应的上传文件信息（可能有多个文件）；
 * 2. 对象是ArrayAccess，可以直接存取当前单个上传文件的相关信息（name,tmp_name,type,size,error）。
 *
 * 具体有3种情况：
 * 1. 如果指定$key不存在，那么：
 * 迭代器是一个空迭代器：
 * count($info) === 0; //true
 * foreach($info as $file){
 * //循环体完全不会执行到
 * }
 * 当前信息是一个“无上传文件”信息：
 * $info->error === UPLOAD_ERR_NO_FILE; //true
 * //其他字段值均为null
 *
 * 2. 如果指定$key是单个文件，类似于 <input type="file" name="$key"/>，那么：
 * 迭代器只有一个元素：
 * count($info) === 1; //true
 * foreach($info as $file){
 * //只执行一次
 * }
 * 当前信息就是此上传文件信息：
 * echo $info->name; //上传的文件名
 *
 * 3. 如果指定$key是多重文件，类似于<input type="file" name="$key[]"/><input type="file" name="$key[]"/>，那么：
 * 迭代器包含所有文件：
 * count($info); //实际文件数量，注意有可能只有1。
 * foreach($info as $file){
 * //遍历所有多个上传文件
 * }
 * 当前信息就是当前迭代器指向的文件信息，一开始指向第一个：
 * echo $info->name; //指向迭代器的current位置文件的信息。
 * 例，打印所有上传文件名：
 * foreach($info as $file){
 * //随着迭代器遍历，$info指向的信息内容也在跟着变动
 * if($info->error == UPLOAD_ERR_OK){
 * echo $info->name;
 * }
 * }
 *
 * @property string $name
 * @property string $type
 * @property int $size
 * @property string $tmp_name
 * @property int $error
 * @package mFramework
 * @author Wynn Chen
 */
class UploadedFile implements \ArrayAccess, \Countable, \SeekableIterator
{

	const MOVE_ERR_OK = 0;

	const MOVE_ERR_MAKE_DIR = 31;

	const MOVE_ERR_MOVE_FILE = 32;

	const MOVE_ERR_NOT_UPLOADED_FILE = 33;

	/**
	 * 记录相关的$_FILES条目信息。
	 *
	 * @var array
	 */
	protected $info;

	/**
	 * 当前指针位置
	 *
	 * @var int
	 */
	protected $position = 0;

	/**
	 * 目前正在使用的这个文件信息
	 *
	 * @var Map
	 */
	protected $data;

	protected $dummy = array('name' => null,'type' => null,'size' => 0,'tmp_name' => null,'error' => UPLOAD_ERR_NO_FILE);

	public function __construct($key)
	{
		$this->data = new Map();
		
		// 不存在这个key
		if (!isset($_FILES[$key])) {
			$this->info = array();
			$this->data->exchangeArray($this->dummy);
			return;
		}
		
		$item = $_FILES[$key];
		if (is_array($_FILES[$key]['error'])) {
			// 多重文件，重整
			$array = &$this->info;
			foreach ($item['error'] as $k => $v) {
				$array[$k] = array('name' => $item['name'][$k],'type' => $item['type'][$k],'size' => $item['size'][$k],'tmp_name' => $item['tmp_name'][$k],'error' => $v);
			}
			$this->data->exchangeArray($array[0]);
		} else {
			// 正常单个文件
			$this->data->exchangeArray($item);
			$this->info = array($item);
		}
	}

	/**
	 * 是否有实际上传文件？（包括上传过程出错）
	 *
	 * @return boolean
	 */
	public function isEmpty()
	{
		return ($this->error === UPLOAD_ERR_NO_FILE);
	}

	/**
	 * 是多个文件？
	 *
	 * @return boolean
	 */
	public function isMultiple()
	{
		return $this->count() > 1;
	}

	/**
	 * 有出错么？
	 *
	 * @return boolean
	 */
	public function hasError()
	{
		return $this->error !== 0;
	}

	/**
	 * 支持属性式访问
	 *
	 * @param unknown $name			
	 * @return \mFramework\Map
	 */
	public function __get($name)
	{
		return $this->data[$name];
	}

	/**
	 * 正常应该用不到
	 *
	 * @param unknown $name			
	 * @param unknown $value			
	 */
	public function __set($name, $value)
	{
		$this->data[$name] = $value;
	}

	/**
	 * 支持属性式访问
	 *
	 * @param unknown $name			
	 */
	public function __isset($name)
	{
		return isset($this->data[$name]);
	}

	/**
	 * 正常应该用不到。
	 *
	 * @param unknown $name			
	 */
	public function __unset($name)
	{
		unset($this->data[$name]);
	}

	/**
	 * 直接映射到当前数据条目
	 *
	 * @see ArrayAccess::offsetExists()
	 */
	public function offsetExists($offset)
	{
		return $this->data->offsetExists($offset);
	}

	/**
	 * 直接映射到当前数据条目
	 *
	 * @see ArrayAccess::offsetGet()
	 */
	public function offsetGet($offset)
	{
		return $this->data->offsetGet($offset);
	}

	/**
	 * 直接映射到当前数据条目
	 * 正常应该用不到这个操作。
	 *
	 * @see ArrayAccess::offsetSet()
	 */
	public function offsetSet($offset, $value)
	{
		return $this->data->offsetSet($offset, $value);
	}

	/**
	 * 直接映射到当前数据条目
	 * 正常应该用不到这个操作。
	 *
	 * @see ArrayAccess::offsetUnset()
	 */
	public function offsetUnset($offset)
	{
		return $this->data->offsetUnset($offset);
	}

	public function valid()
	{
		return isset($this->info[$this->position]);
	}

	public function current()
	{
		return $this;
	}

	public function next()
	{
		$this->position += 1;
		$this->seek($this->position);
	}

	public function rewind()
	{
		$this->seek(0);
	}

	public function key()
	{
		return $this->position;
	}

	public function seek($position)
	{
		$this->position = $position;
		if (isset($this->info[$position])) {
			$this->data->exchangeArray($this->info[$position]);
		} else {
			$this->data->exchangeArray($this->dummy);
		}
	}

	/**
	 * 返回文件信息的个数。
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->info);
	}

	/**
	 * 取得上传文件的内容（字符串）
	 * 注意本方法自身不判定错误，需要自行预先判定是否有出错。
	 *
	 * @codeCoverageIgnore
	 *
	 * @return string|boolean 文件内容，出错时返回false
	 */
	public function getFileContents()
	{
		if (is_uploaded_file($this->tmp_name)) {
			return (@file_get_contents($this->tmp_name));
		} else {
			return false;
		}
	}

	/**
	 * 移动到目标位置去。注意如果中途失败可能会留下空目录。
	 *
	 * @codeCoverageIgnore
	 *
	 * @param string $dest
	 *			目标文件名，包括全路径
	 * @return int 错误码，self::MOVE_ERR_*系列
	 */
	public function moveFile($dest)
	{
		
		// 要弄出目录来先：
		@mkdir(dirname($dest), 0777, true);
		if (!move_uploaded_file($this->tmp_name, $dest)) {
			if (is_uploaded_file($this->tmp_name)) {
				return self::MOVE_ERR_MOVE_FILE;
			} else {
				return self::MOVE_ERR_NOT_UPLOADED_FILE;
			}
		}
		return self::MOVE_ERR_OK;
	}

	/**
	 * 上传时各种错误的对应信息
	 * 注意：UPLOAD_ERR_OK和self::MOVE_ERR_OK是没有错误信息的。
	 *
	 * @param int $error			
	 * @return string
	 */
	static public function getErrorMsg($error)
	{
		switch ($error) {
			case UPLOAD_ERR_INI_SIZE:
				return '上传的文件尺寸超过系统允许上限。';
				break;
			case UPLOAD_ERR_FORM_SIZE:
				return '上传的文件尺寸超过表单允许上限。';
				break;
			case UPLOAD_ERR_PARTIAL:
				return '文件只有部分完成上传。';
				break;
			case UPLOAD_ERR_NO_FILE:
				return '没有上传文件。';
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				return '找不到临时目录。';
				break;
			case UPLOAD_ERR_CANT_WRITE:
				return '无法写入磁盘。';
				break;
			case UPLOAD_ERR_EXTENSION:
				return '某个功能模块阻止了文件上传。';
				break;
			case self::MOVE_ERR_MAKE_DIR:
				return '无法创建目标目录。';
				break;
			case self::MOVE_ERR_MOVE_FILE:
				return '无法写入目标文件。';
				break;
			case self::MOVE_ERR_NOT_UPLOADED_FILE:
				return '上传文件信息有问题。'; // 可能是攻击。
				break;
			default:
				return '未知错误';
				break;
		}
	}
}