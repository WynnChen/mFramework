<?php
declare(strict_types=1);

namespace mFramework;

use mFramework\Http\Request;
use mFramework\Http\Response;

/**
 * Handles a server request and produces a response.
 *
 * An HTTP request handler process an HTTP request in order to produce an
 * HTTP response.
 */
interface RequestHandlerInterface
{
	/**
	 * Handles a request and produces a response.
	 *
	 * May call other collaborating code to generate the response.
	 * @param Request $request
	 * @return Response
	 */
	public function handle(Request $request): Response;
}