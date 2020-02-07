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
 * 基于WinCache的Cache后端
 *
 * @package mFramework
 * @author Wynn Chen
 */
class Wincache implements \mFramework\Cache
{

	/**
	 * 返回$key相对应的值。如果相应条目不存在返回null。
	 *
	 * @param mixed $key			
	 * @return mixed|null 相应的值，不存在条目直接返回null
	 */
	public function get($key)
	{
		$result = wincache_ucache_get($key, $success);
		return $success ? $result : null; 
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
		return wincache_ucache_set($key, $value, $ttl);
	}

	/**
	 * 指定条目在cache中是否存在
	 *
	 * @param mixed $key			
	 * @return bool 条目是否存在
	 */
	public function has($key)
	{
		return wincache_ucache_exists($key);
	}

	/**
	 * 从cache中删除指定条目
	 *
	 * @param mixed $key			
	 * @return bool 是否成功删除
	 */
	public function del($key)
	{
		return wincache_ucache_delete($key);
	}

	/**
	 * 清空cache，删掉其中所有内容
	 *
	 * @return bool 是否成功操作
	 */
	public function clear()
	{
		return wincache_ucache_clear();
	}
}
