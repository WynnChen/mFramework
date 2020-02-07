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
 * Html5 document
 *
 * @package mFramework
 * @author Wynn Chen
 *		
 */
class Html5Document extends \mFramework\Html\Document
{

	public function __construct($lang = 'zh-cn')
	{
		parent::__construct($lang);
		$this->head->insertBefore(Html::meta()->set('charset', 'utf-8'), $this->title);
		$this->documentElement->set('lang', $lang);
	}

	public function getBody()
	{
		$body = parent::getBody();
		return '<!DOCTYPE html>' . $body;
	}
	
	// {
	// $this->loadXML('<!DOCTYPE html><html lang="'.$lang.'"></html>');
	// $this->documentElement->appendChild($this->head = Html::head(
	// Html::meta()
	// ->set('charset', 'utf-8'),
	// ));
	// }
}
