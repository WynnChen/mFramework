<?php
declare(strict_types=1);
namespace mFramework\ResponseEmitter;

use mFramework\Http\Response;

interface ResponseEmitterInterface
{
	public function emit(Response $response): void;
}