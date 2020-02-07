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
 * 基于php数组的Cache后端
 *
 * @package mFramework
 * @author Wynn Chen
 */
class ArrayCache implements \mFramework\Cache
{

	/**
	 *
	 * @var array( $key => [$value, $expire])
	 */
	private $cache = [];

	/**
	 * 检查是否已过期，过期就清理掉。
	 *
	 * @param unknown $key			
	 * @return 清理后这个key是否存在。
	 */
	private function clearExpire($key)
	{
		if (!isset($this->cache[$key])) {
			return false;
		}
		list($value, $expire) = $this->cache[$key];
		if ($expire < microtime(true)) {
			unset($this->cache[$key]);
			return false;
		}
		return true;
	}

	/**
	 * 返回$key相对应的值。如果相应条目不存在返回null。
	 *
	 * @param mixed $key			
	 * @return mixed|null 相应的值，不存在条目直接返回null
	 */
	public function get($key)
	{
		return $this->clearExpire($key) ? $this->cache[$key][0] : null;
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
		$this->cache[$key] = [$value, $ttl ? (microtime(true) + $ttl) : PHP_INT_MAX];
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
		return $this->clearExpire($key);
	}

	/**
	 * 从cache中删除指定条目
	 *
	 * @param mixed $key			
	 * @return bool 是否成功删除
	 */
	public function del($key)
	{
		if(isset($this->cache[$key])){
			unset($this->cache[$key]);
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * 清空cache，删掉其中所有内容
	 *
	 * @return bool 是否成功操作
	 */
	public function clear()
	{
		$this->cache = [];
		return true;
	}
}
