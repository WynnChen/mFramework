<?php
declare(strict_types=1);
namespace mFramework\Routing;

use mFramework\Http\Request;

/**
 * Router
 */
Interface RouterInterface
{
	/**
	 * 解析 request，将必要的解析结果放在 $request 的 attribute 中，供后继（一般是dispatcher）使用。
	 *
	 * @param Request $request
	 * @return Request
	 * @throw RouteException route失败时
	 */
	public function route(Request $request): Request;
}
