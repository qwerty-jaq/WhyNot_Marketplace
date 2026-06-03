<?php

// Show errors during development - REMOVE before deployment
ini_set('display_errors', 1);
error_clear_last();
error_reporting(E_ALL);

$DB_HOST = 'localhost';        // change to InfinityFree's MySQL host when deploying
$DB_NAME = 'verkoopdit';       // your database name
$DB_USER = 'root';             // default XAMPP user
$DB_PASS = '';                 // default XAMPP password is empty

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("<h2 style='color:red;'>Database connection failed</h2><p>" . htmlspecialchars($e->getMessage()) . "</p>");
}