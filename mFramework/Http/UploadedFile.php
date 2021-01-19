<?php

declare(strict_types=1);

namespace mFramework\Http;

use function fopen;
use function is_resource;
use function is_string;
use function move_uploaded_file;
use function rename;
use function sprintf;
use const PHP_SAPI;
use const UPLOAD_ERR_CANT_WRITE;
use const UPLOAD_ERR_EXTENSION;
use const UPLOAD_ERR_FORM_SIZE;
use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_NO_TMP_DIR;
use const UPLOAD_ERR_OK;
use const UPLOAD_ERR_PARTIAL;

/**
 * 参照PSR-7，基于 Nyholm/Psr7 的实现修改而来。
 *
 * 代表 HTTP request 中上传的文件的值对象。
 *
 * 此类的实例为不可变，各个方法会维持当前实例的状态并返回带有新值的新实例。
 */
final class UploadedFile
{
	private ?string $clientFilename = null;

	private ?string $clientMediaType = null;

	private int $error;

	private ?string $file = null;

	private bool $moved = false;

	private int $size;

	private ?Stream $stream = null;

	/**
	 * @param resource|Stream|string $streamOrFile
	 * @param int $size
	 * @param int $errorStatus
	 * @param string|null $clientFilename
	 * @param string|null $clientMediaType
	 * @throws InvalidArgumentException
	 */
	public function __construct(mixed $streamOrFile,
								int $size,
								int $errorStatus,
								?string $clientFilename = null,
								?string $clientMediaType = null)
	{
		if ( match($errorStatus){
			UPLOAD_ERR_OK,UPLOAD_ERR_INI_SIZE,UPLOAD_ERR_FORM_SIZE, UPLOAD_ERR_PARTIAL,
			UPLOAD_ERR_NO_FILE,UPLOAD_ERR_NO_TMP_DIR,UPLOAD_ERR_CANT_WRITE,UPLOAD_ERR_EXTENSION => false,
			default => true,}) {
			throw new InvalidArgumentException('Upload file error status must be one of the "UPLOAD_ERR_*" constants.');
		}

		$this->error = $errorStatus;
		$this->size = $size;
		$this->clientFilename = $clientFilename;
		$this->clientMediaType = $clientMediaType;

		if ($this->error === UPLOAD_ERR_OK) {
			// Depending on the value set file or stream variable.
			if (is_string($streamOrFile)) {
				$this->file = $streamOrFile;
			} elseif (is_resource($streamOrFile)) {
				$this->stream = Stream::create($streamOrFile);
			} elseif ($streamOrFile instanceof Stream) {
				$this->stream = $streamOrFile;
			} else {
				throw new InvalidArgumentException('Invalid stream or file provided for UploadedFile');
			}
		}
	}


	/**
	 * @throws RuntimeException if is moved or not ok
	 */
	private function validateActive(): void
	{
		if ($this->error !== UPLOAD_ERR_OK) {
			throw new RuntimeException('Cannot retrieve stream due to upload error');
		}

		if ($this->moved) {
			throw new RuntimeException('Cannot retrieve stream after it has already been moved');
		}
	}

	/**
	 * @return Stream
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function getStream(): Stream
	{
		$this->validateActive();

		if ($this->stream instanceof Stream) {
			return $this->stream;
		}

		$resource = fopen($this->file, 'r');

		return Stream::create($resource);
	}


	/**
	 * 解析文件大小
	 *
	 * 如果可能，返回的是 $_FILES['size'] 的内容。 这个值由 PHP 基于传输的实际大小来计算。
	 *
	 * @return int 文件大小，byte；或者 null，如果未知
	 */
	public function getSize(): int
	{
		return $this->size;
	}

	/**
	 * 和此上传文件对应的错误码。
	 *
	 * 返回值是 PHP 的 UPLOAD_ERR_XXX 系列常量之一。
	 *
	 * 如果成功完成上传，返回 UPLOAD_ERR_OK.
	 *
	 * 返回的是 $_FILES['error'] 的内容
	 *
	 * @see http://php.net/manual/en/features.file-upload.errors.php
	 * @return int PHP 的 UPLOAD_ERR_XXX 系列常量之一
	 */
	public function getError(): int
	{
		return $this->error;
	}

