<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/authentication.php';
require_login();

$me = current_user_id();
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Verfiy te order belongs to current user as buyer AND is completed.
$stmt = $pdo->prepare("
    SELECT o.*, p.prod_title AS p_title
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    WHERE o.order_id = ? AND o.buyer_id = ? AND o.order_status = 'completed'
");
$stmt->execute([$order_id, $me]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['flash_message'] = 'You can only review orders that are completed and bought by you.';
    header('Location: orders.php'); exit;
}

// Prevent duplicate reviews
$check = $pdo->prepare("SELECT review_id FROM reviews WHERE product_id = ? AND reviewer_id = ?");
$check->execute([$order['product_id'], $me]);
if ($check->fetch()) {
    $_SESSION['flash_message'] = 'You already reviewed this product.';
    header('Location: orders.php'); exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating between 1 and 5 stars.';
    } else{
        $pdo->prepare("
            INSERT INTO reviews (product_id, seller_id, reviewer_id, reviewer_name, rating, comment)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            $order['product_id'], $order['seller_id'], $me,
            current_user_name(), $rating, $comment
        ]);
        $_SESSION['flash_message'] = 'Thanks! Your review has been posted.';
        header('Location: product.php?id=' . $order['product_id']); exit;
    }
}

 $page_title = 'Leave a Review - WhyNot?';
 include __DIR__ . '/includes/header.php';
 ?> 

 <div class="container py-4">
    <div class="row justify-content-center">
        <div class="vd-form-card">
            <h3 class="mb-1"><i class="bi bi-star-fill text-warning"></i> Leave a Review</h3>
            <p class="text-muted">For: <strong><?= htmlspecialchars($order['p_title']) ?></strong></p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3 text-center">
                    <label class="form-label d-block">Your Rating *</label>
                    <div class="vd-star-rating fs-2">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" <?= $i == 5 ? 'checked' : '' ?>>
                            <label for="star<?= $i ?>" title="<?= $i ?> stars"><i class="bi bi-star-fill"></i></label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Your Comment (optional)</label>
                    <textarea name="comment" class="form-control" rows="4" placeholder="How was your experience with this seller and product?"></textarea>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Post Review</button>
                    <a href="orders.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
 </div>

 <?php include __DIR__ . '/includes/footer.php'; ?>