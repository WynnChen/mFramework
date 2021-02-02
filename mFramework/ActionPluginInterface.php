<?php
declare(strict_types=1);

namespace mFramework;

use mFramework\Http\Request;
use mFramework\Http\Response;

/**
 *
 * 针对 Action 的插件接口。
 *
 */
Interface ActionPluginInterface
{
	public function startHandle(Action $action, Request $request):void;
	public function afterRun(Map $data, mixed $result):void;
	public function afterRender(View $view, Response $response):void;
	public function endHandle(Response $response):void;
}
