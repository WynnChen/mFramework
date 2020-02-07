<?php
/**
 * mFramework - a mini PHP framework
 * 
 * @package   mFramework
 * @version   v5
 * @copyright 2009-2016 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework\Cache;

/**
 *
 * 事实上没有任何cache的cache后端。
 * 供调试、占位等使用。
 *
 * @package mFramework
 * @author Wynn Chen
 */
class Dummy implements \mFramework\Cache
{

	/**
	 * 返回$key相对应的值。如果相应条目不存在返回null。
	 *
	 * @param mixed $key			
	 * @return mixed|null 相应的值，不存在条目直接返回null
	 */
	public function get($key)
	{
		return null;
	}

	/**
	 * 向$key写入值$value。
	 *
	 * @param mixed $key			
	 * @param mixed $value			
	 * @param int $ttl
	 *			存活时间，0为无限期。
	 * @return bool 是否操作成功了
	 */
	public function set($key, $value, $ttl = 0)
	{
		return true;
	}

	/**
	 * 指定条目在cache中是否存在
	 *
	 * @param mixed $key			
	 * @return bool 条目是否存在
	 */
	public function has($key)
	{
		return false;
	}

	/**
	 * 从cache中删除指定条目
	 *
	 * @param mixed $key			
	 * @return bool 是否成功删除
	 */
	public function del($key)
	{
		return false;
	}

	/**
	 * 清空cache，删掉其中所有内容
	 *
	 * @return bool 是否成功操作
	 */
	public function clear()
	{
		return true;
	}
}
