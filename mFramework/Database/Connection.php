<?php
/**
 * mFramework - a mini PHP framework
 * 
 * @package   mFramework
 * @version   v5
 * @copyright 2009-2016 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework\Database;

use \mFramework\Utility\Paginator;

/**
 * 指向具体单个连接的封装，以PDO为基础。
 *
 * 类同时负责管理持有所有数据库连接。
 *
 *
 * 这里提供的几个快捷查询方法都支持参数化查询，
 * 其内部实现中使用的方案都是$stmt->execute()，使用起来比较方便。
 *
 * 如果需要进行更细腻的绑定操作，例如参数绑定/LOB类型支持，
 * 可以用prepare()方法取得PDOStatement实例，随后即可进行各种进一步操作。
 * 类似于：
 * //$con是DatabaseConnection实例
 * $stmt = $con->prepare("INSERT INTO `t` (`color`, `name`) VALUES (:c, :n)");
 * $stmt->bindParam(':c', $color, PDO::PARAM_STR, 6);
 * $stmt->bindParam(':n', $name, PDO::PARAM_STR, 12);
 * $stmt->execute();
 *
 * 指定连接配置时，不同的数据库需要的配置内容不一定完全一样，参见自对应的connection类。
 *
 * @package mFramework
 * @author Wynn Chen
 */
abstract class Connection extends \PDO
{

	/**
	 * 持有所有数据库连接。
	 *
	 * @var array
	 */
	private static $connections = array();

	/**
	 * 按照名称取得对应的连接实例。
	 * 如果set时提供的是配置参数，这里会实例为实际的连接。
	 * 取不存在的连接会抛出异常。
	 *
	 * @param string $name			
	 * @return \mFramework\Database\Connection
	 */
	static public function get($name)
	{
		if (!isset(self::$connections[$name])) {
			throw new Connection\NameNotFoundException('No such connection named [[' . $name . ']]');
		}
		
		if (!self::$connections[$name] instanceof self) {
			if (empty(self::$connections[$name]['type'])) {
				throw new Connection\TypeMissException('lack database type in connection config named [[' . $name . ']]');
			}
			self::$connections[$name] = self::create(self::$connections[$name]);
		}
		
		return self::$connections[$name];
	}

	/**
	 * 设置连接。
	 * $connection参数为连接的实例，或对应的初始化参数。
	 * 每个名字只能设置一次，设置过后再次设置无效。
	 * 注意此时不验证内容是否有效，如果提供的是参数，实际初始化延迟到getConnection时。
	 *
	 * setConnection($name, $connection); //设置单个
	 *
	 * 一般直接传递配置参数即可。如果需要对连接进行额外初始化操作，
	 * 例如设置一些选项等，则可以另行初始化，操作完毕之后传递进来。
	 *
	 * @param string $name 名称
	 * @param Array|\mFramework\Database\Connection $connection
	 *			链接实例或配置参数
	 * @throws Database\ConnectionException 提供的如果是配置参数又初始化失败时抛出
	 * @return boolean 是否成功设置。重复设置同一个名称时返回false
	 */
	static public function set($name, $connection = null)
	{
		if (isset(self::$connections[$name])) {
			return false;
		} else {
			self::$connections[$name] = $connection;
			return true;
		}
	}

	/**
	 * 按照指定的配置内容生成对应的数据库连接。
	 *
	 * $config['type']必须。
	 *
	 * @param array $config			
	 * @throws ConnectionException
	 * @return \mFramework\Database\Connection
	 */
	static public function create($config)
	{
		try {
			switch ($config['type']) {
				case 'mysql':
					$dbh = new Connection\Mysql($config);
					break;
				case 'sqlite':
					$dbh = new Connection\Sqlite($config);
					break;
				
				default:
					throw new Connection\UnsupportTypeException('This type is not implemented. [[ ' . $config['type'] . ' ]]');
					break;
			}
		} catch (\PDOException $e) {
			throw new ConnectionException('Error on init connection.' . $e->getMessage(), null, $e);
		}
		//强制设置适用exception方式
		$dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		return $dbh;
	}

	/**
	 * 用和数据库具体类型对应的方式将表名/字段名等识别符包起来。
	 * 例如MySQL使用的是 `（重音符）。
	 *
	 * @param string $identifier			
	 * @return string 包好之后的结果
	 */
	abstract public function enclose($identifier);

