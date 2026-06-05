<?php 
require_once __DIR__ .'/includes/db.php';
require_once __DIR__ .'/includes/authentication.php';
require_login();

$me = current_user_id();
$error = ''; $success = '';

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$me]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ??'');
    $phone = trim($_POST['phone'] ??'');
    $loc = trim($_POST['location'] ??'');
    $new_pass = $_POST['new_password'] ?? '';

    if ($first === '') { $error = 'First name is required.'; }
    else {
        $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, phone=?, location_user=? WHERE user_id=?");
        $stmt->execute([$first, $last, $phone, $loc, $me]);

        if ($new_pass !== '') {
            if (strlen($new_pass) < 8)  { $error = 'New password must be at least 8 characters.'; }
            else {
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password_hash=? WHERE user_id=?")->execute([$hash, $me]);
                $success = 'Profile and password updated.';
            }
        } else {
            $success = 'Profile updated!';
        }

        // Refesh
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$me]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['first_name'] = $user['first_name'];
    }
}

// Handle ADDING a payment method
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_card'])) {
    $holder = trim($_POST['card_holder'] ?? '');
    $number = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
    $mm = trim($_POST['expiry_mm'] ?? '');
    $yy = trim($_POST['expiry_yy'] ?? '');

    if ($holder === '' || strlen($number) < 13 || !preg_match('/^\d{2}$/', $mm) || !preg_match('/^\d{2}/', $yy)) {
        $error = 'All card fields are required and must be valid.';
    } else {
        $last4 = substr($number, -4);
        $brand = (str_starts_with($number, '4')) ? 'Visa' : ((str_starts_with($number, '5')) ? 'Mastercard' : 'Card');
        $pdo->prepare("INSERT INTO payment_methods (user_id, card_holder, card_last4, card_brand, expiry_mm, expiry_yy) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$me, $holder, $last4, $brand, $mm, $yy]);
        $success = 'Payment method added!.';
    }
}

// Handle DELETING a payment method
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_card'])) {
    $pmid = (int)$_POST['payment_method_id'];
    $pdo->prepare("DELETE FROM payment_methods WHERE payment_method_id = ? AND user_id = ?")
        ->execute([$pmid, $me]);
    $success = 'Payment method removed!';
}

// Load user's saved payment methods
$methods = $pdo->prepare("SELECT * FROM payment_methods WHERE user_id = ? ORDER BY created_at DESC");
$methods->execute([$me]);
$methods = $methods->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'My Profile - WhyNot?';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="vd-form-card">
                <h2><i class="bi bi-person-circle"></i> My Profile</h2>
                <p class="text-muted">Updated your personal details and password.</p>

                <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First name *</label>
                            <input type="text" name="first_name" class="form-control" required value="<?= htmlspecialchars($user['first_name'] ?? '')?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last name</label>
                            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name'] ?? '')?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email </label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                        <small class="text-muted">Email cannot be changed.</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '')?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($user['location_user'] ?? '')?>">
                        </div>
                    </div>
                    <hr>
                    <p class="text-muted small"> Leave blank to keep current password.</p>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" minlength="8">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="verify.php" class="btn btn-outline-success ms-2"><i class="bi bi-shield-check"></i> Seller Verification</a>
                    
                    <hr class="my-4">
                    <h4><i class="bi bi-credit-card"></i> Saved Payment Methods</h4>

                    <?php if (empty($methods)): ?>
                        <p class="text-muted-small">No saved cards yet.</p>
                    <?php else: ?>
                        <div class="list-group mb-3">
                            <?php foreach ($methods as $m): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-credit-card-2-front"></i>
                                        <strong><?= htmlspecialchars($m['card_brand']) ?></strong>
                                        ending <?= htmlspecialchars($m['card_last4']) ?>
                                        <br>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($m['card_holder']) ?> . Expires <?= htmlspecialchars($m['expiry_mm']) ?>/<?= htmlspecialchars($m['expiry_yy']) ?>
                                        </small>
                                    </div>
                                    <form method="POST" onsubmit="return confirm('Remove this card?');" class="d-inline">
                                        <input type="hidden" name="payment_method_id" value="<?= $m['payment_method_id'] ?>">
                                        <button type="submit" name="delete_card" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <details class="mt-2">
                        <summary class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-plus-circle"></i> Add a payment method
                        </summary>
                        <form method="POST" class="vd-form-card mt-3">
                            <div class="alert alert-info small mb-3">
                                <i class="bi bi-info-circle"></i> Simulated card storage - we only keep the last 4 digits, never full number or CVV.
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Cardholder Name *</label>
                                <input type="text" name="card_holder" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label clas="form-label">Card Number *</label>
                                <input type="text" name="card_number" class="form-control" placeholder="1234 5678 9012 3456" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Expiry Month *</label>
                                    <input type="text" name="expiry_mm" class="form-control" placeholder="12" maxlength="2" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Expiry Year *</label>
                                    <input type="text" name="expiry_yy" class="form-control" placeholder="28" maxlength="2" required>
                                </div>
                            </div>
                            <button type="submit" name="add_card" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Save Card
                            </button>
                        </form>
                    </details>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ .'/includes/footer.php'; ?>