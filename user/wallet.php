<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

check_auth();
$user = get_logged_in_user($pdo);
block_if_not_verified($user);

// Handle withdrawal request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    $amount = (float)$_POST['amount'];
    $payment_method_id = (int)$_POST['payment_method_id'];
    $payment_details = sanitize($_POST['payment_details']);
    $min_withdrawal = (float)get_setting($pdo, 'min_withdrawal', '10.00');

    if ($amount >= $min_withdrawal && $payment_method_id > 0) {
        if ($user['balance'] >= $amount) {
            $pdo->beginTransaction();
            try {
                // Deduct balance
                $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                $stmt->execute([$amount, $user['id']]);

                // Create withdrawal request
                $stmt = $pdo->prepare("INSERT INTO withdrawals (user_id, amount, payment_method_id, payment_details) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user['id'], $amount, $payment_method_id, $payment_details]);

                // Record transaction
                $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status, description) VALUES (?, 'withdrawal', ?, 'pending', 'Withdrawal request')");
                $stmt->execute([$user['id'], -$amount]);

                $pdo->commit();
                set_flash_message('success', 'Withdrawal request submitted successfully!');
                redirect('/user/wallet.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash_message('danger', 'Request failed: ' . $e->getMessage());
            }
        } else {
            set_flash_message('danger', 'Insufficient balance.');
        }
    } else {
        set_flash_message('danger', 'Minimum withdrawal is ' . format_currency($min_withdrawal));
    }
}

// Fetch transactions
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$transactions = $stmt->fetchAll();

// Fetch active payment methods
$stmt = $pdo->query("SELECT * FROM payment_methods WHERE status = 'active'");
$payment_methods = $stmt->fetchAll();

$page_title = "Wallet";
$active_page = 'wallet';
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <h4 class="fw-bold">My Wallet</h4>
    </div>

    <div class="col-12 mb-4">
        <div class="card p-4 bg-primary text-white text-center">
            <small class="opacity-75">Available Balance</small>
            <h1 class="fw-bold"><?php echo format_currency($user['balance']); ?></h1>
            <button class="btn btn-light rounded-pill mt-3 px-4" data-bs-toggle="modal" data-bs-target="#withdrawModal">Withdraw Funds</button>
        </div>
    </div>

    <div class="col-12">
        <h6 class="fw-bold mb-3">Transaction History</h6>
        <?php if (empty($transactions)): ?>
            <div class="card p-4 text-center text-muted">
                <p>No transactions yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($transactions as $tx): ?>
                <div class="card p-3 mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold d-block"><?php echo ucfirst($tx['type']); ?></span>
                            <small class="text-muted"><?php echo htmlspecialchars($tx['description']); ?></small>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold <?php echo $tx['amount'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo ($tx['amount'] > 0 ? '+' : '') . format_currency($tx['amount']); ?>
                            </span>
                            <br>
                            <small class="text-muted"><?php echo date('M d, H:i', strtotime($tx['created_at'])); ?></small>
                        </div>
                    </div>
                    <?php if ($tx['status'] !== 'completed'): ?>
                        <div class="mt-1">
                            <span class="badge bg-<?php echo $tx['status'] == 'pending' ? 'warning' : 'secondary'; ?> small"><?php echo ucfirst($tx['status']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Withdrawal Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Withdraw Funds</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Amount (Min: <?php echo format_currency(get_setting($pdo, 'min_withdrawal', '10.00')); ?>)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Payment Method</label>
                        <select name="payment_method_id" class="form-select mb-2" id="methodSelect" onchange="updateDetailsHint()">
                            <option value="">-- Select Method --</option>
                            <?php foreach ($payment_methods as $pm): ?>
                                <option value="<?php echo $pm['id']; ?>" data-details="<?php echo htmlspecialchars($pm['details']); ?>">
                                    <?php echo htmlspecialchars($pm['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="methodHint" class="small text-info mb-2"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Your Receiving Details</label>
                        <textarea name="payment_details" class="form-control" rows="3" placeholder="Enter your bank account, wallet address, etc." required></textarea>
                    </div>

                    <script>
                    function updateDetailsHint() {
                        const select = document.getElementById('methodSelect');
                        const hint = document.getElementById('methodHint');
                        const selectedOption = select.options[select.selectedIndex];
                        if (selectedOption.value) {
                            hint.innerHTML = "<strong>Note:</strong> " + selectedOption.getAttribute('data-details');
                        } else {
                            hint.innerHTML = "";
                        }
                    }
                    </script>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
