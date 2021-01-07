<?php
/**
 * mFramework - a mini PHP framework
 *
 * @package   mFramework
 * @copyright 2009-2020 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework;

use JetBrains\PhpStorm\Pure;
use mFramework\Http\Request;
use mFramework\Http\Response;

/**
 *
 * Action
 *
 * mFramework 没有 controller 这个层次，直接就是 action 。
 *
 * @package mFramework
 * @author Wynn Chen
 *		
 */
abstract class Action
{
	private Request $request;
	private Response $response;
	private Application $app;
	private View|string|null $view;
	private bool $enable_view = false;
	private Map $data;

	public function __construct(Request $request, Response $response, Application $app)
	{
		$this->request = $request;
		$this->response = $response;
		$this->app = $app;
		$this->data = new Map();
		$this->view = $this->getDefaultView();
	}

	#[Pure] protected function getDefaultView():string
	{
		return substr_replace(self::class, 'View', -6);
	}

	protected function getApp():Application
	{
		return $this->app;
	}

	protected function getRequest():Request
	{
		return $this->request;
	}

	protected function getResponse():Response
	{
		return $this->response;
	}

	/**
	 * View的名称。应当是一个View实例类的名字，或View实例。
	 *
	 * 注意这里不能直接初始化，因为不肯定这个类一定存在。
	 * 逻辑上允许先设置一个事实上不存在的view，在随后的逻辑里再覆盖掉。
	 * setView()会自动打开 auto_render。
	 *
	 * @param string|View $view
	 * @return Action
	 */
	public function setView(string|View $view):self
	{
		$this->view = $view;
		$this->enable_view = true;
		return $this;
	}

	/**
	 * @return View
	 * @throws Action\InvalidViewException
	 */
	public function getView():View
	{
		$view = $this->view;
		if (is_string($view)) {
			if (!class_exists($view)) {
				throw new Action\InvalidViewException('View not found: ' . $view);
			}
			$view = new $view();
		}
		if (!$view instanceof View) {
			throw new Action\InvalidViewException('need string or View object');
		}
		return $view;
	}

	public function disableView():void
	{
		$this->enable_view = false;
	}

	public function enableView():void
	{
		$this->enable_view = true;
	}

	public function isViewEnabled():bool
	{
		return $this->enable_view;
	}

    /**
     * 将数据关联给view
     * @param iterable|string $name
     * @param null $value
     * @return Action
     */
	public function assign(string|iterable $name, $value = null) : self
	{
		if(is_iterable($name)){
			foreach($name as $k=>$v){
				$this->data->offsetSet($k, $v);
			}
		}
		else{
			$this->data->offsetSet($name, $value);
		}
		return $this;
	}

	/**
	 * 取得整个关联的数据对象
	 *
	 */
	public function getData():Map
	{
		return $this->data;
	}

	/**
	 * 主执行方法。
	 */
	public function execute():void
	{
		if($this->request->isGet()){
			$this->runGet($this->request, $this->response);
		}elseif($this->request->isPost()){
			$this->runPost($this->request, $this->response);
		}else{
			$this->run($this->request, $this->response);
		}
	}

	/**
	 * 本action的业务逻辑,GET方法时
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	protected function runGet(Http\Request $request, Http\Response $response)
	{
		$this->run($request, $response);
	}
	/**
	 * 本action的业务逻辑,POST方法时
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	protected function runPost(Http\Request $request, Http\Response $response)
	{
		$this->run($request, $response);
	}
	
	/**
	 * 本action的业务逻辑,默认版本
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	protected function run(Http\Request $request, Http\Response $response)
	{
	}
}

namespace mFramework\Action;
class Exception extends \mFramework\Exception
{}
class InvalidViewException extends Exception
{}