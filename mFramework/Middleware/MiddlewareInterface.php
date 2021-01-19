<?php

namespace mFramework\Middleware;

use mFramework\Http\Request;
use mFramework\Http\Response;
use mFramework\RequestHandlerInterface;

/**
 *
 * 大致参照 PSR15
 *
 * Participant in processing a server request and response.
 *
 * An HTTP middleware component participates in processing an HTTP message:
 * by acting on the request, generating the response, or forwarding the
 * request to a subsequent middleware and possibly acting on its response.
 *
 */
interface MiddlewareInterface
{
	/**
	 * Process an incoming server request.
	 *
	 * Processes an incoming server request in order to produce a response.
	 * If unable to produce the response itself, it may delegate to the provided
	 * request handler to do so.
	 *
	 * 一般这里大概需要这么写，来形成洋葱式的层层包裹结构：
	 * ```
	 * $this->before(); //先进行前处理
	 * $app->handle(); //调用后面的层次
	 * $this->after(); //后处理
	 * ```
	 *
	 * @param Request $request
	 * @param RequestHandlerInterface $app
	 * @return Response
	 */
	public function process(Request $request, RequestHandlerInterface $app): Response;
}
