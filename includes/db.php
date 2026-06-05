<?php

// Display PHP errors:
ini_set('display_errors', 1);
error_clear_last();
error_reporting(E_ALL);

// Auto-detect environment: local XAMPP vs InfinityFree
if (($_SERVER['SERVER_NAME'] ?? '') === 'localhost' || ($_SERVER['SERVER_NAME'] ?? '') === '127.0.0.1') {
    // Local development (XAMPP)
    $DB_HOST = 'localhost';
    $DB_NAME = 'whynot';
    $DB_USER = 'root';
    $DB_PASS = '';
} else {
    // Production (InfinityFree)
    $DB_HOST = 'sql107.infinityfree.com';
    $DB_NAME = 'if0_42096596_whynot_db';
    $DB_USER = 'if0_42096596';
    $DB_PASS = 'GohImFI3y89';
}

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