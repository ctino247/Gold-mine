<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

check_auth();
check_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $price = (float)$_POST['price'];
    $benefits = sanitize($_POST['benefits']);
    $commission = (float)$_POST['commission'];

    if (isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("UPDATE contracts SET name = ?, price = ?, benefits = ?, referral_commission_percentage = ? WHERE id = ?");
        $stmt->execute([$name, $price, $benefits, $commission, $id]);
        set_flash_message('success', 'Contract updated.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO contracts (name, price, benefits, referral_commission_percentage) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $price, $benefits, $commission]);
        set_flash_message('success', 'Contract created.');
    }
}

$stmt = $pdo->query("SELECT * FROM contracts");
$contracts = $stmt->fetchAll();

$page_title = "Manage Contracts";
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <a href="/admin/dashboard.php" class="me-3 text-dark"><i class="fas fa-arrow-left"></i></a>
                <h4 class="fw-bold mb-0">Contracts</h4>
            </div>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addContract">Add New</button>
        </div>
    </div>

    <?php foreach ($contracts as $c): ?>
        <div class="col-12 mb-3">
            <div class="card p-3">
                <h5 class="fw-bold"><?php echo htmlspecialchars($c['name']); ?></h5>
                <p class="text-success fw-bold mb-1"><?php echo format_currency($c['price']); ?></p>
                <p class="small text-muted mb-2">Commission: <?php echo $c['referral_commission_percentage']; ?>%</p>
                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editContract<?php echo $c['id']; ?>">Edit</button>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editContract<?php echo $c['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Contract</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($c['name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Price ($)</label>
                                <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $c['price']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Commission (%)</label>
                                <input type="number" step="0.01" name="commission" class="form-control" value="<?php echo $c['referral_commission_percentage']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Benefits</label>
                                <textarea name="benefits" class="form-control" rows="3"><?php echo htmlspecialchars($c['benefits']); ?></textarea>
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

<!-- Add Modal -->
<div class="modal fade" id="addContract" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Contract</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price ($)</label>
                        <input type="number" step="0.01" name="price" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Commission (%)</label>
                        <input type="number" step="0.01" name="commission" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Benefits</label>
                        <textarea name="benefits" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Create Contract</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
