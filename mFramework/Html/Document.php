<?php
declare(strict_types=1);

namespace mFramework\Html;

use DOMDocument;
use DOMNode;
use \mFramework\Html;
use mFramework\Http\InvalidArgumentException;
use mFramework\Http\Response;
use mFramework\Map;
use mFramework\View;

abstract class Document extends DOMDocument implements View
{
	private static ?self $current = null;

	/**
	 * @return Document
	 * @throws Exception
	 */
	final public static function getCurrent():self
	{
		if (self::$current === null) {
			throw new Exception('No current document.');
		}
		return self::$current;
	}

	private Element $container;
	protected Element $head;
	protected Element $body;
	protected Element $title;

	private ?Element $robots = null;

	private array $js = [];

	private array $js_code = [];

	private array $css = [];

	/**
	 * Document constructor.
	 * @throws Exception
	 */
	public function __construct()
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

		/** @var Element $html_el */
		$html_el = $this->createElement('html');
		$this->appendChild($html_el);//这时候还没有container，不能append()
		$this->setContainer($html_el);

		/** @var Element $head */
		$head = $this->createElement('head');
		/** @var Element $body */
		$body = $this->createElement('body');
		$html_el->append($head, $body);
		$this->head = $head;
		$this->body = $body;
		
		$this->setContainer($this->body);

		/** @var Element $title */
		$title = $this->createElement('title', '-');
		$head->append($title);
		$this->title = $title;
	}

	/**
	 * @param string $text
	 */
	protected function setTitle(string $text): void
	{
		$t = $this->createElement('title', $text);
		//$this->title->replaceWith($t) 工作不如预期，bug?
		$this->title->before($t);
		$this->title->remove();
		$this->title = $t;
	}

	/**
	 * @param Element $node
	 * @throws Exception
	 */
	protected function setContainer(Element $node)
	{
		if($node->ownerDocument !== $this){
			throw new Exception('Container element must belong to this document.');
		}
		$this->container = $node;
	}

	protected function getContainer():Element
	{
		return $this->container;
	}

	/**
	 * 实际上是针对container节点操作。
	 * @param mixed ...$children
	 */
	public function prepend(...$children):void
	{
		$this->container->prepend(...$children);
	}

	/**
	 * 实际上是针对container节点操作。
	 * @param mixed ...$children
	 */
	public function append(...$children):void
	{
		$this->container->append(...$children);
	}

	/**
	 * @return string[]
	 * @noinspection PhpArrayShapeAttributeCanBeAddedInspection
	 */
	protected function getResponseHeader():array
	{
		return array('Content-type' => 'text/html; charset=utf-8');
	}

	final public function setAsCurrent()
	{
		self::$current = $this;
	}

	/**
	 * 注意这里取得的是没有dtd的，从<html>开始。
	 */
	protected function getResponseBody()
	{
		// css
		foreach ($this->css as $file => $media) {
			$this->head->appendChild(Html::link()->type('text/css')
				->rel('stylesheet')
				->href($file)
				->media($media));
		}

		// js
		foreach ($this->js as $file => $in_head) {
			$script = Html::script('')->set('type', 'text/javascript')->set('src', $file);
			if ($in_head) {
				$this->head->appendChild($script);
			} else {
				$this->body->appendChild($script);
			}
		}

		foreach ($this->js_code as $in_head => $code_list) {
			foreach ($code_list as $code) {
				if ($in_head) {
					$this->head->appendChild($code);
				} else {
					$this->body->appendChild($code);
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

	public function useCss($href, $media = null)
	{
		$this->css[$href] = $media;
	}

	public function useJavascript($src, $in_head = false)
	{
		if ($src instanceof DOMNode) {
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
			$this->title->before($node);
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
	 * @throws InvalidArgumentException
	 */
	public function renderResponse(?Map $data=null):Response
	{
		$this->preRender($data);
		$this->render($data);
		$this->postRender($data);
		return new Response(headers: $this->getResponseHeader(), body:$this->getResponseBody());
	}
}
