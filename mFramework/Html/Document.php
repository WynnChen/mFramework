<?php
/**
 * mFramework - a mini PHP framework
 * 
 * @package   mFramework
 * @version   5.0
 * @copyright 2020 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework\Html;

use \mFramework\Html;
use mFramework\Http\Response;
use mFramework\Map;

/**
 *
 * document
 *
 * @package mFramework
 * @author Wynn Chen
 *		
 */
abstract class Document extends \DOMDocument implements \mFramework\View
{

	private static $current = null;

	/**
	 *
	 * @return Html_Document
	 */
	final public static function getCurrent()
	{
		if (self::$current === null) {
			throw new Document\NoCurrentDocumentException('No current document.');
		}
		return self::$current;
	}

	final static public function clearCurrent()
	{
		self::$current = null;
	}

	private $container;

	/**
	 *
	 * @var Element
	 */
	protected $head;

	/**
	 *
	 * @var Element
	 */
	protected $body;

	/**
	 *
	 * @var Element
	 */
	protected $title;

	private $robots = null;

	private $js = null;

	private $js_code = null;

	private $css = null;

	private $ie_css = null;

	/**
	 * 调用renderResponse()时设置，
	 * 在preRender(), render(), postRender()时可以用。
	 *
	 * @var Response
	 */
	protected $response;

	public function __construct($lang = 'zh-CN')
	{
		parent::__construct('1.0', 'utf-8');
		
		$this->setAsCurrent();
		
		$this->registerNodeClass('DOMDocumentFragment', '\mFramework\Html\Fragment');
		$this->registerNodeClass('DOMElement', '\mFramework\Html\Element');
		$this->registerNodeClass('DOMComment', '\mFramework\Html\Comment');
		$this->registerNodeClass('DOMText', '\mFramework\Html\Text');
		
		$this->recover = true;
		
		/*
		 * doctype似乎会影响输出时的格式，
		 * 例如是<br/>还是<br />，以及<div/>是否会自动展开为<div></div>
		 * 因此在这里不直接用loadXML来读取基本模板，以保证输出的一致性
		 */
		
		$this->encoding = 'utf-8';
		$this->appendChild($this->createElement('html'));
		
		$this->head = $this->documentElement->appendChild($this->createElement('head'));
		$this->body = $this->documentElement->appendChild($this->createElement('body'));
		$this->setContainer($this->body);
		
		$this->title = $this->createElement('title');
		$this->title->Append('');
		$this->title->appendTo($this->head);
	}
	
	protected function setTitle($text)
	{
		$title = $this->createElement('title');
		$title->replace($this->title);
		$title->appendChild($this->createTextNode($text)); //某些特殊字符可能会引发问题，需要用textnode包一下。
		$this->title = $title;
		return $this;
	}

	protected function getTitle()
	{
		return $this->title->textContent;
	}

	protected function setContainer(\DOMNode $node)
	{
		$this->container = $node;
	}
	protected function getContainer()
	{
		return $this->container;
	}

	public function prepend(...$children):void
	{
		$this->container->prepend(...$children);
	}

	public function append(...$children):void
	{
		$this->container->append(...$children);
	}

	protected function getHeader()
	{
		return array('Content-type' => 'text/html; charset=utf-8');
	}

	/**
	 */
	final public function setAsCurrent()
	{
		self::$current = $this;
	}

	/**
	 * 注意这里取得的是没有dtd的，从<html>开始。
	 */
	protected function getBody()
	{
		// css
		if ($this->css) {
			foreach ($this->css as $file => $media) {
				$this->head->appendChild(Html::link()->type('text/css')
					->rel('stylesheet')
					->href($file)
					->media($media));
			}
		}
		if ($this->ie_css) {
			foreach ($this->ie_css as $file => $info) {
				$this->head->appendChild(Html::IeConditionalComment($info[0], Html::link()->type('text/css')
					->rel('stylesheet')
					->href($file)
					->media($info[1])));
			}
		}
		// js
		if ($this->js) {
			foreach ($this->js as $file => $in_head) {
				$script = Html::script('')->set('type', 'text/javascript')->set('src', $file);
				if ($in_head) {
					$this->head->appendChild($script);
				} else {
					$this->body->appendChild($script);
				}
			}
		}
		if ($this->js_code) {
			foreach ($this->js_code as $in_head => $code_list) {
				foreach ($code_list as $code) {
					if ($in_head) {
						$this->head->appendChild($code);
					} else {
						$this->body->appendChild($code);
					}
				}
			}
		}
		// workaround.
		$html = $this->saveXML();
		$html = explode("\n", $html, 2)[1];
		// $html = strstr('<!DOCTYPE'); //xml version
		// $html = str_replace('<![CDATA[//]]>', '//', $html); //CDATA(Javascript block)
		return $html;
	}

	public function useCss($href, $media = null, $ie_condition = null)
	{
		if ($ie_condition) {
			if ($ie_condition === true) {
				$ie_condition = 'IE';
			}
			$this->ie_css[$href] = array($ie_condition,$media);
		} else {
			$this->css[$href] = $media;
		}
	}

	public function useJavascript($src, $in_head = false)
	{
		if ($src instanceof \DOMNode) {
			$this->js_code[(int)$in_head][] = $src;
		} else {
			$this->js[$src] = $in_head;
		}
	}

	protected function robotsMeta($index = true, $follow = true)
	{
		$content = ($index ? 'INDEX' : 'NOINDEX') . ', ' . ($follow ? 'FOLLOW' : 'NOFOLLOW');
		$node = Html::meta()->set('name', 'robots')->set('content', $content);
		if ($this->robots) {
			$node->replace($this->robots);
		} else {
			$this->robots = $node;
			$this->title->beforeMe($node);
		}
	}

	protected function preRender(?Map $data=null)
	{}

	protected function postRender(?Map $data=null)
	{}

	protected function render(?Map $data=null)
	{}

	/**
	 * 渲染response页面
	 * @param Map|null $data
	 * @return Response
	 * @throws \mFramework\Http\InvalidArgumentException
	 */
	public function renderResponse(?Map $data=null):Response
	{
		$this->preRender($data);
		$this->render($data);
		$this->postRender($data);
		return new Response(headers: $this->getHeader(), body:$this->getBody());
	}
}
namespace mFramework\Html\Document;

class Exception extends \mFramework\Html\Exception
{}

class NoCurrentDocumentException extends Exception
{}
