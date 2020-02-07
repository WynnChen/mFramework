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
 *
 * Action
 *
 * mFramework 没有 controller 这个层次，直接就是action。
 *
 * @package mFramework
 * @author Wynn Chen
 *		
 */
abstract class Action
{

	/**
	 *
	 * @var Http\Request
	 */
	private $request;

	/**
	 *
	 * @var Http\Response
	 */
	private $response;

	/**
	 *
	 * @var Application
	 */
	private $app;

	private $view = null;

	private $enable_view = false;

	private $data;

	/**
	 *
	 * @param Http\Request $request			
	 * @param Http\Response $response			
	 * @param Application $app			
	 */
	public function __construct(Http\Request $request, Http\Response $response, Application $app)
	{
		$this->request = $request;
		$this->response = $response;
		$this->app = $app;
		$this->data = new Map();
	}

	/**
	 *
	 * @return Application
	 */
	protected function getApp()
	{
		return $this->app;
	}

	/**
	 *
	 * @return \mFramework\Http\Request
	 */
	protected function getRequest()
	{
		return $this->request;
	}

	/**
	 *
	 * @return \mFramework\Http\Response
	 */
	protected function getResponse()
	{
		return $this->response;
	}

	/**
	 * View的名称。应当是一个View实例类的名字，或View实例。
	 * 注意这里不能直接初始化，因为不肯定这个类一定存在。
	 * 逻辑上允许先设置一个事实上不存在的view，在随后的逻辑里再覆盖掉。
	 * setView()会自动打开 auto_render。
	 *
	 * @param string|View $view			
	 * @throws \mFramework\Http\Response\InvalidViewException
	 */
	public function setView($view)
	{
		if (!is_string($view) and !($view instanceof View)) {
			throw new Action\InvalidViewException('invalid View.', 1);
		}
		$this->view = $view;
		$this->enable_view = true;
		return $this;
	}

	public function getView()
	{
		$view = $this->view;
		if (is_string($view)) {
			if (!class_exists($view)) {
				throw new Action\InvalidViewException('View not found: ' . $view);
			}
			$view = new $view();
		}
		if (!$view instanceof View) {
			throw new Action\InvalidViewException('need string or View ojbect ');
		}
		return $view;
	}

	public function disableView()
	{
		$this->enable_view = false;
	}

	public function enableView()
	{
		$this->enable_view = true;
	}

	public function isViewEnabled()
	{
		return $this->enable_view;
	}

	/**
	 * 将数据关联给view
	 *
	 * @param string $name			
	 * @param mixed $value			
	 */
	public function assign($name, $value)
	{
		$this->data->offsetSet($name, $value);
	}

	/**
	 * 取得整个关联的数据对象
	 *
	 * @return mFramework\Map
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * 主执行方法。
	 */
	public function execute()
	{
		$request = $this->request;
		$response = $this->response;
		
		if($request->isGet()){
			$this->runGet($request, $response);
		}
		elseif($request->isPost()){
			$this->runPost($request, $response);
		}
		else{
			$this->run($request, $response);
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
	{}
}
namespace mFramework\Action;

class Exception extends \mFramework\Exception
{}

class InvalidViewException extends Exception
{}