<?php
declare(strict_types=1);

namespace mFramework\Database;

use ArrayAccess;
use mFramework\Database\Attribute\Field;
use mFramework\Database\Attribute\Table;
use mFramework\Utility\Paginator;
use PDO;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

/**
 * 对数据库单行记录的封装。
 * 对象表示单个行，类表征数据库表。
 *
 * 使用 attribute 来标记表信息，大致示例如下：
 *
 * //类的 "Table" attribute 用来配置表相关的3个信息，都是可选的.
 * #[Table(connection:'con_name', name: 'table_name', orderBy: ['id' => 'DESC'])]
 * class Staff extends \mFramework\Database\Record
 * {
 *    //类属性的 "Field" attribute 用来配置字段信息的各个信息
 *
 *    // flags 指定一些属性
 *    #[Field( Field::IS_PK | Field::IS_AUTO_INC )]
 *    public int $id;
 *    // 也可以用named参数。 IS_READ_ONLY 表示在insert/update等操作时会忽略这一列。
 *    #[Field(flags: Field::IS_READ_ONLY)]
 *    public string $phone;
 *    // 字段名、类型、默认值、is_nullable 信息直接通过属性声明推定。
 *    #[Field]
 *    public string $email = 'default@example.com';
 *    ...
 * }
 *
 * 为了能够正常使用，必须在引入了类之后、实际开始使用之前用 TableInfo::register($class_name)
 * 来初始化本类的数据库配置信息。如果使用 mFramework 的classLoader 来做 autoload 那么这一段会自动进行。
 * 否则需要自行处理。
 *
 * @see Table
 * @see Field
 *
 */
abstract class Record implements ArrayAccess
{

	const DATATYPE_NULL = 0;
	const DATATYPE_STRING = 1;
	const DATATYPE_BOOL = 2;
	const DATATYPE_INT = 3;
	const DATATYPE_FLOAT = 4;

	const PARAM_BOOL = PDO::PARAM_BOOL;
	const PARAM_INT = PDO::PARAM_INT;
	const PARAM_FLOAT = PDO::PARAM_STR;
	const PARAM_NULL = PDO::PARAM_NULL;
	const PARAM_STR = PDO::PARAM_STR;
	const PARAM_STRING = PDO::PARAM_STR;
	const PARAM_LOB = PDO::PARAM_LOB;

	/**
	 * @var array TableInfo[]
	 */
	static private array $tableInfo = [];

	/**
	 * 本类的子类必须通过 attributes 来配置数据库表相关信息。
	 *
	 * @return TableInfo|null
	 * @throws Exception
	 */
	final static protected function setUp(): ?TableInfo
	{
		//class attributes 分析,表相关属性
		$reflection = new ReflectionClass(static::class);
		$attributes = $reflection->getAttributes(Table::class);
		if(!$attributes){
			//没有表信息，可能是继承的，找父类：
			$reflection = $reflection->getParentClass();
			if(!$reflection){//没有父类了
				throw new Exception(static::class.' 缺乏数据库属性配置信息。');
			}
			return  $reflection->getName()::getTableInfo(); //用父类的信息
		}
		/** @var Table $table_obj */
		$table_obj = $attributes[0]->newInstance(); //携带着表的几个属性

		// properties attributes 分析，字段属性
		$fields = [];
		$fields_type = [];
		$pk = [];
		$auto_inc = null;
		$ignore_on_write = [];
		$properties = $reflection->getProperties();
		foreach($properties as $property) {
			if ($property->isStatic()) {
				continue; //静态属性不需要管
			}
			$attributes = $property->getAttributes(Field::class);
			if (!$attributes) {
				continue; //没有 "Field" attribute 的再见。
			}
			if(!$property->hasDefaultValue()){
				throw new Exception('字段属性 "'.static::class.'->'.$property->getName().'" 必须有默认值（可以是null）。');
			}
			/** @var Field $field */
			$field = $attributes[0]->newInstance(); //携带flag信息
			//字段名
			$fields[] = $name = $property->getName(); //使用变量名
			//字段类型定义
			$type = $property->getType();
			if ($type instanceof ReflectionNamedType) {
				$type = match (strtolower($type->getName())) {
					'int' => Record::DATATYPE_INT,
					'float' => Record::DATATYPE_FLOAT,
					'bool' => Record::DATATYPE_BOOL,
					default => Record::DATATYPE_STRING, //其他统统按string处理。
				};
			} else { //没有类型信息或 union type也都按照string处理。
				$type = Record::DATATYPE_STRING;
			}
			$fields_type[$name] = $type; //写入字段定义数组。
			if($field->isPk()){
				$pk[] = $name;
			}
			if($field->isAutoInc()){
				$auto_inc = $name;
				$ignore_on_write[] = $name; //auto inc 的也就不能写入。
			}
			if($field->isReadOnly()){
				$ignore_on_write[] = $name;
			}
		}
		return new TableInfo(
			connection: $table_obj->getConnection(),
			table: $table_obj->getName(),
			fields_type: $fields_type,
			pk: $pk,
			ignore_on_write: $ignore_on_write,
			default_order_by: $table_obj->getOrderBy(),
			auto_inc:$auto_inc,
			fields: $fields,
		);
	}

