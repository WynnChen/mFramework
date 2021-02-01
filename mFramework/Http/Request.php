<?php
declare(strict_types=1);

namespace mFramework\Http;

use function array_key_exists;
use function array_keys;
use function current;
use function explode;
use function fopen;
use function function_exists;
use function in_array;
use function is_array;
use function is_int;
use function is_resource;
use function is_string;
use function is_string as is_stringAlias;
use function preg_match;
use function str_replace;
use function strtolower;
use function strtr;
use function substr;
use function trim;
use const UPLOAD_ERR_OK;

/**
 * 参考 PSR-7 的 request 方案。
 *
 * Per the HTTP specification, this interface includes properties for
 * each of the following:
 *
 * - Protocol version
 * - HTTP method
 * - URI
 * - Headers
 * - Message body
 *
 * Additionally, it encapsulates all data as it has arrived to the
 * application from the CGI and/or PHP environment, including:
 *
 * - The values represented in $_SERVER.
 * - Any cookies provided (generally via $_COOKIE)
 * - Query string arguments (generally via $_GET, or as parsed via parse_str())
 * - Upload files, if any (as represented by $_FILES)
 * - Deserialized body parameters (generally from $_POST)
 *
 * $_SERVER values MUST be treated as immutable, as they represent application
 * state at the time of request; as such, no methods are provided to allow
 * modification of those values. The other values provide such methods, as they
 * can be restored from $_SERVER or the request body, and may need treatment
 * during the application (e.g., body parameters may be deserialized based on
 * content type).
 *
 * Additionally, this interface recognizes the utility of introspecting a
 * request to derive and match additional parameters (e.g., via URI path
 * matching, decrypting cookie values, deserializing non-form-encoded body
 * content, matching authorization headers to users, etc). These parameters
 * are stored in an "attributes" property.
 *
 * Requests are considered immutable; all methods that might change state MUST
 * be implemented such that they retain the internal state of the current
 * message and return an instance that contains the changed state.
 *
 * @author Michael Dowling and contributors to guzzlehttp/psr7
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Martijn van der Ven <martijn@vanderven.se>
 *
 */
final class Request extends Message
{

	protected ?string $requestTarget = null;

	protected ?Uri $uri = null;

	private array $attributes = [];

	private array $cookieParams = [];

	private object|null|array $parsedBody = null;

	private array $queryParams = [];

	/** @var UploadedFile[] */
	private array $uploadedFiles = [];

	/**
	 * @param string $method HTTP method
	 * @param string|Uri $uri URI
	 * @param array $headers Request headers
	 * @param null $body Request body
	 * @param string $version Protocol version
	 * @param array $serverParams Typically the $_SERVER superglobal
	 * @param array $customParams 需要携带的自定义参数，给扩展用途使用，不是psr-7的内容。
	 * @throws InvalidArgumentException
	 */
	public function __construct(protected string $method,
								Uri|string $uri,
								array $headers = [],
								$body = null,
								string $version = '1.1',
								private array $serverParams = [],
								private array $customParams = [],
	)
	{
		if (!($uri instanceof Uri)) {
			$uri = new Uri($uri);
		}

		$this->uri = $uri;
		$this->setHeaders($headers);
		$this->protocol = $version;

		if (!$this->hasHeader('Host')) {
			$this->updateHostFromUri();
		}

		// If we got no body, defer initialization of the stream until ServerRequest::getBody()
		if ('' !== $body && null !== $body) {
			$this->stream = Stream::create($body);
		}
	}

	private function updateHostFromUri(): void
	{
		if ('' === $host = $this->uri->getHost()) {
			return;
		}

		if (null !== ($port = $this->uri->getPort())) {
			$host .= ':' . $port;
		}

		if (isset($this->headerNames['host'])) {
			$header = $this->headerNames['host'];
		} else {
			$this->headerNames['host'] = $header = 'Host';
		}

		// Ensure Host is the first header.
		// See: http://tools.ietf.org/html/rfc7230#section-5.4
		$this->headers = [$header => [$host]] + $this->headers;
	}

