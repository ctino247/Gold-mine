<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

function check_auth() {
    if (!is_logged_in()) {
        redirect('/auth/login.php');
    }
}

function check_admin() {
    if (!is_admin()) {
        set_flash_message('danger', 'Unauthorized access.');
        redirect('/user/dashboard.php');
    }
}

function get_logged_in_user($pdo) {
    if (!is_logged_in()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function block_if_not_verified($user) {
    if (!$user['is_verified']) {
        redirect('/auth/verify.php');
    }
}
?>
