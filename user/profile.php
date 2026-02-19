<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

check_auth();
$user = get_logged_in_user($pdo);
block_if_not_verified($user);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    $errors = [];

    // Validate current password if changing email/phone or password
    if (!password_verify($current_password, $user['password'])) {
        $errors[] = "Incorrect current password.";
    } else {
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user['id']]);
        }

        $stmt = $pdo->prepare("UPDATE users SET email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$email, $phone, $user['id']]);

        set_flash_message('success', 'Profile updated successfully!');
        redirect('/user/profile.php');
    }

    foreach ($errors as $error) {
        set_flash_message('danger', $error);
    }
}

$page_title = "My Profile";
$active_page = 'profile';
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12 mb-4 text-center">
        <div class="avatar-circle mx-auto mb-3">
            <i class="fas fa-user-circle fa-5x text-secondary"></i>
        </div>
        <h4 class="fw-bold"><?php echo htmlspecialchars($user['username']); ?></h4>
        <span class="badge bg-secondary"><?php echo strtoupper($user['role']); ?></span>
    </div>

    <div class="col-12">
        <div class="card p-3 mb-3">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">New Password (leave blank to keep current)</label>
                    <input type="password" name="new_password" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-primary">Confirm with Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 rounded-pill">Update Profile</button>
            </form>
        </div>

        <div class="card p-3 mb-4">
            <a href="/auth/logout.php" class="btn btn-outline-danger w-100 rounded-pill">Logout</a>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
