<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

check_auth();
check_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdrawal_id'])) {
    $id = (int)$_POST['withdrawal_id'];
    $action = $_POST['action']; // approve or reject

    $stmt = $pdo->prepare("SELECT w.*, u.email FROM withdrawals w JOIN users u ON w.user_id = u.id WHERE w.id = ?");
    $stmt->execute([$id]);
    $withdrawal = $stmt->fetch();

    if ($withdrawal && $withdrawal['status'] === 'pending') {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE withdrawals SET status = 'approved' WHERE id = ?");
            $stmt->execute([$id]);

            $stmt = $pdo->prepare("UPDATE transactions SET status = 'completed' WHERE user_id = ? AND type = 'withdrawal' AND amount = ? AND status = 'pending' LIMIT 1");
            $stmt->execute([$withdrawal['user_id'], -$withdrawal['amount']]);

            send_email($withdrawal['email'], "Withdrawal Approved", "Your withdrawal request for " . format_currency($withdrawal['amount']) . " has been approved and processed.");
            set_flash_message('success', 'Withdrawal approved.');
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE withdrawals SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$id]);

            // Refund balance
            $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$withdrawal['amount'], $withdrawal['user_id']]);

            $stmt = $pdo->prepare("UPDATE transactions SET status = 'failed' WHERE user_id = ? AND type = 'withdrawal' AND amount = ? AND status = 'pending' LIMIT 1");
            $stmt->execute([$withdrawal['user_id'], -$withdrawal['amount']]);

            send_email($withdrawal['email'], "Withdrawal Rejected", "Your withdrawal request for " . format_currency($withdrawal['amount']) . " was rejected and funds have been returned to your wallet.");
            set_flash_message('info', 'Withdrawal rejected.');
        }
    }
}

$stmt = $pdo->query("SELECT w.*, u.username FROM withdrawals w JOIN users u ON w.user_id = u.id ORDER BY w.created_at DESC");
$withdrawals = $stmt->fetchAll();

$page_title = "Manage Withdrawals";
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="d-flex align-items-center">
            <a href="/admin/dashboard.php" class="me-3 text-dark"><i class="fas fa-arrow-left"></i></a>
            <h4 class="fw-bold mb-0">Withdrawals</h4>
        </div>
    </div>

    <?php foreach ($withdrawals as $w): ?>
        <div class="col-12 mb-3">
            <div class="card p-3">
                <div class="d-flex justify-content-between">
                    <span class="fw-bold"><?php echo htmlspecialchars($w['username']); ?></span>
                    <span class="badge bg-<?php
                        echo $w['status'] == 'approved' ? 'success' : ($w['status'] == 'pending' ? 'warning' : 'danger');
                    ?>"><?php echo ucfirst($w['status']); ?></span>
                </div>
                <h5 class="my-2"><?php echo format_currency($w['amount']); ?></h5>
                <p class="small text-muted mb-3"><?php echo nl2br(htmlspecialchars($w['payment_details'])); ?></p>

                <?php if ($w['status'] === 'pending'): ?>
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="withdrawal_id" value="<?php echo $w['id']; ?>">
                        <button type="submit" name="action" value="approve" class="btn btn-success flex-grow-1">Approve</button>
                        <button type="submit" name="action" value="reject" class="btn btn-danger flex-grow-1">Reject</button>
                    </form>
                <?php endif; ?>
                <small class="text-muted mt-2 d-block"><?php echo $w['created_at']; ?></small>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
