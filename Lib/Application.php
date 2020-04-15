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
 * Application
 * 管理着整个框架应用程序实例的各种运行。
 *
 * 所有 setXX() 方法都只能使用至多一次，即设置之后就不能再次修改，以避免应用程序运行出现不一致问题。
 * 在 execute() 的开始阶段，将对未设置的相关对象按默认方案生成并使用之。在此之前对未设置过的内容使用
 * 相应的 getXX() 将得到null，在此之后则将取得生成的默认方案。
 *
 * 同时可以初始化多个app，但只有第一个可以通过getApp()方法获取到，其他的需要自行管理。
 *
 * @package mFramework
 * @author Wynn Chen
 */
class Application
{

	/**
	 *
	 * @var \mFramework\Application 保存Application的（第一个）实例
	 */
	private static $app = null;

	/**
	 * 按照第一个实例化的App实例。
	 * 如果没有则抛出异常。
	 *
	 * @return \mFramework\Application
	 * @throws \mFramework\Application\NoApplicationException
	 */
	final static public function getApp(): self
	{
		if (self::$app === null) {
			throw new Application\NoApplicationException('No Application has been inited.');
		}
		return self::$app;
	}

	/**
	 *
	 * @var string App名称，初始化时建立，可供区分，没有实际业务含义。
	 */
	private $name;

	/**
	 *
	 * @var \mFramework\Middleware 最外层的middleware，包括Application本身。
	 */
	private $middleware;

	/**
	 *
	 * @var \mFramework\Router 路由器
	 */
	protected $router = null;

	/**
	 *
	 * @var \mFramework\Dispatcher 分发器
	 */
	protected $dispatcher = null;

	/**
	 *
	 * @var \mFramework\Http\Request
	 */
	protected $request = null;

	/**
	 *
	 * @var \mFramework\Http\Response
	 */
	protected $response = null;

	/**
	 *
	 * @var \mFramework\Action
	 */
	protected $action = null;

	/**
	 * 应用程序初始化。
	 *
	 * @param string $name
	 *			应用程序名,识别使用。
	 */
	public function __construct(string $name = '')
	{
		$this->name = $name;
		$this->middleware = $this;
		
		// 注册第一个实例化的app
		if (self::$app === null) {
			self::$app = $this;
		}
	}

	/**
	 * 应用程序名。
	 *
	 * @return string
	 */
	final public function getAppName()
	{
		return $this->name;
	}

	/**
	 * 添加新的 Middleware
	 *
	 * Middleware是层层外包覆，后添加的先生效。
	 *
	 * @param \mFramework\Middleware $middleware			
	 * @return \mFramework\Application $this
	 */
	final public function addMiddleware(Middleware $middleware): self
	{
		$this->middleware = $middleware->setNextMiddleware($this->middleware);
		return $this;
	}

	/**
	 * 设置App所使用的路由器。
	 *
	 * @param \mFramework\Router $router			
	 * @return \mFramework\Application $this
	 */
	final public function setRouter(Router $router): self
	{
		$this->router = $router;
		return $this;
	}

	/**
	 * 获取之前设置的路由器。
	 *
	 * 如果没有设置过则为null。
	 *
	 * @return \mFramework\Router|null;
	 */
	final public function getRouter()
	{
		return $this->router;
	}

	/**
	 * 设置App所使用的分发器。
	 *
	 * @param \mFramework\Dispatcher $dispatcher			
	 * @return \mFramework\Application $this
	 */
	final public function setDispatcher(Dispatcher $dispatcher): self
	{
		$this->dispatcher = $dispatcher;
		return $this;
	}

	/**
	 * 获取之前设置的分发器。
	 *
	 * 如果没有设置过为null。
	 *
	 * @return \mFramework\Dispatcher|null;
	 */
	final public function getDispatcher()
	{
		return $this->dispatcher;
	}

	/**
	 * 手工设置 request 对象。
	 *
	 * @param \mFramework\Request $request			
	 * @return \mFramework\Application
	 */
	final public function setRequest(Http\Request $request): self
	{
		$this->request = $request;
		return $this;
	}

	/**
	 * 获取之前设置的 request 对象。
	 *
	 * 如果没有设置过为null。
	 *
	 * @return \mFramework\Request|null
	 */
	final public function getRequest()
	{
		return $this->request;
	}

	/**
	 * 手工设置 response 对象
	 *
	 * @param Response $response			
	 * @return \mFramework\Application
	 */
	public function setResponse(Http\Response $response): self
	{
		$this->response = $response;
		return $this;
	}

	/**
	 * 获取 response
	 *
	 * @return \mFramework\Http\Response|null
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * 主运行方法
	 */
	public function run()
	{
		$this->middleware->call($this);
	}

	/**
	 * app业务逻辑
	 *
	 * @throws mfErrorException
	 * @return unknown
	 */
	public function call()
	{
		$this->initDefault()
			->route()
			->dispatch()
			->runAction()
			->response();
	}

	private function response()
	{
		if ($this->response->isAutoResponseEnabled()) {
			$this->response->response();
		}
		return $this;
	}

	private function runAction()
	{
		$this->action->execute();
		if ($this->action->isViewEnabled()) {
			$this->render();
		}
		return $this;
	}

	private function render()
	{
		$action = $this->action;
		$view = $action->getView();
		$view->renderResponse($this->response, $action->getData());
		return $this;
	}

	private function dispatch()
	{
		$action = $this->action;
		
		$result = $this->dispatcher->dispatch($action);
		if (!$result) {
			throw new Application\DispatchFailException($action);
		}
		
		list($action_class, $view_class) = $result;
		
		if (!class_exists($action_class)) {
			throw new Application\ActionClassNotFoundException($action_class);
		}
		
		$action_object = new $action_class($this->request, $this->response, $this);
		if (!$action_object instanceof Action) {
			throw new Application\ActionClassInvalidException($action_class);
		}
		$action_object->setView($view_class);
		
		$this->action = $action_object;
		
		return $this;
	}

	private function route()
	{
		// route之
		$action = $this->router->route($this->request);
		if ($action === false) {
			throw new Application\RouteFailException();
		}
		$this->action = $action;
		return $this;
	}

	private function initDefault()
	{
		// 各种内容如果没有设置的全部设置默认：
		$this->request = $this->request ?: new Http\Request();
		$this->response = $this->response ?: new Http\Response();
		$this->router = $this->router ?: new Router\DefaultRouter();
		$this->dispatcher = $this->dispatcher ?: new Dispatcher\DefaultDispatcher();
		return $this;
	}
}
namespace mFramework\Application;

class Exception extends \mFramework\Exception
{}

class NoApplicationException extends Exception
{}

class RouteFailException extends Exception
{}

class DispatchFailException extends Exception
{}

class ActionClassNotFoundException extends Exception
{}

class ActionClassInvalidException extends Exception
{}