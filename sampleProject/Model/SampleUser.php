<?php
/**
 * Model示例。
 */
namespace Model;

class SampleUser extends \mFramework\Database\Record
{

	const TYPE_USER = 1;

	const TYPE_ADMIN = 2;

	protected static $connection = 'default';

	protected static $table = 'user';

	protected static $auto_inc = 'id';

	protected static $pk = array('id');

	protected static $fields = array('id' => self::DATATYPE_INT,'email' => self::DATATYPE_STRING,'password' => self::DATATYPE_STRING,'type' => self::DATATYPE_INT,'remark' => self::DATATYPE_STRING);

	protected static $default = ['type' => self::TYPE_USER];

	/**
	 * 根据 Email 查找到对应用户。
	 * Email字段应当是 unique。
	 *
	 * @param string $email			
	 * @return self|null
	 */
	public static function selectByEmail($email)
	{
		$sql = 'SELECT * FROM ' . self::table(true) . ' WHERE ' . self::e('email') . ' = ?';
		return self::select($sql, [$email])->firstRow();
	}
}

