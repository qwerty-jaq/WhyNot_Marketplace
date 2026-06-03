<?php 


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'localhost'; //should change before final submission.
$dbname = 'verkoopDit';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
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
