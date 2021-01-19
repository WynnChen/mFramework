<?php
declare(strict_types=1);

namespace mFramework\Http;

use function is_int;
use function is_string;
use function sprintf;

/**
 * 参照 PSR-7 和 PSR-15 的 API 设计。
 *
 * Representation of an outgoing, server-side response.
 *
 * Per the HTTP specification, this interface includes properties for
 * each of the following:
 *
 * - Protocol version
 * - Status code and reason phrase
 * - Headers
 * - Message body
 *
 * Responses are considered immutable; all methods that might change state MUST
 * be implemented such that they retain the internal state of the current
 * message and return an instance that contains the changed state.
 *
 * @author Michael Dowling and contributors to guzzlehttp/psr7
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Martijn van der Ven <martijn@vanderven.se>
 */
final class Response extends Message
{
	/** Map of standard HTTP status code/reason phrases */
	private const PHRASES = [
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-status',
		208 => 'Already Reported',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => 'Switch Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Time-out',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Large',
		415 => 'Unsupported Media Type',
		416 => 'Requested range not satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a teapot',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		425 => 'Unordered Collection',
		426 => 'Upgrade Required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',
		451 => 'Unavailable For Legal Reasons',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Time-out',
		505 => 'HTTP Version not supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		508 => 'Loop Detected',
		511 => 'Network Authentication Required'
	];

	private string $reasonPhrase;

	private int $statusCode;

	/**
	 * @param int $status Status code
	 * @param array $headers Response headers
	 * @param string|resource|Stream|null $body Response body
	 * @param string $version Protocol version
	 * @param string|null $reason Reason phrase (when empty a default will be used based on the status code)
	 * @throws InvalidArgumentException
	 */
	public function __construct(int $status = 200, array $headers = [], $body = null, string $version = '1.1', string $reason = null)
	{
		// If we got no body, defer initialization of the stream until Response::getBody()
		if ('' !== $body && null !== $body) {
			$this->stream = Stream::create($body);
		}

		$this->statusCode = $status;
		$this->setHeaders($headers);
		if (null === $reason && isset(self::PHRASES[$this->statusCode])) {
			$this->reasonPhrase = self::PHRASES[$status];
		} else {
			$this->reasonPhrase = $reason ?? '';
		}

		$this->protocol = $version;
	}

	/**
	 * Gets the response status code.
	 *
	 * The status code is a 3-digit integer result code of the server's attempt
	 * to understand and satisfy the request.
	 *
	 * @return int Status code.
	 */
	public function getStatusCode(): int
	{
		return $this->statusCode;
	}

	/**
	 * Gets the response reason phrase associated with the status code.
	 *
	 * Because a reason phrase is not a required element in a response
	 * status line, the reason phrase value MAY be null. Implementations MAY
	 * choose to return the default RFC 7231 recommended reason phrase (or those
	 * listed in the IANA HTTP Status Code Registry) for the response's
	 * status code.
	 *
	 * @link http://tools.ietf.org/html/rfc7231#section-6
	 * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
	 * @return string Reason phrase; must return an empty string if none present.
	 */

	public function getReasonPhrase(): string
	{
		return $this->reasonPhrase;
	}

	/**
	 * Return an instance with the specified status code and, optionally, reason phrase.
	 *
	 * If no reason phrase is specified, implementations MAY choose to default
	 * to the RFC 7231 or IANA recommended reason phrase for the response's
	 * status code.
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that has the
	 * updated status and reason phrase.
	 *
	 * @link http://tools.ietf.org/html/rfc7231#section-6
	 * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
	 * @param int $code The 3-digit integer result code to set.
	 * @param ?string $reasonPhrase The reason phrase to use with the
	 *     provided status code; if none is provided, implementations MAY
	 *     use the defaults as suggested in the HTTP specification.
	 * @return static
	 * @throws InvalidArgumentException For invalid status code arguments.
	 */
	public function withStatus(int $code, ?string $reasonPhrase = null): self
	{
		if (!is_int($code) && !is_string($code)) {
			throw new InvalidArgumentException('Status code has to be an integer');
		}

		$code = (int)$code;
		if ($code < 100 || $code > 599) {
			throw new InvalidArgumentException(sprintf('Status code has to be an integer between 100 and 599. A status code of %d was given', $code));
		}

		$new = clone $this;
		$new->statusCode = $code;
		if ((null === $reasonPhrase || '' === $reasonPhrase) && isset(self::PHRASES[$new->statusCode])) {
			$reasonPhrase = self::PHRASES[$new->statusCode];
		}
		$new->reasonPhrase = $reasonPhrase ?? '';

		return $new;
	}
}
