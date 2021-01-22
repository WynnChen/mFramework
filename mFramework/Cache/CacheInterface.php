<?php
declare(strict_types=1);

namespace mFramework\Cache;

use DateInterval;

/**
 * Cache
 *
 * 参照 PSR 16，用 PHP 8 的方式定义方法签名。
 *
 * mFramework中对cache的看法是纯粹的数据缓存，为数据提供能够(快速)读取的副本，
 * 这个副本的数据可能落后于理论实际值。
 *
 * cache的功能和具体实现无关联，不能期望cache作为数据共享/数据交换使用，
 * 接口不考虑多线程竞争的情况， 不提供inc/dec这类的方法。
 * 
 * //选定cache，取得cache实例：
 * $cache = new Cache\ArrayCache();
 * //接下来就可以用了：
 * $cache->set('key', 'value');
 * $cache->get('key');
 * 
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
interface CacheInterface
{
	/**
	 * Fetches a value from the cache.
	 *
	 * @param string $key     The unique key of this item in the cache.
	 * @param mixed  $default Default value to return if the key does not exist.
	 *
	 * @return mixed The value of the item from the cache, or $default in case of cache miss.
	 *
	 * @throws InvalidArgumentException
	 *   MUST be thrown if the $key string is not a legal value.
	 */
	public function get(string $key, mixed $default = null):mixed;

	/**
	 * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
	 *
	 * @param string $key The key of the item to store.
	 * @param mixed $value The value of the item to store, must be serializable.
	 * @param null|int|DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
	 *                                      the driver supports TTL then the library may set a default value
	 *                                      for it or let the driver take care of that.
	 *
	 * @return bool True on success and false on failure.
	 *
	 * @throws InvalidArgumentException MUST be thrown if the $key string is not a legal value.
	 */
	public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool;

	/**
	 * Delete an item from the cache by its unique key.
	 *
	 * @param string $key The unique cache key of the item to delete.
	 *
	 * @return bool True if the item was successfully removed. False if there was an error.
	 *
	 * @throws InvalidArgumentException MUST be thrown if the $key string is not a legal value.
	 */
	public function delete(string $key): bool;

	/**
	 * Wipes clean the entire cache's keys.
	 *
	 * @return bool True on success and false on failure.
	 */
	public function clear(): bool;

	/**
	 * Obtains multiple cache items by their unique keys.
	 *
	 * @param iterable $keys A list of keys that can be obtained in a single operation.
	 * @param mixed $default Default value to return for keys that do not exist.
	 *
	 * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
	 *
	 * @throws InvalidArgumentException MUST be thrown if $keys is neither an array nor a Traversable,
	 *   or if any of the $keys are not a legal value.
	 */
	public function getMultiple(iterable $keys, mixed $default = null): iterable;

	/**
	 * Persists a set of key => value pairs in the cache, with an optional TTL.
	 *
	 * @param iterable $values A list of key => value pairs for a multiple-set operation.
	 * @param null|int|DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
	 *                                       the driver supports TTL then the library may set a default value
	 *                                       for it or let the driver take care of that.
	 *
	 * @return bool True on success and false on failure.
	 *
	 * @throws InvalidArgumentException MUST be thrown if $values is neither an array nor a Traversable,
	 *   or if any of the $values are not a legal value.
	 */
	public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool;

	/**
	 * Deletes multiple cache items in a single operation.
	 *
	 * @param iterable $keys A list of string-based keys to be deleted.
	 *
	 * @return bool True if the items were successfully removed. False if there was an error.
	 *
	 * @throws InvalidArgumentException MUST be thrown if $keys is neither an array nor a Traversable,
	 *   or if any of the $keys are not a legal value.
	 */
	public function deleteMultiple(iterable $keys): bool;

	/**
	 * Determines whether an item is present in the cache.
	 *
	 * NOTE: It is recommended that has() is only to be used for cache warming type purposes
	 * and not to be used within your live applications operations for get/set, as this method
	 * is subject to a race condition where your has() will return true and immediately after,
	 * another script can remove it making the state of your app out of date.
	 *
	 * @param string $key The cache item key.
	 *
	 * @return bool
	 *
	 * @throws InvalidArgumentException MUST be thrown if the $key string is not a legal value.
	 */
	public function has(string $key): bool;
}

/**
 * Interface used for all types of exceptions thrown by the implementing library.
 */
interface CacheException
{}

/**
 * Exception interface for invalid cache arguments.
 *
 * When an invalid argument is passed it must throw an exception which implements
 * this interface
 */
interface InvalidArgumentException extends CacheException
{}