	/**
	 * select，结果进入特定类的对象实例。
	 *
	 * 不使用 PDO::FETCH_PROPS_LATE 值会被直接强行设置为属性，绕过了ArrayAccess的内容。
	 * 而使用 PDO::FETCH_PROPS_LATE 就无法在__contruct()中进行读取后的格式化之类的后处理。
	 * 自行选择
	 *
	 * @param string $className
	 *			目标类名
	 * @param string $sql
	 *			SQL语句，应当为有返回数据集的，并使用绑定占位符。
	 * @param array $params
	 *			SQL语句对应的待绑定参数，具体格式与使用的绑定占位符格式有关
	 * @param Paginator $paginator
	 *			分页器，如果提供的话会生成对应的limit限制，只取分页器当前页所对应的条目
	 * @param array $ctorargs
	 *			目标类构造器参数，如果需要的话
	 * @param bool $fetch_props_late
	 *			是否使用PDO::FETCH_PROPS_LATE
	 * @throws \mFramework\Database\QueryException
	 * @return \mFramework\Database\ResultSet
	 */
	public function selectObjects($className, $sql, array $params = null, Paginator $paginator = null, array $ctorargs = null, $fetch_props_late = false)
	{
		try {
			
			if(!$params or is_int(array_key_first($params))){
				// ? 格式
				if ($paginator) {
					$sql .= ' LIMIT ?, ?';
					$params[] = [($paginator->getCurrentPage() - 1) * $paginator->getItemsPerPage(), self::PARAM_INT];
					$params[] = [$paginator->getItemsPerPage(), self::PARAM_INT];
				}
				$stmt = $this->prepare($sql);
				$i = 1;
				foreach($params as $param){
					if(is_iterable($param)){
						$stmt->bindValue($i, ...$param);
					}
					else{
						$stmt->bindValue($i, $param);
					}
					$i++;
				}
			}
			else{
				// :xx 格式
				if ($paginator) {
					$sql .= ' LIMIT :mfLimitStart, :mfLimitCount';
					$params[':mfLimitStart'] = [($paginator->getCurrentPage() - 1) * $paginator->getItemsPerPage(), self::PARAM_INT];
					$params[':mfLimitCount'] = [$paginator->getItemsPerPage(), self::PARAM_INT];
				}
				$stmt = $this->prepare($sql);
				foreach($params as $key=>$param){
					if(is_iterable($param)){
						$stmt->bindValue($key, ...$param);
					}
					else{
						$stmt->bindValue($key, $param);
					}
				}
			}
			$stmt->execute();
			$mode = self::FETCH_CLASS;
			$fetch_props_late and $mode |= self::FETCH_PROPS_LATE;
			$stmt->setFetchMode($mode, $className, $ctorargs);
			return new ResultSet($stmt);
		} catch (\PDOException $e) {
			throw new QueryException('查询数据库出错。', 1, $e);
		}
	}

	/**
	 * 执行select语句，返回结果为DatabaseResultSet, 其中每个元素为Map
	 *
	 * @param string $sql
	 *			SQL语句，应当为有返回数据集的，并使用绑定占位符。
	 * @param array $params
	 *			SQL语句对应的待绑定参数，具体格式与使用的绑定占位符格式有关
	 * @param Paginator $paginator
	 *			分页器，如果提供的话会生成对应的limit限制，只取分页器当前页所对应的条目
	 * @throws \mFramework\Database\QueryException
	 * @return \mFramework\Database\ResultSet
	 */
	public function select($sql, array $params = null, Paginator $paginator = null)
	{
		return $this->selectObjects('\mFramework\Map', $sql, $params, $paginator);
	}

	/**
	 * 只查单个值的快捷方法。
	 * 尝试返回第一行第一列的值，不进行任何额外处理，调用者自行处理各种约束。
	 * 用于快速获取诸如 select count(*) from table_name 这类查询的结果。
	 *
	 * @param string $sql			
	 * @param array $param			
	 * @throws QueryException
	 * @return string
	 */
	public function SelectSingleValue($sql, array $params = null)
	{
		try {
			$stmt = $this->prepare($sql);
			$stmt->execute($params);
			return $stmt->fetchColumn();
		} catch (\PDOException $e) {
			throw new QueryException('查询数据库出错。', 2, $e);
		}
	}

	/**
	 * 执行查询并返回影响的行数。
	 * 用于insert，update，delete等，select不适用。
	 *
	 * @param string $sql			
	 * @param array $params			
	 * @throws QueryException
	 * @return int|false
	 */
	public function execute($sql, array $params = null)
	{
		try {
			$stmt = $this->prepare($sql);
			$stmt->execute($params);
			return $stmt->rowCount();
		} catch (\PDOException $e) {
			throw new QueryException('更新数据库出错。', 3, $e);
		}
	}

	/**
	 * 简单封装事务处理，从而无需手写try/catch和beginTransaction/commit/rollback等。
	 * 有如下默认约定：
	 * 1. con使用w模式。
	 * 2. 执行的fn中有问题需要抛出\PDOException，通常推荐抛出\mFramework\Database\QueryException。这样会触发rollBack。
	 * 3. 执行成功时返回的是$fn的return值；rollBack之后返回的是false。因此$fn不推荐返回false以免混淆。
	 *
	 * @param unknown $fn
	 * @param unknown ...$args
	 * @throws TransactionException
	 */
	public function doTransaction($fn, ...$args)
	{
		try{
			$this->beginTransaction();
			$result = $fn(...$args);
			$this->commit();
			return $result;
		}catch(\PDOException $e){
			$this->rollBack();
			return false;
		}
	}
	
}
namespace mFramework\Database\Connection;

class UnsupportTypeException extends \mFramework\Database\ConnectionException
{}

class NameNotFoundException extends \mFramework\Database\ConnectionException
{}

class TypeMissException extends \mFramework\Database\ConnectionException
{}
