<?php


namespace mFramework\Routing;


use mFramework\Http\Request;
use mFramework\Http\Response;
use mFramework\RequestHandlerInterface;

interface DispatcherInterface extends RequestHandlerInterface
{
	/**
	 *
	 * @param Request $request
	 * @return Response
	 * @throw DispatchException 如果dispatch失败则抛出。
	 */
	public function handle(Request $request): Response;
}