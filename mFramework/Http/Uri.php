<?php
declare(strict_types=1);

namespace mFramework\Http;

use mFramework\Func;
use function is_string;
use function ltrim;
use function parse_url;
use function preg_replace_callback;
use function rawurlencode;
use function sprintf;

/**
 * 参照PSR-7，基于 Nyholm/Psr7 的实现修改而来。
 *
 * 代表 URI 的值对象。
 *
 * 本类是为了表示 RFC 3986 所定义的 URI， 并针对大多数常用操作提供相应方法。
 *
 * 此类的实例视为不可变的；所有会改变其状态的方法都会维持当前实例的状态，
 * 而返回另一个包含改变后状态的实例。
 *
 * 在 request 的 message 中通常有 Host 头，而对于服务器端的 request，
 * 通常可以从服务器参数中得到 scheme 信息。
 *
 * @link http://tools.ietf.org/html/rfc3986 (the URI specification)
 */
final class Uri
{
	private const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';
	private const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

	private string $scheme = '';
	private string $userInfo = '';
	private string $host = '';
	private ?int $port = null;
	private string $path = '';
	private string $query = '';
	private string $fragment = '';

	/**
	 * Uri constructor.
	 * @param string $uri
	 * @throws InvalidArgumentException
	 */
	public function __construct(string $uri = '')
	{
		if ($uri !== '') {
			$parts = parse_url($uri);
			if ($parts === false) {
				throw new InvalidArgumentException("Unable to parse URI: $uri");
			}
			// Apply parse_url parts to a URI.
			$this->scheme = isset($parts['scheme']) ? Func::lowercase($parts['scheme']) : '';
			$this->userInfo = $parts['user'] ?? '';
			$this->host = isset($parts['host']) ? Func::lowercase($parts['host']) : '';
			$this->port = isset($parts['port']) ? $this->filterPort($parts['port']) : null;
			$this->path = isset($parts['path']) ? $this->filterPath($parts['path']) : '';
			$this->query = isset($parts['query']) ? $this->filterQueryAndFragment($parts['query']) : '';
			$this->fragment = isset($parts['fragment']) ? $this->filterQueryAndFragment($parts['fragment']) : '';
			if (isset($parts['pass'])) {
				$this->userInfo .= ':' . $parts['pass'];
			}
		}
	}

	/**
	 * @param $port
	 * @return int|null
	 * @throws InvalidArgumentException
	 */
	private function filterPort(int|string|null $port): ?int
	{
		if ($port === null) {
			return null;
		}

		$port = (int)$port;
		if (($port < 0) || ($port > 0xffff)) {
			throw new InvalidArgumentException(sprintf('Invalid port: %d. Must be between 0 and 65535', $port));
		}

		return self::isNonStandardPort($this->scheme, $port) ? $port : null;
	}

	/**
	 * Is a given port non-standard for the current scheme?
	 * @param string $scheme
	 * @param int $port
	 * @return bool
	 */
	private static function isNonStandardPort(string $scheme, int $port): bool
	{
		return match($scheme){
			'http' => ($port !== 80),
			'https' => ($port !== 443),
			default => true,
		};
	}

	private function filterPath(string $path): string
	{
		return preg_replace_callback(
			'/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/]++|%(?![A-Fa-f0-9]{2}))/',
			fn($match) => rawurlencode($match[0]),
			$path
		);
	}

	private function filterQueryAndFragment(string $str): string
	{
		return preg_replace_callback(
			'/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/',
			fn($match) => rawurlencode($match[0]),
			$str
		);
	}

	/**
	 * 返回对应的 URI 表示字符串。
	 *
	 * 根据URI对象中存在的组件不同，结果字符串可能是按 RFC 3986 Section 4.1 标准的完整URI或相对指向。
	 * 本方法会使用恰当的连接符连接不同组件。
	 *
	 * - If a scheme is present, it MUST be suffixed by ":".
	 * - If an authority is present, it MUST be prefixed by "//".
	 * - The path can be concatenated without delimiters. But there are two
	 *   cases where the path has to be adjusted to make the URI reference
	 *   valid as PHP does not allow to throw an exception in __toString():
	 *     - If the path is rootless and an authority is present, the path MUST
	 *       be prefixed by "/".
	 *     - If the path is starting with more than one "/" and no authority is
	 *       present, the starting slashes MUST be reduced to one.
	 * - If a query is present, it MUST be prefixed by "?".
	 * - If a fragment is present, it MUST be prefixed by "#".
	 *
	 * @see http://tools.ietf.org/html/rfc3986#section-4.1
	 * @return string
	 */
	public function __toString(): string
	{
		return self::createUriString($this->scheme, $this->getAuthority(), $this->path, $this->query, $this->fragment);
	}

