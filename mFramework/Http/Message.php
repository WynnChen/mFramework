<?php

declare(strict_types=1);

namespace mFramework\Http;

use mFramework\Func;


/**
 * 基于 Nyholm/Psr7
 *
 * implementing functionality common to requests and responses.
 *
 * 参照 PSR-7，以 PHP 8 的方式整理了接口签名
 *
 * HTTP messages consist of requests from a client to a server and responses
 * from a server to a client. This interface defines the methods common to
 * each.
 *
 * Messages are considered immutable; all methods that might change state MUST
 * be implemented such that they retain the internal state of the current
 * message and return an instance that contains the changed state.
 *
 * @link http://www.ietf.org/rfc/rfc7230.txt
 * @link http://www.ietf.org/rfc/rfc7231.txt
 *
 *
 * @author Michael Dowling and contributors to guzzlehttp/psr7
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Martijn van der Ven <martijn@vanderven.se>
 * @author Wynn Chen <wynn.chen@outlook.com>
 */
abstract class Message
{
	/** Map of all registered headers, as original name => array of values */
	protected array $headers = [];

	/** Map of lowercase header name => original name at registration */
	protected array $headerNames = [];

	protected string $protocol = '1.1';

	protected ?Stream $stream = null;

	/**
	 * Retrieves the HTTP protocol version as a string.
	 *
	 * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
	 *
	 * @return string HTTP protocol version.
	 */
	public function getProtocolVersion(): string
	{
		return $this->protocol;
	}

	/**
	 * Return an instance with the specified HTTP protocol version.
	 *
	 * The version string MUST contain only the HTTP version number (e.g.,
	 * "1.1", "1.0").
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that has the
	 * new protocol version.
	 *
	 * @param string $version HTTP protocol version
	 * @return static
	 */
	public function withProtocolVersion(string $version): static
	{
		if ($this->protocol === $version) {
			return $this;
		}

		$new = clone $this;
		$new->protocol = $version;

		return $new;
	}

	/**
	 * Retrieves all message header values.
	 *
	 * The keys represent the header name as it will be sent over the wire, and
	 * each value is an array of strings associated with the header.
	 *
	 *     // Represent the headers as a string
	 *     foreach ($message->getHeaders() as $name => $values) {
	 *         echo $name . ": " . implode(", ", $values);
	 *     }
	 *
	 *     // Emit headers iteratively:
	 *     foreach ($message->getHeaders() as $name => $values) {
	 *         foreach ($values as $value) {
	 *             header(sprintf('%s: %s', $name, $value), false);
	 *         }
	 *     }
	 *
	 * While header names are not case-sensitive, getHeaders() will preserve the
	 * exact case in which headers were originally specified.
	 *
	 * @return string[][] Returns an associative array of the message's headers. Each
	 *     key MUST be a header name, and each value MUST be an array of strings
	 *     for that header.
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * Checks if a header exists by the given case-insensitive name.
	 *
	 * @param $header
	 * @return bool Returns true if any header names match the given header
	 *     name using a case-insensitive string comparison. Returns false if
	 *     no matching header name is found in the message.
	 */
	public function hasHeader($header): bool
	{
		return isset($this->headerNames[Func::lowercase($header)]);
	}

	/**
	 * Retrieves a comma-separated string of the values for a single header.
	 *
	 * This method returns all of the header values of the given
	 * case-insensitive header name as a string concatenated together using
	 * a comma.
	 *
	 * NOTE: Not all header values may be appropriately represented using
	 * comma concatenation. For such headers, use getHeader() instead
	 * and supply your own delimiter when concatenating.
	 *
	 * If the header does not appear in the message, this method MUST return
	 * an empty string.
	 *
	 * @param string $header Case-insensitive header field name.
	 * @return string A string of values as provided for the given header
	 *    concatenated together using a comma. If the header does not appear in
	 *    the message, this method MUST return an empty string.
	 */
	public function getHeaderLine(string $header): string
	{
		return implode(', ', $this->getHeader($header));
	}

	/**
	 * Retrieves a message header value by the given case-insensitive name.
	 *
	 * This method returns an array of all the header values of the given
	 * case-insensitive header name.
	 *
	 * If the header does not appear in the message, this method MUST return an
	 * empty array.
	 *
	 * @param string $header Case-insensitive header field name.
	 * @return string[] An array of string values as provided for the given
	 *    header. If the header does not appear in the message, this method MUST
	 *    return an empty array.
	 */
	public function getHeader(string $header): array
	{
		$header = Func::lowercase($header);
		if (!isset($this->headerNames[$header])) {
			return [];
		}

		$header = $this->headerNames[$header];

		return $this->headers[$header];
	}

	/**
	 * Return an instance with the provided value replacing the specified header.
	 *
	 * While header names are case-insensitive, the casing of the header will
	 * be preserved by this function, and returned from getHeaders().
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that has the
	 * new and/or updated header and value.
	 *
	 * @param string $header Case-insensitive header field name.
	 * @param string|string[] $value Header value(s).
	 * @return static
	 * @throws InvalidArgumentException for invalid header names or values.
	 */
	public function withHeader(string $header, array|string $value): static
	{
		$value = $this->validateAndTrimHeader($header, $value);
		$normalized = Func::lowercase($header);

		$new = clone $this;
		if (isset($new->headerNames[$normalized])) {
			unset($new->headers[$new->headerNames[$normalized]]);
		}
		$new->headerNames[$normalized] = $header;
		$new->headers[$header] = $value;

		return $new;
	}

