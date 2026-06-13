<?php
include 'includes/header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF($_POST['csrf_token'] ?? '');
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        require_once 'config/mail.php';
        $email_body = "
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Subject:</strong> $subject</p>
            <p><strong>Message:</strong><br>" . nl2br($message) . "</p>
        ";
        sendEmail('your@gmail.com', 'KasiBuys Support', 'Contact Form: ' . $subject, $email_body);
        $success = 'Your message has been sent! We will get back to you within 24 hours.';
    }
}
?>

<div class="form-container" style="max-width:600px;">
    <h2>Contact Us</h2>
    <p class="subtitle">We typically respond within 24 hours</p>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:15px;margin-bottom:30px;text-align:center;">
        <div style="background:var(--light);border-radius:10px;padding:20px;">
            <div style="font-size:2rem;">📧</div>
            <div style="font-size:0.8rem;margin-top:8px;color:var(--gray);">Email Support</div>
            <div style="font-size:0.8rem;font-weight:600;">support@kasibuys.co.za</div>
        </div>
        <div style="background:var(--light);border-radius:10px;padding:20px;">
            <div style="font-size:2rem;">💬</div>
            <div style="font-size:0.8rem;margin-top:8px;color:var(--gray);">Live Chat</div>
            <div style="font-size:0.8rem;font-weight:600;">Mon–Fri 8am–5pm</div>
        </div>
        <div style="background:var(--light);border-radius:10px;padding:20px;">
            <div style="font-size:2rem;">📞</div>
            <div style="font-size:0.8rem;margin-top:8px;color:var(--gray);">Phone</div>
            <div style="font-size:0.8rem;font-weight:600;">0800 KASIBUYS</div>
        </div>
    </div>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Your Name</label>
                <input type="text" name="name" placeholder="Full name" required
                       value="<?= isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : '' ?>">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="your@email.com" required
                       value="<?= isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : '' ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Subject</label>
            <select name="subject" required>
                <option value="">Select a topic</option>
                <option value="Order Issue">Order Issue</option>
                <option value="Payment Problem">Payment Problem</option>
                <option value="Account Problem">Account Problem</option>
                <option value="Report a User">Report a User</option>
                <option value="Technical Issue">Technical Issue</option>
                <option value="General Enquiry">General Enquiry</option>
            </select>
        </div>
        <div class="form-group">
            <label>Message</label>
            <textarea name="message" placeholder="Describe your issue in detail..." required style="min-height:150px;"></textarea>
        </div>
        <button type="submit" class="btn-primary" style="width:100%;padding:14px;">Send Message</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>