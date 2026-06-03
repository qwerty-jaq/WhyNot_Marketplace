<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/authentication.php';
require_login();

$me = current_user_id();
$active_tab = ($_GET['tab'] ?? 'buying') === 'selling' ? 'selling' : 'buying';

// Seller: paid -> confirmed
if (isset($_POST['confirm_order'])) {
    $oid = (int)$_POST['order_id'];
    $pdo->prepare("UPDATE orders SET order_status = 'confirmed' WHERE order_id = ? AND seller_id = ? AND order_status = 'paid'")
        ->execute([$oid, $me]);
    $_SESSION['flash_message'] = "Order #$oid marked as confirmed.";
    header("Location: orders.php?tab=selling");
    exit;
} 

// Seller: confirmed -> shipped
if (isset($_POST['ship_order'])) {
    $oid = (int)$_POST['order_id'];
    $pdo->prepare("UPDATE orders SET order_status = 'shipped' WHERE order_id = ? AND seller_id = ? AND order_status = 'confirmed'")
        ->execute([$oid, $me]);
    $_SESSION['flash_message'] = "Order #$oid marked as shipped.";
    header("Location: orders.php?tab=selling");
    exit;
}

// Buyer: shipped -> confirmed (product sold).
if (isset($_POST['mark_received'])) {
    $oid = (int)$_POST['order_id'];
    $pdo->prepare("UPDATE orders SET order_status = 'completed' WHERE order_id = ? AND buyer_id = ? AND order_status = 'shipped'")
        ->execute([$oid, $me]);
    $pdo->prepare("UPDATE products p JOIN orders o ON p.product_id = o.product_id SET p.prod_status = 'sold' WHERE o.order_id = ? AND o.buyer_id = ?")
        ->execute([$oid, $me]);
    $_SESSION['flash_message'] = "Order #$oid marked as received! You can now leave a review.";
    header("Location: orders.php?tab=buying");
    exit;
}

// Either party: cancel order (only if not yet shipped)
if (isset($_POST['cancel_order'])) {
    $oid = (int)$_POST['order_id'];
    $stmt = $pdo->prepare("SELECT product_id FROM orders WHERE order_id = ? AND (buyer_id = ? OR seller_id = ?) AND order_status IN ('paid', 'confirmed')");
    $stmt->execute([$oid, $me, $me]);
    if ($row = $stmt->fetch()) {
        $pdo->prepare("UPDATE orders SET order_status = 'cancelled' WHERE order_id = ?")->execute([$oid]);
        $pdo->prepare("UPDATE products SET prod_status = 'active' WHERE product_id = ?")->execute([$row['product_id']]);
        $_SESSION['flash_message'] = "Order #$oid has been cancelled.";
    }
    header("Location: orders.php?tab=buying");
    exit;
}

// Orders I bought
$stmt = $pdo->prepare("SELECT * FROM orders WHERE buyer_id = ? ORDER BY created_at DESC");
$stmt->execute([$me]);
$buying = $stmt->fetchAll();

// Orders I'm fulfilling (selling)
$stmt = $pdo->prepare("SELECT * FROM orders WHERE seller_id = ? ORDER BY created_at DESC");
$stmt->execute([$me]);
$selling = $stmt->fetchAll();

