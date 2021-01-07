<?php
/**
 * mFramework - a mini PHP framework
 * 
 * @package   mFramework
 * @version   v5
 * @copyright 2009-2016 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework\Middleware;

/**
 * 自动开始session
 *
 * @package mFramework
 * @author Wynn Chen
 *		
 */
class AutoStartSession extends \mFramework\AbstractMiddleware
{

	/**
	 * Call
	 */
	public function call(\mFramework\Application $application)
	{
		\mFramework\Http\Session::start();
		$this->next->call($application);
	}
}
