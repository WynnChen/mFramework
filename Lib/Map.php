<?php
/**
 * mFramework - a mini PHP framework
 * 
 * @package   mFramework
 * @version   v5
 * @copyright 2009-2016 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework;

/**
 * Map是通用的key-value数据容器。旨在提供一种灵活方便的数据封装与访问方式。
 * 对于容器内key为'key'，value为$value的数据，允许用3种不同的方式访问：
 *
 * 方法1：
 * $map->set('key', $value);
 * $var = $map->get('key');
 * $map->has('key'); //true
 * $map->del('key'); //unset
 * 在这个方式下，试图get不存在的值也是允许的：
 * $var = $has->get('nonexist_key', $default_value);
 *
 * 方法2：
 * $map['key'] = $value;
 * $var = $map['key'];
 * isset($map['key']);
 * unset($map['key']);
 *
 * 方法3：
 * $map->key = $value;
 * $var = $map->key;
 * isset($map->key);
 * unset($map->key);
 *
 * 试图读取不存在的索引一样会引发报错。
 *
 * Map的所有存取方式最终均实际通过 offset*() 系列方法执行具体存取，
 * 因此如果需要扩展时只需要处理这系列即可。
 *
 * 用ArrayObject做基础的原因：
 * 1. 有exchangeArray()方法。
 * 2. Map不需要成为一个Iterator。
 *
 * @package mFramework
 * @author Wynn Chen
 */
class Map extends \ArrayObject
{

	/**
	 * 接受array或者object做参数。
	 *
	 * @param array $data
	 *        	输入的数据。
	 */
	public function __construct(array $data = array())
	{
		parent::__construct($data, self::ARRAY_AS_PROPS);
	}

	/**
	 * 允许以$hash->get($key)的方式来获取数据
	 *
	 * @param string $key        	
	 * @param mixed $default
	 *        	$key不存在时返回的$value
	 * @return array
	 */
	public function get($key, $default = null)
	{
		return $this->offsetExists($key) ? $this->offsetGet($key) : $default;
	}

	/**
	 * 允许以$hash->set($key, $value)的方式来存入数据
	 *
	 * @param string $key        	
	 * @param mixed $value        	
	 * @return $this
	 */
	public function set($key, $value = null)
	{
		$this->offsetSet($key, $value);
	}

	/**
	 * 某个数据是否存在？
	 *
	 * @param mixed $key        	
	 * @return bool
	 */
	public function has($key)
	{
		return $this->offsetExists($key);
	}

	/**
	 * 删除某个值。
	 *
	 * @param mixed $key        	
	 * @return bool
	 */
	public function del($key)
	{
		return $this->offsetUnset($key);
	}
}