$page_title = "My Orders - WhyNot?";
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <h2><i class="bi bi-box-seam"></i> My Orders</h2>

    <ul class="nav nav-tabs mt-4" role="tablist">
        <li class="nav-item"><a class="nav-link <?= $active_tab === 'buying' ? 'active' : '' ?>" data-bs-toggle="tab" href="#buying">Buying (<?= count($buying) ?>)</a></li>
        <li class="nav-item"><a class="nav-link <?= $active_tab === 'selling' ? 'active' : '' ?>" data-bs-toggle="tab" href="#selling">Selling (<?= count($selling) ?>)</a></li>
    </ul>

    <div class="tab-content pt-3">
        <!-- BUYING TAB -->
        <div id="buying" class="tab-pane fade <?= $active_tab === 'buying' ? 'show active' : '' ?>">
            <?php if (empty($buying)): ?>
                <div class="alert alert-info">You haven't bought anything yet. <a href="browse.php">Browse listings</a>.</div>
            <?php else: ?>
                <div class="table-responsive vd-table">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Item</th><th>Price</th><th>Status</th><th>Delivery Address</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($buying as $o): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                                <td><?= htmlspecialchars($o['prod_title']) ?></td>
                                <td>R<?= number_format((float)$o['price'],2) ?></td>
                                <td><span class="badge vd-status-<?= $o['order_status'] ?>"><?= htmlspecialchars($o['order_status']) ?></span></td>
                                <td class="small"><?= htmlspecialchars($o['delivery_address'] ?? '-') ?></td>
                                <td>
                                    <?php if ($o['order_status'] === 'shipped'): ?>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Confirm you have received this item?');">
                                            <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                                            <button type="submit" name="mark_received" class="btn btn-sm btn-success"><i class="bi bi-check-circle"></i>Mark as Received</button>
                                        </form>
                                    <?php elseif (in_array($o['order_status'], ['paid', 'confirmed'])): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                            <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                                            <button type="submit" name="cancel_order" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> Cancel</button>
                                        </form>
                                    <?php elseif ($o['order_status'] === 'completed'): ?>
                                        <?php 
                                        $hasReview = $pdo->prepare("SELECT review_id FROM reviews WHERE product_id = ? AND reviewer_id = ?");
                                        $hasReview->execute([$o['product_id'], $me]);
                                        if ($hasReview->fetch()):
                                        ?>
                                            <span class="text-success small"><i class="bi bi-check-circle-fill"></i> Reviewed</span>
                                        <?php else: ?>
                                                <a href="review.php?order_id=<?= $o['order_id'] ?>" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-star"></i> Leave Review
                                                </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- SELLING TAB -->
        <div id="selling" class="tab-pane fade <?= $active_tab === 'selling' ? 'show active' : '' ?>">
            <?php if (empty($selling)): ?>
                <div class="alert alert-info">You don't have any orders yet. <a href="sell.php">Create a listing</a>.</div>
            <?php else: ?>
                <div class="table-responsive vd-table">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Item</th><th>Price</th><th>Status</th><th>Buyer</th><th>Delivery Address</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($selling as $o): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                                <td><?= htmlspecialchars($o['prod_title']) ?></td>
                                <td>R<?= number_format((float)$o['price'],2) ?></td>
                                <td><span class="badge vd-status-<?= $o['order_status'] ?>"><?= htmlspecialchars($o['order_status']) ?></span></td>
                                <td><?= htmlspecialchars($o['buyer_name'] ?? 'User #' . $o['buyer_id']) ?></td>
                                <td class="small"><?= htmlspecialchars($o['delivery_address'] ?? '-') ?></td>
                                <td>
                                    <?php if ($o['order_status'] === 'paid'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Confirm you have handed over the item to the buyer?');">
                                            <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                                            <button type="submit" name="confirm_order" class="btn btn-sm btn-primary"><i class="bi bi-check2"></i>Confirm</button>
                                        </form>
                                    <?php elseif ($o['order_status'] === 'confirmed'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Mark this order as shipped?');">
                                            <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                                            <button type="submit" name="ship_order" class="btn btn-sm btn-info"><i class="bi bi-truck"></i> Mark as Shipped</button>
                                        </form>
                                    <?php elseif ($o['order_status'] === 'shipped'): ?>
                                        <span class="text-muted small"><i class="bi bi-truck"></i> Awaiting Delivery</span>
                                    <?php elseif ($o['order_status'] === 'completed'): ?>
                                        <span class="text-success small"><i class="bi bi-check-circle-fill"></i> Completed</span>
                                    <?php elseif ($o['order_status'] === 'cancelled'): ?>
                                        <span class="text-danger small"><i class="bi bi-x-circle-fill"></i> Cancelled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?> 