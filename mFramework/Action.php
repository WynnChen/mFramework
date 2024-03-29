<?php
declare(strict_types=1);

namespace mFramework;

use mFramework\Http\Request;
use mFramework\Http\Response;
use mFramework\View\HttpNotFound;
use mFramework\View\HttpRedirect;

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
	/**
	 * @var Map 用于传递给View的数据内容。assign方法也是用于写入这里的
	 */
	protected Map $data;

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
	final public function handle(Request $request): Response
	{
		$this->plugin and $this->plugin->startHandle($this, $request);
		$result = $this->execute($request);
		$this->plugin and $this->plugin->afterExecute($this->data, $result);
		$response = $this->render($result);
		$this->plugin and $this->plugin->endHandle($response);
		return $response;
	}

	/**
	 * 子类默认运行模式。
	 * @param Request $request
	 * @return mixed
	 */
	protected function execute(Request $request)
	{
		//根据 $request 的 method 来决定跑什么
		return match ($request->getMethod()) {
			'GET' => $this->runGet($request),
			'POST' => $this->runPost($request),
			default => $this->run($request),
		};
	}

	/**
	 * @param mixed $result
	 * @return Response
	 * @throws ActionException
	 * @throws Http\InvalidArgumentException
	 */
	final protected function render(mixed $result): Response
	{
		if ($result instanceof Response) {
			return $result;
		}

		if ((string)$result === '') {
			$view = $this->getView();
			$response = $view->renderResponse($this->data);
			$this->plugin and $this->plugin->afterRender($view, $response);
			return $response;
		}

		return new Response(status: 200, body: (string)$result);
	}

	/**
	 * 本action的业务逻辑,GET方法时
	 *
	 * 如果返回Response实例，那么直接使用之；
	 * 如果返回为false或者null或''或void时会触发view渲染；
	 * 如果为其他内容，转为string后作为body生成status:200的response输出。
	 *
	 * @param Request $request
	 * @return Response|void|mixed
	 * @noinspection PhpMissingReturnTypeInspection
	 */
	protected function runGet(Request $request)
	{
		return $this->run($request);
	}

	/**
	 * 本action的业务逻辑,默认版本
	 *
	 * 如果返回Response实例，那么直接使用之；
	 * 如果返回为false或者null或''或void时会触发view渲染；
	 * 如果为其他内容，转为string后作为body生成status:200的response输出。
	 *
	 * @param Request $request
	 * @return Response|void|mixed
	 * @noinspection PhpMissingReturnTypeInspection
	 */
	protected function run(Request $request)
	{
		//子类一般实现这个方法。
		//return '';
	}

	/**
	 * 本action的业务逻辑,POST方法时
	 *
	 * 如果返回Response实例，那么直接使用之；
	 * 如果返回为false或者null或''或void时会触发view渲染；
	 * 如果为其他内容，转为string后作为body生成status:200的response输出。
	 *
	 * @param Request $request
	 * @return Response|void|mixed
	 * @noinspection PhpMissingReturnTypeInspection
	 */
	protected function runPost(Request $request)
	{
		return $this->run($request);
	}

	/**
	 * @return View
	 * @throws ActionException
	 */
	final protected function getView(): View
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
	final protected function setView(string|View $view): self
	{
		$this->view = $view;
		return $this;
	}

	protected function getDefaultView(): string
	{
		return substr_replace($this::class, 'View', -6);
	}

	/**
	 * 关联数据，随后给View用。
	 * @param iterable|string $name
	 * @param mixed $value
	 * @return Action
	 */
	final protected function assign(string|iterable $name, mixed $value = null): self
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

	final protected function getData(): Map
	{
		return $this->data;
	}

	/**
	 * 调用后可以直接return或者随后return，即，在 action 中代码类似于：
	 * return $this->redirect($url); //直接return，返回相应响应
	 * 或者：
	 * $this->redirect($url); //调用 View/Http/Redirect 这个view，放弃直接返回的那个response；
	 * return; //然后终止，不做后继处理了。
	 *
	 *
	 *
	 * @param $url
	 * @param int $code
	 * @param null $msg
	 * @return Response
	 * @throws Http\InvalidArgumentException
	 */
	protected function redirect($url, $code = 302, $msg = null):Response
	{
		$this->assign('_location', $url);
		$this->assign('_reason', $msg);
		$this->assign('_code', $code);
		$this->setView(HttpRedirect::class);
		return new Response(status: $code, headers: ['Location' => $url], reason: $msg);
	}

	/**
	 * @return Response
	 * @throws Http\InvalidArgumentException
	 */
	protected function notFound(): Response
	{
		$this->setView(HttpNotFound::class);
		return new Response(status:404); //todo 做个404页面
	}

}
