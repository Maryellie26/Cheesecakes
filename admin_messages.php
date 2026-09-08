<?php
session_start();
require_once 'db.php';

// Security Guard: Restrict access to authenticated administrators only
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

$notice = '';
$error = '';

// Handle Message Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message'])) {
    $msg_id = (int)($_POST['message_id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $msg_id);
        if ($stmt->execute()) {
            $notice = "Message successfully removed.";
        } else {
            $error = "Failed to remove message.";
        }
        $stmt->close();
    }
}

// Fetch all messages
$messages = [];
$res = $conn->query("SELECT id, name, email, message, created_at FROM messages ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $messages[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Messages - Cheesecake Delight Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Playfair+Display:ital,wght@0,700;1,700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .admin-wrap {
            min-height: 88vh;
            padding: 50px 20px;
            display: flex;
            justify-content: center;
        }

        .admin-card {
            background: #fffdf5;
            max-width: 1050px;
            width: 100%;
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
            border: 1.5px solid #fce3ea;
        }

        .msg-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        .msg-table th, .msg-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #fce3ea;
            font-size: 13.5px;
            vertical-align: top;
        }

        .msg-table th {
            color: #e65275;
            font-weight: 800;
            background: #fff5f7;
        }

        .user-name {
            font-weight: 700;
            color: #4a3431;
        }

        .user-email {
            font-size: 12px;
            color: #8c7b74;
            display: block;
            margin-top: 2px;
        }

        .msg-content {
            line-height: 1.5;
            color: #383432;
            white-space: pre-wrap;
            word-break: break-word;
            max-width: 450px;
        }

        .msg-date {
            font-size: 12px;
            color: #8c7b74;
            white-space: nowrap;
        }

        .btn-del-msg {
            background: #fff0f4;
            border: 1px solid #f7b4c4;
            color: #d13d60;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-del-msg:hover {
            background: #d13d60;
            color: #ffffff;
        }

        .empty-box {
            text-align: center;
            padding: 40px 20px;
            color: #8c7b74;
            font-weight: 600;
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
            <a href="admin_messages.php" class="nav-item" style="border-bottom: 2px solid #ffffff;">MESSAGES</a>
            <a href="Index.php" class="nav-item" target="_blank">STOREFRONT <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 11px;"></i></a>
            <a href="admin_inventory.php?logout=1" class="nav-item" style="color: #ffd6df;">LOGOUT</a>
        </nav>
    </header>

    <main class="admin-wrap">
        <div class="admin-card">
            <h1 class="cart-title" style="margin-bottom: 8px;">Customer Messages</h1>
            <p style="text-align: center; color: #6d6260; font-size: 14px; margin-bottom: 24px;">
                User inquiries, feedback, and notes submitted through the Contact Us form[cite: 16].
            </p>

            <?php if ($notice): ?>
                <div style="background-color: #e6f9ed; color: #1b873f; border: 1.5px solid #a3e9be; padding: 12px 18px; border-radius: 12px; font-weight: 700; font-size: 13.5px; margin-bottom: 20px;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($notice); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background-color: #ffe8ec; color: #d13d60; border: 1.5px solid #f7b4c4; padding: 12px 18px; border-radius: 12px; font-weight: 700; font-size: 13.5px; margin-bottom: 20px;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($messages)): ?>
                <div class="empty-box">
                    <i class="fa-regular fa-envelope-open" style="font-size: 40px; color: #f77290; margin-bottom: 12px;"></i>
                    <p>No messages received yet.</p>
                </div>
            <?php else: ?>
                <table class="msg-table">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Customer</th>
                            <th style="width: 50%;">Message</th>
                            <th style="width: 15%;">Received</th>
                            <th style="width: 10%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                            <tr>
                                <td>
                                    <span class="user-name"><?php echo htmlspecialchars($msg['name']); ?></span>
                                    <span class="user-email"><i class="fa-regular fa-envelope"></i> <?php echo htmlspecialchars($msg['email']); ?></span>
                                </td>
                                <td>
                                    <div class="msg-content"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                </td>
                                <td>
                                    <span class="msg-date"><?php echo !empty($msg['created_at']) ? htmlspecialchars($msg['created_at']) : 'Recently'; ?></span>
                                </td>
                                <td>
                                    <form method="POST" action="admin_messages.php" onsubmit="return confirm('Delete this message?');">
                                        <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                        <button type="submit" name="delete_message" class="btn-del-msg">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <div class="footer-bar"></div>
</body>
</html>