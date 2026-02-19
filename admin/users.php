<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

check_auth();
check_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    $user_id = (int)$_POST['user_id'];
    $new_balance = (float)$_POST['balance'];
    $new_coin_balance = (float)$_POST['coin_balance'];

    $stmt = $pdo->prepare("UPDATE users SET balance = ?, coin_balance = ? WHERE id = ?");
    if ($stmt->execute([$new_balance, $new_coin_balance, $user_id])) {
        set_flash_message('success', 'User balance updated.');
    } else {
        set_flash_message('danger', 'Failed to update balance.');
    }
}

$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

$page_title = "Manage Users";
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="d-flex align-items-center">
            <a href="/admin/dashboard.php" class="me-3 text-dark"><i class="fas fa-arrow-left"></i></a>
            <h4 class="fw-bold mb-0">Manage Users</h4>
        </div>
    </div>

    <?php foreach ($users as $u): ?>
        <div class="col-12 mb-3">
            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="fw-bold"><?php echo htmlspecialchars($u['username']); ?></span>
                        <small class="text-muted d-block"><?php echo htmlspecialchars($u['email']); ?></small>
                        <small class="text-muted d-block"><?php echo htmlspecialchars($u['phone']); ?></small>
                    </div>
                    <span class="badge bg-<?php echo $u['is_verified'] ? 'success' : 'warning'; ?>">
                        <?php echo $u['is_verified'] ? 'Verified' : 'Unverified'; ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <small class="text-muted d-block">Balance</small>
                        <span class="fw-bold"><?php echo format_currency($u['balance']); ?></span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Coins</small>
                        <span class="fw-bold"><?php echo number_format($u['coin_balance'], 0); ?></span>
                    </div>
                </div>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUser<?php echo $u['id']; ?>">Edit Balances</button>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editUser<?php echo $u['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit User: <?php echo htmlspecialchars($u['username']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">Balance ($)</label>
                                <input type="number" step="0.01" name="balance" class="form-control" value="<?php echo $u['balance']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Coins Balance</label>
                                <input type="number" step="0.01" name="coin_balance" class="form-control" value="<?php echo $u['coin_balance']; ?>" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
