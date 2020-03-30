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
namespace mFramework\View;

/**
 *
 * 纯文本 View
 *
 * @package mFramework
 * @author Wynn Chen
 *		
 */
class PlainTextDocument implements \mFramework\View
{
	protected $charset = 'utf-8';
	
	public function getCharset()
	{
		return $this->charset;
	}
	public function setCharset($charset)
	{
		$this->charset = $charset;
	}
	
	/**
	 * 逐个输出$data内容。
	 * {@inheritDoc}
	 * @see \mFramework\View::renderResponse()
	 */
	public function renderResponse(\mFramework\Http\Response $response, \mFramework\Map $data)
	{
		$response->setHeader('Content-type', 'text/plain; charset='.$this->charset);
		$response->setBody(implode('', (array)$data));
	}
}

class Exception extends \mFramework\Exception
{}