	/**
	 * 得到本类对应的配置信息。
	 * 如果在 setUp()（或者是直接 TableInfo::register($class) ）之前调用是null
	 * @return TableInfo|null 如果没有的对应信息为null 。
	 * @throws Exception
	 */
	final static public function getTableInfo(): ?TableInfo
	{
		return self::$tableInfo[static::class] ?? self::$tableInfo[static::class] = static::setUp();
	}

	/**
	 * 取得表名。
	 *
	 * @param bool $enclose 是否要执行enclose
	 * @return string|null 如果是 null 表示table info还没有设置过
	 * @throws ConnectionException
	 */
	final static public function table(bool $enclose = true): string|null
	{
		$table = self::getTableInfo()?->getTable();
		if($table === null){
			return null;
		}
		if($table and $enclose){
			$table = self::enclose($table);
		}
		return $table;
	}

	/**
	 * 根据属性名得到相应的数据库字段名
	 * 这个方法只是用来格式化，不保证这个字段名字一定有效
	 * @param string $field 字段名。
	 * @param bool $full 是否要返回 table.field 这样的完整名称
	 * @param bool $enclose 返回的字段名是否要enclose
	 * @return string|null 结果
	 * @throws ConnectionException
	 */
	final static public function field(string $field, bool $full = false, bool $enclose = true): string|null
	{
		if($enclose){
			$field = self::enclose($field);
		}
		if($full){
			$field = self::table($enclose).'.'.$field;
		}
		return $field;
	}

	/**
	 * 将值按照给定类型进行转换。保留null
	 *
	 * @param mixed $value 需要转换的值
	 * @param int $type 值应当是 self::DATATYPE_* 系列。无相应定义的视为string
	 * @return bool|int|float|string|null 转换好的值
	 */
	protected static function typeCast(mixed $value, int $type): bool|int|float|string|null
	{
		if ($value === null) {
			return null;
		}
		return match ($type) {
			self::DATATYPE_BOOL => (bool)$value,
			self::DATATYPE_INT => (int)$value,
			self::DATATYPE_FLOAT => (float)$value,
			self::DATATYPE_NULL => null, //基本是摆设
			default => (string)$value,
		};
	}
	/**
	 * 按照本class对应的目标数据库的要求将标识符括起来。
	 * 使用读链接的配置。
	 *
	 * @param string $identifier
	 * @return string
	 * @throws ConnectionException
	 * @throws ConnectionException
	 * @throws ConnectionException
	 */
	protected static function enclose(string $identifier): string
	{
		return static::con('r')->enclose($identifier);
	}
	/**
	 * 按照给定的模式名称返回对应需要的连接。
	 * 函数的名字有意起的比较短，方便使用。
	 *
	 * @param string|null $mode
	 * @return Connection
	 * @throws ConnectionException
	 * @throws ConnectionException
	 * @throws ConnectionException
	 */
	protected static function con(string $mode = null): Connection
	{
		$connection = self::getTableInfo()?->getConnection();
		if (is_string($connection)) {
			return Connection::get($connection);
		}
		// 到此 $connection 应该是个array
		$mode = $mode ?: 'w';
		if (isset($connection[$mode])) {
			return Connection::get($connection);
		}
		// 本类内部出问题
		throw new ConnectionException('No such connection mode config. [' . $mode . ']');
	}

