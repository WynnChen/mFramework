<?php
namespace mFramework\View;

use mFramework\Http\InvalidArgumentException;
use mFramework\Http\Response;
use mFramework\Map;
use mFramework\View;

/**
 *
 * json View
 */
class JsonDocument implements View
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
	 * @param Map|null $data
	 * @return Response
	 * @throws InvalidArgumentException
	 */
	public function renderResponse(?Map $data=null):Response
	{
		return new Response(
			headers: ['Content-type'=> 'application/json; charset=utf-8'],
			body: json_encode($this->key ? $data[$this->key] : $data, $this->options, $this->depth));
	}
}
