<?php

namespace mFramework\Middleware;

use mFramework\Http\Response;
use mFramework\Http\Request;
use mFramework\Session;
use mFramework\RequestHandlerInterface;

/**
 * 自动开始session
 * @author Wynn Chen
 *
 */
class AutoStartSessionMiddleware implements MiddlewareInterface
{
	/**
	 * @param Request $request
	 * @param RequestHandlerInterface $app
	 * @return Response
	 */
	public function process(Request $request, RequestHandlerInterface $app): Response
	{
		Session::start();
		return $app->handle($request);
	}
}
