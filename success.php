<?php
session_start();

// Redirect to login if user isn't authenticated[cite: 13]
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php"); //[cite: 13]
    exit; //[cite: 13]
}

$user_display_name = $_SESSION['username'] ?? 'there'; //[cite: 13]
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Created - Cheesecake Delight</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@700&family=Montserrat:wght@500;600;700;800;900&family=Playfair+Display:ital,wght@0,700;1,700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #fbe6b5;
            font-family: 'Poppins', 'Montserrat', sans-serif;
            color: #332d2c;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* --- Navbar Matching Reference Image --- */
        .navbar {
            background-color: #f77290;
            width: 100%;
            padding: 16px 48px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
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
            letter-spacing: 0.3px;
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
            transition: opacity 0.2s ease;
        }

        .nav-item:hover {
            opacity: 0.85;
        }

        /* --- Full Viewport Center Wrapper with Background Image --- */
        .success-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            background-image: url('images/cakebg.png');
            background-size: cover;
            background-position: center right;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        /* --- Card Container Matching Reference --- */
        .success-card {
            background-color: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(5px);
            width: 100%;
            max-width: 500px;
            border-radius: 36px;
            padding: 46px 40px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.06);
            border: 1.5px solid #fde4ec;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
        }

        /* Icon Badge with Confetti Rays */
        .badge-container {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
        }

        .check-badge {
            width: 82px;
            height: 82px;
            background-color: #48bb78;
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            box-shadow: 0 8px 22px rgba(72, 187, 120, 0.35);
        }

        .confetti-ray {
            position: absolute;
            width: 4px;
            height: 12px;
            background-color: #f77290;
            border-radius: 2px;
        }

        .ray-top-left    { top: 4px; left: -22px; transform: rotate(-45deg); }
        .ray-mid-left    { top: 26px; left: -30px; transform: rotate(-90deg); }
        .ray-bot-left    { top: 48px; left: -22px; transform: rotate(-135deg); }

        .ray-top-right   { top: 4px; right: -22px; transform: rotate(45deg); }
        .ray-mid-right   { top: 26px; right: -30px; transform: rotate(90deg); }
        .ray-bot-right   { top: 48px; right: -22px; transform: rotate(135deg); }

        /* Floating Accent Sparks / Hearts */
        .spark {
            position: absolute;
            color: #f77290;
            opacity: 0.6;
            user-select: none;
            pointer-events: none;
        }

        .spark-left  { left: -50px; top: 38%; font-size: 26px; transform: rotate(-18deg); }
        .spark-right { right: -50px; top: 48%; font-size: 26px; transform: rotate(15deg); }
        .spark-dash-left { left: -42px; top: 62%; font-size: 16px; letter-spacing: 2px; }
        .spark-dash-right { right: -42px; top: 72%; font-size: 16px; letter-spacing: 2px; }

        /* Typography */
        .success-title {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 38px;
            font-weight: 700;
            color: #e65275;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .welcome-lead {
            font-size: 15px;
            font-weight: 600;
            color: #4a3431;
            margin-bottom: 6px;
        }

        .welcome-lead strong {
            color: #e65275;
            font-weight: 700;
        }

        .success-subtext {
            font-size: 13px;
            color: #795548;
            font-weight: 500;
            margin-bottom: 28px;
        }

        /* Pill CTA Button */
        .btn-home-success {
            background-color: #f77290;
            color: #ffffff;
            text-decoration: none;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            font-weight: 700;
            padding: 13px 32px;
            border-radius: 26px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 6px 18px rgba(247, 114, 144, 0.35);
            transition: background-color 0.2s ease, transform 0.15s ease;
        }

        .btn-home-success:hover {
            background-color: #e55a7b;
            transform: translateY(-1px);
        }

        .footer-bar {
            height: 70px;
            background-color: #f77290;
            width: 100%;
            flex-shrink: 0;
            margin-top: auto;
        }

        @media (max-width: 600px) {
            .navbar {
                padding: 14px 20px;
            }
            .success-card {
                padding: 38px 24px;
                border-radius: 28px;
            }
            .success-title {
                font-size: 32px;
            }
            .spark {
                display: none;
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
        </nav>
    </header>

    <main class="success-wrapper">
        <div class="success-card">

            <div class="spark spark-left">&#9825;</div>
            <div class="spark spark-dash-left">&#95;&#95;</div>
            <div class="spark spark-right">&#9825;</div>
            <div class="spark spark-dash-right">&#95;&#95;</div>

            <!-- Green Circle Badge with Confetti Rays -->
            <div class="badge-container">
                <span class="confetti-ray ray-top-left"></span>
                <span class="confetti-ray ray-mid-left"></span>
                <span class="confetti-ray ray-bot-left"></span>

                <div class="check-badge">
                    <i class="fa-solid fa-check"></i>
                </div>

                <span class="confetti-ray ray-top-right"></span>
                <span class="confetti-ray ray-mid-right"></span>
                <span class="confetti-ray ray-bot-right"></span>
            </div>

            <h1 class="success-title">Account Created!</h1>

            <p class="welcome-lead">
                Welcome, <strong><?php echo htmlspecialchars($user_display_name); ?></strong>![cite: 13]
            </p>

            <p class="success-subtext">
                Your profile has been created and you are now signed in.[cite: 13]
            </p>

            <a href="index.php" class="btn-home-success">
                <i class="fa-solid fa-house"></i> Go to Homepage &rarr;
            </a>

        </div>
    </main>

    <div class="footer-bar"></div>

</body>
</html>