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

// Get all conversations
$stmt = $pdo->prepare("
    SELECT DISTINCT
        CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END AS other_user_id,
        u.full_name, u.profile_photo,
        MAX(m.created_at) as last_message_time,
        SUM(CASE WHEN m.receiver_id = ? AND m.is_read = 0 THEN 1 ELSE 0 END) as unread_count,
        (SELECT message FROM messages WHERE
            (sender_id = ? AND receiver_id = u.id) OR
            (sender_id = u.id AND receiver_id = ?)
            ORDER BY created_at DESC LIMIT 1) as last_message
    FROM messages m
    JOIN users u ON u.id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY other_user_id, u.full_name, u.profile_photo
    ORDER BY last_message_time DESC
");
$stmt->execute([$uid, $uid, $uid, $uid, $uid, $uid, $uid]);
$conversations = $stmt->fetchAll();
?>

<div style="max-width:800px;margin:40px auto;padding:0 20px;">
    <h2 style="margin-bottom:25px;">Messages</h2>

    <?php if (empty($conversations)): ?>
        <div style="background:white;border-radius:12px;padding:60px;text-align:center;box-shadow:var(--shadow);">
            <div style="font-size:3rem;">💬</div>
            <h3 style="margin:15px 0 10px;">No messages yet</h3>
            <p style="color:var(--gray);">When you contact a seller or get contacted, messages will appear here.</p>
        </div>
    <?php else: ?>
        <div style="background:white;border-radius:12px;box-shadow:var(--shadow);overflow:hidden;">
            <?php foreach ($conversations as $conv): ?>
                <a href="conversation.php?user=<?= $conv['other_user_id'] ?>"
                   style="display:flex;align-items:center;gap:15px;padding:20px;border-bottom:1px solid var(--border);text-decoration:none;color:inherit;transition:background 0.2s;"
                   onmouseover="this.style.background='var(--light)'" onmouseout="this.style.background='white'">
                    <img src="<?= htmlspecialchars($conv['profile_photo'] ?? 'images/default-avatar.png') ?>"
                         style="width:50px;height:50px;border-radius:50%;object-fit:cover;">
                    <div style="flex:1;">
                        <div style="display:flex;justify-content:space-between;">
                            <strong><?= htmlspecialchars($conv['full_name']) ?></strong>
                            <small style="color:var(--gray);"><?= date('d M', strtotime($conv['last_message_time'])) ?></small>
                        </div>
                        <div style="color:var(--gray);font-size:0.85rem;margin-top:3px;">
                            <?= htmlspecialchars(substr($conv['last_message'], 0, 60)) ?>...
                        </div>
                    </div>
                    <?php if ($conv['unread_count'] > 0): ?>
                        <span style="background:var(--primary);color:white;border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;">
                            <?= $conv['unread_count'] ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>