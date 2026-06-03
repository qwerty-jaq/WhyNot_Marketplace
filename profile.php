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

$page_title = 'My Profile - VerkoopDit';
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
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ .'/includes/footer.php'; ?>