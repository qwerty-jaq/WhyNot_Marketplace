<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ .'/includes/authentication.php';
require_login();

$me = current_user_id();
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$me]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    $pdo->prepare("UPDATE users SET verification_status = 'pending', role_user = IF(role_user='buyer', 'seller', role_user) WHERE user_id = ?") ->execute([$me]);
    $_SESSION['user_role'] = 'seller';
    $_SESSION['flash'] = ['type' => 'success',  'msg' => 'Verification application submitted! Admin will review shortly.'];
    header('Location: verify.php'); exit;
}

$page_title = 'Seller Verification - VerkoopDit';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="vd-form-card text-center">
                <i class="bi bi-shield-check display-1 text-primary"></i>
                <h2 class="mt-3"> Seller Verification</h2>
                <p class="text-muted"> Verified sellers display a trust badge on every lsiting.</p>

                <hr>

                <h5>Your current status:</h5>
                <?php if ($user['is_verified']): ?>
                    <p><span class="badge bg-success p-3 fs-5"><i class="bi bi-check-circle"></i>VERIFIED SELLER</span></p>
                    <p class="text-muted">You're already verfied. Your lsitings show a verified badge to buyers.</p>
                <?php elseif ($user['verification_status'] === 'pending'): ?>
                    <p><span class="badge bg-warning p-3 fs-5"><i class="bi bi-hourglass-split"></i> APPLICATION PENDING</span></p>
                    <p class="text-muted">Your application is being reviewed. You'll be notified once an admin approves it.</p>
                <?php elseif ($user['verification_status'] === 'rejected'): ?>
                    <p><span class="badge bg-danger p-3 fs-5"><i class="bi bi-x-circle"></i> REJECTED</span></p>
                    <p class="text-muted">Your previous application wes rejected. You may appkly again.</p>
                    <form method="POST" class="mt-3"><button name="apply" class="btn btn-primary">Apply Again</button></form>
                <?php else: ?>
                    <p><span class="badge bg-secondary p-3 fs-5">NOT APPLIED</span></p>
                    <div class="text-start mt-4">
                        <h6>What you get: </h6>
                        <ul>
                            <li><i class="bi bi-check2 text-success"></i> Verified badge on all your lsitings</li>
                            <li><i class="bi bi-check2 text-success"></i> Higher buyer trust : more sales</li>
                            <li><i class="bi bi-check2 text-success"></i> Listed in featured sellers section</li>
                            <li><i class="bi bi-check2 text-success"></i> Priority in search results</li>
                        </ul>
                        <h6 class="mt-3">Requirements:</h6>
                        <ul>
                            <li>Verified email address</li>
                            <li>Valid phone number on profile</li>
                            <li>Location set on profile</li>
                        </ul>
                    </div>
                    <form method="POST" class="mt-4">
                        <button name="apply" type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-shield-plus"></i> Apply for Verifiction
                        </button>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>