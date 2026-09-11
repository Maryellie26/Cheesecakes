<?php
session_start();
require_once 'db.php';

// Security Guard: Restrict access to authenticated administrators only[cite: 16]
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

// Handle Admin Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['is_admin']);
    unset($_SESSION['admin_user']);
    header("Location: login.php");
    exit;
}

// Ensure database table supports chat replies
$conn->query("ALTER TABLE messages ADD COLUMN IF NOT EXISTS sender_type ENUM('user', 'admin') NOT NULL DEFAULT 'user'");
$conn->query("ALTER TABLE messages ADD COLUMN IF NOT EXISTS user_id INT NULL DEFAULT NULL");

$notice = '';
$error  = '';

// ==========================================
// C - CREATE: Send Admin Reply OR New Inquiry[cite: 16]
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $reply_text   = trim($_POST['reply_message'] ?? '');
    $target_email = trim($_POST['target_email'] ?? '');
    $target_name  = trim($_POST['target_name'] ?? 'Customer');

    if (!empty($reply_text) && !empty($target_email)) {
        $stmt = $conn->prepare("INSERT INTO messages (name, email, message, sender_type) VALUES (?, ?, ?, 'admin')");
        if ($stmt) {
            $stmt->bind_param("sss", $target_name, $target_email, $reply_text);
            $stmt->execute();
            $stmt->close();
            $notice = "Reply sent successfully.";
        }
    } else {
        $error = "Message content cannot be empty.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_new_thread'])) {
    $cust_name = trim($_POST['new_cust_name'] ?? '');
    $cust_mail = trim($_POST['new_cust_email'] ?? '');
    $init_msg  = trim($_POST['new_cust_message'] ?? '');

    if (!empty($cust_name) && !empty($cust_mail) && !empty($init_msg)) {
        $stmt = $conn->prepare("INSERT INTO messages (name, email, message, sender_type) VALUES (?, ?, ?, 'admin')");
        if ($stmt) {
            $stmt->bind_param("sss", $cust_name, $cust_mail, $init_msg);
            $stmt->execute();
            $stmt->close();
            header("Location: admin_messages.php?chat=" . urlencode($cust_mail));
            exit;
        }
    } else {
        $error = "Please fill in all customer details.";
    }
}

// ==========================================
// U - UPDATE: Edit a Specific Message[cite: 16]
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_message'])) {
    $msg_id       = (int)($_POST['message_id'] ?? 0);
    $edited_text  = trim($_POST['edited_message'] ?? '');
    $return_chat  = trim($_POST['return_chat'] ?? '');

    if ($msg_id > 0 && !empty($edited_text)) {
        $stmt = $conn->prepare("UPDATE messages SET message = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $edited_text, $msg_id);
            if ($stmt->execute()) {
                $notice = "Message updated.";
            } else {
                $error = "Failed to update message.";
            }
            $stmt->close();
        }
    }
}

// ==========================================
// D - DELETE: Remove Individual Message OR Thread[cite: 16]
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_single_message'])) {
    $msg_id = (int)($_POST['message_id'] ?? 0);
    if ($msg_id > 0) {
        $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $msg_id);
            $stmt->execute();
            $stmt->close();
            $notice = "Message removed.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_conversation'])) {
    $thread_mail = trim($_POST['thread_email'] ?? '');
    if (!empty($thread_mail)) {
        $stmt = $conn->prepare("DELETE FROM messages WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $thread_mail);
            $stmt->execute();
            $stmt->close();
            header("Location: admin_messages.php");
            exit;
        }
    }
}

