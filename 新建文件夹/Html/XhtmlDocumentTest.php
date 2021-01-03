<?php
use mFramework\Html;
use mFramework\Html\Document\XhtmlDocument;
use mFramework\Map;

class XhtmlDocumentTest extends PHPUnit\Framework\TestCase
{

	public function testXhtmlDoucment()
	{
		$doc = new XhtmlDocument();
		
		$doc->append(Html::div('good'));
		
		$response = new \mFramework\Http\Response();
		$doc->renderResponse($response, new Map());
		
		$this->expectOutputString('HTTP/1.1 200 OK|Content-type: text/html; charset=utf-8|' . '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "dtd/xhtml1-strict.dtd">' . '<html xmlns="http://www.w3.org/1999/xhtml" lang="zh-cn" xml:lang="zh-cn">' . '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>' . '<meta http-equiv="Content-Language" content="zh-cn"/>' . '<title></title></head>' . '<body><div>good</div></body></html>' . "\n");
		$response->response();
	}
}