	/**
	 * 客户端发送的文件名
	 *
	 * 即 $_FILES['name'] 的内容。
	 *
	 * 不能依赖于这个值，客户端可能会发送格式异常的文件名作为潜在攻击手段。
	 *
	 * @return string|null 客户端发送的文件名，没有的话为 null
	 */	public function getClientFilename(): ?string
	{
		return $this->clientFilename;
	}

	/**
	 * 客户端发送的 media type 信息
	 *
	 * 即 $_FILES['type'] 的内容。
	 *
	 * 不能依赖于这个值，客户端可能会发送格式异常的内容作为潜在攻击手段。
	 *
	 * @return string|null 客户端发送的 media type，没有的话为 null
	 */
	public function getClientMediaType(): ?string
	{
		return $this->clientMediaType;
	}

	/**
	 * 将上传的文件移动到新位置。
	 *
	 * 用此方法来代替 move_uploaded_file()。 此方法会检查所处环境，决定应当调用
	 * move_uploaded_file(), rename(), 还是 stream 操作来完成本操作。
	 *
	 * $targetPath 必须是绝对路径或相对路径。如果是相对路径，则按照 PHP 的 rename() 所使用的规则进行解析。
	 *
	 * 完成之后，会删掉原始的文件或 stream 。
	 *
	 * 如果本方法调用不止一次，从第二次开始，会抛出异常。
	 *
	 * 在 SAPI 环境下（有 $_FILES），本方法会调用 is_uploaded_file() 和 move_uploaded_file()
	 * 来确保正确验证权限和上传状态。
	 *
	 * 如果打算把文件移动到某个 stream，需要改用 getStream()，因为 SAPI 操作并不能保证正确写入
	 * stream 目标
	 *
	 * @see http://php.net/is_uploaded_file
	 * @see http://php.net/move_uploaded_file
	 * @param string $targetPath 移动的目标路径+文件名。
	 * @throws InvalidArgumentException 如果指定 $targetPath 无效.
	 * @throws RuntimeException 如果多次调用
	 */
	public function moveTo(string $targetPath): void
	{
		$this->validateActive();

		if ($targetPath === '') {
			throw new InvalidArgumentException('Invalid path provided for move operation; must be a non-empty string');
		}

		if (null !== $this->file) {
			$this->moved = ('cli' === PHP_SAPI) ? rename($this->file, $targetPath) : move_uploaded_file($this->file, $targetPath);
		} else {
			$stream = $this->getStream();
			if ($stream->isSeekable()) {
				$stream->rewind();
			}

			// Copy the contents of a stream into another stream until end-of-file.
			$dest = Stream::create(fopen($targetPath, 'w'));
			while (!$stream->eof()) {
				if (!$dest->write($stream->read(1048576))) { break; }
			}

			$this->moved = true;
		}

		if (false === $this->moved) {
			throw new RuntimeException(sprintf('Uploaded file could not be moved to %s', $targetPath));
		}
	}

	/**
	 * 上传时各种错误的对应信息
	 * 注意：UPLOAD_ERR_OK和self::MOVE_ERR_OK是没有错误信息的。
	 *
	 * @param int $error
	 * @return string
	 */
	static public function errorMsg(int $error): string
	{
		return match ($error) {
			UPLOAD_ERR_INI_SIZE => '上传的文件尺寸超过系统允许上限。',
			UPLOAD_ERR_FORM_SIZE =>  '上传的文件尺寸超过表单允许上限。',
			UPLOAD_ERR_PARTIAL => '文件只有部分完成上传。',
			UPLOAD_ERR_NO_FILE => '没有上传文件。',
			UPLOAD_ERR_NO_TMP_DIR => '找不到临时目录。',
			UPLOAD_ERR_CANT_WRITE => '无法写入磁盘。',
			UPLOAD_ERR_EXTENSION => '某个功能模块阻止了文件上传。',
			default => '未知错误',
		};
	}

}
