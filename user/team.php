<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

check_auth();
$user = get_logged_in_user($pdo);
block_if_not_verified($user);

// Fetch referrals
$stmt = $pdo->prepare("SELECT username, email, created_at FROM users WHERE referred_by = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$referrals = $stmt->fetchAll();

$referral_link = "http://" . $_SERVER['HTTP_HOST'] . "/auth/register.php?ref=" . $user['ref_code'];

$page_title = "My Team";
$active_page = 'team';
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <h4 class="fw-bold">My Referral Network</h4>
        <p class="text-muted small">Invite friends and earn commissions.</p>
    </div>

    <div class="col-12 mb-4">
        <div class="card p-3 bg-light">
            <label class="small fw-bold text-muted mb-2">Your Referral Link</label>
            <div class="input-group">
                <input type="text" class="form-control" value="<?php echo $referral_link; ?>" id="refLink" readonly>
                <button class="btn btn-outline-primary" type="button" onclick="copyLink()">Copy</button>
            </div>
        </div>
    </div>

    <div class="col-12">
        <h6 class="fw-bold mb-3">Referrals (<?php echo count($referrals); ?>)</h6>
        <?php if (empty($referrals)): ?>
            <div class="card p-4 text-center text-muted">
                <i class="fas fa-users fa-3x mb-3 opacity-25"></i>
                <p>No referrals yet. Share your link to start building your team!</p>
            </div>
        <?php else: ?>
            <?php foreach ($referrals as $ref): ?>
                <div class="card p-3 mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold d-block"><?php echo htmlspecialchars($ref['username']); ?></span>
                            <small class="text-muted"><?php echo htmlspecialchars($ref['email']); ?></small>
                        </div>
                        <small class="text-muted"><?php echo date('M d, Y', strtotime($ref['created_at'])); ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function copyLink() {
    var copyText = document.getElementById("refLink");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    alert("Copied: " + copyText.value);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
