<?php
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ .'/includes/authentication.php';
    
    log_out_user();
    session_start();
    $_SESSION['flash'] = ['type' => 'info', 'msg' => 'You have been logged out.'];
    header('Location: index.php');
    exit;
?>