<?php
/**
 * mFramework - a mini PHP framework
 *
 * @package   mFramework
 * @copyright 2009-2020 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework\Cache;

/**
 *
 * 基于php数组的Cache后端
 * 这个cache只能用于单次进程内部，可以用于在密集操作时缓存某些结果。
 */
class ArrayCache implements \mFramework\Cache
{
	/**
	 * array( $key => [$value, $expire] )
	 */
	private array $cache = [];

	/**
	 * 检查是否已过期，过期就清理掉。
	 * 按照清理后的结果返回对应 $value 或者 null
	 *
	 * @param mixed $key
	 * @return mixed 清理后的有效数据或null
	 */
	private function clearExpire(mixed $key):mixed
	{
		if (!isset($this->cache[$key])) {
			return null;
		}
		list($value, $expire) = $this->cache[$key];
		if ($expire < microtime(true)) {
			$this->cache[$key] = null;
			return null;
		}
		return $value;
	}

	/**
	 * 返回$key相对应的值。如果相应条目不存在返回null。
	 *
	 * @param mixed $key
	 * @return mixed 相应的值，不存在条目直接返回null
	 */
	public function get(mixed $key):mixed
	{
		return $this->clearExpire($key);
	}

	/**
	 * 向$key写入值 $value 。
	 *
	 * @param mixed $key			
	 * @param mixed $value			
	 * @param int $ttl
	 *			存活时间，0为无限期。
	 * @return bool 是否操作成功了
	 */
	public function set(mixed $key, mixed $value, int $ttl = 0):bool
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
	public function has(mixed $key):bool
	{
		return $this->clearExpire($key) !== null;
	}

	/**
	 * 从cache中删除指定条目
	 *
	 * @param mixed $key			
	 * @return bool 是否成功删除
	 */
	public function del(mixed $key):bool
	{
		$this->cache[$key] = null;
		return true;
	}

	/**
	 * 清空cache，删掉其中所有内容
	 *
	 * @return bool 是否成功操作
	 */
	public function clear():bool
	{
		$this->cache = [];
		return true;
	}
}
