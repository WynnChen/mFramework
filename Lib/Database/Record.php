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
 * 对数据库单行记录的封装。
 * 对象表示单个行，类表征数据库表。
 * 
 * @package mFramework
 * @author Wynn Chen
 */
abstract class Record extends \mFramework\Map
{

	/**
	 * #@+
	 * 各种字段数据类型定义
	 *
	 * @var int
	 */
	const DATATYPE_NULL = 0;
	// 实际上似乎用不上。
	const DATATYPE_STRING = 1;

	const DATATYPE_BOOL = 2;

	const DATATYPE_INT = 3;

	const DATATYPE_FLOAT = 4;

	/**
	 * #@-
	 */
	
	/**
	 * #@+
	 * 子类需要视情况设置此属性
	 */
	
	/**
	 * 支持读写分离，此时完整格式为:
	 * $connection = array(
	 * 'r' => 'con_r', //读使用的连接名
	 * 'w' => 'con_w', //写使用的连接名
	 * )
	 * 无需分离时可以直接指定字符串：
	 * $connection = 'con_name'
	 * 等价于
	 * $connection = array(
	 * 'r' => 'con_name',
	 * 'w' => 'con_name',
	 * )
	 *
	 * 如果读写分离同时在子类用到了static::con()（无参数），
	 * 应当再加一条 '' => 'con_name'
	 *
	 * 读连接用于retrieve，count等，
	 * 写连接用于insert,update,delete等。
	 *
	 * 如果需要更精细的控制，可以自行实现con()方法
	 *
	 * @var string|array
	 */
	protected static $connection = 'default';

	/**
	 * 表名
	 *
	 * @var string
	 */
	protected static $table = 'tablename';

	/**
	 * 数据表的字段信息。
	 * 字段名=>字段类型， 类型为self::TYPE_*系列。
	 * 以下所有涉及到字段信息的属性中都必须和本属性内的信息不冲突。
	 *
	 * @var array
	 */
	protected static $fields = ['id' => self::DATATYPE_INT];

	/**
	 * 各字段默认值信息
	 * 字段名=>默认值。只需要指定有的字段即可。
	 * 注意值和上面的类型定义不能冲突。
	 *
	 * @var array
	 */
	protected static $default = [];

	/**
	 * auto_inc字段名，如果有。
	 * 这个字段会在insert语句执行之后调用 lastInsertId()更新。
	 *
	 * @var string|null
	 */
	protected static $auto_inc = null;

	/**
	 * 主键字段集合
	 * 将作为update,delete等操作的默认WHERE信息.
	 * 注意：即使和auto_inc字段一致，依然需要声明。
	 *
	 * @var array
	 */
	protected static $pk = [];

	/**
	 * 所有需要在写入（update/insert）时忽略的字段，一般为数据库自动生成值的，例如timestamp类型。
	 * auto_inc字段不用在这里重复声明。
	 *
	 * @var array
	 */
	protected static $ignore_on_write = [];

	/**
	 * 默认排序信息，和orderByStr()的参数格式需求一致
	 *
	 * @var array
	 */
	protected static $default_order_info = [];

	/**
	 * #@-
	 */
	
	/**
	 * 按照给定的模式名称返回对应需要的连接。
	 * 函数的名字有意起的比较短，方便使用。
	 *
	 * @param string $mode			
	 * @return \mFramework\Database\Connection
	 */
	protected static function con(string $mode = null): Connection
	{
		if (is_string(static::$connection)) {
			return Connection::get(static::$connection);
		}
		// 到此 static::$connection 应该是个array
		if (isset(static::$connection[$mode])) {
			return Connection::get(static::$connection[$mode]);
		}
		// 本类内部出问题
		throw new ConnectionException('No such connection mode config. [' . $mode . ']');
	}

	/**
	 * 按照本class对应的目标数据库的要求将标识符括起来。
	 * 使用读链接的配置。
	 *
	 * @param string $identifier			
	 * @return string
	 */
	protected static function enclose(string $identifier): string
	{
		return static::con('r')->enclose($identifier);
	}

	/**
	 * 取得表名。
	 *
	 * @param bool $enclose
	 *			是否要执行enclose
	 * @return string
	 */
	public static function table(bool $enclose = true): string
	{
		return $enclose ? static::enclose(static::$table) : static::$table;
	}

	/**
	 * 取得PK字段信息
	 *
	 * @param bool $enclose
	 *			是否要执行enclose
	 * @return array
	 */
	public static function getPk(bool $enclose = true): array
	{
		return $enclose ? array_map('static::enclose', static::$pk) : static::$pk;
	}

