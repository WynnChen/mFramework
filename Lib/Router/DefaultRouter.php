<?php
/**
 * mFramework - a mini PHP framework
 * 
 * @package   mFramework
 * @version   v5
 * @copyright 2009-2016 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework\Router;

/**
 *
 * 系统的默认路由实现
 * 需要配合urlrewrite才能使用实际用于应用。
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
 * @package mFramework
 * @author Wynn Chen
 *		
 */
class DefaultRouter implements \mFramework\Router
{

	/**
	 * 尝试按照request的uri的内容进行路由。
	 * 这里不考虑最后的/问题，交给urlrewrite步骤去统一。
	 * 标准格式应当是不带最后的/的。
	 * 
	 * 返回的是action字符串，同时会把额外信息设置到$request中。
	 *
	 * @see \mFramework\Router::route()
	 * @param \mFramework\Request $request			
	 * @return string|false
	 */
	public function route(\mFramework\Http\Request $request)
	{
		$url = $request->getUri();
		
		if ($url and $url[0] != '/' and $url[0] != '\\') {
			$url = '/' . $url;
		}
		
		$url = parse_url($url, PHP_URL_PATH);
		$info = pathinfo($url);
		
		$params = ['' => null,'input' => null,'ext' => null];
		
		if (isset($info['extension']) or preg_match('/^\d+$/', $info['filename'])) {
			$params['input'] = $params[''] = $info['filename'];
			$params['ext'] = $info['extension'] ?? null;
			$path = $info['dirname'];
		} else {
			$path = rtrim($url, '/');
		}
		
		$request->setParameters($params);
		
		// 在windows上根目录会被表示为 \ 而不是 /。但子目录还是使用 / 分隔
		$action = ltrim($path, '/\\');
		return $action;
	}

	/**
	 * 逆向路由，从action与params组装出对应的 url path
	 * 注意根目录组装出来是 / ，不考虑IIS下的 \ 表示。
	 * 组装出来的路径带有开头的/但不带最后的/。
	 *
	 * 注意$params的格式，
	 * 'filename' : 只指定主文件名
	 * ['filename']: 只指定主文件名
	 * ['filename', 'html']:有主文件名和扩展名
	 * 注意不指定扩展名时扩展名默认为ext。
	 * 这里不做额外判断。
	 *
	 * 注意默认action是disptcher的问题，router不考虑。
	 *
	 * @see \mFramework\Route::reverseRoute()
	 */
	public function reverseRoute($action, $params = [], $query = [], $fragment = null)
	{
		$url = '/' . trim($action, '/\\'); // 万一有不标准表示法。
		
		if ($params) {
			$input = '';
			$ext = 'html';
			
			if (is_scalar($params)) {
				$input = $params;
			} else {
				$input = array_shift($params);
				$ext = array_shift($params) ?: $ext;
			}
			
			if ($input) {
				if ($url !== '/') {
					$url .= '/';
				}
				$url .= $input . '.' . $ext;
			}
		}
		
		$query = http_build_query((array)$query);
		$query and ($url .= '?' . $query);
		
		$fragment and ($url .= '#' . $fragment);
		
		return $url;
	}
}