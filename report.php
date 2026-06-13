<?php
include 'includes/header.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF($_POST['csrf_token'] ?? '');
    $type = sanitize($_POST['type']);
    $target = sanitize($_POST['target']);
    $reason = sanitize($_POST['reason']);
    $details = sanitize($_POST['details']);

    if (empty($type) || empty($target) || empty($reason)) {
        $error = 'Please fill in all required fields.';
    } else {
        require_once 'config/mail.php';
        $email_body = "
            <p><strong>Reported by:</strong> {$_SESSION['full_name']} (ID: {$_SESSION['user_id']})</p>
            <p><strong>Report Type:</strong> $type</p>
            <p><strong>Target:</strong> $target</p>
            <p><strong>Reason:</strong> $reason</p>
            <p><strong>Details:</strong><br>" . nl2br($details) . "</p>
        ";
        sendEmail('your@gmail.com', 'KasiBuys Admin', 'New Report: ' . $type, $email_body);
        $success = 'Your report has been submitted. Our team will review it within 24 hours.';
    }
}
?>

<div class="form-container" style="max-width:600px;">
    <h2>Report a Problem</h2>
    <p class="subtitle">Help us keep KasiBuys safe for everyone</p>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
        <div class="form-group">
            <label>What are you reporting? *</label>
            <select name="type" required>
                <option value="">Select type</option>
                <option value="Fake Listing">Fake or Misleading Listing</option>
                <option value="Scam">Scam or Fraud</option>
                <option value="Inappropriate Content">Inappropriate Content</option>
                <option value="Fake Profile">Fake Profile</option>
                <option value="Harassment">Harassment or Abuse</option>
                <option value="Counterfeit Item">Counterfeit Item</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div class="form-group">
            <label>Listing URL or Username *</label>
            <input type="text" name="target" placeholder="e.g. listing ID or username" required>
        </div>
        <div class="form-group">
            <label>Reason *</label>
            <select name="reason" required>
                <option value="">Select reason</option>
                <option value="Dangerous Item">Dangerous or Illegal Item</option>
                <option value="Wrong Description">Item Not as Described</option>
                <option value="No Delivery">Payment Sent but No Delivery</option>
                <option value="Fake Photos">Fake or Stolen Photos</option>
                <option value="Spam">Spam</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div class="form-group">
            <label>Additional Details</label>
            <textarea name="details" placeholder="Give us as much detail as possible..." style="min-height:120px;"></textarea>
        </div>
        <button type="submit" class="btn-danger" style="width:100%;padding:14px;">
            <i class="fas fa-flag"></i> Submit Report
        </button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>