	/**
	 * 建立新的record 。
	 *
	 * 注意：在和PDO::FETCH_CLASS配合使用时不能使用 PDO::FETCH_PROPS_LATE，
	 * 也就是说如果是通过select得来的，在调用本方法之前实际上各个字段已经有内容了。
	 * 如果使用了 PDO::FETCH_PROPS_LATE 来进行就无法正确做额外处理了。
	 * @param bool $fetch 用于给PDO的stmt->fetch()来标记是否是通过查询得到的内容，不要手工设置。
	 */
	public function __construct(bool $fetch = false)
	{
		if($fetch){
			//查询得到的结果，需要后处理。
			$this->afterRead();
		}
	}

	/**
	 * 尝试根据sql来select对应的record，结果进入本类实例。
	 * 如果指定了分页器，自动取分页器当前页对应的条目。
	 *
	 * @param string $sql  查询用的SQL，应当是有返回结果集的。
	 * @param array|null $param  sql中对应的占位符所需要绑定的参数
	 * @param Paginator|null $paginator  分页器
	 * @return ResultSet
	 * @throws ConnectionException
	 */
	static public function select(string $sql,
									 ?array $param = null,
									 ?Paginator $paginator = null): ResultSet
	{
		return static::con('r')->selectObjects(static::class, $sql, $param, $paginator);
	}

	/**
	 * 取得全部条目。
	 * 如果提供了分页器，只取分页器当前页对应结果，
	 * 同时可以自动将分页器的所有条目数值设置为结果条数。
	 *
	 * @param Paginator|null $paginator 分页器
	 * @param array|null $order_info 排序信息数组，不提供就使用默认的。
	 * @param string|null $sql
	 * @return ResultSet
	 * @throws ConnectionException
	 */
	static public function selectAll(?Paginator $paginator = null,
									 ?array $order_info = null,
									 ?string $sql = null): ResultSet
	{
		$paginator && $paginator->setTotalItems(static::countAll());

		$sql = $sql ?: 'SELECT * FROM '.self::table();
		$order_info = $order_info ?: self::getTableInfo()?->getDefaultOrderBy() ?: null;
		$order_info and $sql .= static::orderByStr($order_info);

		return static::select($sql, null, $paginator);
	}

	/**
	 * 取得总条数信息
	 *
	 * @return int
	 * @throws ConnectionException
	 */
	static public function countAll(): int
	{
		$sql = 'SELECT count(1) FROM ' . static::table();
		return (int)static::selectSingleValue($sql);
	}

	/**
	 * 取结果的第一行的第一列的结果。
	 * 诸如 select count(*) from `t` 这样的情况使用。
	 *
	 * @param string $sql 需要执行的SQL语句，如果需要绑定参数的用?
	 * @param array|null $param 参数，如果有。
	 * @return string
	 * @throws ConnectionException
	 */
	static protected function selectSingleValue(string $sql, ?array $param = null):string
	{
		return static::con('r')->selectSingleValue($sql, $param);
	}

	/**
	 * 按指定主键尝试取条目。有则直接返回对应条目，无返回null
	 *
	 * 参数写法：直接按照 pk 字段的定义循序写，或者用按照 pk 字段的名称用 named 参数。
	 * 调用方自行保证参数信息正确。
	 *
	 * @param mixed ...$values 主键值，按照定义顺序。
	 * @return Record|null
	 * @throws ConnectionException
	 */
	static public function selectByPk(mixed ...$values): static|null
	{
		list($where, $params) = self::buildPkConstraint($values);
		$sql = 'SELECT * FROM ' . static::table() . ' WHERE ' . $where;
		return static::select($sql, $params)->firstRow();
	}

