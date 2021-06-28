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

	public static function getIp()
	{
		foreach (array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP', 'REMOTE_ADDR') as $header) {
			if (!isset($_SERVER[$header]) || ($spoof = $_SERVER[$header]) === null) {
				continue;
			}
			sscanf($spoof, '%[^,]', $spoof);
			if (!filter_var($spoof, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
				$spoof = null;
			} else {
				return $spoof;
			}
		}
		return '0.0.0.0';
	}

	public static function propertyGet($obj, $prop)
	{
		return $obj->{$prop};
	}
	public static function propertyExists($obj, $prop)
	{
		return isset($obj->{$prop});
	}
	public static function propertyUnset($obj, $prop)
	{
		unset($obj->{$prop});
	}
	public static function propertySet($obj, $prop, $value)
	{
		$obj->{$prop} = $value;
	}
}