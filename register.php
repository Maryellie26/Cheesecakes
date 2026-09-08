<?php
session_start();
require_once 'db.php';

// Redirect if already logged in
if (isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $age       = (int)($_POST['age'] ?? 0);
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if ($firstname === '') {
        $errors[] = "First name is required.";
    }
    if ($lastname === '') {
        $errors[] = "Last name is required.";
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    if ($age <= 0 || $age > 120) {
        $errors[] = "Please enter a valid age.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $errors[] = "An account with this email already exists.";
        } else {
            $fullname = $firstname . ' ' . $lastname;
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, age, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $fullname, $email, $age, $hashed);

            if ($stmt->execute()) {
                $success = "Account created successfully! You can now log in.";
            } else {
                $errors[] = "Database error: Unable to complete registration.";
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Cheesecake Delight</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600;1,700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        body {
            background-color: #fbe6b5;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .auth-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            background-image: url('images/homepagebg.png');
            background-size: cover;
            background-position: center;
        }
        .auth-card {
            background-color: #fffdf7;
            width: 100%;
            max-width: 500px;
            border-radius: 36px;
            padding: 40px;
            box-shadow: 0 16px 40px rgba(0,0,0,0.08);
            border: 1.5px solid #fce3ea;
        }
        .form-row-split {
            display: flex;
            gap: 12px;
            margin-bottom: 14px;
        }
        .form-block {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 14px;
            flex: 1;
        }
        .form-row-split .form-block {
            margin-bottom: 0;
        }
        .form-block label {
            font-size: 13px;
            font-weight: 700;
            color: #433935;
        }
        .form-block input {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1.5px solid #f3d1db;
            outline: none;
            font-size: 13.5px;
            box-sizing: border-box;
            background-color: #ffffff;
            font-family: 'Poppins', sans-serif;
        }
        .form-block input:focus {
            border-color: #f76e8e;
        }
        .btn-register {
            background-color: #e65275;
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 13px;
            font-weight: 800;
            width: 100%;
            cursor: pointer;
            margin-top: 6px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            transition: background-color 0.2s ease;
        }
        .btn-register:hover {
            background-color: #d13d60;
        }
        .banner {
            padding: 11px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 14px;
        }
        .banner-err { background-color: #ffe3e8; color: #ba2348; border: 1px solid #f7b4c4; }
        .banner-ok { background-color: #e6f9ed; color: #1b873f; border: 1px solid #a3e9be; }

        @media (max-width: 480px) {
            .form-row-split {
                flex-direction: column;
                gap: 14px;
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

    <main class="auth-wrapper">
        <div class="auth-card">
            <h1 style="font-family: 'Playfair Display', serif; color: #e65275; text-align: center; margin-bottom: 4px;">Create Account</h1>
            <p style="font-size: 13px; color: #63534d; text-align: center; margin-bottom: 22px;">Sign up to easily place and track your orders</p>

            <?php if (!empty($errors)): ?>
                <div class="banner banner-err">
                    <ul style="padding-left: 18px; margin: 0;">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="banner banner-ok">
                    <?php echo htmlspecialchars($success); ?>
                    <div style="margin-top: 6px;"><a href="login.php" style="color: #1b873f; font-weight: 800;">Log In Now &rarr;</a></div>
                </div>
            <?php else: ?>
                <form method="POST" action="register.php">
                    <!-- Separated First Name & Last Name -->
                    <div class="form-row-split">
                        <div class="form-block">
                            <label for="firstname">First Name</label>
                            <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>" required placeholder="e.g. Maria">
                        </div>

                        <div class="form-block">
                            <label for="lastname">Last Name</label>
                            <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>" required placeholder="e.g. Santos">
                        </div>
                    </div>
                    
                    <div class="form-block">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="name@domain.com">
                    </div>

                    <div class="form-block">
                        <label for="age">Age</label>
                        <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>" required min="1" max="120" placeholder="e.g. 20">
                    </div>

                    <div class="form-block">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required placeholder="Minimum 6 characters">
                    </div>

                    <div class="form-block">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-type password">
                    </div>

                    <button type="submit" name="register" class="btn-register">Register Account</button>
                    <p style="font-size: 13px; text-align: center; margin-top: 14px; color: #63534d;">
                        Already registered? <a href="login.php" style="color: #e65275; font-weight: 700;">Sign in here</a>
                    </p>
                </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>