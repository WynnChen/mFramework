<?php
declare(strict_types=1);

namespace mFramework\Http;

use function clearstatcache;
use function fclose;
use function feof;
use function fopen;
use function fread;
use function fseek;
use function fstat;
use function ftell;
use function fwrite;
use function is_resource;
use function is_string;
use function stream_get_contents;
use function stream_get_meta_data;
use function var_export;
use const SEEK_CUR;
use const SEEK_SET;

/**
 * 参照PSR-7，基于 Nyholm/Psr7
 *
 * 描述了数据流（data stream）
 *
 * 通常，本类实例是外包覆 PHP stream 。
 *
 */
final class Stream
{
	/** @var resource|null A resource reference */
	private $stream = null;

	private bool $seekable;

	private bool $readable;

	private bool $writable;
	/** @var array|mixed|void|null */
	private mixed $uri = null;

	private ?int $size = null;

	private function __construct()
	{
	}

	/**
	 * @param Stream|string|resource $body
	 * @return Stream
	 * @throws InvalidArgumentException
	 */
	public static function create($body = ''): Stream
	{
		if ($body instanceof Stream) {
			return $body;
		}

		if (is_string($body)) {
			$resource = fopen('php://temp', 'rw+');
			fwrite($resource, $body);
			$body = $resource;
		}

		if (is_resource($body)) {
			$new = new self();
			$new->stream = $body;
			$meta = stream_get_meta_data($new->stream);
			$new->seekable = $meta['seekable'] && (0 === fseek($new->stream, 0, SEEK_CUR));

			$new->readable = match($meta['mode']){
				'r', 'w+', 'r+', 'x+', 'c+', 'rb', 'w+b', 'r+b', 'x+b', 'c+b',
				'rt', 'w+t', 'r+t', 'x+t', 'c+t', 'a+' => true,
				default => false,
			};
			$new->writable = match($meta['mode']){
				'w', 'w+', 'rw', 'r+', 'x+', 'c+', 'wb', 'w+b', 'r+b', 'x+b', 'c+b',
				'w+t', 'r+t', 'x+t', 'c+t', 'a', 'a+' => true,
				default => false,
			};

			$new->uri = $new->getMetadata('uri');
			return $new;
		}

		throw new InvalidArgumentException('First argument to Stream::create() must be a string, resource or an instance of Stream.');
	}

	/**
	 * 获取 stream metadata，整个数组或者其中一个 key
	 *
	 * 数组格式和 PHP 的 stream_get_meta_data() 一致。
	 *
	 * @link http://php.net/manual/en/function.stream-get-meta-data.php
	 * @param ?string $key 指定 metadata
	 * @return mixed 如果不设置 $key 返回整个数组；设置 key 返回对应内容，key 无效返回 null
	 */
	public function getMetadata(?string $key = null): mixed
	{
		if (!isset($this->stream)) {
			return $key ? null : [];
		}

		$meta = stream_get_meta_data($this->stream);

		if (null === $key) {
			return $meta;
		}

		return $meta[$key] ?? null;
	}

	public function __destruct()
	{
		$this->close();
	}

	/**
	 * 关闭 stream，释放底层资源
	 *
	 */
	public function close(): void
	{
		if (isset($this->stream)) {
			if (is_resource($this->stream)) {
				fclose($this->stream);
			}
			$this->detach();
		}
	}

	/**
	 * 将底层资源和 stream 分离。
	 *
	 * detach 之后， stream 就处于不可用状态。
	 *
	 * @return resource|null 底层 PHP stream，如果有。
	 */
	public function detach()
	{
		if (!isset($this->stream)) {
			return null;
		}

		$result = $this->stream;
		unset($this->stream);
		$this->size = $this->uri = null;
		$this->readable = $this->writable = $this->seekable = false;

		return $result;
	}


	/**
	 * 将整个 stream 的所有数据读入一个 string 。
	 *
	 * 警告：这可能会耗费大量内存。
	 *
	 * 可能会抛出异常，PHP 7.4开始允许这样处理。
	 *
	 * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	public function __toString(): string
	{
		if ($this->isSeekable()) {
			$this->seek(0);
		}
		return $this->getContents();
	}

	/**
	 * stream is seekable ？
	 *
	 * @return bool
	 */
	public function isSeekable(): bool
	{
		return $this->seekable;
	}

