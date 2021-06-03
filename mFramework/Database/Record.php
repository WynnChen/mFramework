<?php
declare(strict_types=1);

namespace mFramework\Database;

use ArrayAccess;
use mFramework\Database\Attribute\Field;
use mFramework\Database\Attribute\Table;
use mFramework\Utility\Paginator;
use PDO;
use ReflectionClass;
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
 *
 * @see Table
 * @see Field
 *
 */
abstract class Record implements ArrayAccess
{

	const DATATYPE_NULL = PDO::PARAM_NULL;
	const DATATYPE_STRING = PDO::PARAM_STR;
	const DATATYPE_BOOL = PDO::PARAM_BOOL;
	const DATATYPE_INT = PDO::PARAM_INT;
	const DATATYPE_FLOAT = PDO::PARAM_STR;

	/**
	 * 缓存记录所有子类表的表信息
	 * @var array TableInfo[]
	 */
	static private array $tableInfo = [];

	/**
	 * 如果是 fetch 而来的，那么这里记录着fetch得到的值，用于update时做diff判断
	 * @var array|null 快照
	 */
	private ?array $snap = null;

	/**
	 * 建立新的record 。
	 *
	 * 注意：在和PDO::FETCH_CLASS配合使用时不能使用 PDO::FETCH_PROPS_LATE，
	 * 也就是说如果是通过select得来的，在调用本方法之前实际上各个字段已经有内容了。
	 * 如果使用了 PDO::FETCH_PROPS_LATE 来进行就无法正确做额外处理了。
	 * @param bool $fetch 用于给PDO的stmt->fetch()来标记是否是通过查询得到的内容，不要手工设置。
	 * @throws Exception
	 */
	public function __construct(bool $fetch = false)
	{
		if ($fetch) {
			//查询得到的结果。记录快照，然后需要后处理。
			$this->snap = $this->getValuesArray();
			$this->afterRead();
		}
	}

	/**
	 * 取得字段值数组。
	 *
	 * @return array
	 * @throws Exception
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
		if (!$attributes) {
			//没有表信息，可能是继承的，找父类：
			$reflection = $reflection->getParentClass();
			if (!$reflection) {//没有父类了
				throw new Exception(static::class . ' 缺乏数据库属性配置信息。');
			}
			return $reflection->getName()::getTableInfo(); //用父类的信息
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
		foreach ($properties as $property) {
			if ($property->isStatic()) {
				continue; //静态属性不需要管
			}
			$attributes = $property->getAttributes(Field::class);
			if (!$attributes) {
				continue; //没有 "Field" attribute 的再见。
			}
			if (!$property->hasDefaultValue()) {
				throw new Exception('字段属性 "' . static::class . '->' . $property->getName() . '" 必须有默认值（可以是null）。');
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
			if ($field->isPk()) {
				$pk[] = $name;
			}
			if ($field->isAutoInc()) {
				$auto_inc = $name;
				$ignore_on_write[] = $name; //auto inc 的也就不能写入。
			}
			if ($field->isReadOnly()) {
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
			auto_inc: $auto_inc,
			fields: $fields,
			immutable: $table_obj->isImmutable(),
		);
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

	/**
	 * 直接传递一组约束来进行简单 select
	 * $constraint 是 $field => $value ，其中每个元素的值可以是：
	 * - null： 将使用 `field` IS NULL
	 * - 标量： 将使用 `field` = ?
	 * - 可遍历（例如数组）： 将使用 `field` IN (?, ?, ...)
	 *
	 *
	 * @param array $constraint
	 * @param int|array|Paginator|null $paginator
	 * @param array|null $order_by
	 * @param bool $or $constraint中的各个字段约束之间的关系 AND 还是 OR，默认 AND
	 * @return ResultSet|null
	 * @throws ConnectionException
	 * @throws Exception
	 */
	static public function selectBy(array $constraint, null|int|array|Paginator $paginator = null,
									?array $order_by = null, $or = false): ?ResultSet
	{
		if (!$constraint) {
			throw new QueryException('selectBy need constraint ' . get_called_class());
		}
		$field_types = self::getTableInfo()->getFieldsType();
		$where = [];
		$params = [];
		foreach ($constraint as $field => $value) {
			if (!isset($field_types[$field])) {
				throw new QueryException($field . ' for selectBy is invalid ' . get_called_class());
			}
			if ($value === null) {
				$where[] = static::field($field, true) . ' IS NULL';
			} elseif (is_iterable($value)) {
				$i = 0;
				foreach ($value as $v) {
					$params[] = [$v, $field_types[$field]];
					$i++;
				}
				$where[] = static::field($field, true) . ' IN (' . implode(', ', array_fill(0, $i, '?')) . ')';
			} else {
				$where[] = static::field($field, true) . ' = ?';
				$params[] = [$value, $field_types[$field]];
			}
		}

		$where = '(' . implode(') ' . ($or ? 'OR' : 'AND') . ' (', $where) . ')';
		$sql = static::ss() . ' WHERE ' . $where;

		$order_info = $order_by ?: self::getTableInfo()?->getDefaultOrderBy() ?: null;
		$order_info and $sql .= static::orderByStr($order_info);

		return static::select($sql, $params, $paginator);
	}

