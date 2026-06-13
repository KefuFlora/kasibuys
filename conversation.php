<?php
require_once 'config/db.php';
require_once 'config/security.php';
require_once 'config/session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$uid = $_SESSION['user_id'];
$other_id = isset($_GET['user']) ? (int)$_GET['user'] : 0;
$listing_id = isset($_GET['listing']) ? (int)$_GET['listing'] : null;

if (!$other_id || $other_id === $uid) { header('Location: messages.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$other_id]);
$other_user = $stmt->fetch();
if (!$other_user) { header('Location: messages.php'); exit; }

$listing = null;
if ($listing_id) {
    $lstmt = $pdo->prepare("SELECT * FROM listings WHERE id = ?");
    $lstmt->execute([$listing_id]);
    $listing = $lstmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(trim($_POST['message']))) {
    $message = trim($_POST['message']);
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, listing_id, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$uid, $other_id, $listing_id, $message]);

    $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
    $notif->execute([$other_id, $_SESSION['full_name'] . ' sent you a message.', "conversation.php?user=$uid"]);

    header("Location: conversation.php?user=$other_id" . ($listing_id ? "&listing=$listing_id" : ''));
    exit;
}

$pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?")
    ->execute([$other_id, $uid]);

$stmt = $pdo->prepare("
    SELECT m.*, u.full_name, u.profile_photo FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE (m.sender_id = ? AND m.receiver_id = ?)
       OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.created_at ASC
");
$stmt->execute([$uid, $other_id, $other_id, $uid]);
$messages = $stmt->fetchAll();

include 'includes/header.php';
?>

<div style="max-width:800px;margin:40px auto;padding:0 20px;">
    <div style="background:white;border-radius:12px;padding:20px 25px;box-shadow:var(--shadow);margin-bottom:20px;display:flex;align-items:center;gap:15px;">
        <a href="messages.php" style="color:var(--gray);text-decoration:none;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <img src="/<?= htmlspecialchars($other_user['profile_photo'] ?? 'images/default-avatar.png') ?>"
             style="width:45px;height:45px;border-radius:50%;object-fit:cover;">
        <div>
            <strong><?= htmlspecialchars($other_user['full_name']) ?></strong>
            <?php if ($listing): ?>
                <div style="font-size:0.8rem;color:var(--gray);">
                    Re: <a href="listing-single.php?id=<?= $listing['id'] ?>" style="color:var(--primary);">
                        <?= htmlspecialchars(substr($listing['title'], 0, 40)) ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div style="background:white;border-radius:12px;padding:25px;box-shadow:var(--shadow);margin-bottom:20px;min-height:400px;max-height:500px;overflow-y:auto;" id="chat-box">
        <?php if (empty($messages)): ?>
            <p style="text-align:center;color:var(--gray);margin-top:50px;">
                Start the conversation! Say hi to <?= htmlspecialchars(explode(' ', $other_user['full_name'])[0]) ?> 👋
            </p>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <?php $is_mine = $msg['sender_id'] == $uid; ?>
                <div style="display:flex;justify-content:<?= $is_mine ? 'flex-end' : 'flex-start' ?>;margin-bottom:15px;">
                    <?php if (!$is_mine): ?>
                        <img src="/<?= htmlspecialchars($msg['profile_photo'] ?? 'images/default-avatar.png') ?>"
                             style="width:35px;height:35px;border-radius:50%;object-fit:cover;margin-right:10px;align-self:flex-end;">
                    <?php endif; ?>
                    <div style="max-width:70%;">
                        <div style="background:<?= $is_mine ? 'var(--primary)' : 'var(--light)' ?>;
                                    color:<?= $is_mine ? 'white' : '#333' ?>;
                                    padding:12px 16px;border-radius:<?= $is_mine ? '18px 18px 4px 18px' : '18px 18px 18px 4px' ?>;
                                    font-size:0.92rem;line-height:1.5;">
                            <?= nl2br(htmlspecialchars($msg['message'])) ?>
                        </div>
                        <div style="font-size:0.75rem;color:var(--gray);margin-top:4px;text-align:<?= $is_mine ? 'right' : 'left' ?>;">
                            <?= date('d M, H:i', strtotime($msg['created_at'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <form method="POST" style="background:white;border-radius:12px;padding:20px;box-shadow:var(--shadow);display:flex;gap:10px;">
        <input type="hidden" name="listing_id" value="<?= $listing_id ?>">
        <textarea name="message" placeholder="Type your message..." required
                  style="flex:1;padding:12px 16px;border:2px solid var(--border);border-radius:10px;resize:none;height:50px;font-size:0.95rem;outline:none;"
                  onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.submit();}"></textarea>
        <button type="submit" class="btn-primary" style="padding:12px 20px;">
            <i class="fas fa-paper-plane"></i>
        </button>
    </form>
</div>

<script>
    const chatBox = document.getElementById('chat-box');
    chatBox.scrollTop = chatBox.scrollHeight;
</script>

<?php include 'includes/footer.php'; ?>