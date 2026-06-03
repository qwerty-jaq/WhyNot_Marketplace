<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ .'/includes/authentication.php';

$id = isset($_GET['id']) ? intval($_GET['id']) :0;
if ($id <= 0) { die('Invalid product.'); }

// Increment view count
$pdo->prepare("UPDATE products SET views = views + 1 WHERE product_id = ?")->execute([$id]);

// Get the product + seller info
$stmt = $pdo->prepare("
    SELECT p.*, c.cat_name, u.first_name AS seller_first, u.last_name AS seller_last, u.email AS seller_email, u.location_user AS seller_location, u.is_verified AS seller_verified
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN users u ON p.seller_id = u.user_id
    WHERE p.product_id = ? AND p.prod_status = 'active'
    ");

$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) { die('Product not found or is no longer active.'); }

// Reviews 
$stmt = $pdo->prepare("SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC");
$stmt->execute([$id]);
$reviews = $stmt->fetchAll();

// Handle "Send Message" form submission
$msg_sent = false; $msg_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    if (!is_logged_in()) {
        $msg_error = 'You must be logged in to send a message.';
    } else {
        $message = trim($_POST['message']);
        if ($message === '') {
            $msg_error = 'Message cannot be empty.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, product_id, content) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $product['seller_id'], $id, $message]);
            $msg_sent = true;
        }
    }
}

// Handle "Buy Now" from submission
$order_made = false;
$order_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_now'])) {
    if (!is_logged_in()) {
        $_SESSION['flash_message'] = 'You must be logged in to make a purchase.';
        header('Location: login.php');
        exit;
    }
    if (current_user_id() == $product['seller_id']) {
        $_SESSION['flash_message'] = 'You cannot buy your own product.';
        header("Location: product.php?id={$id}");
        exit;
    } 

    $status = $pdo->prepare("SELECT prod_status FROM products WHERE product_id = ?");
    $status->execute([$id]);
    if ($status->fetchColumn() !== 'active') {
        $_SESSION['flash_message'] = 'Sorry, this product is no longer available.';
        header("Location: product.php?id={$id}");
        exit;
    }
    $pdo->prepare("INSERT INTO orders (buyer_id, seller_id, product_id, product_title, price, buyer_name) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([current_user_id(), $product['seller_id'], $id, $product['prod_title'], $product['price'], current_user_name()]);
    $pdo->prepare("UPDATE products SET prod_status = 'pending' WHERE product_id = ?")->execute([$id]);
    $_SESSION['flash_message'] = 'Order placed successfully! Check your orders for details.';
    header("Location: orders.php");
    exit;
}

    

    

    
    
        


$img = !empty($product['image_url']) ? "/VerkoopDit/uploads/" . htmlspecialchars($product['image_url']) : 'https://via.placeholder.com/400x300?text=No+Image';

