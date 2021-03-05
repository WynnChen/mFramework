<?php
declare(strict_types=1);

namespace mFramework;

use mFramework\Html\Document;
use mFramework\Html\Element;
use mFramework\Html\Element\Input;
use mFramework\Html\Element\Select;
use mFramework\Html\Fragment;

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
 * @method static html(...$contents):Element
 * metadata:
 * @method static head(...$contents):Element
 * @method static base():Element
 * @method static link():Element
 * @method static meta():Element
 * @method static style(...$contents):Element
 * @method static title(...$contents):Element
 * content root
 * @method static body(...$contents):Element
 * content sectioning
 * @method static address(...$contents):Element
 * @method static article(...$contents):Element
 * @method static aside(...$content):Element
 * @method static footer(...$contents):Element
 * @method static header(...$contents):Element
 * @method static h1(...$contents):Element
 * @method static h2(...$contents):Element
 * @method static h3(...$contents):Element
 * @method static h4(...$contents):Element
 * @method static h5(...$contents):Element
 * @method static h6(...$contents):Element
 * @method static hgroup(...$contents):Element
 * @method static main(...$contents):Element
 * @method static nav(...$contents):Element
 * @method static section(...$contents):Element
 * text content
 * @method static blockquote(...$contents):Element
 * @method static dd(...$contents):Element
 * @method static div(...$contents):Element
 * @method static dl(...$contents):Element
 * @method static dt(...$contents):Element
 * @method static figcaption(...$contents):Element
 * @method static figure(...$contents):Element
 * @method static hr():Element
 * @method static ol(...$contents):Element
 * @method static p(...$contents):Element
 * @method static pre(...$contents):Element
 * @method static ul(...$contents):Element
 * @method static li(...$contents):Element
 * inline text semantics
 * @method static abbr(...$contents):Element
 * @method static b(...$contents):Element
 * @method static bdi(...$contents):Element
 * @method static bdo(...$contents):Element
 * @method static br():Element
 * @method static cite(...$contents):Element
 * @method static code(...$contents):Element
 * @method static data(...$contents):Element
 * @method static dfn(...$contents):Element
 * @method static em(...$contents):Element
 * @method static i(...$contents):Element
 * @method static kbd(...$contents):Element
 * @method static mark(...$contents):Element
 * @method static q(...$contents):Element
 * @method static rb(...$contents):Element
 * @method static rp(...$contents):Element
 * @method static rt(...$contents):Element
 * @method static rtc(...$contents):Element
 * @method static ruby(...$contents):Element
 * @method static s(...$contents):Element
 * @method static samp(...$contents):Element
 * @method static small(...$contents):Element
 * @method static span(...$contents):Element
 * @method static strong(...$contents):Element
 * @method static sub(...$contents):Element
 * @method static time(...$contents):Element
 * @method static u(...$contents):Element
 * @method static var(...$contents):Element
 * @method static wbr(...$contents):Element
 * image and multimedia
 * @method static area(...$contents):Element
 * @method static audio(...$contents):Element
 * @method static map(...$contents):Element
 * @method static track(...$contents):Element
 * @method static video(...$contents):Element
 * embedded content
 * @method static embed(...$contents):Element
 * @method static iframe(...$contents):Element
 * @method static object(...$contents):Element
 * @method static param(...$contents):Element
 * @method static picture(...$contents):Element
 * @method static portal(...$contents):Element
 * @method static source(...$contents):Element
 * SVG and MathML
 * @method static math(...$contents):Element
 * Scripting
 * @method static canvas(...$contents):Element
 * @method static noscript(...$contents):Element
 * @method static script(...$contents):Element
 * Demarcating edits
 * @method static del(...$contents):Element
 * @method static ins(...$contents):Element
 * Table content
 * @method static caption(...$contents):Element
 * @method static col(...$contents):Element
 * @method static colgroup(...$contents):Element
 * @method static table(...$contents):Element
 * @method static tbody(...$contents):Element
 * @method static td(...$contents):Element
 * @method static tfoot(...$contents):Element
 * @method static th(...$contents):Element
 * @method static thead(...$contents):Element
 * @method static tr(...$contents):Element
 * Forms
 * @method static datalist(...$contents):Element
 * @method static fieldset(...$contents):Element
 * @method static label(...$contents):Element
 * @method static legend(...$contents):Element
 * @method static meter(...$contents):Element
 * @method static optgroup(...$contents):Element
 * @method static option(...$contents):Element
 * @method static output(...$contents):Element
 * @method static progress(...$contents):Element
 *
 */
class Html
{

	/**
	 * @param string $name
	 * @param array|null $args
	 * @return Element
	 * @throws Html\Exception
	 */
	public static function __callStatic(string $name, ?array $args = null):Element
	{
		$doc = Document::getCurrent();
		$node = $doc->createElement($name); // 不直接传递arg进去。
		if($args){
			$node->append(...$args);
		}
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

	public static function input($name = null, $type = 'text', $value = null)
	{
		return new Input($name, $type, $value);
	}

	public static function select($name, ...$content)
	{
		return new Select($name, ...$content);
	}

	public static function button(...$content)
	{
		return self::__callStatic('button', $content)->set('type', 'submit');
	}

	public static function textarea($name, ...$content)
	{
		$el = self::__callStatic('textarea', [''])->set('name', $name);
		$el->append(...$content);
		return $el;
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
	 * @param mixed ...$content
	 * @return Html\Fragment
	 * @throws Html\Exception
	 */
	public static function fragment(...$content):Fragment
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

}

