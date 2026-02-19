<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

check_auth();
$user = get_logged_in_user($pdo);
block_if_not_verified($user);

// Handle purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contract_id'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    $contract_id = (int)$_POST['contract_id'];

    $stmt = $pdo->prepare("SELECT * FROM contracts WHERE id = ?");
    $stmt->execute([$contract_id]);
    $contract = $stmt->fetch();

    if ($contract) {
        if ($user['balance'] >= $contract['price']) {
            $pdo->beginTransaction();
            try {
                // Deduct balance
                $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                $stmt->execute([$contract['price'], $user['id']]);

                // Add user contract
                $stmt = $pdo->prepare("INSERT INTO user_contracts (user_id, contract_id, status) VALUES (?, ?, 'active')");
                $stmt->execute([$user['id'], $contract_id]);

                // Record transaction
                $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, description) VALUES (?, 'purchase', ?, ?)");
                $stmt->execute([$user['id'], -$contract['price'], "Purchased " . $contract['name']]);

                // Referral Commission
                if ($user['referred_by']) {
                    $stmt = $pdo->prepare("SELECT id, referred_by FROM users WHERE id = ?");
                    $stmt->execute([$user['referred_by']]);
                    $sponsor = $stmt->fetch();

                    if ($sponsor) {
                        // For simplicity, we use the commission percentage from the contract purchased
                        $commission_pct = $contract['referral_commission_percentage'];
                        $commission_amount = ($contract['price'] * $commission_pct) / 100;

                        if ($commission_amount > 0) {
                            $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                            $stmt->execute([$commission_amount, $sponsor['id']]);

                            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, description) VALUES (?, 'referral', ?, ?)");
                            $stmt->execute([$sponsor['id'], 'referral', $commission_amount, "Commission from " . $user['username'] . " purchasing " . $contract['name']]);

                            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                            $stmt->execute([$sponsor['id'], "You earned " . format_currency($commission_amount) . " referral commission!"]);
                        }
                    }
                }

                $pdo->commit();
                set_flash_message('success', 'Contract purchased successfully!');
                redirect('/user/contracts.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash_message('danger', 'Purchase failed: ' . $e->getMessage());
            }
        } else {
            set_flash_message('danger', 'Insufficient balance.');
        }
    }
}

// Fetch all contracts
$stmt = $pdo->query("SELECT * FROM contracts");
$contracts = $stmt->fetchAll();

// Fetch active user contracts
$stmt = $pdo->prepare("SELECT contract_id FROM user_contracts WHERE user_id = ? AND status = 'active'");
$stmt->execute([$user['id']]);
$active_user_contracts = $stmt->fetchAll(PDO::FETCH_COLUMN);

$page_title = "Contracts";
$active_page = 'contracts';
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <h4 class="fw-bold">Investment Contracts</h4>
        <p class="text-muted small">Choose a plan to start earning commissions.</p>
    </div>

    <?php foreach ($contracts as $contract): ?>
        <div class="col-12 mb-3">
            <div class="card p-3 <?php echo in_array($contract['id'], $active_user_contracts) ? 'border-primary' : ''; ?>">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($contract['name']); ?></h5>
                        <p class="text-success fw-bold mb-2"><?php echo format_currency($contract['price']); ?></p>
                        <p class="small text-muted mb-3"><?php echo nl2br(htmlspecialchars($contract['benefits'])); ?></p>
                    </div>
                    <?php if (in_array($contract['id'], $active_user_contracts)): ?>
                        <span class="badge bg-primary h-50">Active</span>
                    <?php endif; ?>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="contract_id" value="<?php echo $contract['id']; ?>">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill" <?php echo in_array($contract['id'], $active_user_contracts) ? 'disabled' : ''; ?>>
                        <?php echo in_array($contract['id'], $active_user_contracts) ? 'Already Purchased' : 'Purchase Now'; ?>
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
