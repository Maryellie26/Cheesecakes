<?php
// Initialize session to manage user state and authentication flags
session_start();

// Include database connection settings
require_once 'db.php';

// --- SESSION GUARD ---
// If the user is already authenticated, redirect them away from registration to the storefront
if (isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit;
}

// Initialize array to hold form validation and database error messages
$errors = [];

// --- FORM SUBMISSION HANDLING ---
// Process submission when a POST request is triggered via the 'register' button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // Sanitize and trim incoming text input fields
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $age       = (int)($_POST['age'] ?? 0);
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    // Validate that the first name is provided
    if ($firstname === '') {
        $errors[] = "First name is required.";
    }
    // Validate that the last name is provided
    if ($lastname === '') {
        $errors[] = "Last name is required.";
    }
    // Validate email presence and email syntax format
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    // Validate age range between 1 and 120
    if ($age <= 0 || $age > 120) {
        $errors[] = "Please enter a valid age.";
    }
    // Check minimum character requirement for password security
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    // Ensure password and confirmation input match identically
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    // Proceed if all inputs passed validation
    if (empty($errors)) {
        // --- DUPLICATE ACCOUNT CHECK ---
        // Prepare parameterized statement to see if this email is already registered
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        // Reject if account already exists
        if ($check->num_rows > 0) {
            $errors[] = "An account with this email already exists.";
        } else {
            // Combine first and last names for unified storage
            $fullname = $firstname . ' ' . $lastname;

            // Hash the password securely using the default algorithm (Bcrypt/Argon2)
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // --- INSERT USER RECORD ---
            // Prepare parameterized insertion query to prevent SQL injection
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, age, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $fullname, $email, $age, $hashed);

            if ($stmt->execute()) {
                // Auto-login: assign active session data for the newly registered account
                $_SESSION['user_id']         = $stmt->insert_id;
                $_SESSION['user_email']      = $email;
                $_SESSION['username']        = $fullname;
                $_SESSION['just_registered'] = true;

                // Free resources
                $stmt->close();
                $check->close();

                // Redirect user to the success splash screen
                header("Location: success.php");
                exit;
            } else {
                // Database insert failed
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
    <!-- Document Metadata & Viewport Setup -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Cheesecake Delight</title>
    
    <!-- External Typography & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600;1,700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    
    <style>
        /* Base Reset & Page Layout Setup */
        body {
            background-color: #fbe6b5;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Centered Background Container */
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

        /* Form Card Container */
        .auth-card {
            background-color: #fffdf7;
            width: 100%;
            max-width: 500px;
            border-radius: 36px;
            padding: 40px;
            box-shadow: 0 16px 40px rgba(0,0,0,0.08);
            border: 1.5px solid #fce3ea;
        }

        /* Two-column Input Row */
        .form-row-split {
            display: flex;
            gap: 12px;
            margin-bottom: 14px;
        }

        /* Input Group Block */
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

        /* Primary Register Action Button */
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

        /* Error Notification Banner */
        .banner-err {
            padding: 11px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 14px;
            background-color: #ffe3e8;
            color: #ba2348;
            border: 1px solid #f7b4c4;
        }

        /* Bottom Visual Brand Footer */
        .footer-bar {
            height: 70px;
            background-color: #f77290;
            width: 100%;
            flex-shrink: 0;
            margin-top: auto;
        }

        /* Responsive Breakpoints */
        @media (max-width: 480px) {
            .form-row-split {
                flex-direction: column;
                gap: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Header -->
    <header class="navbar">
        <a href="index.php#home" class="brand-logo-nav">
            <img src="images/cheesecakeLogo.png" alt="Logo">
            <span class="nav-brand-name">Cheesecake Delight</span>
        </a>
    </header>

    <!-- Main Registration Wrapper -->
    <main class="auth-wrapper">
        <div class="auth-card">
            <h1 style="font-family: 'Playfair Display', serif; color: #e65275; text-align: center; margin-bottom: 4px;">Create Account</h1>
            <p style="font-size: 13px; color: #63534d; text-align: center; margin-bottom: 22px;">Sign up to easily place and track your orders</p>

            <!-- Error Notification Display -->
            <?php if (!empty($errors)): ?>
                <div class="banner-err">
                    <ul style="padding-left: 18px; margin: 0;">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Registration Form Posting to register.php -->
            <form method="POST" action="register.php">
                <!-- Name Row (First Name & Last Name) -->
                <div class="form-row-split">
                    <div class="form-block">
                        <label for="firstname">First Name</label>
                        <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>" required>
                    </div>

                    <div class="form-block">
                        <label for="lastname">Last Name</label>
                        <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <!-- Email Address Input -->
                <div class="form-block">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>

                <!-- Age Numeric Input -->
                <div class="form-block">
                    <label for="age">Age</label>
                    <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>" required min="1" max="120">
                </div>

                <!-- Password Input -->
                <div class="form-block">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <!-- Confirm Password Input -->
                <div class="form-block">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <!-- Submit Button -->
                <button type="submit" name="register" class="btn-register">Register Account</button>
                
                <!-- Login Redirection Link -->
                <p style="font-size: 13px; text-align: center; margin-top: 14px; color: #63534d;">
                    Already registered? <a href="login.php" style="color: #e65275; font-weight: 700;">Sign in here</a>
                </p>
            </form>
        </div>
    </main>

    <!-- Bottom Pink Footer Bar -->
    <div class="footer-bar"></div>
</body>
</html>