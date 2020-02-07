<?php

abstract class DatabaseTestCase extends PHPUnit\DbUnit\TestCase
{
	// 只实例化 pdo 一次，供测试的清理和基境读取使用。
	private static $pdo = null;
	
	// 对于每个测试，只实例化 PHPUnit_Extensions_Database_DB_IDatabaseConnection 一次。
	private $conn = null;

	public function getConnection()
	{
		if ($this->conn === null) {
			if (self::$pdo == null) {
				self::$pdo = new PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
			}
			$this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
			
			self::$pdo->exec('DROP TABLE IF EXISTS `test`.`blog`;');
			self::$pdo->exec('
CREATE TABLE `test`.`blog` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `heading` VARCHAR(45) NULL,
  `abstract` VARCHAR(45) NULL,
  `body` TEXT NULL,
  PRIMARY KEY (`id`));
				
			');
		}
		return $this->conn;
	}
}