<?php
session_start();
require_once 'db.php';

// --- Validation Helper Functions ---
function validateRequired(string $value, string $label): ?string
{
    return trim($value) === '' ? "$label is required." : null;
}

function validateEmailFormat(string $value): ?string
{
    if (trim($value) === '') {
        return "Email is required.";
    }
    return filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "Enter a valid email address.";
}

function validateMinLength(string $value, string $label, int $min): ?string
{
    return strlen($value) < $min ? "$label must be at least $min characters long." : null;
}

function validateMatch(string $val1, string $val2, string $label): ?string
{
    return $val1 !== $val2 ? "$label do not match." : null;
}

function validateRegistrationInput(array $post): array
{
    $fullname         = trim($post['fullname'] ?? '');
    $email            = trim($post['email'] ?? '');
    $password         = $post['password'] ?? '';
    $confirm_password = $post['confirm_password'] ?? '';
    $age              = trim($post['age'] ?? '');
    $phone            = trim($post['phone'] ?? '');
    $terms            = isset($post['terms']);

    $ageError = null;
    if ($age !== '' && (!ctype_digit($age) || (int)$age < 1 || (int)$age > 120)) {
        $ageError = "Please enter a valid age.";
    }

    $errors = array_filter([
        validateRequired($fullname, 'Full Name'),
        validateEmailFormat($email),
        validateRequired($password, 'Password'),
        validateMinLength($password, 'Password', 6),
        validateRequired($confirm_password, 'Confirm Password'),
        validateMatch($password, $confirm_password, 'Passwords'),
        $ageError,
        !$terms ? "Please agree to the Terms & Conditions and Privacy Policy." : null,
    ]);

    return [
        'errors' => array_values($errors),
        'data'   => [
            'fullname' => $fullname,
            'email'    => $email,
            'password' => $password,
            'age'      => $age !== '' ? (int)$age : null,
            'phone'    => $phone,
        ],
    ];
}

$errors = [];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $validation = validateRegistrationInput($_POST);
    $errors     = $validation['errors'];
    $data       = $validation['data'];

    if (empty($errors)) {
        // Check if email already exists in MySQL
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $data['email']);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $errors[] = "An account with this email already exists.";
        } else {
            // Hash password securely
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);

            $insert_stmt = $conn->prepare("INSERT INTO users (fullname, email, password, age, phone) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("sssis", $data['fullname'], $data['email'], $hashed_password, $data['age'], $data['phone']);

            
                $errors[] = "Something went wrong. Please try again.";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an Account - Cheesecake Delight</title>
    
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
            background-image: url('images/homepagebg.png');
            background-size: cover;
            background-position: center left;
            background-repeat: no-repeat;
            position: relative;
        }

        .auth-card {
            background-color: #fffdf7;
            width: 100%;
            max-width: 580px;
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
            background-color: #e65275;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 30px;
            margin-bottom: 20px;
            box-shadow: 0 6px 18px rgba(230, 82, 117, 0.28);
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
            margin-bottom: 26px;
            text-align: center;
        }

        .auth-form {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .input-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            width: 100%;
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
            padding: 13px 42px 13px 44px;
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
            right: 14px;
            color: #8c7b74;
            font-size: 15px;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .toggle-password:hover {
            color: #e65275;
        }

        .terms-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            color: #433935;
            cursor: pointer;
            margin-top: 2px;
            margin-bottom: 4px;
            user-select: none;
        }

        .terms-wrap input[type="checkbox"] {
            accent-color: #f76e8e;
            cursor: pointer;
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        .terms-link {
            color: #e65275;
            text-decoration: none;
            font-weight: 600;
        }

        .terms-link:hover {
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

        @media (max-width: 600px) {
            .input-row {
                grid-template-columns: 1fr;
                gap: 16px;
            }
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
                <i class="fa-solid fa-user-plus"></i>
            </div>

            <h1 class="card-heading">Create an Account</h1>
            <p class="card-subtext">Sign up to get started with Cheesecake Delight</p>

            <?php if (!empty($errors)): ?>
                <div class="error-banner">
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" class="auth-form">
                <div class="input-row">
                    <div class="input-block">
                        <label for="fullname">Full Name</label>
                        <div class="input-field-wrap">
                            <i class="fa-regular fa-user input-icon-left"></i>
                            <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="input-block">
                        <label for="email">Email</label>
                        <div class="input-field-wrap">
                            <i class="fa-regular fa-envelope input-icon-left"></i>
                            <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-block">
                        <label for="password">Password</label>
                        <div class="input-field-wrap">
                            <i class="fa-solid fa-lock input-icon-left"></i>
                            <input type="password" id="password" name="password" placeholder="Create a password" required>
                            <i class="fa-regular fa-eye toggle-password" id="togglePassword"></i>
                        </div>
                    </div>

                    <div class="input-block">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-field-wrap">
                            <i class="fa-solid fa-lock input-icon-left"></i>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                            <i class="fa-regular fa-eye toggle-password" id="toggleConfirmPassword"></i>
                        </div>
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-block">
                        <label for="age">Age</label>
                        <div class="input-field-wrap">
                            <i class="fa-solid fa-cake-candles input-icon-left"></i>
                            <input type="number" id="age" name="age" min="1" max="120" placeholder="Enter age" value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="input-block">
                        <label for="phone">Phone Number (Optional)</label>
                        <div class="input-field-wrap">
                            <i class="fa-solid fa-phone input-icon-left"></i>
                            <input type="tel" id="phone" name="phone" placeholder="Enter phone number" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <label class="terms-wrap">
                    <input type="checkbox" name="terms" id="terms" <?php echo isset($_POST['terms']) ? 'checked' : ''; ?> required>
                    <span>I agree to the <a href="#" class="terms-link">Terms &amp; Conditions</a> and <a href="#" class="terms-link">Privacy Policy</a></span>
                </label>

                <button type="submit" name="register" class="btn-submit">Create Account</button>

                <div class="or-divider">OR</div>

                <a href="login.php" class="btn-outline">I already have an account</a>
            </form>
        </div>
    </main>

    <div class="footer-bar"></div>

    <script>
        function setupPasswordToggle(toggleId, inputId) {
            const toggle = document.getElementById(toggleId);
            const input = document.getElementById(inputId);

            if (toggle && input) {
                toggle.addEventListener('click', function () {
                    const isPassword = input.getAttribute('type') === 'password';
                    input.setAttribute('type', isPassword ? 'text' : 'password');
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            }
        }

        setupPasswordToggle('togglePassword', 'password');
        setupPasswordToggle('toggleConfirmPassword', 'confirm_password');
    </script>
</body>
</html>