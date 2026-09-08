<?php
session_start();
require_once 'db.php';

// Security Guard: Restrict access to authenticated administrators only[cite: 12]
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

$notice = '';
$error = '';

// --- CREATE: Manually log an inquiry / customer note ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_message'])) {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $error = "Please fill in the name, email, and message content.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid customer email address.";
    } else {
        $stmt = $conn->prepare("INSERT INTO messages (name, email, message) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $name, $email, $message);
            if ($stmt->execute()) {
                $notice = "Inquiry logged successfully.";
            } else {
                $error = "Failed to log message into database.";
            }
            $stmt->close();
        }
    }
}

// --- UPDATE: Edit an existing inquiry / append notes ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_message'])) {
    $msg_id  = (int)($_POST['message_id'] ?? 0);
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($msg_id <= 0 || empty($name) || empty($email) || empty($message)) {
        $error = "All fields are required to update the inquiry.";
    } else {
        $stmt = $conn->prepare("UPDATE messages SET name = ?, email = ?, message = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sssi", $name, $email, $message, $msg_id);
            if ($stmt->execute()) {
                $notice = "Message #$msg_id updated successfully.";
            } else {
                $error = "Failed to update message details.";
            }
            $stmt->close();
        }
    }
}

// --- DELETE: Remove Message ---[cite: 12]
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message'])) {
    $msg_id = (int)($_POST['message_id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $msg_id);
        if ($stmt->execute()) {
            $notice = "Message successfully removed."; //[cite: 12]
        } else {
            $error = "Failed to remove message."; //[cite: 12]
        }
        $stmt->close();
    }
}