	/**
	 * 从组件生成 URI 字符串。
	 * @param string $scheme
	 * @param string $authority
	 * @param string $path
	 * @param string $query
	 * @param string $fragment
	 * @return string
	 */
	private static function createUriString(string $scheme,
											string $authority,
											string $path,
											string $query,
											string $fragment): string
	{
		$uri = '';

		if ($scheme !== '') {
			$uri .= $scheme . ':';
		}

		if ($authority !== '') {
			$uri .= '//' . $authority;
		}

		if ($path !== '') {
			if ($path[0] !== '/') {
				if ($authority !== '') {
					// If the path is rootless and an authority is present, the path MUST be prefixed by "/"
					$path = '/' . $path;
				}
			} elseif (isset($path[1]) && ($path[1] === '/')) {
				if ($authority === '') {
					// If the path is starting with more than one "/" and no authority is present, the
					// starting slashes MUST be reduced to one.
					$path = '/' . ltrim($path, '/');
				}
			}
			$uri .= $path;
		}

		if ($query !== '') {
			$uri .= '?' . $query;
		}

		if ($fragment !== '') {
			$uri .= '#' . $fragment;
		}

		return $uri;
	}

	/**
	 * 解析 URI 的认证信息部分。
	 *
	 * 如果没有，返回空字符串。
	 *
	 * URI 的认证信息语法格式：
	 *
	 * <pre>
	 * [user-info@]host[:port]
	 * </pre>
	 *
	 * 如果端口组件未设置或是标准端口，则应当不包括端口部分。
	 *
	 * @see https://tools.ietf.org/html/rfc3986#section-3.2
	 * @return string URI 认证信息，格式为 "[user-info@]host[:port]"
	 */
	public function getAuthority(): string
	{
		if ($this->host === '') {
			return '';
		}

		$authority = $this->host;
		if ('' !== $this->userInfo) {
			$authority = $this->userInfo . '@' . $authority;
		}

		if (null !== $this->port) {
			$authority .= ':' . $this->port;
		}

		return $authority;
	}

	/**
	 * 解析 URI 的 scheme 部分。
	 *
	 * 如果没有 scheme 部分，返回空字符串。
	 *
	 * 按照 RFC 3986 Section 3.1，返回的值必须是全小写。
	 *
	 * 后面的 ":" 字符并非 scheme 的一部分，不应当包含。
	 *
	 * @see https://tools.ietf.org/html/rfc3986#section-3.1
	 * @return string URI 的 scheme
	 */
	public function getScheme(): string
	{
		return $this->scheme;
	}

	/**
	 * 解析 URI 的用户信息部分。
	 *
	 * 如果没有，返回空字符串。
	 *
	 * 如果 URI 中有用户部分，返回之。此外，如果还有 password，会接在用户值后面，中间用冒号 (":") 分隔。
	 *
	 * 后面的 "@" 字符不是用户信息的一部分，不包括。
	 *
	 * @return string URI 的用户信息部分，格式为 "username[:password]"
	 */
	public function getUserInfo(): string
	{
		return $this->userInfo;
	}

	/**
	 * 解析 URI 的 host 部分。
	 *
	 * 如果没有，返回空字符串。
	 *
	 * 按 RFC 3986 Section 3.2.2 所述，返回的值应当是全小写。
	 *
	 * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
	 * @return string URI 的 host 部分。
	 */
	public function getHost(): string
	{
		return $this->host;
	}

	/**
	 * 解析 URI 的 port 部分。
	 *
	 * 如果有 port 且其值不是当前 scheme 的默认 port，则返回其 integer 值。
	 * 如果 port 是当前 scheme 的默认 port，则返回 null。
	 *
	 * 如果没有 port，返回 null 值。
	 *
	 * @return null|int URI 的 port
	 */
	public function getPort(): ?int
	{
		return $this->port;
	}

	/**
	 * 解析 URI 的 path 部分。
	 *
	 * path 可以是空字符串或绝对路径（以 / 开头）或无根路径（不以 / 开头）。
	 *
	 * 按 RFC 7230 Section 2.7.3 定义，空路径 "" 和绝对路径 "/" 视为等同。
	 * 但本方法不会自动对此进行标准化操作，因为在有截断 base 路径的情况下（比如前端控制器）区别很大。
	 * 处理 "" 和 "/" 是调用方的工作。
	 *
	 * 返回的值使用 % 编码，但不双重编码。编码字符信息参考 RFC 3986, Sections 2 和 3.3。
	 *
	 * 例如，非路径分隔符的 "/" 字符必须使用 "%2F" 形式。
	 *
	 * @see https://tools.ietf.org/html/rfc3986#section-2
	 * @see https://tools.ietf.org/html/rfc3986#section-3.3
	 * @return string URI 的 path 部分。
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * 解析 URI 的 query string 部分。
	 *
	 * 如果没有 query string，返回空字符串。
	 *
	 * 前面的 "?" 不是 query 的一部分。
	 *
	 * 值必须用 % 编码，但不得双重编码。编码字符信息参考 RFC 3986, Sections 2 和 3.4.
	 *
	 * 例如，不是分隔符的 "&" 应当使用 "%26" 形式。
	 *
	 * @see https://tools.ietf.org/html/rfc3986#section-2
	 * @see https://tools.ietf.org/html/rfc3986#section-3.4
	 * @return string URI 的 query string 部分。
	 */
	public function getQuery(): string
	{
		return $this->query;
	}