	/**
	 * Create a new server request from the current environment variables.
	 * Defaults to a GET request to minimise the risk of an \InvalidArgumentException.
	 * Includes the current request headers as supplied by the server through `getallheaders()`.
	 * If `getallheaders()` is unavailable on the current server it will fallback to its own `getHeadersFromServer()` method.
	 * Defaults to php://input for the request body.
	 *
	 * @throws InvalidArgumentException if no valid method or URI can be determined
	 */
	public static function fromGlobals(): Request
	{
		$server = $_SERVER;
		if (false === isset($server['REQUEST_METHOD'])) {
			$server['REQUEST_METHOD'] = 'GET';
		}

		$headers = function_exists('getallheaders') ? getallheaders() : self::getHeadersFromServer($_SERVER);

		$post = null;
		if ('POST' === self::getMethodFromEnv($server)) {
			foreach ($headers as $headerName => $headerValue) {
				if (strtolower($headerName) !== 'content-type') {
					continue;
				}
				$post = match (strtolower(trim(explode(';', $headerValue, 2)[0]))){
					'application/x-www-form-urlencoded' , 'multipart/form-data' => $_POST,
					default => null,
				};
			}
		}

		return self::fromArrays($server, $headers, $_COOKIE, $_GET, $post, $_FILES, fopen('php://input', 'r') ?: null);
	}

	/**
	 * Implementation from Zend\Diactoros\marshalHeadersFromSapi().
	 * Get parsed headers from ($_SERVER) array.
	 *
	 * @param array $server typically $_SERVER or similar structure
	 * @return array
	 */
	public static function getHeadersFromServer(array $server): array
	{
		$headers = [];
		foreach ($server as $key => $value) {
			// Apache prefixes environment variables with REDIRECT_
			// if they are added by rewrite rules
			if (str_starts_with($key, 'REDIRECT_')) {
				$key = substr($key, 9);

				// We will not overwrite existing variables with the
				// prefixed versions, though
				if (array_key_exists($key, $server)) {
					continue;
				}
			}

			if ($value && str_starts_with($key, 'HTTP_')) {
				$name = strtr(strtolower(substr($key, 5)), '_', '-');
				$headers[$name] = $value;

				continue;
			}

			if ($value && str_starts_with($key, 'CONTENT_')) {
				$name = 'content-' . strtolower(substr($key, 8));
				$headers[$name] = $value;

				continue;
			}
		}

		return $headers;
	}

	private static function getMethodFromEnv(array $environment): string
	{
		if (false === isset($environment['REQUEST_METHOD'])) {
			throw new \InvalidArgumentException('Cannot determine HTTP method');
		}

		return $environment['REQUEST_METHOD'];
	}

