<?php
use mFramework\Html;
use mFramework\Html\Document\Html5Document;
use mFramework\Map;

class Html5DocumentTest extends PHPUnit\Framework\TestCase
{

	public function testXhtmlDoucment()
	{
		$doc = new Html5Document();
		
		$doc->append(Html::div('good'));
		
		$response = new \mFramework\Http\Response();
		$doc->renderResponse($response, new Map());
		
		$this->expectOutputString('HTTP/1.1 200 OK|Content-type: text/html; charset=utf-8|' . '<!DOCTYPE html>' . '<html lang="zh-cn">' . '<head><meta charset="utf-8"/>' . '<title></title></head>' . '<body><div>good</div></body></html>' . "\n");
		$response->response();
	}
}
