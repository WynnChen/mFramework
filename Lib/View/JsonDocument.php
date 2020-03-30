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
 * json View
 *
 * @package mFramework
 * @author Wynn Chen
 *		
 */
class JsonDocument implements \mFramework\View
{
	protected $key;
	protected $options =  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
	protected $depth = 512;
	
	public function getKey()
	{
		return $this->key;
	}
	public function setKey($key)
	{
		$this->key = $key;
	}
	
	
	public function getOptions()
	{
		return $this->options;
	}
	/**
	 * $options for json_encode()
	 * @param int $options
	 */
	public function setOptions($options)
	{
		$this->options = $options;
	}
	
	public function getDepth()
	{
		return $this->depth;
	}
	
	/**
	 * $depth for json_encode()
	 * @param int $depth
	 */
	public function setDepth($depth)
	{
		$this->depth = $depth;
	}
	
	
	
	/**
	 * 一般 $data直接转json，除非调用 $this->setKey() 指定了特定key，那么改用 $data[$key]
	 * 
	 * {@inheritDoc}
	 * @see \mFramework\View::renderResponse()
	 */
	public function renderResponse(\mFramework\Http\Response $response, \mFramework\Map $data)
	{
		$response->setHeader('Content-type', 'application/json; charset=utf-8');
		$response->setBody(json_encode($this->key ? $data[$this->key] : $data, $this->options, $this->depth) );
	}
}

class Exception extends \mFramework\Exception
{}
