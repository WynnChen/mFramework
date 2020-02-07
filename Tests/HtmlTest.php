<?php
use mFramework\Html;
use mFramework\Html\Document;
use mFramework\Html\Document\XhtmlDocument;

class HtmlTest extends PHPUnit\Framework\TestCase
{

	protected function setUp()
	{
		new mFramework\Html\Document\XhtmlDocument();
	}

	protected function tearDown()
	{
		mFramework\Html\Document::clearCurrent();
	}

	public function testNoDocumentException()
	{
		Document::clearCurrent();
		$this->expectException('mFramework\\Html\\Exception');
		Html::div();
	}

	public function testNormalElement()
	{
		$div = Html::div('text');
		$this->assertEquals('<div>text</div>', (string)$div);
		$div = Html::div(null);
		$this->assertEquals('<div></div>', (string)$div);
		$div = Html::div('');
		$this->assertEquals('<div></div>', (string)$div);
	}

	/**
	 * PHP5.6.0 实测：
	 * 在load了XHTML的之后，似乎自动认识了有关的HTML空元素，
	 * 同样的调用方式对于HTML的空元素和非空元素出来的结果是不一样的
	 * 但是对于HTML5又不认识了，真麻烦。
	 * 因此除非知道自己在做什么，不要直接用loadXML加载有doctype声明的html
	 */
	public function testEmptyElement()
	{
		$element = Html::br();
		$this->assertEquals('<br/>', (string)$element);
		$element = Html::hr();
		$this->assertEquals('<hr/>', (string)$element);
		$element = Html::div('');
		$this->assertEquals('<div></div>', (string)$element);
		$element = Html::p(null);
		$this->assertEquals('<p></p>', (string)$element);
	}

	public function testTextarea()
	{
		$element = Html::textarea('content');
		$this->assertEquals('<textarea name="content"></textarea>', (string)$element);
	}

	public function testImg()
	{
		$element = Html::img('srca', 'alta');
		$this->assertEquals('<img src="srca" alt="alta"/>', (string)$element);
	}

	public function testVar()
	{
		$element = Html::varElement('varvalue');
		$this->assertEquals('<var>varvalue</var>', (string)$element);
		$element = Html::varElement();
		$this->assertEquals('<var></var>', (string)$element);
	}

	public function testAlink()
	{
		$element = Html::alink('hrefa', Html::span('good'));
		$this->assertEquals('<a href="hrefa"><span>good</span></a>', (string)$element);
		$element = Html::alink('hrefa');
		$this->assertEquals('<a href="hrefa"></a>', (string)$element);
	}

	public function testForm()
	{
		$element = Html::form('posturl');
		$this->assertEquals('<form action="posturl" method="post"></form>', (string)$element);
	}

	public function testUploadForm()
	{
		$element = Html::UploadForm('posturl');
		$this->assertEquals('<form action="posturl" method="post" enctype="multipart/form-data"></form>', (string)$element);
	}

	public function testMaxFileSize()
	{
		$element = Html::maxFileSize('18623');
		$this->assertEquals('<input name="MAX_FILE_SIZE" type="hidden" value="18623"/>', (string)$element);
	}

	public function testInput()
	{
		$element = Html::input('some');
		$this->assertEquals('<input name="some" type="text"/>', (string)$element);
		$element = Html::input('some', 'hidden');
		$this->assertEquals('<input name="some" type="hidden"/>', (string)$element);
		$element = Html::input('some', 'file');
		$this->assertEquals('<input name="some" type="file"/>', (string)$element);
	}

	public function testCheckbox()
	{
		$element = Html::checkbox('some', 'va', 'good', true);
		$id = $element->input->id();
		$this->assertEquals('<span class="checkbox"><input name="some" type="checkbox" id="' . $id . '" value="va" checked="checked"/>' . '<label for="' . $id . '" class="follow">good</label></span>', (string)$element);
	}

	public function testRadio()
	{
		$element = Html::radio('some', 'va', 'good', true);
		$id = $element->input->id();
		$this->assertEquals('<span class="radio"><input name="some" type="radio" id="' . $id . '" value="va" checked="checked"/>' . '<label for="' . $id . '" class="follow">good</label></span>', (string)$element);
	}

	public function testSelect()
	{
		$element = Html::select('somename');
		$this->assertEquals('<select name="somename"/>', (string)$element);
		$element = Html::select('somename', '');
		$this->assertEquals('<select name="somename"></select>', (string)$element);
	}

	public function testButton()
	{
		$element = Html::button();
		$this->assertEquals('<button type="submit"></button>', (string)$element);
		$element = Html::button('some');
		$this->assertEquals('<button type="submit">some</button>', (string)$element);
	}

	public function testNormalTable()
	{
		$element = Html::normalTable();
		$this->assertEquals('<table cellspacing="0" cellpadding="0" border="0"></table>', (string)$element);
	}

	public function testFormatText()
	{
		$txt = 'line1
line2
line3';
		$element = Html::div(Html::formatText($txt));
		$this->assertEquals('<div>line1<br/>line2<br/>line3</div>', (string)$element);
	}

	public function testText()
	{
		$text = Html::text('abc');
		$this->assertInstanceOf('mFramework\\Html\\Text', $text);
		$this->assertEquals('abc', (string)$text);
	}
}