	/**
	 * 解析 URI 的 fragment 部分。
	 *
	 * 如果没有 fragment，返回空字符串。
	 *
	 * 前面的 "#" 字符不是 fragment 的一部分。
	 *
	 * 值必须 % 编码，但不得双重编码。编码字符信息参考 RFC 3986, Sections 2 和 3.5.
	 *
	 * @see https://tools.ietf.org/html/rfc3986#section-2
	 * @see https://tools.ietf.org/html/rfc3986#section-3.5
	 * @return string URI 的 fragment 部分
	 */
	public function getFragment(): string
	{
		return $this->fragment;
	}

	/**
	 * 返回带有指定 scheme 的实例。
	 *
	 * 当前实例不会改变，返回带有新值的新实例。
	 *
	 * 指定 scheme 不区分大小写，内部全部使用小写标准格式。
	 *
	 * 输入空 scheme 表示移除 scheme 部分。
	 *
	 * @param string $scheme 新实例使用的 scheme
	 * @return self 带有指定 scheme 的新实例
	 * @throws InvalidArgumentException 端口异常时抛出，实际上不会抛出。
	 */
	public function withScheme(string $scheme): self
	{
		if ($this->scheme === $scheme = Func::lowercase($scheme)) {
			return $this;
		}

		$new = clone $this;
		$new->scheme = $scheme;
		$new->port = $new->filterPort($new->port);

		return $new;
	}

	/**
	 * 返回带有指定用户信息的实例。
	 *
	 * Password 可选，但 user 必须有。
	 * 空 user 字符串等价于移除用户信息。
	 *
	 * @param string $user 用户名
	 * @param ?string $password 和 $user 相应的密码。
	 * @return self 带着新用户信息的实例。
	 */
	public function withUserInfo(string $user, ?string $password = null): self
	{
		$info = $user;
		if (($password !== null) && ($password !== '')) {
			$info .= ':' . $password;
		}

		if ($this->userInfo === $info) {
			return $this;
		}

		$new = clone $this;
		$new->userInfo = $info;

		return $new;
	}

	/**
	 * 返回带着指定 host 的实例。
	 *
	 * 空 host 值等价于移除 host。
	 *
	 * @param string $host 新实例使用的 host
	 * @return static 带有指定 host 的新实例
	 */
	public function withHost(string $host): self
	{
		if ($this->host === $host = Func::lowercase($host)) {
			return $this;
		}

		$new = clone $this;
		$new->host = $host;

		return $new;
	}

	/**
	 * 返回带有指定 port 的实例。
	 *
	 * port 设为 null 值等价于移除 port 信息。
	 *
	 * @param null|int $port 新实例使用的 port 值
	 * @return self 带有指定 port 的实例
	 * @throws InvalidArgumentException 针对无效 port
	 */
	public function withPort(?int $port): self
	{
		if ($this->port === $port = $this->filterPort($port)) {
			return $this;
		}

		$new = clone $this;
		$new->port = $port;

		return $new;
	}

	/**
	 * 带有指定 path 的新实例
	 *
	 * path可以是空或者绝对路径或者无根路径。
	 *
	 * 如果想要让 path 是相对于域而非相对于路径，则必须用 "/" 开头。不以 "/" 开头的路径则假设是基于某个
	 * 应用程序或使用者所知的基础路径。
	 *
	 * 用户可以提供编码或未编码的路径，会自动处理成正确成编码格式。
	 *
	 * @param string $path 新实例使用的路径。
	 * @return static 带有指定路径的实例。
	 */
	public function withPath(string $path): self
	{
		if ($this->path === $path = $this->filterPath($path)) {
			return $this;
		}

		$new = clone $this;
		$new->path = $path;

		return $new;
	}

	/**
	 * 返回带有指定 query string 的实例。
	 *
	 * 用户可以提供编码或未编码的路径，会自动处理成正确成编码格式。
	 *
	 * 空查询字符串等价于移除查询字符串。
	 *
	 * @param string $query 新实例使用的 query string
	 * @return static 带有指定 query string 的新实例
	 */
	public function withQuery(string $query): self
	{
		if ($this->query === $query = $this->filterQueryAndFragment($query)) {
			return $this;
		}

		$new = clone $this;
		$new->query = $query;

		return $new;
	}

	/**
	 * 返回带有指定 URI fragment.
	 *
	 * 用户可以提供编码或未编码的路径，会自动处理成正确成编码格式。
	 *
	 *空 fragment 字符串等价于移除 fragment
	 *
	 * @param string $fragment 新实例使用的 fragment
	 * @return static 带着指定 fragment 的新实例
	 */
	public function withFragment(string $fragment): self
	{
		if ($this->fragment === $fragment = $this->filterQueryAndFragment($fragment)) {
			return $this;
		}

		$new = clone $this;
		$new->fragment = $fragment;

		return $new;
	}
}
