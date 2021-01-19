<?php
declare(strict_types=1);

namespace mFramework;

use function strtr;

/**
 * 一些公用的工具函数
 *
 * @package mFramework
 */
class Func
{
	/**
	 * 来自 Nyholm/Psr7
	 *
	 * implementing a locale-independent lowercasing logic.
	 *
	 * @param string $value
	 * @return string
	 * @author Nicolas Grekas <p@tchwork.com>
	 */
	public static function lowercase(string $value): string
	{
		return strtr($value, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
	}
}