<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

check_auth();
$user = get_logged_in_user($pdo);
block_if_not_verified($user);

// Fetch active contract
$stmt = $pdo->prepare("SELECT c.name FROM user_contracts uc JOIN contracts c ON uc.contract_id = c.id WHERE uc.user_id = ? AND uc.status = 'active' LIMIT 1");
$stmt->execute([$user['id']]);
$active_contract = $stmt->fetch();

// Fetch referral earnings
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'referral' AND status = 'completed'");
$stmt->execute([$user['id']]);
$referral_earnings = $stmt->fetch()['total'] ?? 0;

// Fetch latest notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

$page_title = "Dashboard";
$active_page = 'home';
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <h4 class="fw-bold">Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h4>
        <p class="text-muted small">Your recruitment dashboard</p>
    </div>

    <div class="col-6 mb-3">
        <div class="card p-3 h-100 bg-primary text-white">
            <small>Wallet Balance</small>
            <h4 class="mb-0 fw-bold"><?php echo format_currency($user['balance']); ?></h4>
            <i class="fas fa-wallet position-absolute top-0 end-0 p-3 opacity-50"></i>
        </div>
    </div>

    <div class="col-6 mb-3">
        <div class="card p-3 h-100 bg-info text-white">
            <small>Coins Balance</small>
            <h4 class="mb-0 fw-bold"><?php echo number_format($user['coin_balance'], 0); ?> RC</h4>
            <i class="fas fa-coins position-absolute top-0 end-0 p-3 opacity-50"></i>
        </div>
    </div>

    <div class="col-12 mb-3">
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted d-block">Active Contract</small>
                    <span class="fw-bold"><?php echo $active_contract ? htmlspecialchars($active_contract['name']) : 'None'; ?></span>
                </div>
                <div>
                    <a href="/user/contracts.php" class="btn btn-sm btn-outline-primary rounded-pill">Manage</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 mb-3">
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted d-block">Referral Earnings</small>
                    <span class="fw-bold text-success"><?php echo format_currency($referral_earnings); ?></span>
                </div>
                <i class="fas fa-users-cog text-muted"></i>
            </div>
        </div>
    </div>

    <div class="col-12">
        <h6 class="fw-bold mb-3">Recent Notifications</h6>
        <?php if (empty($notifications)): ?>
            <div class="card p-3 text-center">
                <p class="text-muted mb-0">No notifications yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <div class="card p-3 mb-2 <?php echo $notif['is_read'] ? '' : 'border-start border-primary border-4'; ?>">
                    <p class="mb-1 small"><?php echo htmlspecialchars($notif['message']); ?></p>
                    <small class="text-muted"><?php echo date('M d, H:i', strtotime($notif['created_at'])); ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