// ==========================================
// R - READ: Grouped Conversations & Thread[cite: 16]
// ==========================================
$conversations = [];
$conv_res = $conn->query("
    SELECT m.email, m.name, 
           MAX(m.id) AS last_msg_id,
           (SELECT message FROM messages WHERE email = m.email ORDER BY id DESC LIMIT 1) AS last_message,
           (SELECT created_at FROM messages WHERE email = m.email ORDER BY id DESC LIMIT 1) AS last_time
    FROM messages m
    GROUP BY m.email, m.name
    ORDER BY last_msg_id DESC
");

if ($conv_res) {
    while ($row = $conv_res->fetch_assoc()) {
        $conversations[] = $row;
    }
}

// Determine active conversation
$active_email = trim($_GET['chat'] ?? ($conversations[0]['email'] ?? ''));

// Fetch thread messages for active conversation
$thread_messages  = [];
$active_user_name = 'Customer';

if (!empty($active_email)) {
    $stmt = $conn->prepare("SELECT id, name, email, message, sender_type, created_at FROM messages WHERE email = ? ORDER BY id ASC");
    if ($stmt) {
        $stmt->bind_param("s", $active_email);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $thread_messages[] = $row;
            if ($row['sender_type'] === 'user') {
                $active_user_name = $row['name'];
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Messages - Cheesecake Delight Admin</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Playfair+Display:ital,wght@0,700;1,700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">

    <style>
        html, body { height: 100%; margin: 0; padding: 0; }
        body {
            background-color: #fbe6b5;
            background-image: url('images/cheesecakebgg.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            font-family: 'Poppins', 'Montserrat', sans-serif;
            color: #332d2c;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .chat-app-wrapper {
            flex: 1;
            width: 100%;
            max-width: 1220px;
            margin: 25px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 330px 1fr;
            gap: 20px;
            height: calc(100vh - 170px);
            min-height: 620px;
        }

        /* --- Left Side: Conversations Sidebar --- */
        .chat-sidebar-card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(5px);
            border-radius: 26px;
            border: 1.5px solid #fde4ec;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .sidebar-header {
            padding: 16px 18px;
            border-bottom: 1px solid #fde4ec;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .sidebar-title {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #e65275;
            font-size: 17px;
            font-weight: 800;
        }

        .btn-new-chat {
            background-color: #f76e8e;
            color: #ffffff;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            transition: background 0.15s ease;
        }

        .btn-new-chat:hover { background-color: #e55a7b; }

        .search-box-wrap {
            padding: 10px 18px;
            border-bottom: 1px solid #fef0f3;
        }

        .search-users-input {
            width: 100%;
            padding: 8px 14px;
            border-radius: 20px;
            border: 1.5px solid #f3d1db;
            font-size: 12px;
            outline: none;
            font-family: 'Poppins', sans-serif;
            box-sizing: border-box;
        }

        .chat-users-list {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .chat-user-item {
            padding: 13px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
            border-bottom: 1px solid #fef0f3;
            transition: background 0.15s ease;
            position: relative;
        }

        .chat-user-item:hover { background-color: #fff6f8; }
        .chat-user-item.active { background-color: #ffeef3; }

        .avatar-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #ffdbe4;
            color: #e65275;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
            overflow: hidden;
        }

        .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }

        .user-preview-info { flex: 1; min-width: 0; }

        .user-preview-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3px;
        }

        .preview-name {
            font-size: 13.5px;
            font-weight: 700;
            color: #4a3431;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .preview-time { font-size: 10.5px; color: #a89a94; }

        .preview-msg {
            font-size: 12px;
            color: #8c7b74;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }

        /* --- Right Side: Active Chat Window --- */
        .chat-main-card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(5px);
            border-radius: 26px;
            border: 1.5px solid #fde4ec;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-conversation-header {
            padding: 14px 24px;
            border-bottom: 1px solid #fde4ec;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .active-user-meta {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .active-user-titles h3 {
            font-size: 16px;
            font-weight: 800;
            color: #e65275;
            margin-bottom: 1px;
        }

        .active-user-titles span {
            font-size: 11.5px;
            color: #8c7b74;
            font-weight: 500;
        }

        .chat-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-header-action {
            background: #ffffff;
            border: 1.5px solid #f7b4c4;
            color: #e65275;
            padding: 7px 14px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }

        .btn-header-action:hover {
            background: #ffeef3;
        }

        .btn-header-delete {
            color: #d13d60;
            border-color: #f7b4c4;
        }

        .btn-header-delete:hover {
            background: #ffebee;
        }

        /* Message Bubbles */
        .chat-messages-container {
            flex: 1;
            padding: 20px 24px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .message-row {
            display: flex;
            gap: 10px;
            max-width: 78%;
            align-items: flex-end;
            position: relative;
        }

        .message-row.user-row { align-self: flex-start; }
        .message-row.admin-row {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .bubble-content {
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .chat-bubble {
            padding: 13px 18px;
            border-radius: 20px;
            font-size: 13.5px;
            line-height: 1.45;
            word-break: break-word;
            position: relative;
        }

        .user-row .chat-bubble {
            background-color: #fff3f6;
            color: #4a3431;
            border: 1px solid #fce3ea;
            border-bottom-left-radius: 4px;
        }

        .admin-row .chat-bubble {
            background-color: #ffffff;
            border: 1.5px solid #f9cad7;
            color: #e65275;
            font-weight: 500;
            border-bottom-right-radius: 4px;
        }

        .bubble-time {
            font-size: 10.5px;
            color: #a89a94;
            margin-top: 4px;
        }

        .user-row .bubble-time { text-align: left; }
        .admin-row .bubble-time { text-align: right; }

        /* Bubble Hover Action Buttons (Edit / Delete) */
        .bubble-actions-pop {
            display: none;
            position: absolute;
            top: -12px;
            right: 0;
            background: #ffffff;
            border: 1px solid #f7b4c4;
            border-radius: 12px;
            padding: 2px 6px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            gap: 6px;
            align-items: center;
        }

        .user-row .bubble-actions-pop { right: auto; left: 0; }

        .bubble-content:hover .bubble-actions-pop {
            display: flex;
        }

        .btn-mini-act {
            background: none;
            border: none;
            font-size: 11px;
            color: #8c7b74;
            cursor: pointer;
            padding: 3px;
        }

        .btn-mini-act:hover { color: #e65275; }

        /* Bottom Input Bar */
        .chat-input-bar {
            padding: 14px 20px;
            border-top: 1px solid #fde4ec;
            display: flex;
            align-items: center;
            gap: 12px;
            background: #ffffff;
        }

        .chat-input-bar input {
            flex: 1;
            border: 1.5px solid #f3d1db;
            border-radius: 24px;
            padding: 12px 20px;
            outline: none;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
        }

        .chat-input-bar input:focus { border-color: #f76e8e; }

        .btn-send-chat {
            width: 44px;
            height: 44px;
            background-color: #f76e8e;
            color: #ffffff;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.2s ease, transform 0.15s ease;
        }

        .btn-send-chat:hover {
            background-color: #e55a7b;
            transform: scale(1.05);
        }

        /* Modals */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-box {
            background: #fffdf7;
            max-width: 460px;
            width: 100%;
            border-radius: 24px;
            padding: 28px;
            border: 1.5px solid #fce3ea;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.12);
        }

        .modal-box h3 {
            font-family: 'Playfair Display', serif;
            color: #e65275;
            font-size: 20px;
            margin-bottom: 14px;
        }

        .form-row {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 12px;
        }

        .form-row label {
            font-size: 12.5px;
            font-weight: 700;
            color: #4a3431;
        }

        .form-row input, .form-row textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #f3d1db;
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            outline: none;
            box-sizing: border-box;
        }

        .btn-modal-submit {
            background-color: #f76e8e;
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 800;
            font-size: 13.5px;
            width: 100%;
            cursor: pointer;
            margin-top: 6px;
        }

        .btn-modal-submit:hover { background-color: #e55a7b; }

        .footer-bar {
            height: 70px;
            background-color: #f77290;
            width: 100%;
            flex-shrink: 0;
            margin-top: auto;
        }

        @media (max-width: 900px) {
            .chat-app-wrapper {
                grid-template-columns: 1fr;
                height: auto;
            }
            .chat-main-card {
                height: 550px;
            }
        }
    </style>
</head>
<body>

    <header class="navbar">
        <a href="admin_dashboard.php" class="brand-logo-nav">
            <img src="images/cheesecakeLogo.png" alt="Logo">
            <span class="nav-brand-name">Cheesecake Delight</span>
        </a>
        <nav class="nav-links">
            <a href="admin_dashboard.php" class="nav-item">DASHBOARD</a>
            <a href="admin_inventory.php" class="nav-item">INVENTORY</a>
            <a href="admin_orders.php" class="nav-item">ORDERS</a>
            <a href="admin_history.php" class="nav-item">HISTORY</a>
            <a href="admin_users.php" class="nav-item">USERS</a>
            <a href="admin_messages.php" class="nav-item" style="border-bottom: 2px solid #ffffff;">MESSAGES</a>
            <a href="Index.php" class="nav-item" target="_blank">STOREFRONT <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 11px;"></i></a>
            <a href="admin_messages.php?logout=1" class="nav-item" style="color: #ffd6df;">LOGOUT</a>
        </nav>
    </header>

    <main class="chat-app-wrapper">

        <!-- LEFT: Customer Threads List -->
        <div class="chat-sidebar-card">
            <div class="sidebar-header">
                <div class="sidebar-title">
                    <i class="fa-solid fa-comments"></i> Messages
                </div>
                <button type="button" class="btn-new-chat" title="Start New Conversation" onclick="openNewThreadModal()">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>

            <div class="search-box-wrap">
                <input type="text" class="search-users-input" placeholder="Search customer..." onkeyup="filterChats(this.value)">
            </div>

            <div class="chat-users-list" id="chatUsersList">
                <?php if (empty($conversations)): ?>
                    <p style="padding: 24px; text-align: center; font-size: 12.5px; color: #8c7b74;">No conversations found.</p>
                <?php else: ?>
                    <?php foreach ($conversations as $c): 
                        $is_active = ($c['email'] === $active_email);
                    ?>
                        <a href="admin_messages.php?chat=<?php echo urlencode($c['email']); ?>" class="chat-user-item <?php echo $is_active ? 'active' : ''; ?>">
                            <div class="avatar-circle">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div class="user-preview-info">
                                <div class="user-preview-top">
                                    <span class="preview-name"><?php echo htmlspecialchars($c['name']); ?></span>
                                    <span class="preview-time"><?php echo date('h:i A', strtotime($c['last_time'] ?? 'now')); ?></span>
                                </div>
                                <span class="preview-msg"><?php echo htmlspecialchars($c['last_message'] ?? 'Start chatting...'); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT: Active Chat Conversation Box -->
        <div class="chat-main-card">
            <div class="chat-conversation-header">
                <div class="active-user-meta">
                    <div class="avatar-circle" style="width: 44px; height: 44px;">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="active-user-titles">
                        <h3><?php echo htmlspecialchars($active_user_name); ?></h3>
                        <span><?php echo htmlspecialchars($active_email); ?></span>
                    </div>
                </div>

                <div class="chat-header-actions">
                    <a href="admin_users.php" class="btn-header-action">
                        <i class="fa-regular fa-user"></i> Users
                    </a>

                    <?php if (!empty($active_email)): ?>
                        <form method="POST" action="admin_messages.php" style="display:inline;" onsubmit="return confirm('Delete all messages in this conversation with <?php echo htmlspecialchars($active_user_name); ?>?');">
                            <input type="hidden" name="thread_email" value="<?php echo htmlspecialchars($active_email); ?>">
                            <button type="submit" name="delete_conversation" class="btn-header-action btn-header-delete" title="Delete entire conversation">
                                <i class="fa-regular fa-trash-can"></i> Clear Chat
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Messages Stream -->
            <div class="chat-messages-container" id="chatContainer">
                <?php if (empty($thread_messages)): ?>
                    <div style="text-align: center; margin: auto; color: #8c7b74;">
                        <i class="fa-regular fa-paper-plane" style="font-size: 36px; color: #f76e8e; margin-bottom: 10px;"></i>
                        <p style="font-weight: 600;">No messages yet in this conversation.</p>
                        <p style="font-size: 12px; margin-top: 4px;">Type below to send the first reply.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($thread_messages as $m): 
                        $is_admin = ($m['sender_type'] === 'admin');
                    ?>
                        <div class="message-row <?php echo $is_admin ? 'admin-row' : 'user-row'; ?>">
                            <div class="avatar-circle" style="width: 32px; height: 32px; font-size: 13px;">
                                <?php echo $is_admin ? '<i class="fa-solid fa-shield-halved"></i>' : '<i class="fa-solid fa-user"></i>'; ?>
                            </div>

                            <div class="bubble-content">
                                <!-- Bubble hover actions for Update & Delete -->
                                <div class="bubble-actions-pop">
                                    <button type="button" class="btn-mini-act" title="Edit Message" onclick='openEditMessageModal(<?php echo $m['id']; ?>, <?php echo json_encode($m['message']); ?>)'>
                                        <i class="fa-solid fa-pencil"></i>
                                    </button>
                                    <form method="POST" action="admin_messages.php?chat=<?php echo urlencode($active_email); ?>" style="display:inline;" onsubmit="return confirm('Delete this message?');">
                                        <input type="hidden" name="message_id" value="<?php echo $m['id']; ?>">
                                        <button type="submit" name="delete_single_message" class="btn-mini-act" title="Delete message">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>

                                <div class="chat-bubble">
                                    <?php echo nl2br(htmlspecialchars($m['message'])); ?>
                                </div>
                                <span class="bubble-time"><?php echo date('h:i A', strtotime($m['created_at'] ?? 'now')); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Bottom Reply Bar -->
            <?php if (!empty($active_email)): ?>
                <form method="POST" action="admin_messages.php?chat=<?php echo urlencode($active_email); ?>" class="chat-input-bar">
                    <input type="hidden" name="target_email" value="<?php echo htmlspecialchars($active_email); ?>">
                    <input type="hidden" name="target_name" value="<?php echo htmlspecialchars($active_user_name); ?>">
                    <input type="text" name="reply_message" placeholder="Type a message..." required autocomplete="off">
                    <button type="submit" name="send_reply" class="btn-send-chat" title="Send Reply">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            <?php endif; ?>
        </div>

    </main>

    <!-- CREATE: New Customer Message Modal -->
    <div id="newThreadModal" class="modal-overlay">
        <div class="modal-box">
            <h3>Start New Message / Inquiry</h3>
            <form method="POST" action="admin_messages.php">
                <div class="form-row">
                    <label>Customer Full Name</label>
                    <input type="text" name="new_cust_name" placeholder="e.g. Maria Santos" required>
                </div>
                <div class="form-row">
                    <label>Customer Email</label>
                    <input type="email" name="new_cust_email" placeholder="e.g. maria@gmail.com" required>
                </div>
                <div class="form-row">
                    <label>Initial Message / Inquiry</label>
                    <textarea name="new_cust_message" rows="3" placeholder="Type customer message or reply note..." required></textarea>
                </div>
                <div style="display: flex; gap: 8px; margin-top: 14px;">
                    <button type="button" class="btn-header-action" style="flex: 1;" onclick="closeNewThreadModal()">Cancel</button>
                    <button type="submit" name="create_new_thread" class="btn-modal-submit" style="flex: 1; margin: 0;">Start Chat</button>
                </div>
            </form>
        </div>
    </div>

    <!-- UPDATE: Edit Single Message Modal -->
    <div id="editMessageModal" class="modal-overlay">
        <div class="modal-box">
            <h3>Edit Message</h3>
            <form method="POST" action="admin_messages.php?chat=<?php echo urlencode($active_email); ?>">
                <input type="hidden" id="edit_msg_id" name="message_id">
                <div class="form-row">
                    <label>Message Content</label>
                    <textarea id="edit_msg_text" name="edited_message" rows="4" required></textarea>
                </div>
                <div style="display: flex; gap: 8px; margin-top: 14px;">
                    <button type="button" class="btn-header-action" style="flex: 1;" onclick="closeEditMessageModal()">Cancel</button>
                    <button type="submit" name="update_message" class="btn-modal-submit" style="flex: 1; margin: 0;">Save Edit</button>
                </div>
            </form>
        </div>
    </div>

    <div class="footer-bar"></div>

    <script>
        // Scroll to the bottom of the conversation stream automatically
        const container = document.getElementById('chatContainer');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }

        // Live search filter for sidebar conversations
        function filterChats(query) {
            const items = document.querySelectorAll('.chat-user-item');
            const q = query.toLowerCase();
            items.forEach(el => {
                const name = el.querySelector('.preview-name').innerText.toLowerCase();
                el.style.display = name.includes(q) ? 'flex' : 'none';
            });
        }

        // Modal Controls
        function openNewThreadModal() {
            document.getElementById('newThreadModal').style.display = 'flex';
        }
        function closeNewThreadModal() {
            document.getElementById('newThreadModal').style.display = 'none';
        }

        function openEditMessageModal(id, text) {
            document.getElementById('edit_msg_id').value = id;
            document.getElementById('edit_msg_text').value = text;
            document.getElementById('editMessageModal').style.display = 'flex';
        }
        function closeEditMessageModal() {
            document.getElementById('editMessageModal').style.display = 'none';
        }

        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                closeNewThreadModal();
                closeEditMessageModal();
            }
        };
    </script>
</body>
</html>