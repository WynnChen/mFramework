<?php
declare(strict_types=1);
namespace mFramework\Routing;


use mFramework\Http\Request;

/**
 *
 * 系统的默认路由实现
 * 需要配合 urlrewrite 才能使用实际用于应用。
 *
 * 对于 /a/b/c/x-y-z.html 的这样的路径，路径部分作为action，文件名部分url携带的输入参数。
 * 最后一段作为文件名处理（param信息）还是目录处理（action信息）看是否有扩展名。
 * /a/b/c/ 与 /a/b/c 判定为无文件名部分，action为a/b/c；
 * /a/b/c.html 判定为有文件名，action为/a/b,文件名为c.html
 * 文件名的主文件名部分存储于$request的parameter['']与parameter['input']中。
 * 扩展名信息存储于$request的parameter['ext']中。
 *
 * 例外情况是最后一段为纯数字时，依然判定为param，例如 /a/b/12，action为/a/b，param为12
 *
 * route不为文件名内部负责，如果需要携带多个参数需要自行组装/分拆。
 *
 */
class  DefaultRouter implements RouterInterface
{

	/**
	 * 尝试按照request的uri的内容进行路由。
	 * 这里不考虑最后的/问题，交给urlrewrite步骤去统一。
	 * 标准格式应当是不带最后的/的。
	 *
	 * 设定 的attribute params 包括如下内容:
	 * path: 请求的路径标准化后的结果，可供 dispatcher 使用
	 * input 或 ''(空字符串): 即文件名的主文件名部分
	 * ext: 文件名的扩展名信息。
	 *
	 * attribute param "action" 为 $params['path'] 的内容，方便调取。
	 *
	 *
	 * @param Request $request
	 * @return Request
	 * @see \mFramework\RouterInterface::route()
	 */
	public function route(Request $request):Request
	{
		$path = $request->getUri()->getPath();
		$path = trim(urldecode($path), '/\\');
		$info = pathinfo($path);

		$action = $path;
		$params = ['' => null, 'input' => null, 'ext' => null];
		
		if (isset($info['extension']) or preg_match('/^\d+$/', $info['filename'])) {
			$params['input'] = $params[''] = $info['filename'];
			$params['ext'] = $info['extension'] ?? null;
			$action = $info['dirname'];
		}

		if($action == '.'){
			$action = '';
		}
		return $request->withAttribute('action', $action)->withCustomParams($params);
	}

}