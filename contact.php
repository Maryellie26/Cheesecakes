<?php
session_start();
require_once 'db.php';

// Ensure table supports sender_type and user association
$conn->query("ALTER TABLE messages ADD COLUMN IF NOT EXISTS sender_type ENUM('user', 'admin') NOT NULL DEFAULT 'user'");
$conn->query("ALTER TABLE messages ADD COLUMN IF NOT EXISTS user_id INT NULL DEFAULT NULL");

// Determine customer identity
$user_id    = $_SESSION['user_id'] ?? null;
$user_email = $_SESSION['user_email'] ?? ($_SESSION['guest_chat_email'] ?? '');
$user_name  = $_SESSION['username'] ?? ($_SESSION['guest_chat_name'] ?? '');

// Handle Message Post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name    = trim($_POST['name'] ?? ($user_name ?: 'Guest Customer'));
    $email   = trim($_POST['email'] ?? ($user_email ?: 'guest@cheesecakedelight.com'));
    $message = trim($_POST['message'] ?? '');

    if (!empty($message)) {
        $_SESSION['guest_chat_email'] = $email;
        $_SESSION['guest_chat_name']  = $name;
        $user_email = $email;
        $user_name  = $name;

        $stmt = $conn->prepare("INSERT INTO messages (user_id, name, email, message, sender_type) VALUES (?, ?, ?, ?, 'user')");
        if ($stmt) {
            $stmt->bind_param("isss", $user_id, $name, $email, $message);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: contact.php");
    exit;
}

// Fetch Thread for Customer
$chat_thread = [];
if (!empty($user_email)) {
    $stmt = $conn->prepare("SELECT id, name, email, message, sender_type, created_at FROM messages WHERE email = ? ORDER BY id ASC");
    if ($stmt) {
        $stmt->bind_param("s", $user_email);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $chat_thread[] = $row;
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
    <title>Contact Us - Cheesecake Delight</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@700&family=Montserrat:wght@700;800;900&family=Playfair+Display:ital,wght@0,700;1,700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #fbe6b5;
            background-image: url('images/contactusbg.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            font-family: 'Poppins', sans-serif;
            color: #332d2c;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navbar Header */
        .navbar {
            background-color: #f77290;
            padding: 16px 48px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .brand-logo-nav {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .brand-logo-nav img {
            width: 38px;
            height: 38px;
            object-fit: contain;
        }

        .brand-logo-nav .nav-brand-name {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 36px;
        }

        .nav-item {
            color: #ffffff;
            text-decoration: none;
            font-family: 'Montserrat', sans-serif;
            font-size: 14.5px;
            font-weight: 800;
            letter-spacing: 0.8px;
            padding-bottom: 3px;
        }

        .nav-item.active {
            border-bottom: 2.5px solid #ffffff;
        }

        /* Page Layout */
        .contact-layout-wrapper {
            flex: 1;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px 20px 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Center Header Decor */
        .header-decor {
            text-align: center;
            margin-bottom: 24px;
        }

        .header-heart {
            color: #f76e8e;
            font-size: 22px;
            display: block;
            margin-bottom: 2px;
        }

        .title-ray-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
        }

        .rays {
            color: #f76e8e;
            font-size: 20px;
            font-weight: 800;
        }

        .page-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 38px;
            font-weight: 900;
            color: #f7557d;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .page-subtitle {
            font-family: 'Caveat', cursive;
            font-size: 22px;
            font-weight: 700;
            color: #63534d;
            margin-top: 2px;
        }

        /* Main Grid: Left Info vs Right Chat */
        .contact-grid {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 28px;
            width: 100%;
            max-width: 1080px;
            align-items: start;
        }

        /* Left Info Cards */
        .info-col {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .info-pill-card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(4px);
            border-radius: 20px;
            padding: 14px 18px;
            border: 1.5px solid #fde4ec;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.03);
        }

        .info-pill-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff8da6 0%, #f76e8e 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(247, 110, 142, 0.3);
        }

        .info-pill-text h4 {
            font-size: 13px;
            font-weight: 700;
            color: #e65275;
            margin-bottom: 2px;
        }

        .info-pill-text p {
            font-size: 12.5px;
            color: #433935;
            font-weight: 500;
            line-height: 1.3;
        }

        /* Right Chat Card */
        .chat-panel-card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(4px);
            border-radius: 26px;
            border: 1.5px solid #fde4ec;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            height: 440px;
            overflow: hidden;
        }

        .chat-panel-header {
            padding: 14px 22px;
            border-bottom: 1px solid #fde4ec;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chat-icon-badge {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background-color: #f76e8e;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .chat-panel-header h3 {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 18px;
            font-weight: 700;
            color: #e65275;
        }

        /* Message Stream */
        .chat-stream-box {
            flex: 1;
            padding: 18px 22px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .msg-line {
            display: flex;
            gap: 10px;
            max-width: 82%;
            align-items: flex-start;
        }

        /* Customer Bubble: Aligned Right */
        .msg-line.user-line {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        /* Admin Bubble: Aligned Left */
        .msg-line.admin-line {
            align-self: flex-start;
        }

        .avatar-thumb {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
            margin-top: 2px;
            overflow: hidden;
        }

        .user-line .avatar-thumb {
            background-color: #ffdbe4;
            color: #e65275;
            border: 1px solid #fce3ea;
        }

        .admin-line .avatar-thumb {
            background-color: #ffffff;
            border: 1.5px solid #f7b4c4;
            color: #e65275;
        }

        .admin-line .avatar-thumb img {
            width: 80%;
            height: 80%;
            object-fit: contain;
        }

        .bubble-wrap {
            display: flex;
            flex-direction: column;
        }

        .chat-bubble {
            padding: 12px 18px;
            border-radius: 20px;
            font-size: 13px;
            line-height: 1.45;
            word-break: break-word;
        }

        .user-line .chat-bubble {
            background-color: #fff0f4;
            color: #4a3431;
            border: 1px solid #fcd5df;
            border-top-right-radius: 4px;
        }

        .admin-line .chat-bubble {
            background-color: #ffffff;
            border: 1.5px solid #f9cad7;
            color: #4a3431;
            border-top-left-radius: 4px;
        }

        .chat-time-meta {
            font-size: 10px;
            color: #a89a94;
            margin-top: 3px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .user-line .chat-time-meta { justify-content: flex-end; }
        .admin-line .chat-time-meta { justify-content: flex-start; }

        /* Bottom Input Form */
        .chat-input-bar {
            padding: 12px 18px;
            border-top: 1px solid #fde4ec;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
        }

        .input-relative-wrap {
            flex: 1;
            position: relative;
            display: flex;
            align-items: center;
        }

        .clip-btn {
            position: absolute;
            left: 16px;
            color: #e65275;
            font-size: 15px;
            pointer-events: none;
        }

        .input-relative-wrap input {
            width: 100%;
            border: 1.5px solid #f3d1db;
            border-radius: 26px;
            padding: 10px 18px 10px 42px;
            outline: none;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            color: #4a3431;
        }

        .input-relative-wrap input:focus {
            border-color: #f76e8e;
        }

        .input-relative-wrap input::placeholder {
            color: #b0a5a0;
        }

        .btn-send-pill {
            width: 40px;
            height: 40px;
            background-color: #f76e8e;
            color: #ffffff;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        .btn-send-pill:hover {
            background-color: #e55a7b;
            transform: scale(1.05);
        }

        .footer-bar {
            height: 70px;
            background-color: #f77290;
            width: 100%;
            flex-shrink: 0;
            margin-top: auto;
        }

        @media (max-width: 880px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }
            .chat-panel-card {
                height: 480px;
            }
        }
    </style>
</head>
<body>

    <header class="navbar">
        <a href="index.php#home" class="brand-logo-nav">
            <img src="images/cheesecakeLogo.png" alt="Logo">
            <span class="nav-brand-name">Cheesecake Delight</span>
        </a>
        <nav class="nav-links">
            <a href="index.php#home" class="nav-item">HOME</a>
            <a href="index.php#menu" class="nav-item">MENU</a>
            <a href="cart.php" class="nav-item">CART</a>
            <a href="contact.php" class="nav-item active">CONTACT</a>
        </nav>
    </header>

    <main class="contact-layout-wrapper">

        <!-- Top Heading with Rays and Heart -->
        <div class="header-decor">
            <span class="header-heart">&#9825;</span>
            <div class="title-ray-wrap">
                <span class="rays">&#95;&#95;&#92;</span>
                <h1 class="page-title">Contact Us</h1>
                <span class="rays">&#47;&#95;&#95;</span>
            </div>
            <p class="page-subtitle">We'd love to hear from you!</p>
        </div>

        <div class="contact-grid">

            <!-- LEFT: 4 Contact Information Cards -->
            <div class="info-col">
                <div class="info-pill-card">
                    <div class="info-pill-icon">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div class="info-pill-text">
                        <h4>Visit Us</h4>
                        <p>Dumaguete, Philippines</p>
                    </div>
                </div>

                <div class="info-pill-card">
                    <div class="info-pill-icon">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <div class="info-pill-text">
                        <h4>Call Us</h4>
                        <p>+63 875 945 7256</p>
                    </div>
                </div>

                <div class="info-pill-card">
                    <div class="info-pill-icon">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    <div class="info-pill-text">
                        <h4>Email Us</h4>
                        <p>cheesecakedelight@gmail.com</p>
                    </div>
                </div>

                <div class="info-pill-card">
                    <div class="info-pill-icon">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="info-pill-text">
                        <h4>Business Hours</h4>
                        <p>Mon - Sun: 9AM - 8PM</p>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Conversational Message Card -->
            <div class="chat-panel-card">
                <div class="chat-panel-header">
                    <div class="chat-icon-badge">
                        <i class="fa-solid fa-comment-dots"></i>
                    </div>
                    <h3>Your Message</h3>
                </div>

                <!-- Chat Stream Window -->
                <div class="chat-stream-box" id="chatStreamBox">
                    <?php if (empty($chat_thread)): ?>
                        <div style="text-align: center; margin: auto; color: #8c7b74; max-width: 320px;">
                            <i class="fa-regular fa-paper-plane" style="font-size: 30px; color: #f76e8e; margin-bottom: 8px;"></i>
                            <p style="font-weight: 700; color: #e65275; font-size: 13.5px;">Ask us anything!</p>
                            <p style="font-size: 12px; margin-top: 4px;">Send a message below and admin replies will appear right here.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($chat_thread as $m): 
                            $is_admin = ($m['sender_type'] === 'admin');
                        ?>
                            <div class="msg-line <?php echo $is_admin ? 'admin-line' : 'user-line'; ?>">
                                <div class="avatar-thumb">
                                    <?php if ($is_admin): ?>
                                        <img src="images/cheesecakeLogo.png" alt="Admin">
                                    <?php else: ?>
                                        <i class="fa-solid fa-user"></i>
                                    <?php endif; ?>
                                </div>

                                <div class="bubble-wrap">
                                    <div class="chat-bubble">
                                        <?php echo nl2br(htmlspecialchars($m['message'])); ?>
                                    </div>
                                    <div class="chat-time-meta">
                                        <span><?php echo date('h:i A', strtotime($m['created_at'] ?? 'now')); ?></span>
                                        <?php if (!$is_admin): ?>
                                            <i class="fa-solid fa-check-double" style="font-size: 9px; color: #f76e8e;"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Bottom Input Form -->
                <form method="POST" action="contact.php" class="chat-input-bar">
                    <div class="input-relative-wrap">
                        <i class="fa-solid fa-paperclip clip-btn"></i>
                        <input type="text" name="message" placeholder="Type your message..." required autocomplete="off">
                    </div>
                    <button type="submit" name="send_message" class="btn-send-pill" title="Send Message">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </div>

        </div>

    </main>

    <div class="footer-bar"></div>

    <script>
        const chatBox = document.getElementById('chatStreamBox');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    </script>
</body>
</html>