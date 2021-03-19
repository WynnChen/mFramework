<?php
declare(strict_types=1);
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
	protected $headers = ['Content-type'=> 'application/json; charset=utf-8'];

	/**
	 * @return string[]
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 */
	public function getHeader($key)
	{
		return $this->headers[$key] ?? null;
	}

	/**
	 * @param array $headers
	 */
	public function setHeader($key, $value): static
	{
		if($value === null){
			unset($this->headers[$key]);
		}
		$this->headers[$key] = $value;
		return $this;
	}

	public function getKey()
	{
		return $this->key;
	}
	public function setKey($key): static
	{
		$this->key = $key;
		return $this;
	}
	
	
	public function getOptions()
	{
		return $this->options;
	}
	/**
	 * $options for json_encode()
	 * @param int $options
	 */
	public function setOptions($options): static
	{
		$this->options = $options;
		return $this;
	}
	
	public function getDepth()
	{
		return $this->depth;
	}
	
	/**
	 * $depth for json_encode()
	 * @param int $depth
	 */
	public function setDepth($depth): static
	{
		$this->depth = $depth;
		return $this;
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
			headers: $this->headers,
			body: json_encode($this->key ? $data[$this->key] : $data, $this->options, $this->depth));
	}

}
