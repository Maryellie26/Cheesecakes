<?php
session_start();
require_once 'db.php';

$msg_feedback = '';
$feedback_type = '';

// CREATE: Insert user contact message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($name) && !empty($email) && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO messages (name, email, message) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $name, $email, $message);
            if ($stmt->execute()) {
                $msg_feedback = "Thank you! Your message has been sent successfully.";
                $feedback_type = 'success';
            } else {
                $msg_feedback = "Database error: Unable to record your message.";
                $feedback_type = 'error';
            }
            $stmt->close();
        }
    } else {
        $msg_feedback = "Please fill out all fields.";
        $feedback_type = 'error';
    }
}

$total_items = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_items += (int)($item['qty'] ?? 1);
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
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Montserrat:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,600;0,700;1,600;1,700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: #fbe6b5;
            font-family: 'Poppins', 'Montserrat', sans-serif;
            color: #332d2c;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background-color: #f77290;
            padding: 20px 48px;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        .brand-logo-nav {
            position: absolute;
            left: 48px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .brand-logo-nav img { width: 45px; height: 45px; object-fit: contain; }
        .brand-logo-nav .nav-brand-name {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
        }
        .nav-links { display: flex; gap: 40px; }
        .nav-item {
            color: #ffffff;
            text-decoration: none;
            font-size: 15px;
            font-weight: 800;
        }
        .contact-section {
            flex: 1;
            padding: 50px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-image: url('images/contactusbg.png');
            background-size: cover;
        }
        .contact-container {
            max-width: 1100px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 40px;
        }
        .form-card {
            background: #fffdf5;
            border-radius: 36px;
            padding: 38px 40px;
            width: 100%;
            max-width: 480px;
            border: 1px solid #fce3ea;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 14px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1.5px solid #f7bfd0;
            outline: none;
        }
        .btn-send {
            background-color: #f76e8e;
            color: #ffffff;
            border: none;
            border-radius: 14px;
            padding: 14px;
            font-weight: 800;
            cursor: pointer;
            width: 100%;
        }
        .banner {
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 14px;
        }
        .banner-success { background: #e6f9ed; color: #1b873f; border: 1px solid #a3e9be; }
        .banner-error { background: #ffe8ec; color: #d13d60; border: 1px solid #f7b4c4; }
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
            <a href="contact.php" class="nav-item" style="border-bottom: 2px solid #ffffff;">CONTACT</a>
        </nav>
    </header>

    <main class="contact-section">
        <div class="contact-container">
            <div class="form-card">
                <h2 style="color: #f76e8e; margin-bottom: 15px;">Send Us a Message</h2>

                <?php if ($msg_feedback): ?>
                    <div class="banner <?php echo $feedback_type === 'success' ? 'banner-success' : 'banner-error'; ?>">
                        <?php echo htmlspecialchars($msg_feedback); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="contact.php">
                    <div class="form-group">
                        <label>Your Name</label>
                        <input type="text" name="name" required placeholder="Enter your name">
                    </div>
                    <div class="form-group">
                        <label>Your Email</label>
                        <input type="email" name="email" required placeholder="Enter your email">
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" rows="4" required placeholder="Type your message..."></textarea>
                    </div>
                    <button type="submit" name="send_message" class="btn-send">
                        <i class="fa-regular fa-paper-plane"></i> SEND MESSAGE
                    </button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>