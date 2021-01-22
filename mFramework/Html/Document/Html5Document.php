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
		$this->head->insertBefore(Html::meta()->set('charset', 'utf-8'), $this->title);
		$this->documentElement->setAttribute('lang', $lang);
	}

	public function getResponseBody(): string
	{
		$body = parent::getResponseBody();
		return '<!DOCTYPE html>' . $body;
	}

}
