<?php
declare(strict_types=1);
namespace mFramework\View;


use mFramework\Http\Response;
use mFramework\Map;
use mFramework\View;

class HttpRedirect implements View
{
	public function renderResponse(?Map $data = null): Response
	{
		$response = new Response(status:$data->_code ?: 302, headers: ['Location'=>$data->_location], reason: $data->_reason);
	}
}
