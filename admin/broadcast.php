<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

check_auth();
check_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    $subject = sanitize($_POST['subject']);
    $message = $_POST['message']; // Allow HTML in broadcast?

    $stmt = $pdo->query("SELECT email FROM users WHERE is_verified = 1");
    $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $success_count = 0;
    foreach ($emails as $email) {
        if (send_email($email, $subject, $message, $pdo)) {
            $success_count++;
        }
    }

    set_flash_message('success', "Broadcast sent to $success_count users.");
    redirect('/admin/settings.php');
} else {
    redirect('/admin/dashboard.php');
}
?>
