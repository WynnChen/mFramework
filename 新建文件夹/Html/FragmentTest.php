<?php
use mFramework\Html;
use mFramework\Html\Document;
use mFramework\Html\Element;
use mFramework\Html\Document\XhtmlDocument;

class FragmentTest extends PHPUnit\Framework\TestCase
{

	protected function setUp():void
	{
		new XHtmlDocument();
	}

	protected function tearDown():void
	{
		Document::clearCurrent();
	}

	public function testAppendXML()
	{
		$frag = Html::fragment();
		$frag->appendXML(null);
		$frag->appendXML('<span>s</span><strong>a<em><img src="abc"/></em>我和你</strong>');
		$element = new Element('div', $frag);
		$this->assertEquals('<div><span>s</span><strong>a<em><img src="abc"/></em>我和你</strong></div>', (string)$element);
		$this->expectException('mFramework\Html\InvalidHtmlException');
		$frag->appendXML('<span><img src="abc"></strong>');
	}

	public function testAppendHTML()
	{
		$frag = Html::fragment();
		$frag->appendHTML('<span>s</span><strong>a<em><img src="abc"/></em>拉拉</strong>');
		$element = new Element('div', $frag);
		$this->assertEquals('<div><span>s</span><strong>a<em><img src="abc"/></em>拉拉</strong></div>', (string)$element);
	}

	public function testAppendHTML2()
	{
		$frag = Html::fragment();
		$frag->appendHTML('<span><img src="abc"></strong>'); // 不匹配的结束标签
		$element = new Element('div', $frag);
		$this->assertEquals('<div><span><img src="abc"/></span></div>', (string)$element);
	}

	public function testAppendHTML3()
	{
		$frag = Html::fragment();
		$frag->appendHTML('<span><img src="abc"><b><i>a</b></i></strong>');
		$element = new Element('div', $frag);
		$this->assertEquals('<div><span><img src="abc"/><b><i>a</i></b></span></div>', (string)$element);
	}

	public function testAppendHTML4()
	{
		$frag = Html::fragment();
		$frag->appendHTML('');
		$element = new Element('div', $frag);
		$this->assertEquals('<div></div>', (string)$element);
	}

	public function testAppendHTML5()
	{
		$frag = Html::fragment();
		$frag->appendHTML('我123<span></head></body1></html>abc');
		$element = new Element('div', $frag);
		$this->assertEquals('<div>我123<span>abc</span></div>', (string)$element);
	}
}
