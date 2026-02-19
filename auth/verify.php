<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$user_id = $_SESSION['temp_user_id'] ?? null;

if (!$user_id) {
    if (is_logged_in()) {
        $user_id = $_SESSION['user_id'];
    } else {
        redirect('/auth/login.php');
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('/auth/login.php');
}

if ($user['is_verified']) {
    redirect('/user/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['otp'])) {
        $otp = sanitize($_POST['otp']);

        if (password_verify($otp, $user['otp_hash']) && strtotime($user['otp_expiry']) > time()) {
            $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, otp_hash = NULL, otp_expiry = NULL WHERE id = ?");
            $stmt->execute([$user_id]);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            unset($_SESSION['temp_user_id']);

            set_flash_message('success', 'Account verified successfully!');
            redirect('/user/dashboard.php');
        } else {
            set_flash_message('danger', 'Invalid or expired OTP.');
        }
    } elseif (isset($_POST['resend'])) {
        if ($user['resend_count'] >= 5) {
            set_flash_message('danger', 'Maximum resend attempts reached.');
        } elseif ($user['last_resend_at'] && strtotime($user['last_resend_at']) > time() - 60) {
            set_flash_message('warning', 'Please wait 60 seconds before resending.');
        } else {
            $otp = generate_otp();
            $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $stmt = $pdo->prepare("UPDATE users SET otp_hash = ?, otp_expiry = ?, resend_count = resend_count + 1, last_resend_at = NOW() WHERE id = ?");
            $stmt->execute([$otp_hash, $otp_expiry, $user_id]);

            send_email($user['email'], "Your new OTP", "Your new OTP is: <b>$otp</b>", $pdo);
            set_flash_message('success', 'A new OTP has been sent to your email.');
        }
    }
}

$page_title = "Verify OTP";
include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card p-4 text-center">
            <h3>Verify Account</h3>
            <p>Please enter the 6-digit OTP sent to your email (<?php echo $user['email']; ?>).</p>

            <form method="POST" action="">
                <div class="mb-3">
                    <input type="text" name="otp" class="form-control text-center" maxlength="6" placeholder="000000" required style="font-size: 24px; letter-spacing: 5px;">
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-3">Verify</button>
            </form>

            <form method="POST" action="">
                <button type="submit" name="resend" class="btn btn-link">Resend OTP</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
