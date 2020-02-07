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
 * Router
 *
 * @package mFramework
 * @author Wynn Chen
 */
Interface Router
{

	/**
	 * 执行实际的路由工作。
	 * 成功需要返回action，否则返回false。
	 * 失败时必须返回false，不能是任何其他值，包括null, ''。
	 *
	 * 如果在url上额外携带了信息，在这里需要用$request->setParameter()把相关信息设置好。
	 *
	 * @param Http\Request $request			
	 * @return string|bool $action|false
	 */
	public function route(Http\Request $request);
}
