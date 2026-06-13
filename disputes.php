<?php
require_once '../config/db.php';
require_once '../config/security.php';
require_once '../config/session.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

include '../includes/header.php';
// Handle resolve/close
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dispute_id = (int)$_POST['dispute_id'];
    $action = $_POST['action'];
    $allowed = ['resolved', 'closed'];
    if (in_array($action, $allowed)) {
        $pdo->prepare("UPDATE disputes SET status = ? WHERE id = ?")
            ->execute([$action, $dispute_id]);
    }
    header('Location: disputes.php');
    exit;
}

$disputes = $pdo->query("
    SELECT d.*, o.amount, l.title as listing_title,
    u.full_name as raised_by_name, u.email as raised_by_email
    FROM disputes d
    JOIN orders o ON d.order_id = o.id
    JOIN listings l ON o.listing_id = l.id
    JOIN users u ON d.raised_by = u.id
    ORDER BY d.created_at DESC
")->fetchAll();
?>

<div style="max-width:1100px;margin:40px auto;padding:0 20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;">
        <h2>Disputes (<?= count($disputes) ?>)</h2>
        <a href="dashboard.php" class="btn-secondary">← Back to Admin</a>
    </div>

    <?php if (empty($disputes)): ?>
        <div style="background:white;border-radius:12px;padding:60px;text-align:center;box-shadow:var(--shadow);">
            <div style="font-size:3rem;">✅</div>
            <h3 style="margin:15px 0 10px;">No disputes</h3>
            <p style="color:var(--gray);">All transactions are running smoothly!</p>
        </div>
    <?php else: ?>
        <?php foreach ($disputes as $d): ?>
            <div style="background:white;border-radius:12px;padding:25px;box-shadow:var(--shadow);margin-bottom:15px;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <h3 style="margin-bottom:8px;"><?= htmlspecialchars($d['listing_title']) ?></h3>
                        <p style="color:var(--gray);font-size:0.85rem;margin-bottom:10px;">
                            Raised by: <strong><?= htmlspecialchars($d['raised_by_name']) ?></strong>
                            (<?= htmlspecialchars($d['raised_by_email']) ?>)
                            &bull; <?= date('d M Y', strtotime($d['created_at'])) ?>
                            &bull; Order value: <strong>R <?= number_format($d['amount'],2) ?></strong>
                        </p>
                        <div style="background:var(--light);padding:12px 15px;border-radius:8px;font-size:0.9rem;">
                            <strong>Reason:</strong> <?= htmlspecialchars($d['reason']) ?>
                        </div>
                    </div>
                    <span style="padding:5px 15px;border-radius:10px;font-size:0.85rem;white-space:nowrap;margin-left:20px;
                        background:<?= $d['status']==='open' ? '#f8d7da' : '#d4edda' ?>;
                        color:<?= $d['status']==='open' ? '#721c24' : '#155724' ?>;">
                        <?= ucfirst($d['status']) ?>
                    </span>
                </div>
                <?php if ($d['status'] === 'open'): ?>
                    <div style="margin-top:15px;display:flex;gap:10px;">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="dispute_id" value="<?= $d['id'] ?>">
                            <button name="action" value="resolved" class="btn-primary" style="padding:8px 20px;">
                                Mark Resolved
                            </button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="dispute_id" value="<?= $d['id'] ?>">
                            <button name="action" value="closed" class="btn-secondary" style="padding:8px 20px;">
                                Close Dispute
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>