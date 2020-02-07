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
 * Dispatcher
 *
 * @package mFramework
 * @author Wynn Chen
 */
interface Dispatcher
{

	/**
	 * 将传递进入的 $action 信息解析出相应的action类名和默认的View名。
	 * 失败必须返回false，不能是任何其他值，包括null。
	 *
	 * @param string $action			
	 * @return string|false 对应的action类,或失败返回false
	 */
	public function dispatch(string $action);
}