$page_title = $product['prod_title'] . ' - WhyNot?';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="browse.php">Browse</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['prod_title']) ?></li>
        </ol>
    </nav>

    <div class="row">
        
        <!-- PRODUCT IMAGE -->
        <div class="col-md-6 mb-4">
            <img src="<?= $img ?>" class="img-fluid rounded shadow-sm" alt="<?= htmlspecialchars($product['prod_title']) ?>">
        </div>

        <!-- PRODUCT DETAILS -->
        <div class="col-md-6 mb-4">
            <h2><?= htmlspecialchars($product['prod_title']) ?></h2>
            <p class="vd-price h3 my-3">R<?= number_format((float)$product['price'], 2) ?></p>

            <p>
                <span class="badge vd-badge-condition"><?= htmlspecialchars($product['condition_status']) ?></span>
                <span class="badge bg-secondary"><?= htmlspecialchars($product['cat_name'] ?? 'Uncategorized') ?></span>
                <span class="text-muted small ms-2"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($product['seller_location'] ?? 'SA') ?></span>
            </p>

            <p class="text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($product['location_user'] ?? 'SA') ?></p>

            <hr>

            <h6>Description</h6>
            <p><?= nl2br(htmlspecialchars($product['prod_description'])) ?></p>

            <hr>

            <h6>Seller</h6>
            <p>
                <strong><?= htmlspecialchars($product['seller_first'] . ' ' . $product['seller_last']) ?></strong><br>
                <?php if ($product['seller_verified']): ?>
                    <span class="badge bg-success"><i class="bi bi-patch-check"></i> Verified Seller</span><br>
                <?php endif; ?>
                <br>
                <small class="text-muted"><?= htmlspecialchars($product['seller_location'] ?? 'SA') ?></small>
            </p>

            <!-- ACTI0N BUTTONS -->
             <?php if ($product['prod_status'] === 'active'): ?>
                <div class="mt-4">
                    <a href="buynow.php?product_id=<?= $product['product_id'] ?>" class="btn btn-primary btn-lg"><i class="bi bi-bag-check"></i> Buy Now</a>
                    <button class="btn btn-outline-secondary btn-lg" data-bs-toggle="modal" data-bs-target="#messageModal">
                        <i class="bi bi-chat-dots"></i> Contact Seller
                    </button>
                </div>
            <?php else: ?>
                <div class="alert alert-warning mt-3">This listing is no longer available (Status: <?= htmlspecialchars($product['prod_status']) ?>).</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- REVIEWS Section -->
     <section class="mt-5">
        <h4>Reiews (<?= count($reviews) ?>)</h4>
        <?php if (empty($reviews)): ?>
            <p class="texted-muted">No reviews yet. Be the first to review this product!</p>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
                <div class="vd-review-card mb-3 p-3 border rounded">
                    <div class="d-flex align-items-center mb-2">
                        <strong><?= htmlspecialchars($review['reviewer_name']) ?></strong>
                        <span class="text-muted small ms-2"><?= date('M d, Y', strtotime($review['created_at'])) ?>
                            <?php for ($i=1;$i<=5;$i++): ?>
                                <i class="bi bi-star<?= $i <= $review['rating'] ? '-fill text-warning' : '' ?>"></i>
                            <?php endfor; ?>
                        </span>
                        <p class="mt-0 mt-2"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                        <small class="text-muted"><?= date('d M Y', strtotime($review['created_at'])) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>

<!-- BUY NOW MODEL -->
<div class="modal fade" id="buyModal" tabindex="-1" aria-labelledby="buyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="buy_now" value="1">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Purchase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if ($order_made): ?>
                        <div class="alert alert-success">Order placed successfully! Check your <a href="orders.php">Orders</a> for details.</div>
                    <?php elseif ($order_error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($order_error) ?></div>
                    <?php else: ?>
                    <p><strong>Item :</strong> <?= htmlspecialchars($product['prod_title']) ?></p>
                    <p><strong>Total: </strong> R<?= number_format((float)$product['price'], 2) ?></p>
                    <div class="mb-3">
                        <label class="form-label">Additional Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Place Order</button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Place Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MESSAGE SELLER MODEL -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="message" value="1">
                <div class="modal-header"><h5 class="modal-title">Contact Seller</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <?php if ($msg_sent): ?>
                        <div class="alert alert-success">Message sent to seller! They will respond via email.</div>
                    <?php elseif ($msg_error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($msg_error) ?></div>
                    <?php else: ?>
                        <p>Send a message to <strong><?= htmlspecialchars($product['seller_first'] . ' ' . $product['seller_last']) ?></strong> about this product.</p>
                        <div class="mb-3">
                            <label class="form-label">Your Message</label>
                            <textarea name="message" class="form-control" rows="4" required></textarea>
                        </div>
                    <?php endif; ?>
                </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </div>
                </form>
            </div>
        </div>
    </div>
</div>



<?php include __DIR__ . '/includes/footer.php'; ?>