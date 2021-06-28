<?php
declare(strict_types=1);

namespace mFramework\Database;

use ArrayAccess;
use mFramework\Database\Attribute\Field;
use mFramework\Database\Attribute\Table;
use mFramework\Func;
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
 * 支持 array access，但仅限于public属性（即使在可以访问private/protected的类内部）
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
		if($fetch){
			//查询得到的结果。记录快照，然后需要后处理。
			$this->snap = $this->getValuesArray();
			$this->afterRead();
		}
	}

	/**
	 * 取得字段值数组。
	 *
	 * 注意这是根据数据库表的字段定义来取的，即标记了#[Field]的属性，即使protected/private也会取。
	 *
	 * @param string ...$fields 需要的字段，如果不写就全部。注意只有数据库表字段有效。
	 * @return array
	 * @throws Exception
	 */
	public function getValuesArray(string ...$fields): array
	{
		$f = self::getTableInfo()->getFields();
		if($fields){
			$f = array_intersect($f, $fields);
		}
		$array = array();
		foreach ($f as $field) {
			$array[$field] = $this->{$field};
		}
		return $array;
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
			$array[$field] = $this->{$field};
		}
		return $array;
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
	 * 模板方法，在insert()与update()中写数据库之前被调用。
	 *
	 * 可以在这里进行一些清理工作
	 */
	protected function beforeWrite()
	{
	}

	/**
	 * 得到本类对应的配置信息。
	 * 如果在 setUp()（或者是直接 TableInfo::register($class) ）之前调用是null
	 * @return TableInfo|null 如果没有的对应信息为null 。
	 * @throws Exception
	 */
	final static public function getTableInfo(): ?TableInfo
	{
		return TableInfo::get(static::class);
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
		if($enclose){
			$field = self::e($field);
		}
		if($full){
			$field = self::table($enclose).'.'.$field;
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
		if($table === null){
			return null;
		}
		if($table and $enclose){
			$table = self::e($table);
		}
		return $table;
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
	 * $constraint 是 $field => $value ，其中每个元素的值可以是：
	 * - null： 将使用 `field` IS NULL
	 * - 标量： 将使用 `field` = ?
	 * - 可遍历（例如数组）： 将使用 `field` IN (?, ?, ...)
	 *
	 * Note:
	 * 这里其实可以扩展$constraint的定义方式来支持更复杂的约束条件类型，
	 * 例如大于、小于甚至更复杂的运算甚至递归定义，并不很困难。
	 * 但对于比较复杂的约束条件与其设法定义一套DSL不如直接写SQL反而更加直接且高效，
	 * 因此这里的支持复杂度到此为止。
	 *
	 * @param array $constraint $field => $value_info
	 * @param bool $or 不同字段使用 AND 还是 OR 模式？默认为 AND
	 * @return array [$where, $params],其中 $params是 [$value, $type]
	 * @throws ConnectionException
	 * @throws Exception
	 */
	protected static function buildConstraint(array $constraint, bool $or=false): array
	{
		$field_types = self::getTableInfo()->getFieldsType();
		$where = [];
		$params = [];
		foreach ($constraint as $field => $value) {
			if (!isset($field_types[$field])) {
				throw new QueryException('Field "' . $field . '" is invalid ' . get_called_class());
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
		return array($where, $params);
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
		return self::buildConstraint(array_combine($pk, $values));
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
	 * 返回 simple select 语句，方便使用.注意最后是没有空格的，后面加东西的时候需要自己补上。
	 * @return string
	 * @throws ConnectionException
	 * @throws Exception
	 */
	static protected function ss() : string
	{
		return 'SELECT * FROM ' . static::table();
	}

	/**
	 * 尝试根据sql来select对应的record，结果进入本类实例。
	 * 如果指定了分页器，自动取分页器当前页对应的条目。
	 *
	 * @param string $sql 查询用的SQL，应当是有返回结果集的。
	 * @param array|null $params sql中对应的占位符所需要绑定的参数
	 * @param int|array|Paginator|null $paginator 分页信息
	 * @param bool|null $named
	 * @return ResultSet
	 * @throws ConnectionException
	 * @throws Exception
	 */
	static public function select(string $sql,
								  ?array $params = null,
								  null|int|array|Paginator $paginator = null,
								  ?bool $named = null): ResultSet
	{
		return static::con('r')->selectObjects(static::class, $sql, $params, $paginator,named:$named);
	}

	/**
	 * 直接传递一组约束来进行简单 select
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
		if(!$constraint){
			throw new QueryException('selectBy need constraint ' . get_called_class());
		}
		list($where, $params) = self::buildConstraint($constraint, $or);
		$sql = static::ss() . ' WHERE ' . $where;

		$order_info = $order_by ?: self::getTableInfo()?->getDefaultOrderBy() ?: null;
		$order_info and $sql .= static::orderByStr($order_info);

		return static::select($sql, $params, $paginator, named:false);
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

		return static::select($sql, paginator:$paginator, named:false);
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
		$sql = 'SELECT count(*) FROM ' . static::table();
		return (int)static::selectSingleValue($sql);
	}

	/**
	 * 取结果的第一行的第一列的结果。
	 * 诸如 select count(*) from `t` 这样的情况使用。
	 *
	 * @param string $sql 需要执行的SQL语句，如果需要绑定参数的用?
	 * @param array|null $param 参数，如果有。
	 * @param bool|null $named
	 * @return string|false
	 * @throws ConnectionException
	 * @throws Exception
	 */
	static protected function selectSingleValue(string $sql, ?array $param = null, ?bool $named = null):string|false
	{
		return static::con('r')->selectSingleValue($sql, $param, $named);
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
		if(!count($values)){
			return null;
		}
		list($where, $params) = self::buildPkConstraint($values);
		$sql = static::ss(). ' WHERE ' . $where;
		return static::select($sql, $params, named:false)->firstRow();
	}


	/**
	 * 执行无结果集的sql， insert,update之类。
	 * 返回影响的行数，如果查询失败会抛出exception 。
	 *
	 * @param string $sql
	 * @param array|null $param
	 * @param bool|null $named
	 * @return int
	 * @throws ConnectionException
	 * @throws Exception
	 */
	static protected function execute(string $sql, ?array $param = null, ?bool $named = null):int
	{
		return static::con('w')->execute($sql, $param, $named);
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
		return static::con('w')->doTransaction($fn, ...$args);
	}

	/**
	 * insert新记录，会自动忽略 auto inc 和 read only 字段。
	 *
	 * @return int 更新行数
	 * @throws ConnectionException
	 * @throws Exception
	 */
	public function insert(): int
	{
		// 所有需要指定值的字段
		$info = self::getTableInfo();
		$f = $fields = $info->getWriteFields();
		if (!$fields) {
			throw new QueryException('Insert need at least one col.');
		}
		$this->beforeWrite();

		array_walk($f, fn(&$x) => $x = self::field($x));
		$sql = 'INSERT INTO ' . static::table() . ' (' . implode(', ', $f) . ') VALUES (' . implode(', ', array_fill(1, count($f), '?')) . ')';

		//不指定类型会导致bool值出问题。（不指定则视为string，bool false 转换为 string 为 ""， 而mysql使用tinyint来模拟bool，""对于tinyint来说是个非法值）
		$type = $info->getFieldsType();
		array_walk($fields, fn(&$x) => $x = [$this->{$x}, $type[$x]]);
		$con = static::con('w');
		$result = $con->execute($sql, $fields, named:false);
		if($result){
			//先处理autoinc
			$auto_inc = self::getTableInfo()?->getAutoInc();
			if ($auto_inc) {
				$this->{$auto_inc} = self::typeCast($con->lastInsertId(), $type[$auto_inc] ?? self::DATATYPE_STRING);
			}
			//更新snap。这个要后做，才能把autoinc的内容同步过来。
			$this->snap = $this->getValuesArray();

		}
		return $result;
	}

	/**
	 * 删除。按照pk来进行。
	 *
	 * @param mixed ...$values
	 * @return int 删除的行数
	 * @throws ConnectionException
	 * @throws Exception
	 */
	static public function deleteByPk(mixed ...$values): int
	{
		if(self::getTableInfo()?->isImmutable()){
			throw new QueryException('this table is immutable.');
		}
		if(!count($values)){
			return 0;
		}

		list($where, $params) = self::buildPkConstraint($values);
		$sql = 'DELETE FROM ' . static::table() . ' WHERE ' . $where;
		return static::execute($sql,$params,named:false);
	}

	/**
	 * delete之。根据本对象内的 pk 或者 auto inc来决定标准。
	 *
	 * @return int|false 删除结果
	 * @throws ConnectionException
	 * @throws Exception
	 */
	public function delete(): int|false
	{
		$info =self::getTableInfo();
		if($info->isImmutable()){
			throw new QueryException('this table is immutable.');
		}
		$fields = $info->getPk() ?? (array)$info->getAutoInc();
		if (!$fields) {
			throw new QueryException('delete need a col for WHERE.');
		}
		$types = $info->getFieldsType();
		$where = [];
		$params = null;
		foreach ($fields as $field) {
			if ($this->{$field} === null) {
				$where[] = self::field($field) . ' IS NULL';
			} else {
				$where[] = self::field($field) . ' = ?';
				$params[] = [$this->{$field}, $types[$field]];
			}
		}
		$sql = 'DELETE FROM ' . static::table() . ' WHERE ' . implode(' AND ', $where);
		return self::execute($sql, $params, named:false);
	}



	/**
	 * 跳过某些特定字段更新本记录内容。 以PK作为where的根据。
	 * 返回值为数据库是否有“实际”更新，不等于成功与否。
	 * 除了指定排除字段，还会排除掉更新 pk 和 ignore on write 字段。
	 *
	 * @param string ...$fields 不更新那些字段？允许多个，null 为默认值。
	 * @return int 是否有更新
	 * @throws ConnectionException
	 * @throws Exception
	 */
	public function updateWithout(string ...$fields): int
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
	 * @param string ...$fields 要更新那些字段？默认为空，即自动计算。
	 * @return int 结果
	 * @throws ConnectionException
	 * @throws Exception
	 */
	public function update(string ...$fields): int
	{
		if(self::getTableInfo()?->isImmutable()){
			throw new QueryException('this table is immutable.');
		}
		$info = self::getTableInfo();
		if(!$fields){ //自动计算需要更新的字段
			$fields = $info->getWriteFields();
			if($this->snap){
				//计算diff，去掉内容相同的字段，节约开销
				foreach ($fields as $key => $field){
					if($this->snap[$field] === $this->{$field}){
						unset($fields[$key]);
					}
				}
			}
		}
		if (!$fields) {
			return 0; //不需要更新，直接返回0。
		}

		// 计算where的依据
		$by_fields = $info->getPrimaryKey() ?? (array)$info->getAutoInc();
		if (!$by_fields) {
			throw new QueryException('Update need a col for WHERE.');
		}

		$this->beforeWrite();

		$type = self::getTableInfo()?->getFieldsType();
		$set = array();
		$where = array();
		$params = [];
		foreach ($fields as $field) {
			$set[] = self::field($field) . ' = ?';
			$params[] = [$this->{$field}, $type[$field]];
		}
		foreach ($by_fields as $field) {
			if ($this->{$field} === null) {
				$where[] = self::field($field) . ' IS NULL';
			} else {
				$where[] = self::field($field) . ' = ?';
				$params[] = [$this->{$field}, $type[$field]];
			}
		}
		$sql = 'UPDATE ' . static::table() . ' SET ' . implode(', ', $set) . ' WHERE ' . implode(' AND ', $where);
		if($result = self::execute($sql, $params, named:false)){
			$this->snap = $this->getValuesArray(); //成功了要更新一下快照。
		}
		return $result;
	}

	////这样数组式访问只能处理public属性。

	public function offsetExists($offset): bool
	{
		return Func::propertyExists($this, $offset);
	}

	public function offsetGet($offset): mixed
	{
		return Func::propertyGet($this, $offset);
	}

	public function offsetUnset($offset){
		Func::propertyUnset($this, $offset);
	}

	public function offsetSet($offset, $value){
		Func::propertySet($this, $offset, $value);
	}

	/**
	 * 一次性设置多个字段，会过滤并非有效字段的内容。
	 *
	 * @param iterable $values
	 * @param bool $include_readonly_fields
	 * @return $this
	 * @throws Exception
	 */
	public function setValues(iterable $values, $include_readonly_fields = false) : static
	{
		if($include_readonly_fields){
			$fields = self::getTableInfo()->getFields();
		}
		else{
			$fields = self::getTableInfo()->getWriteFields();
		}
		$fields = array_flip($fields);
		foreach($values as $key => $value){
			if(isset($fields[$key])){
				$this->offsetSet($key, $value);
			}
		}
		return $this;
	}

	/**
	 * 读取数据表数据快照。
	 * 快照来自于 fetch, update
	 * @return array|null
	 */
	public function getSnap(): ?array
	{
		return $this->snap;
	}

}
