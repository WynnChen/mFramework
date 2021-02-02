<?php
declare(strict_types=1);

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
 */
abstract class Action implements RequestHandlerInterface
{
	private View|string|null $view = null;
	private bool $enable_view = true;

	private Map $data;

	public function __construct(private ?ActionPluginInterface $plugin = null)
	{
		$this->data = new Map();
	}

	/**
	 * 主执行方法。
	 *
	 * 如果 runXX() 返回了 Response 实例，直接使用，否则尝试调用 view 渲染
	 *
	 * @param Request $request
	 * @return Response
	 * @throws ActionException
	 * @throws Http\InvalidArgumentException
	 */
	public function handle(Request $request): Response
	{
		$this->plugin and $this->plugin->startHandle($this, $request);
		//根据 $request 的 method 来决定跑什么
		$result = match ($request->getMethod()) {
			'GET' => $this->runGet($request),
			'POST' => $this->runPost($request),
			default => $this->run($request),
		};
		$this->plugin and $this->plugin->afterRun($this->getData(), $result);

		if ($result instanceof Response) {
			$response = $result;
		} else {
			if ($this->isViewEnabled()) {
				$view = $this->getView();
				$response = $view->renderResponse($this->data);
				$this->plugin and $this->plugin->afterRender($view, $response);
			} else {
				$response = new Response(status: 200, body: $result);
			}
		}
		$this->plugin and $this->plugin->endHandle($response);
		return $response;
	}

	/**
	 * 本action的业务逻辑,GET方法时
	 *
	 * @param Request $request
	 * @return mixed Response实例或者对 Response 的 body 有效的所有类型。
	 * @noinspection PhpMissingReturnTypeInspection
	 */
	protected function runGet(Request $request)
	{
		return $this->run($request);
	}

	/**
	 * 本action的业务逻辑,默认版本
	 *
	 * @param Request $request
	 * @return mixed Response实例或者对 Response 的body有效的所有类型。
	 * @noinspection PhpMissingReturnTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	protected function run(Request $request)
	{
		//子类一般实现这个方法。
		return '';
	}

	/**
	 * 本action的业务逻辑,POST方法时
	 *
	 * @param Request $request
	 * @return mixed Response实例或者对 Response 的body有效的所有类型。
	 * @noinspection PhpMissingReturnTypeInspection
	 */
	protected function runPost(Request $request)
	{
		return $this->run($request);
	}

	protected function isViewEnabled(): bool
	{
		return $this->enable_view;
	}

	/**
	 * @return View
	 * @throws ActionException
	 */
	protected function getView(): View
	{
		$view = $this->view;

		if ($view === null) {
			$view = $this->getDefaultView();
		}

		if (is_string($view)) {
			if (!class_exists($view)) {
				throw new ActionException('View not found: ' . $view);
			}
			$view = new $view();
		}

		if (!$view instanceof View) {
			throw new ActionException('need string or View object');
		}

		return $view;
	}

	/**
	 * View的名称。应当是一个View实例类的名字，或View实例。
	 *
	 * 注意这里不能直接初始化，因为不肯定这个类一定存在。
	 * 逻辑上允许先设置一个事实上不存在的view，在随后的逻辑里再覆盖掉。
	 * setView()会自动 enableView();
	 *
	 * @param string|View $view
	 * @return Action
	 */
	protected function setView(string|View $view): self
	{
		$this->view = $view;
		$this->enable_view = true;
		return $this;
	}

	protected function getDefaultView(): string
	{
		return substr_replace($this::class, 'View', -6);
	}

	protected function disableView(): void
	{
		$this->enable_view = false;
	}

	protected function enableView(): void
	{
		$this->enable_view = true;
	}

	/**
	 * 关联数据，随后给View用。
	 * @param iterable|string $name
	 * @param mixed $value
	 * @return Action
	 */
	protected function assign(string|iterable $name, mixed $value = null): self
	{
		if (is_iterable($name)) {
			foreach ($name as $k => $v) {
				$this->data->offsetSet($k, $v);
			}
		} else {
			$this->data->offsetSet($name, $value);
		}
		return $this;
	}


	protected function getData(): Map
	{
		return $this->data;
	}
}