	/**
	 * 取得PK字段值
	 * 字段 => 值 数组。
	 *
	 * @return array
	 */
	public function getPkValues(): array
	{
		$array = array();
		foreach (static::$pk as $field) {
			$array[$field] = $this[$field];
		}
		return $array;
	}

	/**
	 * 取得字段值数组。
	 * 如果不考虑非数据库字段的额外属性带来的污染问题，
	 * 可以直接用getArrayCopy()
	 *
	 * @return array
	 */
	public function getValuesArray(): array
	{
		$array = array();
		foreach (static::$fields as $field => $type) {
			$array[$field] = $this[$field];
		}
		return $array;
	}

	/**
	 * 将值按照给定类型进行转换。保留null
	 *
	 * @param mixed $value
	 *			需要转换的值
	 * @param mixed $type
	 *			值应当是 self::DATATYPE_* 系列。无相应定义的视为string
	 * @return mixed 转换好的值
	 */
	protected function typeCast($value, $type)
	{
		if ($value === null) {
			return null;
		}
		switch ($type) {
			case self::DATATYPE_BOOL:
				return (bool)$value;
				break;
			case self::DATATYPE_INT:
				return (int)$value;
				break;
			case self::DATATYPE_FLOAT:
				return (float)$value;
				break;
			case self::DATATYPE_NULL: // 这个基本是摆设
				return null;
				break;
			default:
				return (string)$value;
				break;
		}
	}

	/**
	 * 建立新的record。
	 *
	 * 注意：在和PDO::FETCH_CLASS配合使用时不能使用PDO::FETCH_PROPS_LATE，
	 * 也就是说如果是通过select得来的，在调用本方法之前实际上各个字段已经有内容了。
	 */
	public function __construct()
	{
		parent::__construct();
		/*
		 * reset()这个不可少，直接调用key不一定会出什么事。
		 * 由于这个数组在后面会被各种遍历，所以第二次跑到这里的时候指针实际上在最后再往后一个位置，
		 * key()会得到null。
		 */
		reset(static::$fields);
		if (property_exists($this, key(static::$fields))) {
			// 是从数据库select出来的结果
			// 模板方法
			$this->afterRead();
		} else {
			// 初始化所有字段的默认值
			foreach (static::$fields as $field => $type) {
				$this[$field] = static::$default[$field] ?? null;
			}
		}
	}

	/**
	 * 模板方法，从数据库读取后被调用。
	 * 实际调用时机是通过select来创建本对象时实例的 __construct() 内。
	 * 手工new的时候不会触发。
	 *
	 * 可以在这里进行一些清理和格式化工作。
	 */
	protected function afterRead()
	{
		// 格式化各个字段先
		foreach (static::$fields as $field => $type) {
			$value = static::typeCast($this->$field, $type);
			$this->offsetSet($field, $value);
			unset($this->$field);
		}
	}

	/**
	 * 模板方法，在insert()与update()中写数据库之前被调用。
	 *
	 * 可以在这里进行一些清理工作。
	 */
	protected function beforeWrite()
	{
		// 格式化各个字段先
		foreach (static::$fields as $field => $type) {
			$value = static::typeCast($this->offsetGet($field), $type);
			$this->offsetSet($field, $value);
		}
	}

	/**
	 * 以下为标准常用CRUD操作。涉及到sql的都不应该暴露给外部。 *
	 */
	
	/**
	 * 尝试根据sql来select对应的record，结果进入本类实例。
	 * 如果指定了分页器，自动取分页器当前页对应的条目。
	 *
	 * @param string $sql
	 *			查询用的SQL，应当是有返回结果集的。
	 * @param array $param
	 *			sql中对应的占位符所需要绑定的参数
	 * @param Paginator $paginator
	 *			分页器
	 * @return ResultSet
	 */
	static protected function select(string $sql, array $param = null, Paginator $paginator = null)
	{
		return static::con('r')->selectObjects(get_called_class(), $sql, $param, $paginator);
	}

	/**
	 * 执行无结果集的sql， insert,update之类。
	 * 返回影响的行数，如果查询失败返回false。
	 *
	 * @param string $sql			
	 * @param array $param			
	 * @return int
	 */
	static protected function execute(string $sql, array $param = null)
	{
		return static::con('w')->execute($sql, $param);
	}