	/**
	 * 定位到 stream 中的特定位置。
	 *
	 * @link http://www.php.net/manual/en/function.fseek.php
	 * @param int $offset Stream offset
	 * @param int $whence 指定基于 offset 计算光标的具体方式。有效值和 PHP 的 `fseek()` 的 $whence 一致。
	 *     SEEK_SET: 位置设置为等于 offset bytes
	 *     SEEK_CUR: 位置设置为 当前位置+offset
	 *     SEEK_END: 位置设置为 stream末端+offset
	 * @throws RuntimeException 失败时抛出
	 */
	public function seek(int $offset, int $whence = SEEK_SET): void
	{
		if (!$this->seekable) {
			throw new RuntimeException('Stream is not seekable');
		}

		if (fseek($this->stream, $offset, $whence) === -1) {
			throw new RuntimeException('Unable to seek to stream position ' . $offset . ' with whence ' . var_export($whence, true));
		}
	}

	/**
	 * Returns the remaining contents in a string
	 *
	 * @return string
	 * @throws RuntimeException if unable to read or an error occurs while
	 *     reading.
	 */
	public function getContents(): string
	{
		if (!isset($this->stream)) {
			throw new RuntimeException('Unable to read stream contents');
		}

		if (false === $contents = stream_get_contents($this->stream)) {
			throw new RuntimeException('Unable to read stream contents');
		}

		return $contents;
	}

	/**
	 * Get the size of the stream if known.
	 *
	 * @return int|null Returns the size in bytes if known, or null if unknown.
	 */
	public function getSize(): ?int
	{
		if ($this->size !== null) {
			return $this->size;
		}

		if (!isset($this->stream)) {
			return null;
		}

		// Clear the stat cache if the stream has a URI
		if ($this->uri) {
			clearstatcache(true, $this->uri);
		}

		$stats = fstat($this->stream);
		if (isset($stats['size'])) {
			$this->size = $stats['size'];

			return $this->size;
		}

		return null;
	}

	/**
	 * Returns the current position of the file read/write pointer
	 *
	 * @return int Position of the file pointer
	 * @throws RuntimeException on error.
	 */
	public function tell(): int
	{
		if (false === $result = ftell($this->stream)) {
			throw new RuntimeException('Unable to determine stream position');
		}

		return $result;
	}

	/**
	 * Returns true if the stream is at the end of the stream.
	 *
	 * @return bool
	 */
	public function eof(): bool
	{
		return !$this->stream || feof($this->stream);
	}

	/**
	 * Seek to the beginning of the stream.
	 *
	 * If the stream is not seekable, this method will raise an exception;
	 * otherwise, it will perform a seek(0).
	 *
	 * @throws RuntimeException on failure.
	 * @link http://www.php.net/manual/en/function.fseek.php
	 * @see seek()
	 */
	public function rewind(): void
	{
		$this->seek(0);
	}

	/**
	 * Returns whether or not the stream is writable.
	 *
	 * @return bool
	 */
	public function isWritable(): bool
	{
		return $this->writable;
	}

	/**
	 * Write data to the stream.
	 *
	 * @param string $string The string that is to be written.
	 * @return int Returns the number of bytes written to the stream.
	 * @throws RuntimeException on failure.
	 */
	public function write(string $string): int
	{
		if (!$this->writable) {
			throw new RuntimeException('Cannot write to a non-writable stream');
		}

		// We can't know the size after writing anything
		$this->size = null;

		if (false === $result = fwrite($this->stream, $string)) {
			throw new RuntimeException('Unable to write to stream');
		}

		return $result;
	}

	/**
	 * Returns whether or not the stream is readable.
	 *
	 * @return bool
	 */
	public function isReadable(): bool
	{
		return $this->readable;
	}

	/**
	 * Read data from the stream.
	 *
	 * @param int $length Read up to $length bytes from the object and return
	 *     them. Fewer than $length bytes may be returned if underlying stream
	 *     call returns fewer bytes.
	 * @return string Returns the data read from the stream, or an empty string
	 *     if no bytes are available.
	 * @throws RuntimeException if an error occurs.
	 */
	public function read(int $length): string
	{
		if (!$this->readable) {
			throw new RuntimeException('Cannot read from non-readable stream');
		}

		if (false === $result = fread($this->stream, $length)) {
			throw new RuntimeException('Unable to read from stream');
		}

		return $result;
	}
}
