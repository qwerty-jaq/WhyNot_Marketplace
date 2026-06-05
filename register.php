<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ .'/includes/authentication.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $first_name = trim($_POST['first_name'] ??'');
    $last_name = trim($_POST['last_name'] ??'');
    $phone = trim($_POST['phone'] ??'');
    $location = trim($_POST['location'] ??'');
    $role = $_POST['role'] ?? 'buyer';

    //Validation
    if ($username === '' || $email === '' || $password === '' || $first_name === '') {
        $error = 'All required fields must be filled.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($role, ['buyer', 'seller'])) {
        $error = 'Invalid role selected.';
    } else {
        // Check duplicates
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $error = 'A user with this email or username already exists.';
        } else {
            // Create user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, phone, location_user, role_user) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hash, $first_name, $last_name, $phone, $location, $role]);

            // Auto-login
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$pdo->lastInsertId()]);
            $user = $stmt->fetch();
            log_in_user($user['user_id'], $user['role_user'], $user['first_name']);

            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Welcome to WhyNot?, ' . $first_name . '!'];
            header('Location: index.php');
            exit;
        }
    }
}

$page_title = 'Sign Up - WhyNot?';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="vd-form-card">
                <h2 class="text-center">Create your account</h2>
                <p class="text-muted text-center mb-4">Join WhyNot? in less than a minute</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First name *</label>
                            <input type="text" name="first_name" class="form-control" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last name</label>
                            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ??'') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label> <small class="text-muted">(min 8 characters)</small>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ??'') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($_POST['location'] ??'') ?> ">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">I want to *</label>
                        <select name="role" class="form-select" required>
                            <option value="buyer">Buy Only</option>
                            <option value="seller">Buy & Sell</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Create Account</button>
                </form>

                <p class="text-center mt-3 small">
                    Already have an account? <a href="login.php">Log in here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>