	/**
	 * 取结果的第一行的第一列的结果。
	 * 诸如 select count(*) from `t` 这样的情况使用。
	 *
	 * @param string $sql
	 *			需要执行的SQL语句，如果需要绑定参数的用?
	 * @param array $param
	 *			参数，如果有。
	 * @return string
	 */
	static protected function selectSingleValue(string $sql, array $param = null)
	{
		return static::con('r')->selectSingleValue($sql, $param);
	}

	/**
	 * 取得全部条目。
	 * 如果提供了分页器，只取分页器当前页对应结果，
	 * 同时可以自动将分页器的所有条目数值设置为结果条数。
	 *
	 * @param Paginator $paginator
	 *			分页器
	 * @param array $order_info
	 *			排序信息数组。
	 * @return ResultSet
	 */
	static public function selectAll(Paginator $paginator = null, array $order_info = null): ResultSet
	{
		$paginator && $paginator->setTotalItems(static::countAll());
		
		$sql = 'SELECT * FROM ' . static::table();
		$order_info = $order_info ?: static::$default_order_info ?: null;
		$order_info and $sql .= static::orderByStr($order_info);
		
		return static::select($sql, null, $paginator);
	}

	/**
	 * 随机选择若干条结果。
	 * 注意这个方法的速度取决于表内总的条目数量，条目数量大的时候性能极差。
	 *
	 * @param int $limit
	 *			要几个？
	 * @return ResultSet|Array 注意数量可能比要求的少
	 */
	static public function selectRandom($limit)
	{
		$sql = 'SELECT * FROM ' . static::table() . ' ORDER BY rand() LIMIT ' . (int)$limit;
		return static::select($sql);
	}

	/**
	 * 取得总条数信息
	 *
	 * @return int
	 */
	static public function countAll(): int
	{
		$sql = 'SELECT count(*) FROM ' . static::table();
		return static::selectSingleValue($sql);
	}

	/**
	 * 按指定主键尝试取条目。有则直接返回对应条目，无返回null
	 *
	 * $value参数写法：
	 * 主键只有一个字段时可以直接写值，也可以用数组（格式如下文）。
	 * 主键不止一个字段时必须用数组：主键字段名 => 值
	 *
	 * 调用方自行保证参数信息正确。
	 *
	 * @param array|mixed $value
	 *			主键值。主键只有一个字段时可以直接写，否则用array。
	 * @throws QueryException
	 * @return Record|null
	 */
	static public function SelectByPk($value)
	{
		$pk = static::$pk;
		if (!$pk) {
			throw new QueryException('No PK info. ' . get_called_class());
		}
		if (count($pk) == 1) {
			reset($pk);
			$value = [current($pk) => $value];
		}
		$where = array();
		$params = array();
		foreach ($pk as $field) {
			if($value[$field] === null){
				$where[] = static::enclose($field) . ' IS NULL';
			}else{
				$where[] = static::enclose($field) . ' = ?';
				$params[] = $value[$field];
			}
		}
		$sql = 'SELECT * FROM ' . static::table() . ' WHERE ' . implode(' AND ', $where);
		return static::select($sql, $params)->firstRow();
	}
	
