<?php
namespace Appcore;

use PDO;
use PDOException;

class Database {
    private static $pdo;

    public static function conn(array $cfg): PDO {
        if (self::$pdo) return self::$pdo;
        $dsn = "mysql:host={$cfg['DB']['host']};dbname={$cfg['DB']['name']};charset={$cfg['DB']['charset']}";
        try {
            self::$pdo = new PDO($dsn, $cfg['DB']['user'], $cfg['DB']['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Database connection error.');
        }
        return self::$pdo;
    }
}
