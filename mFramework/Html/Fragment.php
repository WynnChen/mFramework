<?php
/**
 * mFramework - a mini PHP framework
 * 
 * Require PHP 7 since v4.0
 *
 * @package   mFramework
 * @version   4.0
 * @copyright 2009 Wynn Chen
 * @author	Wynn Chen <wynn.chen@outlook.com>
 */
namespace mFramework\Html;

/**
 *
 * document
 *
 * @package mFramework
 * @author Wynn Chen
 *		
 */
class Fragment extends \DOMDocumentFragment
{
	use NodeTrait;

	/**
	 * 附加格式良好的HTML。
	 * 如果格式有问题抛出异常，并不执行任何附加操作。
	 *
	 * @param string $xml			
	 * @throws InvalidHtmlException
	 * @return Fragment $this
	 */
	public function appendXML($xml)
	{
		if ($xml == '') {
			$this->append(''); // 随便放个什么保证不空。
			return $this;
		}
		libxml_use_internal_errors(true);
		if (!parent::appendXML($xml)) {
			throw new InvalidHtmlException('illegel raw XML(XHTML/HTML) data.');
		}
		return $this;
	}

	/**
	 * 附加HTML，HTML可能有问题时可以用。
	 *
	 * @param unknown $html			
	 * @throws InvalidHtmlException
	 * @return Fragment $this
	 */
	public function appendHTML($html)
	{
		if ($html == '') {
			$this->append(''); // 随便放个什么保证不空。
			return $this;
		}
		
		$doc = new \DOMDocument('1.0', 'utf-8');
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
		while (strpos($html, '</' . $body_tag) !== false) {
			$count++;
			$body_tag = 'body' . $count;
		}
		while (strpos($html, '</' . $html_tag) !== false) {
			$count++;
			$html_tag = 'html' . $count;
		}
		$html_tag = '<' . $html_tag . '>';
		$body_tag_close = '</' . $body_tag . '>';
		$body_tag = '<' . $body_tag . '>';
		
		$doc->loadHTML($html_tag . '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head>' . $body_tag . $html, LIBXML_COMPACT | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOENT | LIBXML_NOERROR | LIBXML_NOWARNING);
		// if(!$doc->loadHTML('<head>
		// <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		// </head>'.$html, LIBXML_COMPACT|LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD|LIBXML_NOENT|LIBXML_NOERROR|LIBXML_NOWARNING )){
		// throw new InvalidHtmlException('illegel (X)HTML data.');
		// }
		$html = $doc->saveXML();
		// 把包裹标记拿掉。
		$start = strpos($html, $body_tag) + strlen($body_tag);
		$end = strrpos($html, $body_tag_close);
		$html = substr($html, $start, $end - $start);
		// 合法格式了
		$this->appendXML($html);
		return $this;
	}
}