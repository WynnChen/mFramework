<?php
declare(strict_types=1);

namespace mFramework\Html\Document;

use \mFramework\Html;
use mFramework\Html\Document;

/**
 *
 * XHtml document
 *
 */
class XhtmlDocument extends Document
{

	public function __construct($lang = 'zh-cn')
	{
		parent::__construct();
		$meta = Html::meta();
		$meta->setAttribute('http-equiv', 'Content-Type');
		$meta->setAttribute('content', 'text/html; charset=utf-8');
		$this->title->before($meta);

		$meta = Html::meta();
		$meta->setArrribute('http-equiv', 'Content-Language');
		$meta->setArrribute('content', $lang);
		$this->title->before($meta);

		$this->documentElement->setAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
		$this->documentElement->setAttribute('lang', $lang);
		$this->documentElement->setAttribute('xml:lang', $lang);
	}

	public function getResponseBody(): string
	{
		$body = parent::getResponseBody();
		return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "dtd/xhtml1-strict.dtd">' . $body;
	}

}
