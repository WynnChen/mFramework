<?php
declare(strict_types=1);

namespace mFramework;

use mFramework\Http\Request;
use mFramework\Http\Response;
use mFramework\Middleware\MiddlewareInterface;
use mFramework\ResponseEmitter\DefaultResponseEmitter;
use mFramework\ResponseEmitter\ResponseEmitterInterface;
use mFramework\Routing\DefaultDispatcher;
use mFramework\Routing\DispatcherInterface;
use SplStack;

/**
 * Application
 * 管理着整个框架应用程序实例的各种运行。
 *
 * 同时可以初始化多个app，但只有第一个可以通过getApp()方法获取到，其他的需要自行管理。
 *
 */
class Application implements RequestHandlerInterface
{
	protected SplStack $middlewares;

	/**
	 * 应用程序初始化。
	 * @param RequestHandlerInterface|null $dispatcher
	 * @param ResponseEmitterInterface|null $responseEmitter
	 */
	public function __construct(protected ?RequestHandlerInterface $dispatcher = null,
								protected ?ResponseEmitterInterface $responseEmitter = null,
	)
	{
		$this->middlewares = new SplStack();
		if(!$this->dispatcher){
			$this->dispatcher = new DefaultDispatcher();
		}
		if(!$this->responseEmitter){
			$this->responseEmitter = new DefaultResponseEmitter();
		}
	}

	/**
	 * 主运行方法
	 *
	 * @param Request|null $request
	 * @throws Http\InvalidArgumentException
	 */
	public function run(?Request $request = null) : void
	{
		$this->responseEmitter->emit($this->handle($request ?? Request::fromGlobals()));
	}

	/**
	 * 添加新的 Middleware
	 * middleware 按照 stack 方式组织，后加入的先生效。形成洋葱结构。
	 *
	 * @param MiddlewareInterface $middleware
	 * @return Application
	 */
	public function addMiddleware(MiddlewareInterface $middleware): self
	{
		$this->middlewares->push($middleware);
		return $this;
	}

	public function handle(Request $request): Response
	{
		if($this->middlewares->isEmpty()){
			return $this->dispatcher->handle($request);
		}
		else{
			return $this->middlewares->pop()->process($request, $this);
		}
	}

}