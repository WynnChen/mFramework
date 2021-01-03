<?php
use mFramework\Map;

class MapTest extends PHPUnit\Framework\TestCase
{

	/**
	 * 只需要测试 set() get() has() del() 这一套，其他是ArrayObject() 提供。
	 * $map->set('key', $value);
	 * $var = $map->get('key');
	 * $map->has('key'); //true
	 * $map->del('key'); //unset
	 */
	public function testMap()
	{
		$map = new Map();
		// 基本
		$this->assertCount(0, $map);
		$this->assertFalse($map->has('key'));
		$this->assertNull($map->get('nonexistent_key')); // 不存在的有$default
		$map->set('key', 'value');
		$this->assertEquals('value', $map->get('key'));
		$this->assertCount(1, $map);
		$this->assertTrue($map->has('key'));
		$map->del('key');
		$this->assertNull($map->get('key')); // 不存在的是null
		$this->assertCount(0, $map);

		//批量设置
		$map->batchSet(['key_a'=>'value_a' ,'key_b'=>'value_b']);
		$this->assertEquals('value_a', $map->get('key_a'));
		$this->assertEquals('value_b', $map->get('key_b'));

		// key的各种强制转换（类同array）
		$map->set('8', 'value_8');
		$this->assertEquals('value_8', $map->get(8));
		$this->assertNull($map->get('08'));
		$map->set(true, 'value_true');
		$this->assertEquals('value_true', $map->get(1));
		$this->assertEquals('value_true', $map->get('1'));
		$map->set(2.7, 'value_float');
		$this->assertEquals('value_float', $map->get('2.7'));
		$map->set(1, 'a');
		$this->assertEquals('a', $map->get(1));
		$map->set('1', 'b');
		$this->assertEquals('b', $map->get(1));
		$map->set(true, 'c');
		$this->assertEquals('c', $map->get(1));
		$map->set(1.5, 'd');
		$this->assertEquals('d', $map->get('1.5'));
	}
	
}
