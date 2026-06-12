<?php

class DB
{
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dbPath  = __DIR__ . '/../database/rides.sqlite';
            $sqlPath = __DIR__ . '/../database/rides.sql';
            $isNew   = !file_exists($dbPath);

            self::$instance = new PDO('sqlite:' . $dbPath);
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance->exec('PRAGMA foreign_keys = ON;');

            if ($isNew) {
                self::$instance->exec(file_get_contents($sqlPath));
            }
        }

        return self::$instance;
    }
}