	/**
	 * 删除。按照pk来进行。
	 *
	 * @param mixed ...$values
	 * @return int|false
	 * @throws ConnectionException
	 */
	static public function deleteByPk(mixed ...$values): int|false
	{
		list($where, $params) = self::buildPkConstraint($values);
		$sql = 'DELETE FROM ' . static::table() . ' WHERE ' . $where;
		return static::execute($sql, $params);

	}

	/**
	 * 执行无结果集的sql， insert,update之类。
	 * 返回影响的行数，如果查询失败返回false 。
	 *
	 * @param string $sql
	 * @param array|null $param
	 * @return int|false
	 * @throws ConnectionException
	 */
	static protected function execute(string $sql, ?array $param = null):int|false
	{
		return static::con('w')->execute($sql, $param);
	}

	/**
	 * 简单封装事务处理，从而无需手写try/catch和beginTransaction/commit/rollback等。
	 * 有如下默认约定：
	 * 1. con使用w模式。
	 * 2. 执行的fn中有问题需要抛出\PDOException，通常推荐抛出\mFramework\Database\QueryException。这样会触发rollBack。
	 * 3. 执行成功时返回的是$fn的return值；rollBack之后返回的是false。因此$fn不推荐返回false以免混淆。
	 *
	 * @param callback $fn
	 * @param mixed ...$args
	 * @return mixed
	 * @throws ConnectionException
	 */
	static public function doTransaction(callable $fn, mixed ...$args): mixed
	{
		$con = static::con('w'); //事务通常有write需求，默认用w模式。
		return $con->doTransaction($fn, ...$args);
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
	 * @param array|null $info 字段名 => 正/逆 序。
	 * @return string 拼装好的 order by 字符串，以" ORDER BY"开头（带空格）。或者 ''（$info为空数组时）
	 * @throws ConnectionException
	 */
	static protected function orderByStr(?array $info): string
	{
		if(!$info){
			return '';
		}
		array_walk($info, function (&$order, $field) {
			if (is_bool($order)) {
				$order = $order ? 'DESC' : 'ASC';
			} else {
				$order = strtoupper($order);
				if ($order != 'DESC' and $order != 'ASC') {
					throw new QueryException('ORDER BY info invalid.');
				}
			}
			$order = self::field($field) . ' ' . $order; //$info 数组是字段名。
		});
		return ' ORDER BY ' . implode(', ', $info);
	}

	/**
	 * insert新记录，会自动忽略 auto inc 和 read only 字段。
	 *
	 * @return boolean 是否有更新
	 * @throws ConnectionException
	 */
	public function insert(): bool
	{
		// 所有需要指定值的字段
		$fields = self::getTableInfo()?->getWriteFields();
		if (!$fields) {
			throw new QueryException('Insert need at least one col.');
		}
		$this->beforeWrite();

		$cols = [];
		$values = [];
		$params = null;
		foreach ($fields as $field) {
			$cols[] = self::field($field);
			$values[] = '?';
			$params[] = $this[$field];
		}
		$sql = 'INSERT INTO ' . static::table() . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $values) . ')';
		$result = static::execute($sql, $params);
		$auto_inc = self::getTableInfo()?->getAutoInc();
		if ($result && $auto_inc) {
			$this->{$auto_inc} = self::typeCast(static::con('w')->lastInsertId(), self::getTableInfo()?->getFieldsType()[$auto_inc] ?? self::DATATYPE_STRING);
		}
		return (bool)$result;
	}

	/**
	 * delete之。根据本对象内的 pk 或者 auto inc来决定标准。
	 *
	 * @return boolean 是否有删除
	 * @throws ConnectionException
	 */
	public function delete(): bool
	{
		$fields = self::getTableInfo()?->getPk() ?? (array)self::getTableInfo()?->getAutoInc();
		if (!$fields) {
			throw new QueryException('delete need a col for WHERE.');
		}
		$where = [];
		$params = null;
		foreach ($fields as $field) {
			if ($this[$field] === null) {
				$where[] = self::field($field) . ' IS NULL';
			} else {
				$where[] = self::field($field) . ' = ?';
				$params[] = $this[$field];
			}
		}
		$sql = 'DELETE FROM ' . static::table() . ' WHERE ' . implode(' AND ', $where);
		return (bool)static::execute($sql, $params);
	}

