<?php
/**
 * mFramework
 */
namespace mFramework\Cache;

use DateInterval;

/**
 *
 * 事实上没有任何cache的cache后端。
 * 供调试、占位等使用。
 *
 * @package mFramework
 * @author Wynn Chen
 */
class DummyCache implements CacheInterface
{

	public function get(string $key, mixed $default = null):mixed
	{
		return null;
	}

	public function set(string $key, mixed $value, DateInterval|int|null $ttl = null):bool
	{
		return true;
	}

	public function has(string $key): bool
	{
		return false;
	}

	public function delete(string $key):bool
	{
		return true;
	}

	public function clear(): bool
	{
		return true;
	}

	public function getMultiple(iterable $keys, mixed $default = null): iterable
	{
		$result = [];
		foreach($keys as $key){
			$result[$key] = $default;
		}
		return $result;
	}

	public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
	{
		return true;
	}

	public function deleteMultiple(iterable $keys): bool
	{
		return true;
	}

}
