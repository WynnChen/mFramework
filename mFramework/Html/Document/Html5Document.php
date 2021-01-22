<?php
declare(strict_types=1);

namespace mFramework\Html\Document;

use \mFramework\Html;
use mFramework\Html\Document;

/**
 *
 * Html5 document
 *
 */
class Html5Document extends Document
{

	public function __construct($lang = 'zh-cn')
	{
		parent::__construct();
		$meta = $this->createElement('meta');
		$meta->setAttribute('charset', 'utf-8');
		$this->title->before($meta);
		$this->documentElement->setAttribute('lang', $lang);
	}

	public function getResponseBody(): string
	{
		$body = parent::getResponseBody();
		return '<!DOCTYPE html>' . $body;
	}

}