	/**
	 * Create a new server request from a set of arrays.
	 *
	 * @param array $server typically $_SERVER or similar structure
	 * @param array $headers typically the output of getallheaders() or similar structure
	 * @param array $cookie typically $_COOKIE or similar structure
	 * @param array $get typically $_GET or similar structure
	 * @param array|null $post typically $_POST or similar structure, represents parsed request body
	 * @param array $files typically $_FILES or similar structure
	 * @param null $body Typically stdIn
	 *
	 * @return Request
	 * @throws InvalidArgumentException
	 */
	public static function fromArrays(array $server,
									  array $headers = [],
									  array $cookie = [],
									  array $get = [],
									  ?array $post = null,
									  array $files = [],
									  $body = null): Request
	{
		$method = self::getMethodFromEnv($server);
		$uri = self::getUriFromEnvWithHTTP($server);
		$protocol = isset($server['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $server['SERVER_PROTOCOL']) : '1.1';

		$serverRequest = self::createServerRequest($method, $uri, $server);
		foreach ($headers as $name => $value) {
			// Because PHP automatically casts array keys set with numeric strings to integers, we have to make sure
			// that numeric headers will not be sent along as integers, as withAddedHeader can only accept strings.
			if (is_int($name)) {
				$name = (string)$name;
			}
			$serverRequest = $serverRequest->withAddedHeader($name, $value);
		}

		$serverRequest = $serverRequest
			->withProtocolVersion($protocol)
			->withCookieParams($cookie)
			->withQueryParams($get)
			->withParsedBody($post)
			->withUploadedFiles(self::normalizeFiles($files));

		if (null === $body) {
			return $serverRequest;
		}

		if (is_resource($body)) {
			$body = self::createStreamFromResource($body);
		} elseif (is_stringAlias($body)) {
			$body = self::createStream($body);
		} elseif (!$body instanceof Stream) {
			throw new InvalidArgumentException('The $body parameter to ServerRequestCreator::fromArrays must be string, resource or StreamInterface');
		}

		return $serverRequest->withBody($body);
	}

	/**
	 * @param array $environment
	 * @return Uri
	 * @throws InvalidArgumentException
	 */
	private static function getUriFromEnvWithHTTP(array $environment): Uri
	{
		$uri = self::createUriFromArray($environment);
		if (empty($uri->getScheme())) {
			$uri = $uri->withScheme('http');
		}

		return $uri;
	}

	/**
	 * Create a new uri from server variable.
	 *
	 * @param array $server typically $_SERVER or similar structure
	 * @return Uri
	 * @throws InvalidArgumentException
	 */
	private static function createUriFromArray(array $server): Uri
	{
		$uri = self::createUri();

		if (isset($server['HTTP_X_FORWARDED_PROTO'])) {
			$uri = $uri->withScheme($server['HTTP_X_FORWARDED_PROTO']);
		} else {
			if (isset($server['REQUEST_SCHEME'])) {
				$uri = $uri->withScheme($server['REQUEST_SCHEME']);
			} elseif (isset($server['HTTPS'])) {
				$uri = $uri->withScheme('on' === $server['HTTPS'] ? 'https' : 'http');
			}

			if (isset($server['SERVER_PORT'])) {
				$uri = $uri->withPort((int) $server['SERVER_PORT']);
			}
		}

		if (isset($server['HTTP_HOST'])) {
			if (1 === preg_match('/^(.+)\:(\d+)$/', $server['HTTP_HOST'], $matches)) {
				$uri = $uri->withHost($matches[1])->withPort($matches[2]);
			} else {
				$uri = $uri->withHost($server['HTTP_HOST']);
			}
		} elseif (isset($server['SERVER_NAME'])) {
			$uri = $uri->withHost($server['SERVER_NAME']);
		}

		if (isset($server['REQUEST_URI'])) {
			$uri = $uri->withPath(current(explode('?', $server['REQUEST_URI'])));
		}

		if (isset($server['QUERY_STRING'])) {
			$uri = $uri->withQuery($server['QUERY_STRING']);
		}

		return $uri;
	}

	/**
	 * @param string $uri
	 * @return Uri
	 * @throws InvalidArgumentException
	 */
	public static function createUri(string $uri = ''): Uri
	{
		return new Uri($uri);
	}

	/**
	 * @param string $method
	 * @param $uri
	 * @param array $serverParams
	 * @return Request
	 * @throws InvalidArgumentException
	 */
	public static function createServerRequest(string $method, Uri|string $uri, array $serverParams = []): Request
	{
		return new self($method, $uri, [], null, '1.1', $serverParams);
	}

	/**
	 * Create a new instance with the specified uploaded files.
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that has the
	 * updated body parameters.
	 *
	 * @param array $uploadedFiles An array tree of UploadedFileInterface instances.
	 * @return self
	 */
	public function withUploadedFiles(array $uploadedFiles): self
	{
		$new = clone $this;
		$new->uploadedFiles = $uploadedFiles;

		return $new;
	}

	/**
	 * Return an instance with the specified body parameters.
	 *
	 * These MAY be injected during instantiation.
	 *
	 * If the request Content-Type is either application/x-www-form-urlencoded
	 * or multipart/form-data, and the request method is POST, use this method
	 * ONLY to inject the contents of $_POST.
	 *
	 * The data IS NOT REQUIRED to come from $_POST, but MUST be the results of
	 * deserializing the request body content. Deserialization/parsing returns
	 * structured data, and, as such, this method ONLY accepts arrays or objects,
	 * or a null value if nothing was available to parse.
	 *
	 * As an example, if content negotiation determines that the request data
	 * is a JSON payload, this method could be used to create a request
	 * instance with the deserialized parameters.
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that has the
	 * updated body parameters.
	 *
	 * @param null|array|object $data The deserialized body data. This will
	 *     typically be in an array or object.
	 * @return self
	 * @throws InvalidArgumentException if an unsupported argument type is
	 *     provided.
	 */
	public function withParsedBody(object|array|null $data): self
	{
		$new = clone $this;
		$new->parsedBody = $data;

		return $new;
	}

	/**
	 * Return an instance with the specified query string arguments.
	 *
	 * These values SHOULD remain immutable over the course of the incoming
	 * request. They MAY be injected during instantiation, such as from PHP's
	 * $_GET superglobal, or MAY be derived from some other value such as the
	 * URI. In cases where the arguments are parsed from the URI, the data
	 * MUST be compatible with what PHP's parse_str() would return for
	 * purposes of how duplicate query parameters are handled, and how nested
	 * sets are handled.
	 *
	 * Setting query string arguments MUST NOT change the URI stored by the
	 * request, nor the values in the server params.
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that has the
	 * updated query string arguments.
	 *
	 * @param array $query Array of query string arguments, typically from
	 *     $_GET.
	 * @return self
	 */
	public function withQueryParams(array $query): self
	{
		$new = clone $this;
		$new->queryParams = $query;

		return $new;
	}

	public function withCustomParams(array $params): self
	{
		$new = clone $this;
		$new->customParams = $params;

		return $new;
	}

	/**
	 * Return an instance with the specified cookies.
	 *
	 * The data IS NOT REQUIRED to come from the $_COOKIE superglobal, but MUST
	 * be compatible with the structure of $_COOKIE. Typically, this data will
	 * be injected at instantiation.
	 *
	 * This method MUST NOT update the related Cookie header of the request
	 * instance, nor related values in the server params.
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that has the
	 * updated cookie values.
	 *
	 * @param array $cookies Array of key/value pairs representing cookies.
	 * @return self
	 */
	public function withCookieParams(array $cookies): self
	{
		$new = clone $this;
		$new->cookieParams = $cookies;

		return $new;
	}

	/**
	 * Return an UploadedFile instance array.
	 *
	 * @param array $files A array which respect $_FILES structure
	 *
	 * @return UploadedFile[]
	 *
	 * @throws InvalidArgumentException for unrecognized values
	 */
	private static function normalizeFiles(array $files): array
	{
		$normalized = [];

		foreach ($files as $key => $value) {
			if ($value instanceof UploadedFile) {
				$normalized[$key] = $value;
			} elseif (is_array($value) && isset($value['tmp_name'])) {
				$normalized[$key] = self::createUploadedFileFromSpec($value);
			} elseif (is_array($value)) {
				$normalized[$key] = self::normalizeFiles($value);
			} else {
				throw new InvalidArgumentException('Invalid value in files specification');
			}
		}

		return $normalized;
	}

	/**
	 * Create and return an UploadedFile instance from a $_FILES specification.
	 *
	 * If the specification represents an array of values, this method will
	 * delegate to normalizeNestedFileSpec() and return that return value.
	 *
	 * @param array $value $_FILES struct
	 *
	 * @return array|UploadedFile
	 * @throws InvalidArgumentException
	 */
	private static function createUploadedFileFromSpec(array $value): array|UploadedFile
	{
		if (is_array($value['tmp_name'])) {
			return self::normalizeNestedFileSpec($value);
		}

		try {
			$stream = self::createStreamFromFile($value['tmp_name']);
		} catch (RuntimeException $e) {
			$stream = self::createStream();
		}

		return self::createUploadedFile(
			$stream,
			(int)$value['size'],
			(int)$value['error'],
			$value['name'],
			$value['type']
		);
	}

	/**
	 * Normalize an array of file specifications.
	 *
	 * Loops through all nested files and returns a normalized array of
	 * UploadedFileInterface instances.
	 *
	 * @param array $files
	 * @return UploadedFile[]
	 * @throws InvalidArgumentException
	 */
	private static function normalizeNestedFileSpec(array $files = []): array
	{
		$normalizedFiles = [];

		foreach (array_keys($files['tmp_name']) as $key) {
			$spec = [
				'tmp_name' => $files['tmp_name'][$key],
				'size' => $files['size'][$key],
				'error' => $files['error'][$key],
				'name' => $files['name'][$key],
				'type' => $files['type'][$key],
			];
			$normalizedFiles[$key] = self::createUploadedFileFromSpec($spec);
		}

		return $normalizedFiles;
	}

	/**
	 * @param string $filename
	 * @param string $mode
	 * @return Stream
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	private static function createStreamFromFile(string $filename, string $mode = 'r'): Stream
	{
		$resource = @fopen($filename, $mode);
		if (false === $resource) {
			if ('' === $mode || false === in_array($mode[0], ['r', 'w', 'a', 'x', 'c'])) {
				throw new InvalidArgumentException('The mode ' . $mode . ' is invalid.');
			}

			throw new RuntimeException('The file ' . $filename . ' cannot be opened.');
		}

		return Stream::create($resource);
	}

	/**
	 * @param string $content
	 * @return Stream
	 * @throws InvalidArgumentException
	 */
	private static function createStream(string $content = ''): Stream
	{
		return Stream::create($content);
	}

	/**
	 * @param Stream $stream
	 * @param int|null $size
	 * @param int $error
	 * @param string|null $clientFilename
	 * @param string|null $clientMediaType
	 * @return UploadedFile
	 * @throws InvalidArgumentException
	 */
	private static function createUploadedFile(Stream $stream, int $size = null, int $error = UPLOAD_ERR_OK, string $clientFilename = null, string $clientMediaType = null): UploadedFile
	{
		if (null === $size) {
			$size = $stream->getSize();
		}

		return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
	}

	/**
	 * @param $resource
	 * @return Stream
	 * @throws InvalidArgumentException
	 */
	private static function createStreamFromResource($resource): Stream
	{
		return Stream::create($resource);
	}

	/**
	 * Retrieves the message's request target.
	 *
	 * Retrieves the message's request-target either as it will appear (for
	 * clients), as it appeared at request (for servers), or as it was
	 * specified for the instance (see withRequestTarget()).
	 *
	 * In most cases, this will be the origin-form of the composed URI,
	 * unless a value was provided to the concrete implementation (see
	 * withRequestTarget() below).
	 *
	 * If no URI is available, and no request-target has been specifically
	 * provided, this method MUST return the string "/".
	 *
	 * @return string
	 */
	public function getRequestTarget(): string
	{
		if (null !== $this->requestTarget) {
			return $this->requestTarget;
		}

		if ('' === $target = $this->uri->getPath()) {
			$target = '/';
		}
		if ('' !== $this->uri->getQuery()) {
			$target .= '?' . $this->uri->getQuery();
		}

		return $target;
	}

	/**
	 * Return an instance with the specific request-target.
	 *
	 * If the request needs a non-origin-form request-target — e.g., for
	 * specifying an absolute-form, authority-form, or asterisk-form —
	 * this method may be used to create an instance with the specified
	 * request-target, verbatim.
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that has the
	 * changed request target.
	 *
	 * @link http://tools.ietf.org/html/rfc7230#section-5.3 (for the various
	 *     request-target forms allowed in request messages)
	 * @param ?string $requestTarget
	 * @return self
	 * @throws InvalidArgumentException
	 */
	public function withRequestTarget(?string $requestTarget): self
	{
		if (preg_match('#\s#', $requestTarget)) {
			throw new InvalidArgumentException('Invalid request target provided; cannot contain whitespace');
		}

		$new = clone $this;
		$new->requestTarget = $requestTarget;

		return $new;
	}

	/**
	 * Retrieves the HTTP method of the request.
	 *
	 * @return string Returns the request method.
	 */
	public function getMethod(): string
	{
		return $this->method;
	}

	/**
	 * Return an instance with the provided HTTP method.
	 *
	 * While HTTP method names are typically all uppercase characters, HTTP
	 * method names are case-sensitive and thus implementations SHOULD NOT
	 * modify the given string.
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that has the
	 * changed request method.
	 *
	 * @param string $method Case-sensitive method.
	 * @return self
	 * @throws InvalidArgumentException for invalid HTTP methods.
	 */
	public function withMethod(string $method): self
	{
		if (!is_string($method)) {
			throw new InvalidArgumentException('Method must be a string');
		}

		$new = clone $this;
		$new->method = $method;

		return $new;
	}

	/**
	 * Retrieves the URI instance.
	 *
	 * This method MUST return a UriInterface instance.
	 *
	 * @link http://tools.ietf.org/html/rfc3986#section-4.3
	 * @return Uri Returns a UriInterface instance
	 *     representing the URI of the request.
	 */
	public function getUri(): Uri
	{
		return $this->uri;
	}

	/**
	 * Returns an instance with the provided URI.
	 *
	 * This method MUST update the Host header of the returned request by
	 * default if the URI contains a host component. If the URI does not
	 * contain a host component, any pre-existing Host header MUST be carried
	 * over to the returned request.
	 *
	 * You can opt-in to preserving the original state of the Host header by
	 * setting `$preserveHost` to `true`. When `$preserveHost` is set to
	 * `true`, this method interacts with the Host header in the following ways:
	 *
	 * - If the Host header is missing or empty, and the new URI contains
	 *   a host component, this method MUST update the Host header in the returned
	 *   request.
	 * - If the Host header is missing or empty, and the new URI does not contain a
	 *   host component, this method MUST NOT update the Host header in the returned
	 *   request.
	 * - If a Host header is present and non-empty, this method MUST NOT update
	 *   the Host header in the returned request.
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that has the
	 * new UriInterface instance.
	 *
	 * @link http://tools.ietf.org/html/rfc3986#section-4.3
	 * @param Uri $uri New request URI to use.
	 * @param bool $preserveHost Preserve the original state of the Host header.
	 * @return self
	 */
	public function withUri(Uri $uri, bool $preserveHost = false): self
	{
		if ($uri === $this->uri) {
			return $this;
		}

		$new = clone $this;
		$new->uri = $uri;

		if (!$preserveHost || !$this->hasHeader('Host')) {
			$new->updateHostFromUri();
		}

		return $new;
	}

	/**
	 * Retrieve server parameters.
	 *
	 * Retrieves data related to the incoming request environment,
	 * typically derived from PHP's $_SERVER superglobal. The data IS NOT
	 * REQUIRED to originate from $_SERVER.
	 *
	 * @return array
	 */
	public function getServerParams(): array
	{
		return $this->serverParams;
	}

	public function getServerParam(string $name, mixed $default = null)
	{
		return $this->serverParams[$name] ?? $default;
	}

	/**
	 * Retrieve normalized file upload data.
	 *
	 * This method returns upload metadata in a normalized tree, with each leaf
	 * an instance of Psr\Http\Message\UploadedFileInterface.
	 *
	 * These values MAY be prepared from $_FILES or the message body during
	 * instantiation, or MAY be injected via withUploadedFiles().
	 *
	 * @return array An array tree of UploadedFileInterface instances; an empty
	 *     array MUST be returned if no data is present.
	 */
	public function getUploadedFiles(): array
	{
		return $this->uploadedFiles;
	}

	/**
	 * Retrieve cookies.
	 *
	 * Retrieves cookies sent by the client to the server.
	 *
	 * The data MUST be compatible with the structure of the $_COOKIE
	 * superglobal.
	 *
	 * @return array
	 */
	public function getCookieParams(): array
	{
		return $this->cookieParams;
	}

	public function getCookieParam(string $name, mixed $default = null)
	{
		return $this->cookieParams[$name] ?? $default;
	}

	/**
	 * Retrieve query string arguments.
	 *
	 * Retrieves the deserialized query string arguments, if any.
	 *
	 * Note: the query params might not be in sync with the URI or server
	 * params. If you need to ensure you are only getting the original
	 * values, you may need to parse the query string from `getUri()->getQuery()`
	 * or from the `QUERY_STRING` server param.
	 *
	 * @return array
	 */
	public function getQueryParams(): array
	{
		return $this->queryParams;
	}

	public function getQueryParam(string $name, mixed $default = null)
	{
		return $this->queryParams[$name] ?? $default;
	}

	/**
	 * Retrieve any parameters provided in the request body.
	 *
	 * If the request Content-Type is either application/x-www-form-urlencoded
	 * or multipart/form-data, and the request method is POST, this method MUST
	 * return the contents of $_POST.
	 *
	 * Otherwise, this method may return any results of deserializing
	 * the request body content; as parsing returns structured content, the
	 * potential types MUST be arrays or objects only. A null value indicates
	 * the absence of body content.
	 *
	 * @return null|array|object The deserialized body parameters, if any.
	 *     These will typically be an array or object.
	 */
	public function getParsedBody(): object|array|null
	{
		return $this->parsedBody;
	}

	public function getPostParams(): array
	{
		return $this->method === 'POST' ? $this->parsedBody : [];
	}

	public function getPostParam(string $name, mixed $default = null)
	{
		if($this->method === 'POST'){
			return $this->parsedBody[$name] ?? $default;
		}else{
			return $default;
		}
	}

	public function getCustomParams(): array
	{
		return $this->customParams;
	}

	public function getCustomParam(string $name = '', mixed $default = null)
	{
		return $this->customParams[$name] ?? $default;
	}

	/**
	 * Retrieve attributes derived from the request.
	 *
	 * The request "attributes" may be used to allow injection of any
	 * parameters derived from the request: e.g., the results of path
	 * match operations; the results of decrypting cookies; the results of
	 * deserializing non-form-encoded message bodies; etc. Attributes
	 * will be application and request specific, and CAN be mutable.
	 *
	 * @return array Attributes derived from the request.
	 */
	public function getAttributes(): array
	{
		return $this->attributes;
	}

	/**
	 * Retrieve a single derived request attribute.
	 *
	 * Retrieves a single derived request attribute as described in
	 * getAttributes(). If the attribute has not been previously set, returns
	 * the default value as provided.
	 *
	 * This method obviates the need for a hasAttribute() method, as it allows
	 * specifying a default value to return if the attribute is not found.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $default Default value to return if the attribute does not exist.
	 * @return mixed
	 * @see getAttributes()
	 */
	public function getAttribute(string $attribute, $default = null): mixed
	{
		if (false === array_key_exists($attribute, $this->attributes)) {
			return $default;
		}

		return $this->attributes[$attribute];
	}

	/**
	 * Return an instance with the specified derived request attribute.
	 *
	 * This method allows setting a single derived request attribute as
	 * described in getAttributes().
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that has the
	 * updated attribute.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value of the attribute.
	 * @return static
	 * @see getAttributes()
	 */
	public function withAttribute(string $attribute, mixed $value): self
	{
		$new = clone $this;
		$new->attributes[$attribute] = $value;

		return $new;
	}

	/**
	 * Return an instance that removes the specified derived request attribute.
	 *
	 * This method allows removing a single derived request attribute as
	 * described in getAttributes().
	 *
	 * This method MUST be implemented in such a way as to retain the
	 * immutability of the message, and MUST return an instance that removes
	 * the attribute.
	 *
	 * @param string $attribute The attribute name.
	 * @return static
	 * @see getAttributes()
	 */
	public function withoutAttribute(string $attribute): self
	{
		if (false === array_key_exists($attribute, $this->attributes)) {
			return $this;
		}

		$new = clone $this;
		unset($new->attributes[$attribute]);

		return $new;
	}

	public function isGet():bool
	{
		return $this->method === 'GET';
	}

	public function isPost():bool
	{
		return $this->method === 'POST';
	}

}
