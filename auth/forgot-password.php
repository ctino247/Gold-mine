<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // I'll repurpose the otp fields or add a new table for reset tokens.
        // For simplicity, I'll add a reset_token and reset_token_expiry to users table.
        // Wait, I should update the schema.

        $stmt = $pdo->prepare("UPDATE users SET otp_hash = ?, otp_expiry = ? WHERE id = ?");
        $stmt->execute([password_hash($token, PASSWORD_DEFAULT), $expiry, $user['id']]);

        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/auth/reset-password.php?email=" . urlencode($email) . "&token=" . $token;

        $subject = "Password Reset Request";
        $message = "Click the following link to reset your password: <a href='$reset_link'>$reset_link</a>. Link expires in 1 hour.";
        send_email($email, $subject, $message, $pdo);

        set_flash_message('success', 'Password reset link has been sent to your email.');
    } else {
        set_flash_message('danger', 'Email not found.');
    }
}

$page_title = "Forgot Password";
include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card p-4">
            <h3 class="text-center mb-4">Forgot Password</h3>
            <p class="text-center">Enter your email to receive a reset link.</p>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
            </form>

            <div class="text-center mt-3">
                <a href="/auth/login.php">Back to Login</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
