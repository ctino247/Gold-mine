<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

check_auth();
check_admin();

// Stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_balance = $pdo->query("SELECT SUM(balance) FROM users")->fetchColumn();
$pending_withdrawals = $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn();
$total_contracts = $pdo->query("SELECT COUNT(*) FROM user_contracts WHERE status = 'active'")->fetchColumn();

$page_title = "Admin Dashboard";
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12 mb-4 d-flex justify-content-between align-items-center">
        <h4 class="fw-bold">Admin Panel</h4>
        <a href="/user/dashboard.php" class="btn btn-sm btn-outline-secondary">User View</a>
    </div>

    <div class="col-6 mb-3">
        <div class="card p-3 bg-light">
            <small class="text-muted">Total Users</small>
            <h3 class="mb-0"><?php echo $total_users; ?></h3>
        </div>
    </div>
    <div class="col-6 mb-3">
        <div class="card p-3 bg-light">
            <small class="text-muted">Total Balance</small>
            <h3 class="mb-0"><?php echo format_currency($total_balance); ?></h3>
        </div>
    </div>
    <div class="col-6 mb-3">
        <div class="card p-3 bg-light">
            <small class="text-muted">Pending Withdrawals</small>
            <h3 class="mb-0 text-warning"><?php echo $pending_withdrawals; ?></h3>
        </div>
    </div>
    <div class="col-6 mb-3">
        <div class="card p-3 bg-light">
            <small class="text-muted">Active Contracts</small>
            <h3 class="mb-0 text-success"><?php echo $total_contracts; ?></h3>
        </div>
    </div>

    <div class="col-12 mt-4">
        <div class="list-group">
            <a href="/admin/users.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3">
                <span><i class="fas fa-users me-2"></i> Manage Users</span>
                <i class="fas fa-chevron-right small opacity-50"></i>
            </a>
            <a href="/admin/withdrawals.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3">
                <span><i class="fas fa-money-bill-wave me-2"></i> Withdrawals</span>
                <i class="fas fa-chevron-right small opacity-50"></i>
            </a>
            <a href="/admin/contracts.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3">
                <span><i class="fas fa-file-contract me-2"></i> Manage Contracts</span>
                <i class="fas fa-chevron-right small opacity-50"></i>
            </a>
            <a href="/admin/settings.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3">
                <span><i class="fas fa-cog me-2"></i> Site Settings</span>
                <i class="fas fa-chevron-right small opacity-50"></i>
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
