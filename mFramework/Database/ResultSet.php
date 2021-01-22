<?php
declare(strict_types=1);

namespace mFramework\Database;

use IteratorIterator;
use PDOStatement;

/**
 * 数据库查询结果集
 *
 * ResultSet的实际特性依赖于数据库和PDO的设置。
 * 默认情况下PDO用的是单向游标，因此只能遍历一轮。
 * 一般而言，只用foreach遍历，并且只遍历一次，是比较安全的方式。
 * 如果需要多次反复使用，可以取得array之后进行。
 *
 * 由于继承并扩展 PDOStatement 类的方案不能用于持久化的PDO连接实例，因此采用外包覆的方式。
 *
 */
class ResultSet extends IteratorIterator
{

	/**
	 * 结果集中是否有内容？
	 */
	protected bool $has;

	/**
	 * 结果集的第一行。
	 */
	protected mixed $first;

	/**
	 * 对应的PDOStatement
 	*/
	protected PDOStatement $stmt;

	/**
	 * 建立数据结果集迭代器
	 *
	 * @param PDOStatement $stmt
	 */
	public function __construct(PDOStatement $stmt)
	{
		parent::__construct($stmt);
		$this->stmt = $stmt;
		$this->rewind(); // 需要用这个重置一下。
		$this->has = $this->valid();
		$this->first = $this->current();
	}

	/**
	 * 本结果集中是否有内容？
	 *
	 * @return boolean
	 */
	public function hasRows():bool
	{
		return $this->has;
	}

	/**
	 * 返回第一行。没有为null
	 * 如果db模块整体使用，返回一般为 record
	 *
	 * @return Record|null
	 */
	public function firstRow(): Record|null
	{
		return $this->first;
	}

	/**
	 * 取得“剩余”结果的数组副本。
	 * 注意是获取所有“剩余”结果，因此要在进行任何迭代操作之前就调用本方法。
	 * 会增加开销，尤其是内存开销。
	 *
	 * 注意每次取得的数组都是临时计算的，调用方需要自行缓存结果。
	 *
	 * 如果不指定 $key_callback 参数，默认为顺序数组。
	 *
	 * 如果 $key_callback 参数为string，那么以对应的记录字段为key，注意如果此字段有重复值那么可能出现记录覆盖的问题，自行处理。
	 * 如果指定了callable，那么调用之。 格式为 function($row,$offset){}，返回值应当是string或int，作为key使用。
	 *
	 * $value_callback 用法与 $key_callback 类似，应用于数组的值。
	 * callback格式为 function($row, $offset, $key){}，$key参数为之前所生成的key值，如果未指定$key_callback，为null。
	 *
	 * @param callable|string|null $key_callback string|callable 如何确定数组的key
	 * @param callable|string|null $value_callback string|callable 如何确定数组的value
	 * @return array 结果数组
	 */
	public function getArray(callable|string|null $key_callback = null, callable|string|null $value_callback = null): array
	{
		// 不能直接用$this->stmt->fetchAll()，会丢掉第一行。
		$array = [];
		
		foreach ($this as $offset => $row) {
			if ($key_callback === null) {
				$key = null;
			} elseif (is_callable($key_callback)) {
				//不要 $key_callback($row, $offset)，call_user_func接受的格式更多。
				$key = call_user_func($key_callback, $row, $offset); 
			} else {
				$key = $row->$key_callback;
			}
			
			if ($value_callback === null) {
				$value = $row;
			} elseif (is_callable($value_callback)) {
				$value = call_user_func($value_callback, $row, $offset, $key);
			} else {
				$value = $row->$value_callback;
			}
			
			if ($key_callback === null) {
				$array[] = $value;
			} else {
				$array[$key] = $value;
			}
		}
		
		return $array;
	}
	

}	
