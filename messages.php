<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/authentication.php';
require_login();

$me = current_user_id();
$with = isset($_GET['with']) ? (int)$_GET['with'] : 0;

// Send reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $receiver = (int)$_POST['receiver_id'];
    $content  = trim($_POST['content'] ?? '');
    $product_id = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;
   
    if ($receiver > 0 && $content !== '' && $receiver !== $me) {
        $pdo->prepare("INSERT INTO messages (product_id, sender_id, receiver_id, sender_name, content) VALUES (?, ?, ?, ?, ?)")
            ->execute([$product_id, $me, $receiver, current_user_name(), $content]);
    }
    header('Location: messages.php?with=' . $receiver);
    exit;
}

// Mark as read when opening an conversation
if ($with > 0) {
    $pdo->prepare("UPDATE messages SET is_read = TRUE WHERE sender_id = ? AND receiver_id = ?")
        ->execute([$with, $me]);
}

// Conversation list (one row per other-user)
$convStmt = $pdo->prepare("
    SELECT 
        u.user_id, u.first_name, u.last_name,
        MAX(m.created_at) AS last_message_time,
        SUM(CASE WHEN m.receiver_id = :me1 AND m.is_read = 0 THEN 1 ELSE 0 END) AS unread_count
    FROM messages m
    JOIN users u ON u.user_id = CASE WHEN m.sender_id = :me2 THEN m.receiver_id ELSE m.sender_id END
    WHERE m.sender_id = :me3 OR m.receiver_id = :me4
    GROUP BY u.user_id, u.first_name, u.last_name
    ORDER BY last_message_time DESC
");
$convStmt->execute([':me1' => $me, ':me2' => $me, ':me3' => $me, ':me4' => $me]);
$conversations = $convStmt->fetchAll();

// Latest message preview per conversation
foreach ($conversations as &$conv) {
    $prev = $pdo->prepare("
        SELECT content, sender_id FROM messages
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at DESC LIMIT 1
    ");
    $prev->execute([$me, $conv['user_id'], $conv['user_id'], $me]);
    $latest = $prev->fetch();
    $conv['preview'] = $latest['content'] ?? '';
    $conv['preview_from_me'] = ($latest['sender_id'] ?? 0) == $me;
}
unset($conv);

// Fetch the active conversation thread (if any)
$thread = [];
$otherUser = null;
if ($with > 0) {
    $stmt = $pdo->prepare("SELECT user_id, first_name, last_name FROM users WHERE user_id = ?");
    $stmt->execute([$with]);
    $otherUser = $stmt->fetch();

    if ($otherUser) {
        $stmt = $pdo->prepare("
            SELECT m.*, p.prod_title AS p_title FROM messages m
            LEFT JOIN products p ON m.product_id = p.product_id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$me, $with, $with, $me]);
        $thread = $stmt->fetchAll();
    }
}

$total_unread = array_sum(array_column($conversations, 'unread_count'));

$page_title = "Messages - WhyNot?";
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <h2 class="mb-4">
        <i class="bi bi-chat-dots"></i> My Messages
        <?php if ($total_unread > 0): ?>
            <span class="badge bg-danger"><?= $total_unread ?> new</span>
        <?php endif; ?>
    </h2>
    
    <div class="row">
        <!-- Conversation list -->
        <div class="col-md-4 mb-4">
            <div class="vd-form-card p-0">
                <div class="list-group list-group-flush">
                    <?php if (empty($conversations)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-chat-square-dots fs-1"></i>
                            <p class="mt-2">No conversations yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $c): ?>
                            <a href="messages.php?with=<?= $c['user_id'] ?>"
                                 class="list-group-item list-group-item-action <?= $with == $c['user_id'] ? 'active' : '' ?>">
                                  <div class="d-flex justify-content-between align-items-start">
                                       <div class="flex-grow-1 me-2">
                                        <div class="fw-bold">
                                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?>
                                        </div>
                                        <small class="d-block text-truncate" style="max-width: 200px;">
                                            <?= htmlspecialchars(mb_substr((string)($c['preview'] ?? ''), 0, 60)) ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted d-block"><?= date('d M', strtotime($c['last_message_time'])) ?></small>
                                        <?php if ($c['unread_count'] > 0): ?>
                                            <span class="badge bg-danger rounderd-pill"><?= $c['unread_count'] ?></span>
                                        <?php endif; ?>
                                    </div>                         
                                 </div>  
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- CONVERSATION DETAIL-->
         <div class="col-md-8">
            <?php if ($with > 0 && $otherUser): ?>
                <div class="vd-form-card d-flex flex-column" style="height: 70vh;">
                    <div class="border-bottom pb-2 mb-3">
                        <h5 class="mb-0">
                            <i class="bi bi-person-circle"></i>
                            <?= htmlspecialchars(($otherUser['first_name'] ?? '') . ' ' . ($otherUser['last_name'] ?? '')) ?>
                        </h5>
                    </div>

                    <div class="flex-grow-1 overflow-auto mb-3" id="messages-area">
                        <?php if (empty($thread)): ?>
                            <p class="tex-center text-muted mt-4">No messages yet.</p>
                        <?php else: ?>
                            <?php foreach ($thread as $m): ?>
                                <?php $isMine = $m['sender_id'] == $me; ?>
                                <div class="p-2 px-3 rounded-3 <?= $isMine ? 'justify-conent-end' : 'justify-content-start' ?>">
                                    <div class="p-2 px-3 rounded-3 <?= $isMine ? 'bg-primary text-white' : 'bg-light border' ?>" style="max-width: 70%;">
                                        <?php if (!empty($m['p_title'])): ?>
                                            <small class=" <?= $isMine ? 'text-white-50' : 'text-muted' ?> d-block">
                                                <i class="bi bi-tag"></i> Re: <?= htmlspecialchars($m['p_title']) ?>
                                            </small>
                                        <?php endif; ?>
                                        <div><?= nl2br(htmlspecialchars($m['content'])) ?></div>
                                        <small class="<?= $isMine ? 'text-white-50' : 'text-muted' ?> d-block mt-1">
                                            <?= date('s M H:i', strtotime($m['created_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <form method="POST" class="mt-auto">
                        <input type="hidden" name="reciever_id" value="<?= $otherUser['user_id'] ?>">
                        <div class="input-group">
                            <textare name="content" class="form-control" rows="2" placeholder="Type a message..." required></textare>
                            <button type="submit" name="send_message" class="btn btn-primary">
                                <i class="bi bi-send"></i> Send
                            </button>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                    <div class="vd-form-card text-center text-muted p-5">
                        <i class="bi bi-chat-dots fs-1"></i>
                        <p clas="mt-3">Select a conversation from the left to start reading.</p>
                    </div>
                <?php endif; ?>
         </div>
    </div>
</div>

<script>
    const messagesArea = document.getElementById('messages-area');
    if (messagesArea) messagesArea.scrollTop = messagesArea.scrollHeight;
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>