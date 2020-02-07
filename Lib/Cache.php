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
 * Cache
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
 * @package mFramework
 * @author Wynn Chen
 */
interface Cache
{

	/**
	 * 返回$key相对应的值。如果相应条目不存在返回null。
	 *
	 * @param mixed $key			
	 * @return mixed|null 相应的值，不存在条目直接返回null
	 */
	public function get($key);

	/**
	 * 向$key写入值$value。
	 *
	 * @param mixed $key			
	 * @param mixed $value			
	 * @param int $ttl
	 *			存活时间，0为无限期。
	 * @return bool 是否操作成功了
	 */
	public function set($key, $value, $ttl = 0);

	/**
	 * 指定条目在cache中是否存在
	 *
	 * @param mixed $key			
	 * @return bool 条目是否存在
	 */
	public function has($key);

	/**
	 * 从cache中删除指定条目
	 *
	 * @param mixed $key			
	 * @return bool 是否成功删除
	 */
	public function del($key);

	/**
	 * 清空cache，删掉其中所有内容
	 *
	 * @return bool 是否成功操作
	 */
	public function clear();
}
