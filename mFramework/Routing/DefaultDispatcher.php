<?php
declare(strict_types=1);
namespace mFramework\Routing;

use mFramework\Application;
use mFramework\Http\Request;
use mFramework\Http\Response;
use mFramework\RequestHandlerInterface;

/**
 *
 * mFramework 的默认 dispatcher ，一般和默认的 router 共同使用。
 * 本 dispatcher 根据 routing result 的 path 内容映射寻找对应的 action 并调用其 execute() 方法
 *
 * 将 $action 前后的 \ 或 / 去掉；中间的 / 替换为 _ ，加上Action后缀，得到类名。
 * 例如：
 * '' -> 'indexAction' //默认
 * 'list' -> 'listAction'
 * 'blog/post' -> 'blog_postAction'
 * 'blog/update/myfav' -> 'blog_update_myfavAction'
 *
 * 初始化时可以指定如果action没有的时候使用的默认action类名。
 *
 * $router 的 action 结果 存放在 $request->getAttribute('action') 中。
 *
 * 相应view名称的规则和action类似，例如：
 * '' -> 'indexView' //默认
 * 'list' -> 'listView'
 * 'blog/post' -> 'blog_postView'
 * 'blog/update/myfav' -> 'blog_update_myfavView'
 * 注意：默认view只是参考数据，可以在action中通过setView()方法另设。
 *
 */
class DefaultDispatcher implements DispatcherInterface
{
	/**
	 * @param string $default_action action为空时使用的默认的action名
	 * @param RouterInterface|null $router
	 */
	public function __construct(private string $default_action = 'indexAction',
								private ?RouterInterface $router = null)
	{
		if($this->router === null){
			$this->router = new DefaultRouter();
		}
	}

	/**
	 *
	 * 将传递进入的 $action 信息解析出相应的action类名。
	 *
	 * @param Request $request
	 * @return Response
	 * @throws DispatchException
	 * @throws RouteException
	 */
	public function handle(Request $request): Response
	{
		$request = $this->router->route($request);
		if(!$request){
			throw new RouteException('route 失败。');
		}

		$action =  $request->getAttribute('action'); //是个 path，不需要trim，router那边处理好了。
		if ($action === '' or $action === null) {
			$action_class = $this->default_action;
		}
		else{
			$action_class = str_replace(['/','\\'], '_', $action).'Action';
		}

		if(!class_exists($action_class)){
			throw new DispatchException('dispatch 失败，'.$action.' 对应的 '.$action_class.' 未找到。');
		}
		/** @var RequestHandlerInterface $obj */
		$obj = new $action_class();
		if(!$obj instanceof RequestHandlerInterface){
			throw new DispatchException('action 类 '.$action_class.' 必须实现 \mFramework\RequestHandlerInterface 接口');
		}
		return $obj->handle($request);
	}
}