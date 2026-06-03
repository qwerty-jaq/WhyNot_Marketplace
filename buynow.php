<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/authentication.php';
require_login();

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if ($product_id <= 0) {
    $_SESSION['flash_message'] = 'Invalid product.';
    header('Location: browse.php'); exit;
}

// Fetch product + seller info
$stmt = $pdo->prepare("
    SELECT p.*, u.first_name AS seller_first, u.last_name AS seller_last
    FROM products p
    LEFT JOIN users u ON p.seller_id = u.user_id
    WHERE p.product_id = ? AND p.prod_status = 'active'
");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    $_SESSION['flash_message'] = 'Product not found.';
    header('Location: browse.php'); exit;
}

if (current_user_id() == $product['seller_id']) {
    $_SESSION['flash_message'] = 'You cannot buy your own product.';
    header('Location: product.php?id=' . $product_id); exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardholder  = trim($_POST['cardholder']  ?? '');
    $card_number = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
    $expiry      = trim($_POST['expiry']      ?? '');
    $cvv         = trim($_POST['cvv']         ?? '');
    $address     = trim($_POST['address']     ?? '');
    $notes       = trim($_POST['notes']       ?? '');

    // ---- Validate ----
    if ($cardholder === '')                 $errors[] = 'Cardholder name is required.';
    if ($address === '')                    $errors[] = 'Delivery address is required.';
    if (!preg_match('/^\d{3,4}$/', $cvv))   $errors[] = 'CVV must be 3 or 4 digits.';

    // Luhn check on card number
    if (strlen($card_number) < 13 || strlen($card_number) > 19) {
        $errors[] = 'Card number must be 13-19 digits.';
    } else {
        $sum = 0; $alt = false;
        for ($i = strlen($card_number) - 1; $i >= 0; $i--) {
            $n = (int)$card_number[$i];
            if ($alt) { $n *= 2; if ($n > 9) $n -= 9; }
            $sum += $n;
            $alt = !$alt;
        }
        if ($sum % 10 !== 0) $errors[] = 'Invalid card number (failed Luhn check).';
    }

    // Expiry date check (MM/YY)
    if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $expiry, $matches)) {
        $errors[] = 'Expiry date must be in MM/YY format.';
    } else {
        $exp = DateTime::createFromFormat('Y-m-d', sprintf('20%02d-%02d-01', $matches[2], $matches[1]));
        $exp->modify('last day of this month');
        if ($exp < new DateTime()) $errors[] = 'Card has expired.';
    } 
     
    // ---- Process Simulated Payment ----
    if (empty($errors)) {
        $check = $pdo->prepare("SELECT prod_status FROM products WHERE product_id = ?");
        $check->execute([$product_id]);
        if ($check->fetchColumn() !== 'active') {
            $_SESSION['flash_message'] = 'Sorry, this product was just sold.';
            header('Location: browse.php'); exit;
        } else {
            $payment_method = 'Card ending in ' . substr($card_number, -4);
            $transaction_id = 'SIM-' . time() . '-' . random_int(1000,9999);

            $pdo->prepare("
                INSERT INTO orders
                    (buyer_id, seller_id, product_id, prod_title, price, buyer_name,
                    order_status, delivery_address, payment_method, transaction_id, paid_at, notes)
                VALUES (?, ?, ?, ?, ?, ?, 'paid', ?, ?, ?, NOW(), ?)
            ")->execute([
                current_user_id(), $product['seller_id'], $product_id, $product['prod_title'], $product['price'],
                current_user_name(), $address, $payment_method, $transaction_id, $notes
            ]);

            $pdo->prepare("UPDATE products SET prod_status = 'pending' WHERE product_id = ?")->execute([$product_id]);
            $_SESSION['flash_message'] = 'Payment successful! Transaction ID: ' . $transaction_id;
            header('Location: orders.php'); exit;
        }
    }
}

$page_title = 'Checkout - WhyNot?';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <h2 class="mb-4"><i class="bi bi-credit-card"></i> Checkout</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
           <strong>Please Fix:</strong>
           <ul class="mb-0">
              <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
              <?php endforeach; ?>
           </ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Order Summary -->
        <div class="col-md-4 mb-4">
            <div class="card vd-form-card">
                <h5>Order Summary</h5><hr>
                <p class="mb-1"><strong><?= htmlspecialchars($product['prod_title']) ?></strong></p>
                <p class="text-muted small">From <?= htmlspecialchars($product['seller_first'] ?? '') . ' ' . ($product['seller_last'] ?? '') ?></p>
                <hr>
                <div class="d-flex justify-content-between"><span>Subtotal:</span><span>R<?= number_format((float)$product['price'], 2) ?></span></div>
                <div class="d-flex justify-content-between"><span>Delivery:</span><span>R0.00</span></div>
                <hr>
                <div class="d-flex justify-content-between fw-bold"><span>Total:</span><span>R<?= number_format((float)$product['price'], 2) ?></span></div>
             </div>
        </div>

    <!-- Payment Form -->
     <div class="col-md-8">
        <form method="POST" class="vd-form-card">
            <h5>Delivery</h5>
            <div class="mb-3">
                <label class="form-label">Delivery Address *</label>
                <textarea class="form-control" name="address" rows="2" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Notes for Seller (optional)</label>
                <textarea class="form-control" name="notes" rows="2"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
            </div>

            <h5 class="mt-4"><i class="bi bi-credit-card"></i> Payment Details</h5>
            <div class="alert alert-info small">
                <i class="bi bi-info-circle"></i> <strong>Simulated Payment.</strong> No real money is processed.
                Test Card: <code>4111 1111 1111 1111</code>, Expiry: Any future date, CVV: Any 3-4 digits.
            </div>

            <div class="mb-3">
                <label class="form-label">Cardholder Name *</label>
                <input type="text" name="cardholder" class="form-control" required value="<?= htmlspecialchars($_POST['cardholder'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Card Number *</label>
                <input type="text" name="card_number" class="form-control" required placeholder="1234 5678 9012 3456" maxlength="19" value="<?= htmlspecialchars($_POST['card_number'] ?? '') ?>">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Expiry Date (MM/YY) *</label>
                    <input type="text" name="expiry" class="form-control" required placeholder="12/28" maxlength="5" value="<?= htmlspecialchars($_POST['expiry'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">CVV *</label>
                    <input type="text" name="cvv" class="form-control" required placeholder="123" maxlength="4" value="<?= htmlspecialchars($_POST['cvv'] ?? '') ?>">
                </div>
            </div>

            <div class="d-grid gap-2 mt-3">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-lock-fill"></i> Pay R<?= number_format((float)$product['price'], 2) ?>
                </button>
                <a href="product.php?id=<?= $product_id ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
     </div>
</div>
</div>


<?php include __DIR__ . '/includes/footer.php'; ?>