// --- READ: Fetch all messages ---[cite: 12]
$messages = [];
$res = $conn->query("SELECT id, name, email, message, created_at FROM messages ORDER BY id DESC"); //[cite: 12]
if ($res) {
    while ($row = $res->fetch_assoc()) { //[cite: 12]
        $messages[] = $row; //[cite: 12]
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
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            background-image: url('images/cheesecakebgg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .admin-card {
            background: #fffdf5;
            max-width: 1100px;
            width: 100%;
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
            border: 1.5px solid #fce3ea;
        }

        .top-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .msg-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
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
            max-width: 440px;
        }

        .msg-date {
            font-size: 12px;
            color: #8c7b74;
            white-space: nowrap;
        }

        .btn-act {
            border: none;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-edit-msg {
            background-color: #ff709b;
            color: #ffffff;
        }

        .btn-edit-msg:hover {
            background-color: #e8507c;
        }

        .btn-del-msg {
            background: #fff0f4;
            border: 1px solid #f7b4c4;
            color: #d13d60;
        }

        .btn-del-msg:hover {
            background: #d13d60;
            color: #ffffff;
        }

        .add-panel {
            display: none;
            background: #ffffff;
            border: 1.5px dashed #f7b4c4;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 25px;
        }

        .grid-inputs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
            margin-bottom: 12px;
        }

        .grid-inputs input, .grid-inputs textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #f3d1db;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            outline: none;
        }

        /* Edit Modal Overlay */
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
        }

        .modal-box {
            background: #fffdf5;
            max-width: 500px;
            width: 90%;
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.15);
            border: 1.5px solid #fce3ea;
        }

        .modal-box h3 {
            font-family: 'Playfair Display', serif;
            color: #e65275;
            margin-bottom: 15px;
        }

        .modal-box .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 12px;
        }

        .modal-box label {
            font-size: 12.5px;
            font-weight: 700;
            color: #4a3431;
        }

        .modal-box input, .modal-box textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #f3d1db;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            outline: none;
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
            <a href="admin_users.php" class="nav-item">USERS</a>
            <a href="admin_messages.php" class="nav-item" style="border-bottom: 2px solid #ffffff;">MESSAGES</a>
            <a href="Index.php" class="nav-item" target="_blank">STOREFRONT <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 11px;"></i></a>
            <a href="admin_inventory.php?logout=1" class="nav-item" style="color: #ffd6df;">LOGOUT</a>
        </nav>
    </header>

    <main class="admin-wrap">
        <div class="admin-card">
            <div class="top-flex">
                <div>
                    <h1 class="cart-title" style="margin-bottom: 4px; text-align: left;">Customer Messages</h1>
                    <p style="color: #6d6260; font-size: 13.5px;">User inquiries, feedback, and notes submitted through the Contact Us form[cite: 12].</p>
                </div>
                <button type="button" class="btn-act btn-edit-msg" style="padding: 10px 18px; font-size: 13px;" onclick="toggleAddBox()">
                    <i class="fa-solid fa-pen-to-square"></i> Log Inquiry / Note
                </button>
            </div>

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

            <!-- CREATE: Admin Manual Log Form -->
            <div id="addMessageBox" class="add-panel">
                <h3 style="color: #e65275; margin-bottom: 12px; font-size: 15px;">Log New Customer Inquiry</h3>
                <form method="POST" action="admin_messages.php">
                    <div class="grid-inputs">
                        <input type="text" name="name" placeholder="Customer Name" required>
                        <input type="email" name="email" placeholder="Customer Email" required>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <textarea name="message" rows="3" placeholder="Inquiry details, order feedback, or phone memo..." required style="width: 100%; padding: 10px 14px; border: 1.5px solid #f3d1db; border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 13px;"></textarea>
                    </div>
                    <button type="submit" name="create_message" class="btn-act btn-edit-msg" style="padding: 9px 18px;">Save Inquiry</button>
                </form>
            </div>

            <!-- READ: Messages Table -->
            <?php if (empty($messages)): ?>
                <div style="text-align: center; padding: 40px 20px; color: #8c7b74; font-weight: 600;">
                    <i class="fa-regular fa-envelope-open" style="font-size: 40px; color: #f77290; margin-bottom: 12px;"></i>
                    <p>No messages received yet.</p>
                </div>
            <?php else: ?>
                <table class="msg-table">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Customer</th>
                            <th style="width: 45%;">Message</th>
                            <th style="width: 15%;">Received</th>
                            <th style="width: 15%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                            <tr>
                                <td>
                                    <span class="user-name" id="name-<?php echo $msg['id']; ?>"><?php echo htmlspecialchars($msg['name']); ?></span>
                                    <span class="user-email" id="email-<?php echo $msg['id']; ?>"><i class="fa-regular fa-envelope"></i> <?php echo htmlspecialchars($msg['email']); ?></span>
                                </td>
                                <td>
                                    <div class="msg-content" id="msg-<?php echo $msg['id']; ?>"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                </td>
                                <td>
                                    <span class="msg-date"><?php echo !empty($msg['created_at']) ? htmlspecialchars($msg['created_at']) : 'Recently'; ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <!-- UPDATE Trigger -->
                                        <button type="button" class="btn-act btn-edit-msg" title="Edit Note" onclick="openEditModal(<?php echo $msg['id']; ?>)">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>

                                        <!-- DELETE Form[cite: 12] -->
                                        <form method="POST" action="admin_messages.php" onsubmit="return confirm('Delete this message?');">
                                            <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                            <button type="submit" name="delete_message" class="btn-act btn-del-msg" title="Delete Message">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <!-- UPDATE: Modal Form -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-box">
            <h3>Edit Message Note</h3>
            <form method="POST" action="admin_messages.php">
                <input type="hidden" id="modal_msg_id" name="message_id" value="">
                
                <div class="form-group">
                    <label for="modal_name">Customer Name</label>
                    <input type="text" id="modal_name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="modal_email">Customer Email</label>
                    <input type="email" id="modal_email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="modal_message">Message Details</label>
                    <textarea id="modal_message" name="message" rows="4" required></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
                    <button type="button" class="btn-act btn-del-msg" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="update_message" class="btn-act btn-edit-msg">Update Note</button>
                </div>
            </form>
        </div>
    </div>

    <div class="footer-bar"></div>

    <script>
        function toggleAddBox() {
            const box = document.getElementById('addMessageBox');
            box.style.display = box.style.display === 'block' ? 'none' : 'block';
        }

        function openEditModal(id) {
            const name = document.getElementById('name-' + id).innerText.trim();
            const email = document.getElementById('email-' + id).innerText.trim();
            const message = document.getElementById('msg-' + id).innerText.trim();

            document.getElementById('modal_msg_id').value = id;
            document.getElementById('modal_name').value = name;
            document.getElementById('modal_email').value = email;
            document.getElementById('modal_message').value = message;

            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
</body>
</html>