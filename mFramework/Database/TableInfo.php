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
	 * @var array self[]
	 */
	static private array $tables = [];

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

//	/**
//	 * @var array array(字段名 => 默认值, ...) 实际上似乎没啥用
//	 */
//	private array $default_values = [];

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
	private array $default_order_by = [];

	/**
	 * @var string|null auto inc 字段，如果有。
	 */
	private ?string $auto_inc = null;

	/**
	 * @var array 字段列表
	 */
	private array $fields = [];

	/**
	 * @var array 缓存信息，需要写的字段表，即 diff($fields, $ignore_on_write)
	 */
	private array $write_fields = [];

	/**
	 * TableInfo constructor.
	 * 就不要直接 new 了。
	 */
	private function __construct()
	{}

	/**
	 * 需要自行提前register
	 * @param string $class
	 * @return static|null 配置不存在为null
	 */
	static public function getInfo(string $class):?self
	{
		return self::$tables[$class] ?? null;
	}

	/**
	 * 注册某一个类对应的表信息，从那个类的 attribute 中生成。
	 *
	 * @param string $class
	 * @return bool 注册是否成功（指示的是注册动作本身，但不保证注册的信息是否有效）
	 * @throws ReflectionException
	 */
	static public function register(string $class):bool
	{
		if(!empty(self::$tables[$class])){
			return false;
		}
		self::$tables[$class] = self::setUp($class);
		return true;
	}

	/**
	 * 尝试从那个类的 attribute 中生成配置信息。
	 * @param $class
	 * @return self
	 * @throws ReflectionException
	 */
	private static function setUp($class): ?self
	{
		$table_info_obj = new self();

		//class attributes 分析,表相关属性
		$reflection = new ReflectionClass($class);
		do{
			$attributes = $reflection->getAttributes(Table::class);
			if(!$attributes){
				//没有表信息，可能是继承的，找父类：
				$reflection = $reflection->getParentClass();
				if(!$reflection){//没有父类了
					return $table_info_obj; //不能抛出异常，因为某些类装备用于继承的，不要求配置。
				}
				if($info = self::getInfo($reflection->getName())){ //分析过了.
					return $info;
				}
			}
		}while(!$attributes);
		$table_info = $attributes[0]->getArguments();
		$table_info_obj->connection = $table_info['connection'] ?? $table_info[0] ?? 'default';
		$table_info_obj->table = $table_info['name'] ?? $table_info[1] ?? '';
		if(isset($table_info['orderBy'])){
			$table_info_obj->default_order_by = $table_info['orderBy'] ?? $table_info[2];
		}

		// properties attributes 分析，字段属性
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
				throw new Exception('字段属性 "'.$class.'->'.$property->getName().'" 必须有默认值（可以是null）。');
			}
			$info = $attributes[0]->getArguments();
			//字段名
			$name = $property->getName(); //使用变量名
			$table_info_obj->fields[] = $name;
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
			$table_info_obj->fields_type[$name] = $type; //写入字段定义数组。

			$flags = $info['flags'] ?? $info[0] ?? 0;
			if($flags & Field::IS_PK){
				$table_info_obj->pk[] = $name;
			}
			if($flags & Field::IS_AUTO_INC){
				$table_info_obj->auto_inc = $name;
				$table_info_obj->ignore_on_write[] = $name; //auto inc 的也就不能写入。
			}
			if($flags & Field::IS_READ_ONLY){
				$table_info_obj->ignore_on_write[] = $name;
			}
		}
		//两个缓存性质的数组
		$table_info_obj->write_fields = array_diff($table_info_obj->fields, $table_info_obj->ignore_on_write);

		return $table_info_obj;
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

	public function getDefaultOrderBy(): array
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

}