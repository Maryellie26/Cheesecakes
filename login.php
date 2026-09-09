<?php
session_start();
require_once 'db.php';

// Redirect if already logged in
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header("Location: admin_dashboard.php");
    exit;
}
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$errors = [];

// Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $login_input = trim($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';

    if ($login_input === '') {
        $errors[] = "Email or username is required.";
    }
    if ($password === '') {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        // --- 1. ADMIN CREDENTIALS CHECK ---
        $ADMIN_USER = 'Marielle';
        $ADMIN_PASS = 'admin123';

        if (($login_input === $ADMIN_USER || strtolower($login_input) === 'admin@cheesecake.com') && $password === $ADMIN_PASS) {
            $_SESSION['is_admin']   = true;
            $_SESSION['admin_user'] = $ADMIN_USER;
            header("Location: admin_dashboard.php");
            exit;
        }

        // --- 2. CUSTOMER / USER DATABASE CHECK ---
        $stmt = $conn->prepare("SELECT id, fullname, email, password FROM users WHERE email = ? OR fullname = ?");
        if ($stmt) {
            $stmt->bind_param("ss", $login_input, $login_input);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($id, $fullname, $email, $hashed_password);
                $stmt->fetch();

                if (password_verify($password, $hashed_password)) {
                    $_SESSION['user_id']    = $id;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['username']   = $fullname;

                    header("Location: index.php");
                    exit;
                } else {
                    $errors[] = "Incorrect password.";
                }
            } else {
                $errors[] = "No account found with that email or username.";
            }
            $stmt->close();
        } else {
            $errors[] = "Database query error. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cheesecake Delight</title>
    
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
            padding: 22px 48px;
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

        .nav-links {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 52px;
        }

        .nav-item {
            color: #ffffff;
            text-decoration: none;
            font-family: 'Montserrat', sans-serif;
            font-size: 16.5px;
            font-weight: 800;
            letter-spacing: 1px;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .nav-item:hover {
            opacity: 0.85;
            transform: translateY(-1px);
        }

        .nav-icons {
            position: absolute;
            right: 48px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .icon-link {
            color: #ffffff;
            font-size: 20px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .icon-link:hover {
            opacity: 0.85;
            transform: translateY(-2px);
        }

        .btn-signin {
            background-color: #ffffff;
            color: #f77290;
            font-family: 'Montserrat', sans-serif;
            font-size: 14.5px;
            font-weight: 800;
            text-decoration: none;
            padding: 8px 22px;
            border-radius: 30px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-signin:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
        }

        .auth-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            background-image: url('images/cheesecakebgg.png');
            background-size: cover;
            background-position: center left;
            background-repeat: no-repeat;
            position: relative;
        }

        .auth-card {
            background-color: #fffdf7;
            width: 100%;
            max-width: 480px;
            border-radius: 40px;
            padding: 46px 42px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.08);
            border: 1px solid #fce3ea;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .avatar-badge {
            width: 72px;
            height: 72px;
            background-color: #f76e8e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 32px;
            margin-bottom: 20px;
            box-shadow: 0 6px 18px rgba(247, 110, 142, 0.28);
        }

        .card-heading {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 700;
            color: #e65275;
            margin-bottom: 6px;
            text-align: center;
        }

        .card-subtext {
            font-size: 13.5px;
            color: #63534d;
            font-weight: 500;
            margin-bottom: 30px;
            text-align: center;
        }

        .auth-form {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .input-block {
            display: flex;
            flex-direction: column;
            gap: 7px;
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
            padding: 13px 44px 13px 44px;
            background-color: #ffffff;
            border: 1.5px solid #f3d1db;
            border-radius: 14px;
            font-family: 'Montserrat', sans-serif;
            font-size: 13.5px;
            color: #332d2c;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .input-field-wrap input::placeholder {
            color: #a89a94;
            font-size: 13px;
        }

        .input-field-wrap input:focus {
            border-color: #f76e8e;
            box-shadow: 0 0 0 3px rgba(247, 110, 142, 0.15);
        }

        .toggle-password {
            position: absolute;
            right: 16px;
            color: #8c7b74;
            font-size: 16px;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .toggle-password:hover {
            color: #e65275;
        }

        .form-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            margin-top: 2px;
            margin-bottom: 6px;
            width: 100%;
        }

        .remember-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #433935;
            cursor: pointer;
            font-weight: 500;
        }

        .remember-wrap input[type="checkbox"] {
            accent-color: #f76e8e;
            cursor: pointer;
            width: 15px;
            height: 15px;
        }

        .forgot-link {
            color: #f76e8e;
            text-decoration: none;
            font-weight: 600;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .btn-submit {
            background-color: #e65275;
            color: #ffffff;
            border: none;
            border-radius: 14px;
            padding: 14px;
            font-family: 'Montserrat', sans-serif;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 0.4px;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(230, 82, 117, 0.3);
            transition: background-color 0.2s ease, transform 0.2s ease;
            width: 100%;
            margin-top: 4px;
        }

        .btn-submit:hover {
            background-color: #d13d60;
            transform: translateY(-2px);
        }

        .or-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 800;
            color: #a89a94;
            margin: 6px 0;
            width: 100%;
            letter-spacing: 0.5px;
        }

        .btn-outline {
            background-color: transparent;
            color: #e65275;
            border: 2px solid #f3b9c7;
            border-radius: 14px;
            padding: 13px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
            width: 100%;
            display: block;
        }

        .btn-outline:hover {
            background-color: #ffeef3;
            border-color: #e65275;
        }

        .error-banner {
            width: 100%;
            background-color: #ffe3e8;
            color: #ba2348;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
            text-align: left;
            border: 1px solid #f7b4c4;
        }

        .error-banner ul {
            margin: 0;
            padding-left: 18px;
        }

        .footer-bar {
            height: 70px;
            background-color: #f77290;
            width: 100%;
            flex-shrink: 0;
            margin-top: auto;
        }

        @media (max-width: 900px) {
            .navbar {
                flex-direction: column;
                padding: 16px 20px;
            }
            .brand-logo-nav {
                position: static;
                margin-bottom: 12px;
            }
            .nav-icons {
                position: static;
                margin-top: 12px;
            }
            .nav-links {
                gap: 20px;
                flex-wrap: wrap;
            }
        }

        @media (max-width: 520px) {
            .auth-card {
                padding: 34px 22px;
                border-radius: 28px;
            }
            .card-heading {
                font-size: 26px;
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
            <a href="index.php#about" class="nav-item">ABOUT US</a>
            <a href="index.php#menu" class="nav-item">MENU</a>
            <a href="index.php#reviews" class="nav-item">REVIEWS</a>
            <a href="index.php#contact" class="nav-item">CONTACT</a>
        </nav>
        
        <div class="nav-icons">
            <a href="#" class="icon-link" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></a>
            <a href="index.php#menu" class="icon-link cart-icon" aria-label="Shopping Cart">
                <i class="fa-solid fa-cart-shopping"></i>
            </a>
            <a href="login.php" class="btn-signin">Sign In</a>
        </div>
    </header>

    <main class="auth-wrapper">
        <div class="auth-card">
            <div class="avatar-badge">
                <i class="fa-regular fa-user"></i>
            </div>

            <h1 class="card-heading">Welcome Back!</h1>
            <p class="card-subtext">Log in to access your account or dashboard</p>

            <?php if (!empty($errors)): ?>
                <div class="error-banner">
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="auth-form">
                <div class="input-block">
                    <label for="email">Email or Username</label>
                    <div class="input-field-wrap">
                        <i class="fa-regular fa-envelope input-icon-left"></i>
                        <input type="text" id="email" name="email" placeholder="Enter your email or username" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="input-block">
                    <label for="password">Password</label>
                    <div class="input-field-wrap">
                        <i class="fa-solid fa-lock input-icon-left"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <i class="fa-regular fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                </div>

                <div class="form-meta">
                    <label class="remember-wrap">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-link">Forgot Password?</a>
                </div>

                <button type="submit" name="login" class="btn-submit">Log In</button>

                <div class="or-divider">OR</div>

                <a href="register.php" class="btn-outline">Create an Account</a>
            </form>
        </div>
    </main>

    <div class="footer-bar"></div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function () {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }
    </script>
</body>
</html>