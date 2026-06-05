<?php 


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
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
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $plain = 'password123';
    $hash = password_hash($plain, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash");
    $stmt->execute([':hash' => $hash]);
    $rows = $stmt->rowCount();

    echo "<h2> Password seeding complete</h2>";
    echo "<p>Updated <strong>$rows</strong> user(s) in the database. </p>";
    echo "<p>All sample users can now log in with: <code>$plain</code></p>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Database error: " . $e->getMessage() . "</h2>";
}
?>
