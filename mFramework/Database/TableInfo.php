<?php
declare(strict_types=1);

namespace mFramework\Database;

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
	 * TableInfo constructor.
	 * @param string|array $connection
	 * @param string $table
	 * @param array $fields_type
	 * @param array $pk
	 * @param array $ignore_on_write
	 * @param ?array $default_order_by
	 * @param string|null $auto_inc
	 * @param array $fields
	 */
	public function __construct(
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
		private string|array $connection = '',
		/**
		 * @var string 对应的数据库表名
		 */
		private string $table = '',
		/**
		 * @var array array(字段名 => 类型, ...)
		 */
		private array $fields_type = [],
		/**
		 * @var array 主键所有字段，一个字段也要是数组形式。
		 */
		private array $pk = [],
		/**
		 * @var array update/insert 时需要忽略的字段名列表，一般为自动生成的字段，比如 auto inc 的，timestamp类型的。
		 */
		private array $ignore_on_write = [],
		/**
		 * @var array 默认的 order by 信息，数组， array(字段名 => 'ASC'|'DESC') 这样的形式，顺序按定义顺序。
		 */
		private ?array $default_order_by = null,
		/**
		 * @var string|null auto inc 字段，如果有。
		 */
		private ?string $auto_inc = null,
		/**
		 * @var array 字段列表
		 */
		private array $fields = [],
		/**
		 * @var bool 是否immutable，true的表默认不允许 update()方法。
		 */
		private bool $immutable = false,
	)
	{
		//缓存可写字段
		$this->write_fields = array_diff($this->fields, $this->ignore_on_write);
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

	/**
	 * @return bool
	 */
	public function isImmutable(): bool
	{
		return $this->immutable;
	}

}