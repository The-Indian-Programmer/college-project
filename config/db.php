<?php
// config/db.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$host = '127.0.0.1';
$dbname = 'online_examination_system_test';
$username = 'root';
$password = '12345678';

try {
    // Try to connect to MySQL
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    define('DB_TYPE', 'mysql');
} catch (PDOException $e) {
    // Fallback to SQLite in the root directory
    try {
        $sqlite_path = dirname(__DIR__) . '/online_exam.db';
        $pdo = new PDO("sqlite:" . $sqlite_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // Enable foreign keys for SQLite
        $pdo->exec("PRAGMA foreign_keys = ON;");
        define('DB_TYPE', 'sqlite');
    } catch (PDOException $se) {
        die("Database connection failed. MySQL error: " . $e->getMessage() . " | SQLite error: " . $se->getMessage());
    }
}
?>
