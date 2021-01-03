<?php
use mFramework\Html;
use mFramework\Html\Document;
use mFramework\Http\Response;
use mFramework\Map;

class testDocument extends mFramework\Html\Document
{

	public function getTitleNode()
	{
		return $this->title;
	}

	public function setTitle($str)
	{
		return parent::setTitle($str);
	}

	public function getTitle()
	{
		return parent::getTitle();
	}

	public function getHeadNode()
	{
		return $this->head;
	}
}

class someView extends mFramework\Html\Document
{

	protected function render(Map $data)
	{
		$this->useCss('/css/a.css', 'screen', 'lt8');
		$this->useCss('/css/b.css');
		$this->useCss('/css/c.css', null, true);
		$this->useJavascript('/js/a.js', true);
		$this->useJavascript('/js/b.js');
		$this->setTitle('de');
		$this->setTitle('abc' . $this->getTitle());
		$this->robotsMeta(true, false);
		$this->robotsMeta(true, true);
		$this->append(Html::p('everything is fine!'));
	}
}

class HtmlDocumentTest extends PHPUnit\Framework\TestCase
{

	protected function setUp():void
	{
		new testDocument();
	}

	protected function tearDown():void
	{
		Document::clearCurrent();
	}

	public function testCurrent()
	{
		$doc = Document::getCurrent();
		$this->assertEquals($doc, Document::getCurrent());
		$docA = new testDocument();
		$this->assertEquals($docA, Document::getCurrent());
		$doc->setAsCurrent();
		$this->assertEquals($doc, Document::getCurrent());
		Document::clearCurrent();
		$this->expectException('mFramework\\Html\\Document\\NoCurrentDocumentException');
		Document::getCurrent();
	}

	public function testCustomClass()
	{
		$doc = Document::getCurrent();
		$element = $doc->createElement('div');
		$this->assertInstanceOf('mFramework\\Html\\Element', $element);
		$fragment = $doc->createDocumentFragment();
		$this->assertInstanceOf('mFramework\\Html\\Fragment', $fragment);
		$text = $doc->createTextNode('text');
		$this->assertInstanceOf('mFramework\\Html\\Text', $text);
		$comment = $doc->createComment('comment');
		$this->assertInstanceOf('mFramework\\Html\\Comment', $comment);
	}

	public function testContainer()
	{
		$doc = Document::getCurrent();
		$doc->append(Html::div('good'));
		$this->assertEquals('<' . '?' . 'xml version="1.0" encoding="utf-8"?>' . "\n" . '<html><head><title></title></head><body><div>good</div></body></html>' . "\n", $doc->saveXML());
		$doc->prepend(Html::div('bad'));
		$this->assertEquals('<' . '?' . 'xml version="1.0" encoding="utf-8"?>' . "\n" . '<html><head><title></title></head><body><div>bad</div><div>good</div></body></html>' . "\n", $doc->saveXML());
	}

	public function testRender()
	{
		$doc = Document::getCurrent();
		$response = new \mFramework\Http\Response();
		$doc->renderResponse($response, new Map());
		
		$this->expectOutputString('HTTP/1.1 200 OK|Content-type: text/html; charset=utf-8|<html><head><title></title></head><body/></html>' . "\n");
		$response->response();
	}

	public function testTitleAccess()
	{
		$doc = Document::getCurrent();
		$doc->setTitle('abcde');
		$title = $doc->getTitleNode();
		$this->assertInstanceOf('mFramework\Html\Element', $title);
		$this->assertEquals($doc->getHeadNode(), $title->parentNode);
		$this->assertEquals('abcde', $doc->getTitle());
		$this->assertEquals('title', $title->tagName);
		$this->assertEquals('<title>abcde</title>', (string)$title);
		
		// replace
		$doc->setTitle('xyz');
		$title = $doc->getTitleNode();
		$this->assertInstanceOf('mFramework\Html\Element', $title);
		$this->assertEquals($doc->getHeadNode(), $title->parentNode);
		$this->assertEquals('xyz', $doc->getTitle());
		$this->assertEquals('title', $title->tagName);
		$this->assertEquals('<title>xyz</title>', (string)$title);
	}

	public function testAll()
	{
		$doc = new someView();
		$response = new \mFramework\Http\Response();
		$doc->renderResponse($response, new Map());
		
		$this->expectOutputString('HTTP/1.1 200 OK|Content-type: text/html; charset=utf-8|' . '<html><head><meta name="robots" content="INDEX, FOLLOW"/><title>abcde</title>' . '<link type="text/css" rel="stylesheet" href="/css/b.css"/>' . '<!--[if lt8]><link type="text/css" rel="stylesheet" href="/css/a.css" media="screen"/><![endif]-->' . '<!--[if IE]><link type="text/css" rel="stylesheet" href="/css/c.css"/><![endif]-->' . '<script type="text/javascript" src="/js/a.js"></script></head>' . '<body><p>everything is fine!</p><script type="text/javascript" src="/js/b.js"></script></body></html>' . "\n");
		$response->response();
	}
}