	/**
	 * @param array $values 对应于pk数组的 values，可能混合着 named 和 unnamed
	 * @return array [$where, $params]
	 * @throws ConnectionException
	 */
	private static function buildPkConstraint(array $values): array
	{
		$pk = self::getTableInfo()->getPrimaryKey();
		if (!$pk) {
			throw new QueryException('No PK info. ' . get_called_class());
		}

		$where = [];
		$params = [];
		foreach ($pk as $index => $field) {
			$value  = $values[$field] ?? $values[$index] ?? null;
			if ($value === null) {
				$where[] = static::enclose($field) . ' IS NULL';
			} else {
				$where[] = static::enclose($field) . ' = ?';
				$params[] = $value;
			}
		}

		$where = implode(' AND ', $where);

		return [$where, $params];
	}

	/**
	 * 取得PK字段值
	 * 字段 => 值 数组。
	 *
	 * @return array
	 */
	public function getPkValues(): array
	{
		$array = [];
		foreach (self::getTableInfo()->getPrimaryKey() as $field) {
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
		foreach (self::getTableInfo()->getFields() as $field) {
			$array[$field] = $this[$field];
		}
		return $array;
	}

	/**
	 * 跳过某些特定字段更新本记录内容。 以PK作为where的根据。
	 * 返回值为数据库是否有“实际”更新，不等于成功与否。
	 * 除了指定排除字段，还会排除掉更新 pk 和 ignore on write 字段。
	 *
	 * @param string ...$fields 不更新那些字段？允许多个，null 为默认值。
	 * @return boolean 是否有更新
	 * @throws ConnectionException
	 */
	public function updateWithout(string ...$fields): bool
	{
		// 计算要更新哪些字段
		$update_fields = array_diff( self::getTableInfo()->getWriteFields(), $fields);
		return $this->update(...$update_fields);
	}

	/**
	 * 更新本记录内容。 以PK作为where的根据。
	 * 返回值为数据库是否有“实际”更新，不等于成功与否。
	 * 注意：如果有传入参数，则本方法实际更新的字段完全依据参数传递，不考虑 auto inc 和 readonly 属性。
	 *
	 * @param string ...$fields 要更新那些字段？默认为非。
	 * @return int|false 更新行数，或者结果false
	 * @throws ConnectionException
	 */
	public function update(string ...$fields): int|false
	{
		$info = self::getTableInfo();
		if(!$fields){
			$fields = $info->getWriteFields();
		}
		if (!$fields) {
			throw new QueryException('Update need a col to update.');
		}

		// 计算where的依据
		$by_fields = $info->getPrimaryKey() ?? (array)$info->getAutoInc();
		if (!$by_fields) {
			throw new QueryException('Update need a col for WHERE.');
		}

		$this->beforeWrite();

		$set = array();
		$where = array();
		$params = null;
		foreach ($fields as $field) {
			$set[] = self::field($field) . ' = ?';
			$params[] = $this[$field];
		}
		foreach ($by_fields as $field) {
			if ($this[$field] === null) {
				$where[] = self::field($field) . ' IS NULL';
			} else {
				$where[] = self::field($field) . ' = ?';
				$params[] = $this[$field];
			}
		}
		$sql = 'UPDATE ' . static::table() . ' SET ' . implode(', ', $set) . ' WHERE ' . implode(' AND ', $where);
		return static::execute($sql, $params);
	}

	/**
	 * 模板方法，在insert()与update()中写数据库之前被调用。
	 *
	 * 可以在这里进行一些清理工作
	 */
	protected function beforeWrite()
	{
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
	}

	public function offsetExists($offset): bool
	{
		return property_exists($this, $offset);
	}

	public function offsetGet($offset): mixed
	{
		return $this->{$offset};
	}

	public function offsetSet($offset, $value){
		$this->{$offset} = $value;
	}

	public function offsetUnset($offset){
		unset($this->{$offset});
	}
}
