<?php
/**
 * mFramework - a mini PHP framework
 *
 * @package   mFramework
 * @copyright 2009-2020 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework;

/**
 * Application Middleware
 *
 * @package mFramework
 * @author Wynn Chen
 */
abstract class AbstractMiddleware
{

	/**
	 *
	 * @var \mFramework\AbstractMiddleware|\mFramework\Application 指向下一个Middleware，如果是最后一个Middleware则指向Application本体
	 */
	protected $next;

	/**
	 *
	 * @param
	 *			\mFramework\Middleware|\mFramework\Application
	 * @return \mFramework\AbstractMiddleware $this
	 */
	final public function setNextMiddleware($nextMiddleware): self
	{
		$this->next = $nextMiddleware;
		return $this;
	}

	/**
	 *
	 * @return \mFramework\AbstractMiddleware|\mFramework\Application
	 */
	final public function getNextMiddleware()
	{
		return $this->next;
	}

	/**
	 * 执行本middleware的工作。一般需要在其中调用下一个middleware的call()方法：
	 * $this->next->call($app);
	 *
	 * @param
	 *			\mFramework\Application 所属的主Appliation对象。
	 */
	abstract public function call(Application $application);
}
