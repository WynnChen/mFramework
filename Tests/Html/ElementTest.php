<?php
use mFramework\Html;
use mFramework\Html\Document;
use mFramework\Html\Element;
use mFramework\Html\Document\XhtmlDocument;

class HtmlElementTest extends PHPUnit\Framework\TestCase
{

	protected function setUp()
	{
		new XHtmlDocument();
	}

	protected function tearDown()
	{
		Document::clearCurrent();
	}

	public function testNewElement()
	{
		$element = new Element('div');
		$element->append('good');
		$this->assertEquals('<div>good</div>', (string)$element);
		$element = new Element('div', 'good');
		$this->assertEquals('<div>good</div>', (string)$element);
		$element = new Element('div', new Element('br'));
		$this->assertEquals('<div><br/></div>', (string)$element);
		
		Document::clearCurrent();
		$this->expectException('mFramework\Html\Document\NoCurrentDocumentException');
		new Element('div');
	}

	public function testMagicAttributeAccess()
	{
		$element = new Element('div', 'good');
		$element->id('navbar');
		$this->assertEquals('navbar', $element->id());
		$element->id(null);
		$this->assertEmpty($element->id());
		$this->assertEquals('<div>good</div>', (string)$element);
	}

	public function testAttributeAccess()
	{
		$element = new Element('div', 'good');
		$element->set('id', 'navbar');
		$this->assertEquals('navbar', $element->get('id'));
		$element->del('id');
		$this->assertEmpty($element->get('id'));
		$this->assertEquals('<div>good</div>', (string)$element);
	}

	public function testAttributeArrayAccess()
	{
		$element = new Element('div', 'good');
		$element['id'] = 'navbar';
		$this->assertTrue(isset($element['id']));
		$this->assertEquals('navbar', $element['id']);
		unset($element['id']);
		$this->assertFalse(isset($element['id']));
		$this->assertEmpty($element['id']);
		$this->assertEquals('<div>good</div>', (string)$element);
	}

	public function testClass()
	{
		$element = new Element('div', 'good');
		$element->addClass('foo');
		$this->assertTrue($element->hasClass('foo'));
		$this->assertEquals('<div class="foo">good</div>', (string)$element);
		$element['class'] = 'foo bar notice';
		$this->assertTrue($element->hasClass('foo'));
		$this->assertFalse($element->hasClass('foobar'));
		$element->addClass('foo', 'some');
		$this->assertTrue($element->hasClass('some'));
		$element->removeClass('foo', 'bar');
		$this->assertTrue($element->hasClass('notice'));
		$this->assertTrue($element->hasClass('some'));
		$this->assertFalse($element->hasClass('foo'));
		$this->assertFalse($element->hasClass('bar'));
		$this->assertEquals('<div class="notice some">good</div>', (string)$element);
	}

	public function testAppend()
	{
		$element = new Element('div', 'good', 'bad');
		$element->append((new Element('span'))->append('ok'));
		$element->append((new Element('strong'))->append('important'), 'message', '!');
		$this->assertEquals('<div>goodbad<span>ok</span><strong>important</strong>message!</div>', (string)$element);
		$this->expectException('mFramework\Html\InvalidNodeException');
		$element->append(new SplDoublyLinkedList());
	}

	public function testPrepend()
	{
		$element = new Element('div', 'good', 'bad');
		$element->prepend((new Element('span'))->append('ok'));
		$element->prepend((new Element('strong'))->append('important'), 'message', '!');
		$this->assertEquals('<div><strong>important</strong>message!<span>ok</span>goodbad</div>', (string)$element);
		$this->expectException('mFramework\Html\InvalidNodeException');
		$element->append(new SplDoublyLinkedList());
	}

	public function testBeforeMe()
	{
		$element = new Element('div', 'good', 'bad');
		$element->append($e = (new Element('span'))->append('ok'));
		$e->beforeMe((new Element('strong'))->append('important'), 'message', '!');
		$this->assertEquals('<div>goodbad<strong>important</strong>message!<span>ok</span></div>', (string)$element);
		$this->expectException('mFramework\Html\InvalidNodeException');
		$e->beforeMe(new SplDoublyLinkedList());
	}

	public function testBeforeMe2()
	{
		$element = new Element('div', 'good', 'bad');
		$this->expectException('mFramework\Html\NeedParentException');
		$element->beforeMe((new Element('span'))->append('ok'));
	}

	public function testAfterMe()
	{
		$element = new Element('div', 'good', 'bad');
		$element->append($e = (new Element('span'))->append('ok'), new Element('br'));
		$e->afterMe((new Element('strong'))->append('important'), 'message', '!');
		$this->assertEquals('<div>goodbad<span>ok</span><strong>important</strong>message!<br/></div>', (string)$element);
		$this->expectException('mFramework\Html\InvalidNodeException');
		$e->afterMe(new SplDoublyLinkedList());
	}

	public function testAppendTo()
	{
		$e1 = new Element('div', 'good');
		$e2 = new Element('div', 'bad');
		$e2->appendTo($e1);
		$this->assertEquals('<div>good<div>bad</div></div>', (string)$e1);
	}

	public function testPrependTo()
	{
		$e1 = new Element('div', 'good');
		$e2 = new Element('div', 'bad');
		$e2->prependTo($e1);
		$this->assertEquals('<div><div>bad</div>good</div>', (string)$e1);
	}

	public function testInjectBefore()
	{
		$e1 = new Element('div', 'good');
		$e3 = new Element('section');
		$e3->append($e1);
		$e2 = new Element('div', 'bad');
		$e2->injectBefore($e1);
		$this->assertEquals('<section><div>bad</div><div>good</div></section>', (string)$e3);
	}

	public function testInjectAfter()
	{
		$e1 = new Element('div', 'good');
		$e3 = new Element('section');
		$e3->append($e1);
		$e2 = new Element('div', 'bad');
		$e2->injectAfter($e1);
		$this->assertEquals('<section><div>good</div><div>bad</div></section>', (string)$e3);
	}
	
	// 这个方法取消了，但是还是可以这样玩：
	public function testAppendChindrenTo()
	{
		$element = new Element('div', new Element('span', 1), new Element('span', 2, new Element('span', '2-1')));
		$element->set('class', 'fine');
		$box = new Element('section');
		$box->append(...$element->childNodes); // 等价于之前的appendChildrenTo()
		$this->assertEquals('<section><span>1</span><span>2<span>2-1</span></span></section>', (string)$box);
	}

	public function testRemove()
	{
		$element = new Element('div');
		$box = new Element('section', null);
		$box->append($element);
		$element->remove();
		$this->assertEquals('<section></section>', (string)$box);
	}

	public function testReplace()
	{
		$element1 = new Element('div', 1);
		$element2 = new Element('div', 2);
		$box = new Element('section', null);
		$box->append($element1);
		$element2->replace($element1);
		$this->assertEquals('<section><div>2</div></section>', (string)$box);
	}
}
