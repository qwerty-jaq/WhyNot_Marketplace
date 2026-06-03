<?php
require_once __DIR__ . '/authentication.php';

//Unread messages count for navbar badge
$navbar_unread = 0;
if (is_logged_in() && isset($pdo)) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([current_user_id()]);
    $navbar_unread = (int)$stmt->fetchColumn();
}

$page_title = $page_title ?? 'WhyNot? - C2C Marketplace';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>

    <!-- Bootstrap 5 (CDN - no install needed) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Our custom styles (loaded LAST so it overrides Bootstrap) -->
    <link rel="stylesheet" href="/VerkoopDit/css/styles.css">
    </head>
<body>

<!-- ============= NAVBAR ============= -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top vd-navbar">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/VerkoopDit/index.php">
            <i class="bi bi-shop"></i> WhyNot?
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="/VerkoopDit/browse.php">Buy</a></li>
                <?php if (is_logged_in()): ?>
                    <li class="nav-item"><a class="nav-link" href="/VerkoopDit/sell.php">Sell</a></li>
                    <li class="nav-item"><a class="nav-link" href="/VerkoopDit/orders.php">Orders</a></li>
                    <li class="nav-item">
                        <a class="nav-link" href="/VerkoopDit/messages.php">
                            <i class="bi bi-chat-dots"></i>Messages
                            <?php if ($navbar_unread > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?= $navbar_unread ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="/VerkoopDit/seller-dashboard.php">Dashboard</a></li>
                    <?php if (current_user_role() === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link text-warning" href="/VerkoopDit/admin-dashboard.php">Admin</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <!-- Search bar -->
            <form class="d-flex me-3" action="/VerkoopDit/browse.php" method="GET">
                <input class="form-control me-2" type="search" name="q" placeholder="Search items..." aria-label="Search">
                <button class="btn btn-light" type="submit"><i class="bi bi-search"></i></button>
            </form>

            <!-- Auth buttons -->
            <ul class="navbar-nav">
                <?php if (is_logged_in()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars(current_user_name() ?? 'Account') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/VerkoopDit/orders.php">My Orders</a></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/VerkoopDit/login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="/VerkoopDit/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Logout Confirmation Modal -->
     <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-right"></i> Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to log out?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="/VerkoopDit/logout.php" class="btn btn-danger">Yes, log out</a>
                </div>
            </div>
        </div>
     </div>

<!-- Flash messages from session (e.g. after login/logout) -->
<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="container mt-3">
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php unset($_SESSION['flash_message']); // Clear flash message after showing it ?>
<?php endif; ?>

<main class="vd-main">