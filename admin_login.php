<?php
session_start();

$error = '';

// Check if already logged in as admin -> redirect to dashboard
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header("Location: admin_dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Hardcoded secure admin credentials (change these as needed)
    $ADMIN_USER = 'Marielle';
    $ADMIN_PASS = 'admin123';

    if ($username === $ADMIN_USER && $password === $ADMIN_PASS) {
        $_SESSION['is_admin']   = true;
        $_SESSION['admin_user'] = $username;
        header("Location: admin_dashboard.php");
        exit;
    } else {
        $error = "Invalid administrator username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Cheesecake Delight</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600;1,700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        .auth-wrapper {
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

        .auth-card {
            background-color: #fffdf7;
            width: 100%;
            max-width: 440px;
            border-radius: 36px;
            padding: 42px 38px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.08);
            border: 1px solid #fce3ea;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .avatar-badge {
            width: 68px;
            height: 68px;
            background-color: #e65275;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 28px;
            margin-bottom: 18px;
            box-shadow: 0 6px 18px rgba(230, 82, 117, 0.28);
        }

        .card-heading {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            color: #e65275;
            margin-bottom: 6px;
        }

        .card-subtext {
            font-size: 13.5px;
            color: #63534d;
            font-weight: 500;
            margin-bottom: 26px;
        }

        .auth-form {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 16px;
            text-align: left;
        }

        .input-block {
            display: flex;
            flex-direction: column;
            gap: 6px;
            width: 100%;
        }

        .input-block label {
            font-size: 13px;
            font-weight: 700;
            color: #433935;
        }

        .input-field-wrap {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .input-icon-left {
            position: absolute;
            left: 16px;
            color: #8c7b74;
            font-size: 16px;
            pointer-events: none;
        }

        .input-field-wrap input {
            width: 100%;
            padding: 12px 18px 12px 44px;
            background-color: #ffffff;
            border: 1.5px solid #f3d1db;
            border-radius: 14px;
            font-family: 'Montserrat', sans-serif;
            font-size: 13.5px;
            color: #332d2c;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .input-field-wrap input:focus {
            border-color: #f76e8e;
        }

        .btn-submit {
            background-color: #e65275;
            color: #ffffff;
            border: none;
            border-radius: 14px;
            padding: 13px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14.5px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(230, 82, 117, 0.3);
            transition: background-color 0.2s ease, transform 0.2s ease;
            width: 100%;
            margin-top: 6px;
        }

        .btn-submit:hover {
            background-color: #d13d60;
            transform: translateY(-2px);
        }

        .btn-back {
            display: block;
            text-align: center;
            color: #8c7b74;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            margin-top: 14px;
        }

        .btn-back:hover {
            color: #e65275;
        }

        .error-banner {
            width: 100%;
            background-color: #ffe3e8;
            color: #ba2348;
            padding: 11px 14px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
            border: 1px solid #f7b4c4;
            text-align: center;
        }

        .footer-bar {
            height: 70px;
            background-color: #f77290;
            width: 100%;
            flex-shrink: 0;
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

    <main class="auth-wrapper">
        <div class="auth-card">
            <div class="avatar-badge">
                <i class="fa-solid fa-shield-halved"></i>
            </div>

            <h1 class="card-heading">Admin Portal</h1>
            <p class="card-subtext">Sign in to access your administrative dashboard</p>

            <?php if (!empty($error)): ?>
                <div class="error-banner">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="admin_login.php" class="auth-form">
                <div class="input-block">
                    <label for="username">Admin Username</label>
                    <div class="input-field-wrap">
                        <i class="fa-regular fa-user input-icon-left"></i>
                        <input type="text" id="username" name="username" placeholder="Enter username" required>
                    </div>
                </div>

                <div class="input-block">
                    <label for="password">Password</label>
                    <div class="input-field-wrap">
                        <i class="fa-solid fa-lock input-icon-left"></i>
                        <input type="password" id="password" name="password" placeholder="Enter password" required>
                    </div>
                </div>

                <button type="submit" name="admin_login" class="btn-submit">Login to Dashboard</button>
                <a href="index.php" class="btn-back">&larr; Return to Storefront</a>
            </form>
        </div>
    </main>

    <div class="footer-bar"></div>

</body>
</html>