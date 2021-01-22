<?php
declare(strict_types=1);

namespace mFramework\Database;

use PDOException;

/**
 * 数据库模块相关的Exception
 *
 * @package mFramework
 * @author Wynn Chen
 */
class QueryException extends PDOException
{}