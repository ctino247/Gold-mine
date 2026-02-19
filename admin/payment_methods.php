<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

check_auth();
check_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }

    $name = sanitize($_POST['name']);
    $details = sanitize($_POST['details']);
    $status = sanitize($_POST['status']);

    if (isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("UPDATE payment_methods SET name = ?, details = ?, status = ? WHERE id = ?");
        $stmt->execute([$name, $details, $status, $id]);
        set_flash_message('success', 'Payment method updated.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO payment_methods (name, details, status) VALUES (?, ?, ?)");
        $stmt->execute([$name, $details, $status]);
        set_flash_message('success', 'Payment method added.');
    }
    redirect('/admin/payment_methods.php');
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM payment_methods WHERE id = ?");
    $stmt->execute([$id]);
    set_flash_message('success', 'Payment method deleted.');
    redirect('/admin/payment_methods.php');
}

$stmt = $pdo->query("SELECT * FROM payment_methods ORDER BY created_at DESC");
$methods = $stmt->fetchAll();

$page_title = "Payment Methods";
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <a href="/admin/dashboard.php" class="me-3 text-dark"><i class="fas fa-arrow-left"></i></a>
                <h4 class="fw-bold mb-0">Payment Methods</h4>
            </div>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMethod">Add New</button>
        </div>
    </div>

    <?php if (empty($methods)): ?>
        <div class="col-12">
            <div class="card p-4 text-center text-muted">
                <p>No payment methods found.</p>
            </div>
        </div>
    <?php endif; ?>

    <?php foreach ($methods as $m): ?>
        <div class="col-12 mb-3">
            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($m['name']); ?></h6>
                        <p class="small text-muted mb-2"><?php echo nl2br(htmlspecialchars($m['details'])); ?></p>
                        <span class="badge bg-<?php echo $m['status'] == 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($m['status']); ?>
                        </span>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editMethod<?php echo $m['id']; ?>">Edit</a></li>
                            <li><a class="dropdown-item text-danger" href="?delete=<?php echo $m['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editMethod<?php echo $m['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Payment Method</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Method Name</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($m['name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Details (e.g., Bank details, Instructions)</label>
                                <textarea name="details" class="form-control" rows="3" required><?php echo htmlspecialchars($m['details']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?php echo $m['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $m['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
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
<div class="modal fade" id="addMethod" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Add Payment Method</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Method Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Bank Transfer" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Details</label>
                        <textarea name="details" class="form-control" rows="3" placeholder="Enter instructions or details..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Add Method</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
