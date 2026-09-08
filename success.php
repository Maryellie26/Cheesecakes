<?php
session_start();

// Redirect to login if user isn't authenticated
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

$user_display_name = $_SESSION['username'] ?? 'there';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - Cheesecake Delight</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Playfair+Display:ital,wght@0,700;1,700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background-color: #f77290;
            padding: 20px 48px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
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

        .brand-logo-nav img {
            width: 45px;
            height: 45px;
            object-fit: contain;
        }

        .brand-logo-nav .nav-brand-name {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.1;
        }

        .success-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            background-image: url('images/homepagebg.png');
            background-size: cover;
            background-position: center left;
            background-repeat: no-repeat;
        }

        .success-card {
            background-color: #fffdf7;
            width: 100%;
            max-width: 460px;
            border-radius: 36px;
            padding: 44px 38px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.08);
            border: 1.5px solid #fce3ea;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .check-badge {
            width: 78px;
            height: 78px;
            background-color: #27ae60;
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin-bottom: 20px;
            box-shadow: 0 8px 24px rgba(39, 174, 96, 0.32);
        }

        .success-title {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            color: #27ae60;
            margin-bottom: 8px;
        }

        .success-subtext {
            font-size: 13.5px;
            color: #63534d;
            line-height: 1.6;
            margin-bottom: 26px;
            font-weight: 500;
        }

        .btn-home-success {
            background-color: #27ae60;
            color: #ffffff;
            text-decoration: none;
            font-family: 'Montserrat', sans-serif;
            font-size: 14.5px;
            font-weight: 800;
            padding: 14px 24px;
            border-radius: 14px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 14px rgba(39, 174, 96, 0.3);
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .btn-home-success:hover {
            background-color: #219653;
            transform: translateY(-2px);
        }

        .footer-bar {
            height: 70px;
            background-color: #f77290;
            width: 100%;
            flex-shrink: 0;
        }

        @media (max-width: 600px) {
            .navbar {
                padding: 16px 20px;
            }
            .brand-logo-nav {
                position: static;
            }
            .success-card {
                padding: 34px 22px;
                border-radius: 28px;
            }
            .success-title {
                font-size: 24px;
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
    </header>

    <main class="success-wrapper">
        <div class="success-card">
            <div class="check-badge">
                <i class="fa-solid fa-check"></i>
            </div>

            <h1 class="success-title">Account Created!</h1>
            <p class="success-subtext">
                Welcome, <strong><?php echo htmlspecialchars($user_display_name); ?></strong>!<br>
                Your profile has been created and you are now signed in.
            </p>

            <a href="index.php#menu" class="btn-home-success">
                Go to Homepage &rarr;
            </a>
        </div>
    </main>

    <div class="footer-bar"></div>

</body>
</html>