<?php
declare(strict_types=1);
namespace mFramework\View;


use mFramework\Http\Response;
use mFramework\Map;
use mFramework\View;

class HttpNotFound implements View
{
	public function renderResponse(?Map $data = null): Response
	{
		return new Response(404);
	}
}
