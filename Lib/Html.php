<?php
/**
 * mFramework - a mini PHP framework
 * 
 * @package   mFramework
 * @version   v5
 * @copyright 2009-2016 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework;

use mFramework\Html\Document;
use mFramework\Html\Element\Input;
use mFramework\Html\Element\Select;

/**
 *
 * Html调用封装
 *
 * mFramework提供了一个基于DOM的模板/View方案。
 *
 * 由于DOM模块的限制，每次只能在“一个”文档上工作，且，必须在文档上。
 * 从模板的需求而言不是太大的问题。
 *
 * 在调用HTML相关方法之前保证有文档存在即可。
 * 最简单的保障方式就是只在Html\Document内部调用。
 *
 * 注意，调用 Html::tagname() 返回的元素输出时得到的字符串不是固定的，取决于所在的document的doctype。
 * 例如 Html::br()，标准xml文档将得到<br/>，doctype为html的将得到<br />；Html::div()，标准xml得到<div/>，html得到<div></div>
 *
 *
 * @package mFramework
 * @author Wynn Chen
 *		
 */
class Html
{

	public static function __callStatic($name, $args = null)
	{
		try {
			$doc = Html\Document::getCurrent();
		} catch (Html\Document\NoCurrentDocumentException $e) {
			throw new Html\Exception('need a document first.');
		}
		$node = $doc->createElement($name); // 不直接传递arg进去。
		$args and $node->append(...$args);
		return $node;
	}

	/**
	 * ** 某些常用pattern的快捷封装 ***
	 */
	
	// var是关键字，只好换个名字。
	public static function varElement(...$content)
	{
		if (!$content) {
			$content = [''];
		} // 空内容也保证不是空标签<var/>，保证<var></var>
		return self::__callStatic('var', $content);
	}

	public static function alink($href, ...$content)
	{
		if (!$content) {
			$content = [''];
		}
		return self::__callStatic('a', $content)->set('href', $href);
	}

	public static function form($action, ...$content)
	{
		if (!$content) {
			$content = [null];
		}
		return self::__callStatic('form', $content)->set('action', $action)->set('method', 'post');
	}

	public static function uploadForm($action, ...$content)
	{
		return self::form($action, ...$content)->set('enctype', 'multipart/form-data');
	}

	/**
	 * 客户端上传文件尺寸限制，单位byte
	 *
	 * @param int $max_size			
	 */
	public static function maxFileSize($max_size = 0)
	{
		return self::__callStatic('input')->set('name', 'MAX_FILE_SIZE')
			->set('type', 'hidden')
			->set('value', $max_size);
	}

	public static function input($name, $type = 'text')
	{
		return new Input($name, $type);
	}

	public static function checkbox($name, $value, $label, $checked = false, $id = null)
	{
		if (!$id) {
			$id = $name . '_' . mt_rand(0, 99) . uniqid();
		}
		$span = self::span()->addClass('checkbox');
		$span->input = self::input($name, 'checkbox')->id($id)
			->value($value)
			->appendTo($span);
		$span->label = self::label($label)->for($id)
			->addClass('follow')
			->appendTo($span);
		if ($checked) {
			$span->input->checked('checked');
		}
		return $span;
	}

	public static function radio($name, $value, $label, $checked = false, $required = false)
	{
		$id = $name . '_' . mt_rand(0, 99) . uniqid();
		$span = self::span()->addClass('radio');
		$span->input = self::input($name, 'radio')->id($id)
			->value($value)
			->appendTo($span);
		$span->label = self::label($label)->for($id)
			->addClass('follow')
			->appendTo($span);
		if ($checked) {
			$span->input->checked('checked');
		}
		if ($required) {
			$span->input->required('required');
		}
		return $span;
	}

	public static function select($name, ...$content)
	{
		return new Select($name, ...$content);
	}

	public static function button(...$content)
	{
		if (!$content) {
			$content = [null];
		}
		return self::__callStatic('button', $content)->set('type', 'submit');
	}

	public static function textarea($name, ...$content)
	{
		if (!$content) {
			$content = [''];
		}
		return self::__callStatic('textarea', $content)->set('name', $name);
	}

	public static function img($src, $alt = null)
	{
		return self::__callStatic('img')->set('src', $src)->set('alt', $alt);
	}

	public static function normalTable(...$content)
	{
		if (!$content) {
			$content = [''];
		}
		return self::__callStatic('table', $content)->set('cellspacing', 0)
			->set('cellpadding', 0)
			->set('border', 0);
	}

	public static function javascript($script)
	{
		return self::__callStatic('script', ['//',document::getCurrent()->createCDATASection("\n" . $script . "\n//")]);
	}

	public static function css($css)
	{
		return self::__callStatic('style', ['/*',document::getCurrent()->createCDATASection("*/\n" . $css . "\n/*"),'*/'])->set('type', 'text/css');
	}

	/**
	 *
	 * @return Html\Comment
	 */
	public static function IeConditionalComment($condition = 'IE', ...$content)
	{
		$frag = self::fragment(...$content);
		return self::comment("[if " . $condition . "]>" . Document::getCurrent()->saveXML($frag) . "<![endif]");
	}

	/**
	 *
	 * @return Html\Comment
	 */
	public static function comment($comment)
	{
		return Document::getCurrent()->createComment($comment);
	}

	/**
	 *
	 * @return Html\Text
	 */
	public static function text($text)
	{
		return Document::getCurrent()->createTextNode($text);
	}

	/**
	 *
	 * @return Html\Fragment
	 */
	public static function fragment(...$content)
	{
		$frag = Document::getCurrent()->createDocumentFragment();
		$frag->append(...$content);
		return $frag;
	}

	/**
	 *
	 * @param string $text			
	 * @return Html\Fragment
	 */
	public static function formatText($text)
	{
		$frag = Document::getCurrent()->createDocumentFragment();
		// $frag->appendXML(trim(nl2br(str_replace(' ', '&#160;',$text), true)));
		$array = [];
		foreach (explode("\n", $text) as $line) {
			$array[] = trim($line);
			$array[] = self::br();
		}
		array_pop($array);
		$frag->append(...$array);
		return $frag;
	}

	public static function svg($use = null)
	{
		$svg = self::__callStatic('svg');
		if ($use) {
			$svg->append(self::__callStatic('use', [''])->set('xlink:href', $use));
		}
		return $svg;
	}
}

