<?php
session_start();
require_once 'db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $conn->prepare("SELECT id, fullname, email, age, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header('Location: index.php');
    exit;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - Cheesecake Delight</title>
    
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
            max-width: 520px;
            border-radius: 40px;
            padding: 46px 42px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.08);
            border: 1px solid #fce3ea;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .avatar-badge {
            width: 76px;
            height: 76px;
            background-color: #48bb78;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 34px;
            margin-bottom: 20px;
            box-shadow: 0 6px 18px rgba(72, 187, 120, 0.35);
        }

        .card-heading {
            font-family: 'Playfair Display', serif;
            font-size: 30px;
            font-weight: 700;
            color: #e65275;
            margin-bottom: 6px;
        }

        .card-subtext {
            font-size: 14px;
            color: #63534d;
            font-weight: 500;
            margin-bottom: 26px;
        }

        .details-table-wrap {
            width: 100%;
            background-color: #ffffff;
            border: 1.5px solid #f3d1db;
            border-radius: 18px;
            overflow: hidden;
            margin-bottom: 26px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13.5px;
        }

        .details-table tr:not(:last-child) {
            border-bottom: 1px solid #fce3ea;
        }

        .details-table th {
            width: 38%;
            padding: 13px 18px;
            background-color: #fff9fa;
            color: #e65275;
            font-weight: 700;
        }

        .details-table td {
            padding: 13px 18px;
            color: #433935;
            font-weight: 600;
        }

        .action-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
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
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(230, 82, 117, 0.3);
            transition: background-color 0.2s ease, transform 0.2s ease;
            display: block;
        }

        .btn-submit:hover {
            background-color: #d13d60;
            transform: translateY(-2px);
        }

        .btn-outline {
            background-color: transparent;
            color: #e65275;
            border: 2px solid #f3b9c7;
            border-radius: 14px;
            padding: 12px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            display: block;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .btn-outline:hover {
            background-color: #ffeef3;
            border-color: #e65275;
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
        </div>
    </header>

    <main class="auth-wrapper">
        <div class="auth-card">
            <div class="avatar-badge">
                <i class="fa-solid fa-check"></i>
            </div>

            <h1 class="card-heading">Account Created!</h1>
            <p class="card-subtext">Your profile has been successfully registered.</p>

            <div class="details-table-wrap">
                <table class="details-table">
                    <tr>
                        <th>User ID</th>
                        <td>#<?= htmlspecialchars((string)$user['id']) ?></td>
                    </tr>
                    <tr>
                        <th>Full Name</th>
                        <td><?= htmlspecialchars($user['fullname']) ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                    </tr>
                    <tr>
                        <th>Age</th>
                        <td><?= htmlspecialchars($user['age'] ?? 'Not specified') ?></td>
                    </tr>
                    <tr>
                        <th>Phone</th>
                        <td><?= htmlspecialchars(!empty($user['phone']) ? $user['phone'] : 'Not provided') ?></td>
                    </tr>
                </table>
            </div>

            <div class="action-group">
                <a href="index.php" class="btn-submit">Go to Homepage</a>
                <a href="register.php" class="btn-outline">Register Another Account</a>
            </div>
        </div>
    </main>

    <div class="footer-bar"></div>

</body>
</html>