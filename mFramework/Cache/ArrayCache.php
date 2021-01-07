<?php
/**
 * mFramework
 */
namespace mFramework\Cache;

use DateInterval;
use DateTime;

/**
 *
 * 基于php数组的Cache后端
 * 这个cache只能用于单次进程内部，可以用于在密集操作时缓存某些结果。
 *
 *  @author	Wynn Chen <wynn.chen@outlook.com>
 */
class ArrayCache implements CacheInterface
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

	public function get(string $key, mixed $default = null):mixed
	{
		return $this->clearExpire($key) ?? $default;
	}

	public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
	{
		if($ttl instanceof DateInterval){
			$d = new DateTime();
			$d->add($ttl);
			$eol = (float)((string)$d->getTimestamp().'.'.$d->format('F'));
		}
		else{
			$eol = ($ttl === null) ? (microtime(true) + $ttl) : PHP_INT_MAX;
		}
		$this->cache[$key] = [$value, $eol];
		return true;
	}

	public function has(string $key): bool
	{
		return $this->clearExpire($key) !== null;
	}

	public function delete(string $key):bool
	{
		$this->cache[$key] = null;
		return true;
	}

	public function clear(): bool
	{
		$this->cache = [];
		return true;
	}

	public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
	{
		foreach($values as $key => $value){
			$this->set($key, $value, $ttl);
		}
		return true;
	}

	public function getMultiple(iterable $keys, mixed $default = null): iterable
	{
		$result = [];
		foreach($keys as $key){
			$result[$key] = $this->get($key, $default);
		}
		return $result;
	}

	public function deleteMultiple(iterable $keys): bool
	{
		foreach($keys as $key){
			$this->delete($key);
		}
		return true;
	}

}
