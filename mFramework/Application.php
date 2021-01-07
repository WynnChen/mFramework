<?php
/**
 * mFramework - a mini PHP framework
 *
 * @package   mFramework
 * @copyright 2009-2020 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework;

use mFramework\Application\NoApplicationException;
use mFramework\Http\Request;
use mFramework\Http\Response;

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
class Application extends AbstractMiddleware
{
	/**
	 * 保存Application的（第一个）实例
	 */
	private static ?Application $app = null;

	/**
	 * 按照第一个实例化的App实例。
	 * 如果没有则抛出异常。
	 *
	 * @return Application
	 * @throws NoApplicationException
	 */
	final static public function getApp(): self
	{
		if (self::$app === null) {
			throw new Application\NoApplicationException('No Application has been initialized.');
		}
		return self::$app;
	}

	/**
	 * 最外层的middleware，包括Application本身。
	 */
	private AbstractMiddleware $middleware;

	protected ?RouterInterface $router = null;
	protected ?Dispatcher $dispatcher = null;
	protected ?Request $request = null;
	protected ?Response $response = null;
	protected ?Action $action = null;

	/**
	 * 应用程序初始化。
	 * @param string $name 标记名称
	 */
	public function __construct(private string $name = '')
	{
		$this->middleware = $this;
		self::$app or (self::$app = $this);
	}

	/**
	 * 应用程序名。
	 */
	final public function getAppName(): string
	{
		return $this->name;
	}

	/**
	 * 添加新的 Middleware
	 * Middleware是层层外包覆，后添加的先生效。
	 * @param AbstractMiddleware $middleware
	 * @return Application
	 */
	final public function addMiddleware(AbstractMiddleware $middleware): self
	{
		$middleware->setNextMiddleware($this->middleware);
		$this->middleware = $middleware;
		return $this;
	}

	/**
	 * 设置App所使用的路由器。
	 *
	 * @param RouterInterface $router
	 * @return Application $this
	 */
	final public function setRouter(RouterInterface $router): self
	{
		$this->router = $router;
		return $this;
	}

	/**
	 * 获取之前设置的路由器。
	 *
	 * 如果没有设置过则为 null。
	 *
	 * @return RouterInterface|null;
	 */
	final public function getRouter(): ?RouterInterface
	{
		return $this->router;
	}

	/**
	 * 设置App所使用的分发器。
	 *
	 * @param \mFramework\Dispatcher $dispatcher			
	 * @return Application $this
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
	 * @param Http\Request $request
	 * @return Application
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
	 */
	final public function getRequest(): ?Request
	{
		return $this->request;
	}

	/**
	 * 手工设置 response 对象
	 *
	 * @param Response $response			
	 * @return Application
	 */
	public function setResponse(Response $response): self
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
	 * app业务逻辑??
	 * @todo 是否要支持forward？
	 *
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