	/**
	 * 根据属性名得到相应的数据库字段名
	 * 这个方法只是用来格式化，不保证这个字段名字一定有效
	 * @param string $field 字段名。
	 * @param bool $full 是否要返回 table.field 这样的完整名称
	 * @param bool $enclose 返回的字段名是否要enclose
	 * @return string|null 结果
	 * @throws ConnectionException
	 * @throws Exception
	 */
	final static public function field(string $field, bool $full = false, bool $enclose = true): string|null
	{
		if ($enclose) {
			$field = self::e($field);
		}
		if ($full) {
			$field = self::table($enclose) . '.' . $field;
		}
		return $field;
	}

	/**
	 * 取得表名。
	 *
	 * @param bool $enclose 是否要执行enclose
	 * @return string|null 如果是 null 表示table info还没有设置过
	 * @throws ConnectionException
	 * @throws Exception
	 */
	final static public function table(bool $enclose = true): string|null
	{
		$table = self::getTableInfo()?->getTable();
		if ($table === null) {
			return null;
		}
		if ($table and $enclose) {
			$table = self::e($table);
		}
		return $table;
	}

	/**
	 * 返回 simple select 语句，方便使用.注意最后是没有空格的，后面加东西的时候需要自己补上。
	 * @return string
	 * @throws ConnectionException
	 * @throws Exception
	 */
	static protected function ss(): string
	{
		return 'SELECT * FROM ' . static::table();
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
	 * @throws Exception
	 */
	static protected function orderByStr(?array $info = null): string
	{
		$info = $info ?: self::getTableInfo()?->getDefaultOrderBy() ?: null;
		if (!$info) {
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
	 * 尝试根据sql来select对应的record，结果进入本类实例。
	 * 如果指定了分页器，自动取分页器当前页对应的条目。
	 *
	 * @param string $sql 查询用的SQL，应当是有返回结果集的。
	 * @param array|null $params sql中对应的占位符所需要绑定的参数
	 * @param int|array|Paginator|null $paginator 分页信息
	 * @return ResultSet
	 * @throws ConnectionException
	 * @throws Exception
	 */
	static public function select(string $sql,
								  ?array $params = null,
								  null|int|array|Paginator $paginator = null): ResultSet
	{
		return static::con('r')->selectObjects(static::class, $sql, $params, $paginator);
	}

	/**
	 * 取得全部条目。
	 * 如果提供了分页器，只取分页器当前页对应结果，
	 * 可以自动将分页器的所有条目数值设置为结果条数(有一个countAll()调用)
	 *
	 * @param int|array|Paginator|null $paginator 分页信息，如果是array需要 [$limit, $offset]
	 * @param array|null $order_info 排序信息数组，不提供就使用默认的。
	 * @param string|null $sql
	 * @return ResultSet
	 * @throws ConnectionException
	 * @throws Exception
	 */
	static public function selectAll(null|int|array|Paginator $paginator = null,
									 ?array $order_info = null,
									 ?string $sql = null): ResultSet
	{
		($paginator instanceof Paginator) and $paginator->setTotalItems(static::countAll());

		$sql = $sql ?: static::ss();
		$order_info = $order_info ?: self::getTableInfo()?->getDefaultOrderBy() ?: null;
		$order_info and $sql .= static::orderByStr($order_info);

		return static::select($sql, null, $paginator);
	}

	/**
	 * 取得总条数信息
	 *
	 * @return int
	 * @throws ConnectionException
	 * @throws Exception
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
	 * @throws ConnectionException
	 * @throws Exception
	 */
	static protected function selectSingleValue(string $sql, ?array $param = null)
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
	 * @return static|null
	 * @throws ConnectionException
	 * @throws Exception
	 */
	static public function selectByPk(mixed ...$values): ?static
	{
		if (!count($values)) {
			return null;
		}
		list($where, $params) = self::buildPkConstraint($values);
		$sql = static::ss() . ' WHERE ' . $where;
		return static::select($sql, $params)->firstRow();
	}

	/**
	 * @param array $values 对应于pk数组的 values，可能混合着 named 和 unnamed
	 * @return array [$where, $params]
	 * @throws ConnectionException
	 * @throws Exception
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
			$value = $values[$field] ?? $values[$index] ?? null;
			if ($value === null) {
				$where[] = static::field($field, true) . ' IS NULL';
			} else {
				$where[] = static::field($field, true) . ' = ?';
				$params[] = $value;
			}
		}

		$where = implode(' AND ', $where);

		return [$where, $params];
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
	 * @throws Exception
	 */
	static public function doTransaction(callable $fn, mixed ...$args): mixed
	{
		$con = static::con('w'); //事务通常有write需求，默认用w模式。
		return $con->doTransaction($fn, ...$args);
	}

	/**
	 * 删除。按照pk来进行。
	 *
	 * @param mixed ...$values
	 * @return int|false
	 * @throws ConnectionException
	 * @throws Exception
	 */
	static public function deleteByPk(mixed ...$values): int|false
	{
		if (self::getTableInfo()?->isImmutable()) {
			throw new QueryException('this table is immutable.');
		}
		if (!count($values)) {
			return 0;
		}
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
	 * @throws Exception
	 */
	static protected function execute(string $sql, ?array $param = null): int|false
	{
		return static::con('w')->execute($sql, $param);
	}

	/**
	 * 和 e()是一样的，优先用e
	 * @param string $identifier
	 * @return string
	 * @throws ConnectionException
	 * @throws Exception
	 */
	protected static function enclose(string $identifier): string
	{
		return static::e($identifier);
	}

	/**
	 * 按照本class对应的目标数据库的要求将标识符括起来。
	 * 使用读链接的配置。
	 *
	 * @param string $s
	 * @return string
	 * @throws ConnectionException
	 * @throws Exception
	 */
	protected static function e(string $s): string
	{
		return static::con('r')->enclose($s);
	}

	/**
	 * 按照给定的模式名称返回对应需要的连接。
	 * 函数的名字有意起的比较短，方便使用。
	 *
	 * @param string|null $mode
	 * @return Connection
	 * @throws ConnectionException
	 * @throws Exception
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
	 * 取得PK字段值
	 * 字段 => 值 数组。
	 *
	 * @return array
	 * @throws Exception
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
	 * insert新记录，会自动忽略 auto inc 和 read only 字段。
	 *
	 * @return boolean 是否有更新
	 * @throws ConnectionException
	 * @throws Exception
	 */
	public function insert(): bool
	{
		// 所有需要指定值的字段
		$f = $fields = self::getTableInfo()?->getWriteFields();
		if (!$fields) {
			throw new QueryException('Insert need at least one col.');
		}
		$this->beforeWrite();

		array_walk($f, fn(&$x) => $x = self::field($x));
		$sql = 'INSERT INTO ' . static::table() . ' (' . implode(', ', $f) . ') VALUES (' . implode(', ', array_fill(1, count($f), '?')) . ')';
		$stmt = static::con('w')->prepare($sql);
		$type = self::getTableInfo()?->getFieldsType();
		$i = 1;
		foreach ($fields as $field) {
			$stmt->bindValue($i, $this[$field], $type[$field]);
			$i++;
		}
		$result = $stmt->execute();
		$auto_inc = self::getTableInfo()?->getAutoInc();
		if ($result && $auto_inc) {
			$this->{$auto_inc} = self::typeCast(static::con('w')->lastInsertId(), $type[$auto_inc] ?? self::DATATYPE_STRING);
		}
		return (bool)$result;
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
	 * delete之。根据本对象内的 pk 或者 auto inc来决定标准。
	 *
	 * @return boolean 是否有删除
	 * @throws ConnectionException
	 * @throws Exception
	 */
	public function delete(): bool
	{
		if (self::getTableInfo()?->isImmutable()) {
			throw new QueryException('this table is immutable.');
		}
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
	 * 跳过某些特定字段更新本记录内容。 以PK作为where的根据。
	 * 返回值为数据库是否有“实际”更新，不等于成功与否。
	 * 除了指定排除字段，还会排除掉更新 pk 和 ignore on write 字段。
	 *
	 * @param string ...$fields 不更新那些字段？允许多个，null 为默认值。
	 * @return boolean 是否有更新
	 * @throws ConnectionException
	 * @throws Exception
	 */
	public function updateWithout(string ...$fields): bool
	{
		// 计算要更新哪些字段
		$update_fields = array_diff(self::getTableInfo()->getWriteFields(), $fields);
		return $this->update(...$update_fields);
	}

	/**
	 * 更新本记录内容。 以PK作为where的根据。
	 * 返回值为数据库是否有“实际”更新，不等于成功与否。
	 * 注意：如果有传入参数，则本方法实际更新的字段完全依据参数传递，不考虑 auto inc 和 readonly 属性。
	 *
	 * @param string ...$fields 要更新那些字段？默认为空，即自动计算。
	 * @return bool 结果
	 * @throws ConnectionException
	 * @throws Exception
	 */
	public function update(string ...$fields): bool
	{
		if (self::getTableInfo()?->isImmutable()) {
			throw new QueryException('this table is immutable.');
		}
		$info = self::getTableInfo();
		if (!$fields) { //自动计算需要更新的字段
			$fields = $info->getWriteFields();
			//计算diff，去掉不要的部分：
			foreach ($fields as $key => $field) {
				if ($this->snap[$field] === $this[$field]) {
					unset($fields[$key]);
				}
			}
		}
		if (!$fields) {
			return true; //没有字段需要更新，直接返回。但并不是错误。
		}

		// 计算where的依据
		$by_fields = $info->getPrimaryKey() ?? (array)$info->getAutoInc();
		if (!$by_fields) {
			throw new QueryException('Update need a col for WHERE.');
		}

		$this->beforeWrite();

		$set = array();
		$where = array();
		$params = [];
		foreach ($fields as $field) {
			$set[] = self::field($field) . ' = ?';
			$params[] = $field;
		}
		foreach ($by_fields as $field) {
			if ($this[$field] === null) {
				$where[] = self::field($field) . ' IS NULL';
			} else {
				$where[] = self::field($field) . ' = ?';
				$params[] = $field;
			}
		}
		$sql = 'UPDATE ' . static::table() . ' SET ' . implode(', ', $set) . ' WHERE ' . implode(' AND ', $where);
		$stmt = static::con('w')->prepare($sql);
		$type = self::getTableInfo()?->getFieldsType();
		$i = 1;
		foreach ($params as $field) {
			$stmt->bindValue($i, $this[$field], $type[$field]);
			$i++;
		}
		if ($stmt->execute()) {
			$this->snap = $this->getValuesArray(); //成功了要更新一下快照。
			return true;
		} else {
			return false;
		}
	}

	public function offsetExists($offset): bool
	{
		return property_exists($this, $offset);
	}

	public function offsetGet($offset): mixed
	{
		return $this->{$offset};
	}

	public function offsetUnset($offset)
	{
		unset($this->{$offset});
	}

	/**
	 * 一次性设置多个字段，会过滤并非有效字段的内容。
	 *
	 * @param iterable $values
	 * @return $this
	 * @throws Exception
	 */
	public function setValues(iterable $values, $include_readonly_fields = false): static
	{
		if ($include_readonly_fields) {
			$fields = self::getTableInfo()->getFields();
		} else {
			$fields = self::getTableInfo()->getWriteFields();
		}
		$fields = array_flip($fields);
		foreach ($values as $key => $value) {
			if (isset($fields[$key])) {
				$this->offsetSet($key, $value);
			}
		}
		return $this;
	}

	public function offsetSet($offset, $value)
	{
		$this->{$offset} = $value;
	}

}