	/**
	 * Make sure the header complies with RFC 7230.
	 *
	 * Header names must be a non-empty string consisting of token characters.
	 *
	 * Header values must be strings consisting of visible characters with all optional
	 * leading and trailing whitespace stripped. This method will always strip such
	 * optional whitespace. Note that the method does not allow folding whitespace within
	 * the values as this was deprecated for almost all instances by the RFC.
	 *
	 * header-field = field-name ":" OWS field-value OWS
	 * field-name   = 1*( "!" / "#" / "$" / "%" / "&" / "'" / "*" / "+" / "-" / "." / "^"
	 *              / "_" / "`" / "|" / "~" / %x30-39 / ( %x41-5A / %x61-7A ) )
	 * OWS          = *( SP / HTAB )
	 * field-value  = *( ( %x21-7E / %x80-FF ) [ 1*( SP / HTAB ) ( %x21-7E / %x80-FF ) ] )
	 *
	 * @see https://tools.ietf.org/html/rfc7230#section-3.2.4
	 * @param string $header
	 * @param array|string $values
	 * @return array
	 * @throws InvalidArgumentException
	 */
	private function validateAndTrimHeader(string $header, array|string|int|float $values): array
	{
		if (preg_match("@^[!#$%&'*+.^_`|~0-9A-Za-z-]+$@", $header) !== 1) {
			throw new InvalidArgumentException('Header name must be an RFC 7230 compatible string.');
		}

		if (!is_array($values)) {
			// This is simple, just one value.
			if (preg_match("@^[ \t\x21-\x7E\x80-\xFF]*$@", (string)$values) !== 1) {
				throw new InvalidArgumentException('Header values must be RFC 7230 compatible strings.');
			}

			return [trim((string)$values, " \t")];
		}

		if (empty($values)) {
			throw new InvalidArgumentException('Header values must be a string or an array of strings, empty array given.');
		}

		// Assert Non empty array
		$returnValues = [];
		foreach ($values as $v) {
			if ((!is_numeric($v) && !is_string($v)) || 1 !== preg_match("@^[ \t\x21-\x7E\x80-\xFF]*$@", (string)$v)) {
				throw new InvalidArgumentException('Header values must be RFC 7230 compatible strings.');
			}

			$returnValues[] = trim((string)$v, " \t");
		}

		return $returnValues;
	}

	/**
	 * Return an instance with the specified header appended with the given value.
	 *
	 * Existing values for the specified header will be maintained. The new
	 * value(s) will be appended to the existing list. If the header did not
	 * exist previously, it will be added.
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that has the
	 * new header and/or value.
	 *
	 * @param string $header Case-insensitive header field name to add.
	 * @param string|string[] $value Header value(s).
	 * @return static
	 * @throws InvalidArgumentException for invalid header names or values.
	 */
	public function withAddedHeader(string $header, array|string $value): static
	{
		if (!is_string($header) || '' === $header) {
			throw new InvalidArgumentException('Header name must be an RFC 7230 compatible string.');
		}

		$new = clone $this;
		$new->setHeaders([$header => $value]);

		return $new;
	}

	/**
	 * @param array $headers
	 * @throws InvalidArgumentException
	 */
	protected function setHeaders(array $headers): void
	{
		foreach ($headers as $header => $value) {
			if (is_int($header)) {
				// If a header name was set to a numeric string, PHP will cast the key to an int.
				// We must cast it back to a string in order to comply with validation.
				$header = (string)$header;
			}
			$value = $this->validateAndTrimHeader($header, $value);
			$normalized = Func::lowercase($header);
			if (isset($this->headerNames[$normalized])) {
				$header = $this->headerNames[$normalized];
				$this->headers[$header] = array_merge($this->headers[$header], $value);
			} else {
				$this->headerNames[$normalized] = $header;
				$this->headers[$header] = $value;
			}
		}
	}

	/**
	 * Return an instance without the specified header.
	 *
	 * Header resolution MUST be done without case-sensitivity.
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that removes
	 * the named header.
	 *
	 * @param string $header Case-insensitive header field name to remove.
	 * @return static
	 */
	public function withoutHeader(string $header): static
	{
		$normalized = Func::lowercase($header);
		if (!isset($this->headerNames[$normalized])) {
			return $this;
		}

		$header = $this->headerNames[$normalized];
		$new = clone $this;
		unset($new->headers[$header], $new->headerNames[$normalized]);

		return $new;
	}

	/**
	 * Gets the body of the message.
	 *
	 * @return Stream Returns the body as a stream.
	 * @throws InvalidArgumentException
	 */
	public function getBody(): Stream
	{
		if (null === $this->stream) {
			$this->stream = Stream::create();
		}

		return $this->stream;
	}

	/**
	 * Return an instance with the specified message body.
	 *
	 * The body MUST be a StreamInterface object.
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return a new instance that has the
	 * new body stream.
	 *
	 * @param Stream $body Body.
	 * @return static
	 */
	public function withBody(Stream $body): static
	{
		if ($body === $this->stream) {
			return $this;
		}

		$new = clone $this;
		$new->stream = $body;

		return $new;
	}

}
