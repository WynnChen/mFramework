<?php
declare(strict_types=1);

namespace mFramework\Database;

use mFramework\Utility\Paginator;
use PDO;
use PDOException;
use PDOStatement;

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
abstract class Connection extends PDO
{
	/**
	 * 持有所有数据库连接。
	 */
	private static array $connections = [];

	/**
	 * 按照名称取得对应的连接实例。
	 * 如果set时提供的是配置参数，这里会实例为实际的连接。
	 * 取不存在的连接会抛出异常。
	 *
	 * @param string $name
	 * @return Connection
	 * @throws ConnectionException
	 */
	static public function get(string $name):Connection
	{
		if (!isset(self::$connections[$name])) {
			throw new ConnectionException('No such connection named [[' . $name . ']]');
		}
		
		if (!self::$connections[$name] instanceof self) {
			if (empty(self::$connections[$name]['type'])) {
				throw new ConnectionException('lack of database type info in connection config named [[' . $name . ']]');
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
	 * set($name, $connection); //设置单个
	 *
	 * 一般直接传递配置参数即可。如果需要对连接进行额外初始化操作，
	 * 例如设置一些选项等，则可以另行初始化，操作完毕之后传递进来。
	 *
	 * @param string $name 名称
	 * @param array|Connection|null $connection 链接实例或配置参数
	 * @return boolean 是否成功设置。重复设置同一个名称时返回false
	 */
	static public function set(string $name, array|Connection|null $connection = null): bool
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
	 * @return Connection
	 * @throws ConnectionException
	 */
	static public function create(array $config):Connection
	{
		try {
			if(empty($config['type'])){
				throw new ConnectionException('No type specified in config.');
			}
			$class = '\\mFramework\\Database\\Connection\\'.$config['type'];
			if(!class_exists($class)){
				throw new ConnectionException('This type is not implemented. [[ ' . $config['type'] . ' ]]');
			}
			$dbh = new $class($config);
			if(!$dbh instanceof self){
				throw new ConnectionException('Class ' . $class . ' must inherit from Database\\Connection.');
			}
		} catch (PDOException $e) {
			throw new ConnectionException('Error on init connection.' . $e->getMessage(), 1, $e);
		}
		//强制设置适用 exception 方式
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbh;
	}

	/**
	 * 用和数据库具体类型对应的方式将表名/字段名等识别符包起来。
	 * 例如MySQL使用的是 `（重音符）。
	 *
	 * @param string $identifier
	 * @return string 包好之后的结果
	 */
	abstract public function enclose(string $identifier): string;

	static protected function bindParams(PDOStatement $stmt, array $params, ?bool $named = null): PDOStatement
	{
		if($named === null){
			$named = is_string(array_key_first($params)); //尝试自动判定使用 :name 还是 ?
		}
		$i = 1;
		foreach($params as $key => $param){
			if(!$named){
				$key = $i;
				$i++;
			}
			if(is_array($param)){
				$stmt->bindValue($key, ...$param);
			}
			else{
				$stmt->bindValue($key, $param);
			}
		}
		return $stmt;
	}

	/**
	 * 在这个connection上prepare sql语句，然后绑定参数，然后$stmt->execute()，之后返回这个$stmt.
	 *
	 * $params数组的值可以是 $value 或者 [$value, $type]。
	 *
	 * $named用于指定绑定使用 :name 还是 ? 模式，如果传递null那么就根据$params的第一个key的类型是string还是int自动判断。
	 *
	 * @param string $sql $sql
	 * @param array|null $params 参数信息
	 * @param bool|null $named named？
	 * @return PDOStatement
	 */
	public function stmtExecute(string $sql, ?array $params = null, ?bool $named = null): PDOStatement
	{
		$stmt = $this->prepare($sql);
		$params and self::bindParams($stmt, $params, $named);
		$stmt->execute();
		return $stmt;
	}

	/**
	 * select，结果进入特定类的对象实例。
	 *
	 * 不使用 PDO::FETCH_PROPS_LATE，因此在目标类中， __construct() 时各个字段已经是有内容的。
	 * 如果是本方法select得到， __construct()会收到 'fetch' => true 的参数，方便做区别处理。
	 *
	 * $params 的元素内容可以是 $value，或者 [$value, $type]
	 * 如果使用 "?" 占位符，$params 的 key 会被忽略，按照foreach的顺序进行参数绑定。
	 *
	 * @param string $className 目标类名，必须继承自 Record
	 * @param string $sql SQL语句，应当为有返回数据集的，并使用绑定占位符。
	 * @param array|null $params SQL语句对应的待绑定参数，具体格式与使用的绑定占位符格式有关
	 * @param int|array|Paginator|null $paginator 分页器，如果提供的话会生成对应的limit限制，只取分页器当前页所对应的条目
	 * @param array|null $construct_args 目标类的额外构造器参数。必然会有的一个参数是 ['fetch'=>true]
	 * @param bool|null $named 强行指定占位符是 :name 还是 ?
	 * @return ResultSet
	 */
	public function selectObjects(string $className, string $sql, ?array $params = null, null|int|array|Paginator $paginator = null, ?array $construct_args = null, ?bool $named = null): ResultSet
	{
		try {
			$params = $params ?? [];
			if($named === null){
				$named = is_string(array_key_first($params)); //使用 :name 还是 ?
			}
			if ($paginator) {
				$sql .= $named ? ' LIMIT :mfLimitStart, :mfLimitCount' : ' LIMIT ?, ?';
				if(is_int($paginator)){
					$limit = $paginator;
					$offset = 0;
				}elseif(is_array($paginator)){
					if(count($paginator) != 2){
						throw new QueryException('limit 信息数组数量不正确。');
					}
					list($limit, $offset) = $paginator;
				}else{
					$limit = $paginator->getItemsPerPage();
					$offset = ($paginator->getCurrentPage() - 1) * $paginator->getItemsPerPage();
				}

				if($named){
					$params[':mfLimitStart'] = [$offset, self::PARAM_INT];
					$params[':mfLimitCount'] = [$limit, self::PARAM_INT];
				}else{
					$params[] = [$offset, self::PARAM_INT];
					$params[] = [$limit, self::PARAM_INT];

				}
			}

			$construct_args['fetch'] = true;
			$stmt = $this->stmtExecute($sql, $params, $named);
			$stmt->setFetchMode(self::FETCH_CLASS, $className, $construct_args);
			return new ResultSet($stmt);
		} catch (PDOException $e) {
			throw new QueryException('查询数据库出错。', 1, $e);
		}
	}

	/**
	 * 执行select语句，返回结果为DatabaseResultSet, 其中每个元素为Map
	 *
	 * @param string $sql SQL语句，应当为有返回数据集的，并使用绑定占位符。
	 * @param array|null $params SQL语句对应的待绑定参数，具体格式与使用的绑定占位符格式有关
	 * @param int|array|Paginator|null $paginator 分页器，如果提供的话会生成对应的limit限制，只取分页器当前页所对应的条目
	 * @param bool|null $named
	 * @return ResultSet
	 */
	public function select(string $sql, ?array $params = null, null|int|array|Paginator $paginator = null, ?bool $named = null): ResultSet
	{
		return $this->selectObjects(Row::class , $sql, $params, $paginator, named:$named);
	}

	/**
	 * 只查单个值的快捷方法。
	 * 尝试返回第一行第一列的值，不进行任何额外处理，调用者自行处理各种约束。
	 * 用于快速获取诸如 select count(*) from table_name 这类查询的结果。
	 *
	 * @param string $sql
	 * @param array|null $params
	 * @param bool|null $named
	 * @return mixed 各种可能的存储值或者null
	 */
	public function selectSingleValue(string $sql, ?array $params = null, ?bool $named = null): mixed
	{
		try {
			$stmt = $this->stmtExecute($sql, $params, $named);
			return $stmt->fetchColumn();
		} catch (PDOException $e) {
			throw new QueryException('查询数据库出错。', 2, $e);
		}
	}

	/**
	 * 执行查询并返回影响的行数。
	 * 用于insert，update，delete等，select不适用。
	 *
	 * @param string $sql
	 * @param array|null $params
	 * @param bool|null $named
	 * @return int
	 */
	public function execute(string $sql, ?array $params = null, ?bool $named = null): int
	{
		try {
			$stmt = $this->stmtExecute($sql, $params, $named);
			return $stmt->rowCount();
		} catch (PDOException $e) {
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
	 * @param callable $fn
	 * @param mixed ...$args
	 * @return mixed $fn()的返回值，执行时失败返回false
	 * @noinspection PhpUnusedLocalVariableInspection
	 */
	public function doTransaction(callable $fn, mixed ...$args): mixed
	{
		try{
			$this->beginTransaction();
			$result = $fn(...$args);
			$this->commit();
			return $result;
		}catch(PDOException $e){
			$this->rollBack();
			return false;
		}
	}

}