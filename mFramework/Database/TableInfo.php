<?php
declare(strict_types=1);

namespace mFramework\Database;

use mFramework\Database\Attribute\Field;
use mFramework\Database\Attribute\Table;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

/**
 * Record 类连接数据库的表配置信息
 * value object，不可变。
 */
class TableInfo
{
	/**
	 * @var array 缓存信息，需要写的字段表，即 diff($fields, $ignore_on_write)
	 */
	private array $write_fields;
	/**
	 * @var array 相应类的动态的静态类方法
	 */
	private array $static_macros = [];
	/**
	 * @var array 相应类的动态的对象方法
	 */
	private array $macros = [];

	/**
	 * 数组格式用于读写分离：
	 * array(
	 *     'r' => 读用的连接名称（string）
	 *     'w' => 写用的连接名称
	 *     ''  => 指定未指定模式时使用的连接，如果没有这条，那么未指定模式时按照'w'模式处理。
	 * )
	 *
	 * 字符串格式是简写，即 r 和 w 都使用这一个连接名称。
	 *
	 * 读连接用于retrieve，count等，
	 * 写连接用于insert,update,delete等。
	 *
	 * 这个配置是给 con() 方法使用的，如果自行另外实现了 con()，那么这个配置可能无效。
	 *
	 */
	private string|array $connection = '';
	/**
	 * @var string 对应的数据库表名
	 */
	private string $table = '';
	/**
	 * @var array array(字段名 => 类型, ...)
	 */
	private array $fields_type = [];
	/**
	 * @var array 主键所有字段，一个字段也要是数组形式。
	 */
	private array $pk = [];
	/**
	 * @var array update/insert 时需要忽略的字段名列表，一般为自动生成的字段，比如 auto inc 的，timestamp类型的。
	 */
	private array $ignore_on_write = [];
	/**
	 * @var array 默认的 order by 信息，数组， array(字段名 => 'ASC'|'DESC') 这样的形式，顺序按定义顺序。
	 */
	private ?array $default_order_by = null;
	/**
	 * @var string|null auto inc 字段，如果有。
	 */
	private ?string $auto_inc = null;
	/**
	 * @var array 字段列表
	 */
	private array $fields = [];
	/**
	 * @var bool 是否immutable，true的表默认不允许 update()方法。
	 */
	private bool $immutable = false;


	/**
	 * @var array $classname => $info
	 */
	static private array $tableInfo = [];

	/**
	 * @param string $class
	 * @return TableInfo|null
	 * @throws Exception
	 * @throws ReflectionException
	 */
	public static function get(string $class): ?TableInfo
	{
		return self::$tableInfo[$class] ?? self::$tableInfo[$class] = self::setUp($class);
	}

	/**
	 * Record类的子类必须通过 attributes 来配置数据库表相关信息。
	 *
	 * @param string $class
	 * @return TableInfo|null
	 * @throws Exception
	 * @throws ReflectionException
	 */
	static private function setUp(string $class): ?TableInfo
	{
		if(!is_a($class, Record::class, true)){
			throw new Exception($class.' 不是数据库表记录类。');
		}
		//class attributes 分析,表相关属性
		$reflection = new ReflectionClass($class);
		$attributes = $reflection->getAttributes(Table::class);
		if(!$attributes){
			//没有表信息，可能是继承的，找父类：
			$reflection = $reflection->getParentClass();
			if(!$reflection){//没有父类了
				throw new Exception($class.' 缺乏数据库属性配置信息。');
			}
			return $reflection->getName()::getTableInfo(); //用父类的信息
		}
		/** @var Table $table_obj */
		$table_obj = $attributes[0]->newInstance(); //携带着表的几个属性
		$table_info = new self();
		$table_info->connection =  $table_obj->getConnection();
		$table_info->table =  $table_obj->getName();
		$table_info->default_order_by = $table_obj->getOrderBy();
		$table_info->immutable =  $table_obj->isImmutable();
		foreach($reflection->getProperties() as $property) {
			if ($property->isStatic()) {
				continue; //静态属性不需要管
			}
			$attributes = $property->getAttributes(Field::class);
			if (!$attributes) {
				continue; //没有 "Field" attribute 的再见。
			}
			if(!$property->hasDefaultValue()){
				throw new Exception('字段属性 "'.$class.'->'.$property->getName().'" 必须有默认值（可以是null）。');
			}
			/** @var Field $field */
			$field = $attributes[0]->newInstance(); //携带flag信息
			//字段名
			$table_info->fields[] = $name = $property->getName(); //使用变量名
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
			$table_info->fields_type[$name] = $type; //写入字段定义数组。
			if($field->isPk()){
				$table_info->pk[] = $name;
			}
			if($field->isAutoInc()){
				$table_info->auto_inc = $name;
				$table_info->ignore_on_write[] = $name; //auto inc 的也就不能写入。
			}
			if($field->isReadOnly()){
				$table_info->ignore_on_write[] = $name;
			}
			//是索引？
			if($field->isUnique()){
				$table_info->static_macros['selectBy'.self::snakeToPascal($name)] = (function($value)use($name){
					return static::selectBy([$name => $value])->firstRow();
				})->bindTo(null, $class);
			}elseif($field->isIndex()){
				$table_info->static_macros['selectBy'.self::snakeToPascal($name)] = (function($value)use($name){
					return static::selectBy([$name => $value]);
				})->bindTo(null, $class);
			}
			//是个外键？
			if($key = $field->getKey()){
				$table_info->macros['get'.self::snakeToPascal(preg_replace('/_id$/','', $name))] = function()use($key, $name){
					return (function($value){
						return static::selectByPk($value);
					})->bindTo(null, $key)($this->$name);
				};
			}
		}
		//缓存可写字段
		$table_info->write_fields = array_diff($table_info->fields, $table_info->ignore_on_write);
		return $table_info;
	}

	public function getConnection(): array|string
	{
		return $this->connection;
	}

	public function getTable(): string
	{
		return $this->table;
	}

//	public function getDefaultValues(): array
//	{
//		return $this->default_values;
//	}

	public function getPk(): array
	{
		return $this->pk;
	}
	public function getPrimaryKey(): array
	{
		return $this->pk;
	}

	public function getIgnoreOnWrite(): array
	{
		return $this->ignore_on_write;
	}

	public function getDefaultOrderBy(): ?array
	{
		return $this->default_order_by;
	}

	public function getAutoInc(): ?string
	{
		return $this->auto_inc;
	}

	public function getFieldsType(): array
	{
		return $this->fields_type;
	}

	public function getFields(): array
	{
		return $this->fields;
	}

	public function getWriteFields(): array
	{
		return $this->write_fields;
	}

	public function getStaticMacros(): array
	{
		return $this->static_macros;
	}

	public function getMacros(): array
	{
		return $this->macros;
	}

	/**
	 * @return bool
	 */
	public function isImmutable(): bool
	{
		return $this->immutable;
	}

	static private function snakeToPascal($name)
	{
		return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
	}

}