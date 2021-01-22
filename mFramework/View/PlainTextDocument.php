<?php
declare(strict_types=1);
namespace mFramework\View;

use mFramework\Http\Response;
use mFramework\Map;

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
	 * @param Map|null $data
	 * @return Response
	 * @throws \mFramework\Http\InvalidArgumentException
	 */
	public function renderResponse(?Map $data=null):Response
	{
		return new Response(headers:['Content-type' => 'text/plain; charset='.$this->charset],
			body: implode('', (array)$data));
	}
}

class Exception extends \mFramework\Exception
{}
