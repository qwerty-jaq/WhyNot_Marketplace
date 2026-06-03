<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/authentication.php';
require_login();

// Handle verificatio action
if (isset($_POST['verify_user'])) {
    $uid = (int)$_POST['user_id'];
    $action = $_POST['verify_action'];
    if ($action === 'approve') {
        $pdo->prepare("UPDATE users SET is_verified = TRUE, verification_status = 'approved' WHERE user_id = ?")->execute([$uid]);
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE users SET is_verified = FALSE, verification_status = 'rejected' WHERE user_id = ?")->execute([$uid]);
    }
    $_SESSION['flash'] = ['type' => 'success', 'msg' => "User verification {$action}d."];
    header("Location: admin-dashboard.php"); exit;
}

// Handle user suspend/delete
if (isset($_POST['delete_user'])) {
    $uid = (int)$_POST['user_id'];
    if ($uid !== current_user_id()) {
        $pdo->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?")->execute([$uid, $uid]);
        $pdo->prepare("DELETE FROM reviews WHERE reviewer_id = ? OR seller_id = ?")->execute([$uid, $uid]);
        $pdo->prepare("DELETE FROM orders WHERE buyer_id = ? OR seller_id = ?")->execute([$uid, $uid]);

        $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$uid]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'User deleted.'];
    }
    header("Location: admin-dashboard.php"); exit;
}

// Stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$revenue = $pdo->query("SELECT COALESCE(SUM(price),0) FROM orders WHERE order_status='completed'")->fetchColumn();

// Pending verifications
$pending = $pdo->query("SELECT * FROM users WHERE verification_status = 'pending' ORDER BY created_at DESC")->fetchAll();

// All users
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

$page_title = 'Admin Dashboard - VerkoopDit';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <h2><i class="bi bi-shield-lock-fill text-warning"></i> Admin Dashboard</h2>

    <!-- STATS -->
    <div class="row my-4">
        <div class="col-md-3 mb-3"><div class="vd-stat"><div class="vd-stat-num"><?= $total_users ?></div><div class="vd-stat-label">Total Users</div></div></div>
        <div class="col-md-3 mb-3"><div class="vd-stat"><div class="vd-stat-num"><?= $total_products ?></div><div class="vd-stat-label">Total Products</div></div></div>
        <div class="col-md-3 mb-3"><div class="vd-stat"><div class="vd-stat-num"><?= $total_orders ?></div><div class="vd-stat-label">Total Orders</div></div></div>
        <div class="col-md-3 mb-3"><div class="vd-stat"><div class="vd-stat-num">R<?= number_format($revenue,2) ?></div><div class="vd-stat-label">Revenue</div></div></div>
    </div>

    <!-- PENDING VERIFICATIONS -->
    <h4 class="mt-4">Pending Seller Verifications (<?= count($pending) ?>)</h4>
    <?php if (empty($pending)): ?>
        <p class="text-muted">No pending verifications.</p>
    <?php else: ?>
        <div class="table-responsive vd-table">
            <table class="table table-hover mb-0">
                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Location</th><th>Joined</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($pending as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['phone']) ?></td>
                        <td><?= htmlspecialchars($u['location_user'] ?? '') ?></td>
                        <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                <input type="hidden" name="verify_action" value="approve">
                                <button type="submit" name="verify_user" class="btn btn-sm btn-success"><i class="bi bi-check"></i> Approve</button>
                            </form>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                <input type="hidden" name="verify_action" value="reject">
                                <button type="submit" name="verify_user" class="btn btn-sm btn-danger"><i class="bi bi-x"></i> Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- ALL USERS -->
    <h4 class="mt-4">All Users</h4>
    <div class="table-responsive vd-table">
        <table class="table table-hover mb-0">
            <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Verified</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= $u['user_id'] ?></td>
                    <td><?= htmlspecialchars(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['role_user']) ?></td>
                    <td><?= $u['is_verified'] ? 'Yes' : 'No' ?></td>
                    <td>
                        <?php if ($u['user_id'] !== current_user_id()): ?>
                           <button type="button" class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                                data-user-id="<?= $u['user_id'] ?>"
                                data-user-name="<?= htmlspecialchars(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?>">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        <?php else: ?>
                            <span class="text-muted">(you)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Delete User Confirmation Modal -->
 <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger"></i> Confirm User Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong id="deleteUserName"></strong>? This action cannot be undone and will remove all their data.
                    <input type="hidden" name="user_id" id="deleteUserId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_user" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script> 
    document.getElementById('deleteUserModal').addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        document.getElementById('deleteUserId').value = button.getAttribute('data-user-id');
            document.getElementById('deleteUserName').textContent = button.getAttribute('data-user-name');
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>