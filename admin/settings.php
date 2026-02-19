<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

check_auth();
check_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    foreach ($_POST as $key => $value) {
        if ($key === 'submit') continue;
        $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
        $stmt->execute([$key, $value, $value]);
    }
    set_flash_message('success', 'Settings updated.');
    redirect('/admin/settings.php');
}

$stmt = $pdo->query("SELECT * FROM settings");
$settings_raw = $stmt->fetchAll();
$settings = [];
foreach ($settings_raw as $s) {
    $settings[$s['key']] = $s['value'];
}

$page_title = "Site Settings";
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="d-flex align-items-center">
            <a href="/admin/dashboard.php" class="me-3 text-dark"><i class="fas fa-arrow-left"></i></a>
            <h4 class="fw-bold mb-0">Settings</h4>
        </div>
    </div>

    <div class="col-12">
        <div class="card p-3">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <h6 class="fw-bold mb-3 border-bottom pb-2">General Settings</h6>
                <div class="mb-3">
                    <label class="form-label">Site Name</label>
                    <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Minimum Withdrawal ($)</label>
                    <input type="number" step="0.01" name="min_withdrawal" class="form-control" value="<?php echo htmlspecialchars($settings['min_withdrawal'] ?? '10.00'); ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label">Default Referral Commission (%)</label>
                    <input type="number" step="0.01" name="referral_commission" class="form-control" value="<?php echo htmlspecialchars($settings['referral_commission'] ?? '10'); ?>">
                </div>

                <h6 class="fw-bold mb-3 border-bottom pb-2">SMTP Configuration (Mail)</h6>
                <p class="small text-muted">Currently configured in <code>config/mail.php</code>. Use this form to override if implemented in code.</p>
                <div class="mb-3">
                    <label class="form-label">SMTP Host</label>
                    <input type="text" name="smtp_host" class="form-control" placeholder="smtp.example.com">
                </div>
                <div class="mb-3">
                    <label class="form-label">SMTP User</label>
                    <input type="text" name="smtp_user" class="form-control" placeholder="user@example.com">
                </div>
                <div class="mb-3">
                    <label class="form-label">SMTP Password</label>
                    <input type="password" name="smtp_pass" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_pass'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">SMTP Port</label>
                    <input type="number" name="smtp_port" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">SMTP From Email</label>
                    <input type="email" name="smtp_from" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_from'] ?? ''); ?>">
                </div>

                <button type="submit" name="submit" class="btn btn-primary w-100 rounded-pill">Save All Settings</button>
            </form>
        </div>

        <div class="card p-3 mt-3">
             <h6 class="fw-bold mb-3 border-bottom pb-2">Broadcast Email</h6>
             <form action="/admin/broadcast.php" method="POST">
                 <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                 <div class="mb-3">
                     <label class="form-label">Subject</label>
                     <input type="text" name="subject" class="form-control" required>
                 </div>
                 <div class="mb-3">
                     <label class="form-label">Message</label>
                     <textarea name="message" class="form-control" rows="4" required></textarea>
                 </div>
                 <button type="submit" class="btn btn-outline-primary w-100 rounded-pill">Send to All Users</button>
             </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
