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
namespace mFramework\Html\Document;

use \mFramework\Html;

/**
 *
 * XHtml document
 *
 * @package mFramework
 * @author Wynn Chen
 *		
 */
class XhtmlDocument extends \mFramework\Html\Document
{

	public function __construct($lang = 'zh-cn')
	{
		parent::__construct($lang);
		$this->head->insertBefore(Html::meta()->set('http-equiv', 'Content-Type')
			->set('content', 'text/html; charset=utf-8'), $this->title);
		$this->head->insertBefore(Html::meta()->set('http-equiv', 'Content-Language')
			->set('content', $lang), $this->title);
		$this->documentElement->set('xmlns', 'http://www.w3.org/1999/xhtml')
			->set('lang', $lang)
			->set('xml:lang', $lang);
	}

	public function getBody()
	{
		$body = parent::getBody();
		return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "dtd/xhtml1-strict.dtd">' . $body;
	}
	
	// {
	// $this->loadXML(
	// '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "dtd/xhtml1-strict.dtd">'.
	// '<html xmlns="http://www.w3.org/1999/xhtml" lang="'.$lang.'" xml:lang="'.$lang.'"></html>'
	// );
	//
	// }
}
