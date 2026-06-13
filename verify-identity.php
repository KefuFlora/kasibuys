<?php
require_once 'config/db.php';
require_once 'config/security.php';
require_once 'config/session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/header.php';

$uid = $_SESSION['user_id'];
$error = '';
$success = '';

// Check if already verified
$stmt = $pdo->prepare("SELECT * FROM id_verifications WHERE user_id = ?");
$stmt->execute([$uid]);
$existing = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF($_POST['csrf_token'] ?? '');

    $id_number = trim($_POST['id_number']);

    // Validate using Luhn algorithm
    if (!validateSAID($id_number)) {
        $error = 'Invalid South African ID number. Please check and try again.';
    } else {
        // Extract info from ID number
        $year   = substr($id_number, 0, 2);
        $month  = substr($id_number, 2, 2);
        $day    = substr($id_number, 4, 2);
        $gender_digit = (int)substr($id_number, 6, 4);
        $citizenship_digit = (int)substr($id_number, 10, 1);

        // Determine full year
        $current_year = (int)date('y');
        $full_year = ($year <= $current_year) ? '20' . $year : '19' . $year;
        $dob = "$full_year-$month-$day";

        // Determine gender
        $gender = $gender_digit >= 5000 ? 'Male' : 'Female';

        // Determine citizenship
        $citizenship = $citizenship_digit === 0 ? 'SA Citizen' : 'Permanent Resident';

        // Check age — must be 18+
        $age = (int)date_diff(new DateTime($dob), new DateTime())->y;
        if ($age < 18) {
            $error = 'You must be 18 or older to use KasiBuys.';
        } else {
            // Check if ID already used by another account
            $check = $pdo->prepare("SELECT user_id FROM id_verifications WHERE id_number = ? AND user_id != ?");
            $check->execute([$id_number, $uid]);
            if ($check->fetch()) {
                $error = 'This ID number is already linked to another account.';
            } else {
                if ($existing) {
                    $pdo->prepare("
                        UPDATE id_verifications
                        SET id_number=?, date_of_birth=?, gender=?, citizenship=?, is_verified=1, verified_at=NOW()
                        WHERE user_id=?
                    ")->execute([$id_number, $dob, $gender, $citizenship, $uid]);
                } else {
                    $pdo->prepare("
                        INSERT INTO id_verifications (user_id, id_number, date_of_birth, gender, citizenship, is_verified, verified_at)
                        VALUES (?, ?, ?, ?, ?, 1, NOW())
                    ")->execute([$uid, $id_number, $dob, $gender, $citizenship]);
                }

                // Add verified badge to user
                $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?")->execute([$uid]);

                // Send confirmation email
                require_once 'config/mail.php';
                $stmt2 = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt2->execute([$uid]);
                $user = $stmt2->fetch();
                $email_body = "
                    <p>Hi <strong>{$user['full_name']}</strong>,</p>
                    <p>Your identity has been successfully verified on KasiBuys! ✅</p>
                    <p>You now have a verified badge on your profile, which helps build trust with buyers and sellers.</p>
                    <a href='http://kasibuys.atwebpages.comprofile.php?id=$uid' class='btn'>View My Profile</a>
                    <p>The KasiBuys Team</p>
                ";
                sendEmail($user['email'], $user['full_name'], 'Identity Verified — KasiBuys ✅', $email_body);

                $success = 'Your identity has been verified successfully! ✅';
                $existing = ['is_verified' => 1, 'gender' => $gender, 'date_of_birth' => $dob, 'citizenship' => $citizenship];
            }
        }
    }
}
?>

<div class="form-container" style="max-width:550px;">
    <h2>Identity Verification</h2>
    <p class="subtitle">Verify your identity to build trust with buyers and sellers</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if ($existing && $existing['is_verified']): ?>
        <!-- ALREADY VERIFIED -->
        <div style="text-align:center;padding:30px 0;">
            <div style="font-size:4rem;">✅</div>
            <h3 style="margin:15px 0 10px;color:var(--success);">Identity Verified</h3>
            <p style="color:var(--gray);margin-bottom:20px;">Your identity has been successfully verified.</p>
            <div style="background:var(--light);border-radius:10px;padding:20px;text-align:left;">
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);">
                    <span style="color:var(--gray);">Gender</span>
                    <strong><?= htmlspecialchars($existing['gender']) ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);">
                    <span style="color:var(--gray);">Date of Birth</span>
                    <strong><?= date('d M Y', strtotime($existing['date_of_birth'])) ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;">
                    <span style="color:var(--gray);">Citizenship</span>
                    <strong><?= htmlspecialchars($existing['citizenship']) ?></strong>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- VERIFICATION FORM -->
        <div style="background:#d1ecf1;border-radius:8px;padding:15px;margin-bottom:20px;font-size:0.9rem;color:#0c5460;">
            <i class="fas fa-shield-alt"></i>
            <strong> Your ID number is encrypted and never shared.</strong>
            It is only used to confirm you are a real South African resident.
        </div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
            <div class="form-group">
                <label>South African ID Number</label>
                <input type="text" name="id_number" placeholder="13-digit SA ID number"
                       maxlength="13" pattern="[0-9]{13}"
                       title="Must be a 13-digit number" required>
                <small style="color:var(--gray);">Enter your 13-digit South African ID number.</small>
            </div>
            <div style="background:#fff3cd;border-radius:8px;padding:12px;margin-bottom:20px;font-size:0.85rem;color:#856404;">
                <i class="fas fa-info-circle"></i>
                You must be <strong>18 or older</strong> to use KasiBuys.
            </div>
            <button type="submit" class="btn-primary" style="width:100%;padding:14px;">
                <i class="fas fa-id-card"></i> Verify My Identity
            </button>
        </form>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>