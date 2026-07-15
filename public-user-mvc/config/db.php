<?php
// config/db.php

class Database {
    private static $host = 'localhost';
    private static $db_name = 'ecolotlk';
    private static $username = 'root';
    private static $password = ''; // Default XAMPP password is empty
    private static $conn = null;

    public static function getConnection() {
        if (self::$conn === null) {
            try {
                self::$conn = new PDO(
                    "mysql:host=" . self::$host . ";dbname=" . self::$db_name,
                    self::$username,
                    self::$password
                );
                // Set error mode to exception
                self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                // Set default fetch mode to associative array
                self::$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                // Support UTF-8
                self::$conn->exec("set names utf8mb4");
            } catch(PDOException $exception) {
                die("Connection error: " . $exception->getMessage());
            }
        }
        return self::$conn;
    }
}
