<?php
declare(strict_types=1);

namespace mFramework\Html;

use DOMDocument;
use DOMDocumentFragment;

class Fragment extends DOMDocumentFragment
{
	use NodeTrait;

	public function __construct()
	{
		parent::__construct();
		libxml_use_internal_errors(true);
	}

	/**
	 * 附加HTML，HTML可能有问题时可以用。
	 *
	 * @param string $html
	 * @return self
	 * @throws Exception
	 */
	static public function fromHtml(string $html):self
	{
		/** @var self $frag */
		$frag = Document::getCurrent()->createDocumentFragment();

		if(!$frag){
			throw new Exception('Fail to create document fragment.');
		}

		if ($html == '') {
			$frag->append(''); // 随便放个什么保证不空。
			return $frag;
		}

		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->recover = true;
		$doc->preserveWhiteSpace = false;
		$doc->substituteEntities = false;
		$doc->strictErrorChecking = false;
		$doc->encoding = 'utf-8';

		libxml_use_internal_errors(true);

		// 避免标签冲突。
		$count = 0;
		$body_tag = 'body';
		$html_tag = 'html';
		while (str_contains($html, '</' . $body_tag)) {
			$count++;
			$body_tag = 'body' . $count;
		}
		while (str_contains($html, '</' . $html_tag)) {
			$count++;
			$html_tag = 'html' . $count;
		}
		$html_tag = '<' . $html_tag . '>';
		$body_tag_close = '</' . $body_tag . '>';
		$body_tag = '<' . $body_tag . '>';

		$doc->loadHTML($html_tag . '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><title>temp</title></head>' . $body_tag . $html,
			LIBXML_COMPACT | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOENT | LIBXML_NOERROR | LIBXML_NOWARNING);

		$html = $doc->saveXML();
		// 把包裹标记拿掉。
		$start = strpos($html, $body_tag) + strlen($body_tag);
		$end = strrpos($html, $body_tag_close);
		$html = substr($html, $start, $end - $start);
		//html现在是有效的
		$frag->appendXML($html);
		return $frag;
	}
}