	static public function deleteByPk($value)
	{
		$pk = static::$pk;
		if (!$pk) {
			throw new QueryException('No PK info. ' . get_called_class());
		}
		if (count($pk) == 1) {
			reset($pk);
			$value = [current($pk) => $value];
		}
		$where = array();
		$params = array();
		foreach ($pk as $field) {
			if($value[$field] === null){
				$where[] = static::enclose($field) . ' IS NULL';
			}else{
				$where[] = static::enclose($field) . ' = ?';
				$params[] = $value[$field];
			}
		}
		$sql = 'DELETE FROM ' . static::table() . ' WHERE ' . implode(' AND ', $where);
		return static::execute($sql, $params);
		
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
	static public function doTransaction($fn, ...$args)
	{
		try{
			$con = static::con('w'); //事务通常有write需求，默认用w模式。
			$con->beginTransaction();
			$result = $fn(...$args);
			$con->commit();
			return $result;
		}catch(\PDOException $e){
			$con->rollBack();
			return false;
		}
	}

	/**
	 * 更新本记录内容。 以PK作为where的根据。
	 * 返回值为数据库是否有“实际”更新，不等于成功与否。
	 * 注意：如果指定的更新字段包含PK或ignore_on_write相关字段，要尤其小心，方法本身不会执行额外的检测。
	 *
	 * @param ...$fields 要更新那些字段？null则更新除PK外的全部，允许多个。			
	 * @return boolean 是否有更新
	 * @throws QueryException
	 */
	public function update(string ...$fields): bool
	{
		// 计算需要更新哪些字段
		$fields = $fields ?: array_diff(array_keys(static::$fields), static::$pk);
		if (!$fields) {
			throw new QueryException('Update need a col to update.');
		}
		
		// 计算where的依据
		$by_fields = (array)(static::$pk ?: static::$auto_inc ?: null);
		if (!$by_fields) {
			throw new QueryException('Update need a col for WHERE.');
		}
		
		$this->beforeWrite();
		
		$set = array();
		$where = array();
		$params = null;
		foreach ($fields as $field) {
			$set[] = static::enclose($field) . ' = ?';
			$params[] = $this[$field];
		}
		foreach ($by_fields as $field) {
			if($this[$field] === null){
				$where[] = static::enclose($field) . ' IS NULL';
			}
			else{
				$where[] = static::enclose($field) . ' <=> ?';
				$params[] = $this[$field];
			}
		}
		$sql = 'UPDATE ' . static::table() . ' SET ' . implode(', ', $set) . ' WHERE ' . implode(' AND ', $where);
		return static::execute($sql, $params);
	}

	/**
	 * 更新本记录内容。 以PK作为where的根据。
	 * 返回值为数据库是否有“实际”更新，不等于成功与否。
	 * 除了指定排除字段，还会排除掉更新 pk 和 ignore on write 字段。
	 *
	 * @param ...$fields 不更新那些字段？允许多个，null则更新全部。			
	 * @return boolean 是否有更新
	 * @throws QueryException
	 */
	public function updateWithout(string ...$fields): bool
	{
		// 计算要更新哪些字段
		$update_fields = array_diff(array_keys(static::$fields), static::$pk, $fields, static::$ignore_on_write);
		return $this->update(...$update_fields);
	}

	/**
	 * insert新记录，会自动忽略autoinc字段和 static::$ignore_on_write 指定字段。
	 *
	 * @return boolean 是否有更新
	 * @throws QueryException
	 */
	public function insert(): bool
	{
		// 计算所有需要指定值的字段
		$fields = array_diff(array_keys(static::$fields), [static::$auto_inc], static::$ignore_on_write);
		if (!$fields) {
			throw new QueryException('Insert need at least one col.');
		}
		
		$this->beforeWrite();
		
		$cols = [];
		$values = [];
		$params = null;
		foreach ($fields as $field) {
			$cols[] = static::enclose($field);
			$values[] = '?';
			$params[] = $this[$field];
		}
		$sql = 'INSERT INTO ' . static::table() . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $values) . ')';
		$result = static::execute($sql, $params);
		if ($result && static::$auto_inc) {
			$this->{static::$auto_inc} = static::typeCast(static::con('w')->lastInsertId(), static::$fields[static::$auto_inc]);
		}
		return $result;
	}

	/**
	 * delete之。依照pk来决定标准。
	 *
	 * @throws QueryException
	 * @return boolean 是否有删除
	 */
	public function delete(): bool
	{
		$fields = (array)(static::$pk ?: static::$auto_inc ?: null);
		if (!$fields) {
			throw new QueryException('delete need a col for WHERE.');
		}
		$where = [];
		$params = null;
		foreach ($fields as $field) {
			if($this[$field] === null){
				$where[] = static::enclose($field) . ' IS NULL';
			}
			else{
				$where[] = static::enclose($field) . ' <=> ?';
				$params[] = $this[$field];
			}
		}
		$sql = 'DELETE FROM ' . static::table() . ' WHERE ' . implode(' AND ', $where);
		return static::execute($sql, $params);
	}

	/**
	 * 从信息数组生成多字段排序条件。
	 * 数组内容格式为：
	 * '字段名' => 'DESC'|'ASC' //大小写均可
	 * 或：
	 * '字段名' => bool // 为 true表示'DESC',false为'ASC'
	 *
	 * 注意自行保证提供的内容正确，避免sql注入风险。
	 * 调用方保证$info有内容。
	 *
	 * @param array $info			
	 * @return string 拼装好的 order by 字符串，以" ORDER BY"开头（带空格）
	 */
	static protected function orderByStr(array $info): string
	{
		array_walk($info, function (&$order, $field) {
			if (is_bool($order)) {
				$order = $order ? 'DESC' : 'ASC';
			} else {
				$order = strtoupper($order);
				if ($order != 'DESC' and $order != 'ASC') {
					throw new QueryException('ORDER BY info invalid.');
				}
			}
			$order = static::enclose($field) . ' ' . $order;
		});
		return ' ORDER BY ' . implode(', ', $info);
	}
}
