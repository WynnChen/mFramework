<?php


namespace mFramework\ResponseEmitter;

use mFramework\Http\Response;

use function connection_status;
use function header;
use function headers_sent;
use function in_array;
use function min;
use function sprintf;
use function strlen;
use function strtolower;

use const CONNECTION_NORMAL;

class DefaultResponseEmitter implements ResponseEmitterInterface
{
	private int $responseChunkSize;

	/**
	 * @param int $responseChunkSize
	 */
	public function __construct(int $responseChunkSize = 4096)
	{
		$this->responseChunkSize = $responseChunkSize;
	}

	/**
	 * Send the response the client
	 *
	 * @param Response $response
	 * @return void
	 */
	public function emit(Response $response): void
	{
		$isEmpty = $this->isResponseEmpty($response);
		if (headers_sent() === false) {
			$this->emitStatusLine($response);
			$this->emitHeaders($response);
		}

		if (!$isEmpty) {
			$this->emitBody($response);
		}
	}

	/**
	 * Emit Response Headers
	 *
	 * @param Response $response
	 */
	private function emitHeaders(Response $response): void
	{
		foreach ($response->getHeaders() as $name => $values) {
			$first = strtolower($name) !== 'set-cookie';
			foreach ($values as $value) {
				$header = sprintf('%s: %s', $name, $value);
				header($header, $first);
				$first = false;
			}
		}
	}

	/**
	 * Emit Status Line
	 *
	 * @param Response $response
	 */
	private function emitStatusLine(Response $response): void
	{
		$statusLine = sprintf(
			'HTTP/%s %s %s',
			$response->getProtocolVersion(),
			$response->getStatusCode(),
			$response->getReasonPhrase()
		);
		header($statusLine, true, $response->getStatusCode());
	}

	/**
	 * Emit Body
	 *
	 * @param Response $response
	 */
	private function emitBody(Response $response): void
	{
		$body = $response->getBody();
		if ($body->isSeekable()) {
			$body->rewind();
		}

		$amountToRead = (int) $response->getHeaderLine('Content-Length');
		if (!$amountToRead) {
			$amountToRead = $body->getSize();
		}

		if ($amountToRead) {
			while ($amountToRead > 0 && !$body->eof()) {
				$length = min($this->responseChunkSize, $amountToRead);
				$data = $body->read($length);
				echo $data;

				$amountToRead -= strlen($data);

				if (connection_status() !== CONNECTION_NORMAL) {
					break;
				}
			}
		} else {
			while (!$body->eof()) {
				echo $body->read($this->responseChunkSize);
				if (connection_status() !== CONNECTION_NORMAL) {
					break;
				}
			}
		}
	}

	/**
	 * Asserts response body is empty or status code is 204, 205 or 304
	 *
	 * @param Response $response
	 * @return bool
	 */
	public function isResponseEmpty(Response $response): bool
	{
		if (in_array($response->getStatusCode(), [204, 205, 304], true)) {
			return true;
		}
		$stream = $response->getBody();
		$seekable = $stream->isSeekable();
		if ($seekable) {
			$stream->rewind();
		}
		return $seekable ? $stream->read(1) === '' : $stream->eof();
	}
}