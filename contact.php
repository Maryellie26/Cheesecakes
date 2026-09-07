<?php
session_start();

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

        /* --- Navbar --- */
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

        .nav-links {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 40px;
        }

        .nav-item {
            color: #ffffff;
            text-decoration: none;
            font-family: 'Montserrat', sans-serif;
            font-size: 15px;
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
            gap: 18px;
        }

        .icon-link {
            color: #ffffff;
            font-size: 19px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -10px;
            background: #ffffff;
            color: #f77290;
            font-size: 11px;
            font-weight: 800;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-signin {
            background-color: #ffffff;
            color: #f77290;
            font-family: 'Montserrat', sans-serif;
            font-size: 13.5px;
            font-weight: 800;
            text-decoration: none;
            padding: 8px 20px;
            border-radius: 30px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        /* --- Main Contact Section --- */
        .contact-section {
            flex: 1;
            position: relative;
            padding: 50px 24px 80px 24px;
            background-color: #fbe6b5;
            background-image: url('images/contactusbg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .section-title {
            text-align: center;
            font-family: 'Anton', sans-serif;
            font-size: clamp(2.4rem, 6vw, 3.8rem);
            letter-spacing: 2px;
            color: #ff6896;
            margin-bottom: 40px;
            text-transform: uppercase;
        }

        .contact-container {
            max-width: 1100px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 40px;
            position: relative;
            z-index: 2;
        }

        /* Left Side: Info List & Bottom Cheesecake */
        .contact-left {
            flex: 1;
            position: relative;
            display: flex;
            flex-direction: column;
            max-width: 480px;
        }

        .info-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            width: 100%;
            max-width: 400px;
            z-index: 2;
        }

        .info-card {
            background: #fffdf5;
            border: 1.5px solid #f9cad7;
            border-radius: 20px;
            padding: 14px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s ease;
        }

        .info-card:hover {
            transform: translateX(4px);
        }

        .icon-circle {
            width: 44px;
            height: 44px;
            background-color: #ffdce6;
            color: #f76e8e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .info-details h3 {
            font-size: 14.5px;
            font-weight: 800;
            color: #f76e8e;
            margin-bottom: 2px;
        }

        .info-details p {
            font-size: 13.5px;
            font-weight: 600;
            color: #383432;
            word-break: break-word;
        }

        .cheesecake-graphic {
            position: absolute;
            left: -30px;
            bottom: -160px;
            width: 320px;
            pointer-events: none;
            z-index: 1;
        }

        .cheesecake-graphic img {
            width: 100%;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 15px 25px rgba(0, 0, 0, 0.12));
        }

        /* Right Side: Form Card */
        .contact-right {
            flex: 1;
            display: flex;
            justify-content: flex-end;
            max-width: 480px;
        }

        .form-card {
            background: #fffdf5;
            border-radius: 36px;
            padding: 38px 40px;
            width: 100%;
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.06);
            border: 1px solid #fce3ea;
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
        }

        .form-icon {
            font-size: 26px;
            color: #f76e8e;
        }

        .form-title {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 27px;
            font-weight: 700;
            color: #f76e8e;
        }

        .message-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 700;
            color: #433935;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 13px 18px;
            border-radius: 14px;
            border: 1.5px solid #f7bfd0;
            background-color: #ffffff;
            font-family: 'Montserrat', sans-serif;
            font-size: 13.5px;
            color: #332d2c;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #baa3a9;
            font-size: 13px;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #f76e8e;
            box-shadow: 0 0 0 3px rgba(247, 110, 142, 0.15);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 105px;
        }

        .btn-send {
            background-color: #f76e8e;
            color: #ffffff;
            border: none;
            border-radius: 14px;
            padding: 14px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 4px;
            box-shadow: 0 4px 14px rgba(247, 110, 142, 0.3);
            transition: transform 0.2s ease, background-color 0.2s ease;
        }

        .btn-send:hover {
            background-color: #e55a7b;
            transform: translateY(-2px);
        }

        /* --- Footer Bar --- */
        .footer-bar {
            height: 70px;
            background-color: #f77290;
            width: 100%;
            flex-shrink: 0;
        }

        /* =========================================
           RESPONSIVE BREAKPOINTS
        ========================================= */

        /* Tablets & Medium Screens */
        @media (max-width: 992px) {
            .navbar {
                padding: 16px 24px;
            }
            .brand-logo-nav {
                left: 24px;
            }
            .nav-icons {
                right: 24px;
            }
            .contact-container {
                gap: 30px;
            }
            .cheesecake-graphic {
                width: 250px;
                bottom: -110px;
                left: -20px;
            }
        }

        /* Single Column Flow for Small Screens */
        @media (max-width: 860px) {
            .navbar {
                flex-direction: column;
                gap: 14px;
                padding: 18px 20px;
            }
            .brand-logo-nav, .nav-icons {
                position: static;
            }
            .nav-links {
                gap: 22px;
                flex-wrap: wrap;
            }
            .contact-section {
                padding: 40px 16px 60px 16px;
            }
            .contact-container {
                flex-direction: column;
                align-items: center;
                gap: 36px;
            }
            .contact-left, .contact-right {
                max-width: 100%;
                width: 100%;
                justify-content: center;
                align-items: center;
            }
            .info-list {
                max-width: 500px;
            }
            .form-card {
                max-width: 500px;
            }
            /* Hide or reposition overlapping graphic on tablet to avoid text clashing */
            .cheesecake-graphic {
                display: none;
            }
        }

        /* Mobile Phones */
        @media (max-width: 520px) {
            .section-title {
                margin-bottom: 25px;
            }
            .form-card {
                padding: 28px 20px;
                border-radius: 26px;
            }
            .form-title {
                font-size: 23px;
            }
            .info-card {
                padding: 12px 16px;
                border-radius: 16px;
            }
            .icon-circle {
                width: 38px;
                height: 38px;
                font-size: 16px;
            }
            .info-details h3 {
                font-size: 13.5px;
            }
            .info-details p {
                font-size: 12.5px;
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
            <a href="contact.php" class="nav-item" style="opacity: 1; border-bottom: 2px solid #ffffff;">CONTACT</a>
        </nav>
        
        <div class="nav-icons">
            <a href="#" class="icon-link" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></a>
            <a href="cart.php" class="icon-link cart-icon" aria-label="Shopping Cart">
                <i class="fa-solid fa-cart-shopping"></i>
                <?php if ($total_items > 0): ?>
                    <span class="cart-badge"><?php echo $total_items; ?></span>
                <?php endif; ?>
            </a>

            <?php if (isset($_SESSION['user_email'])): ?>
                <a href="logout.php" class="btn-signin">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn-signin">Sign In</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="contact-section">
        <h1 class="section-title">CONTACT US</h1>

        <div class="contact-container">
            <div class="contact-left">
                <div class="info-list">
                    <div class="info-card">
                        <div class="icon-circle"><i class="fa-solid fa-location-dot"></i></div>
                        <div class="info-details">
                            <h3>Visit Us</h3>
                            <p>Dumaguete, Philippines</p>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="icon-circle"><i class="fa-solid fa-phone"></i></div>
                        <div class="info-details">
                            <h3>Call Us</h3>
                            <p>+63 675 345 7256</p>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="icon-circle"><i class="fa-solid fa-envelope"></i></div>
                        <div class="info-details">
                            <h3>Email Us</h3>
                            <p>cheesecakedelight@gmail.com</p>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="icon-circle"><i class="fa-solid fa-clock"></i></div>
                        <div class="info-details">
                            <h3>Business Hours</h3>
                            <p>Mon - Sun: 9AM - 8PM</p>
                        </div>
                    </div>
                </div>

                <div class="cheesecake-graphic">
                    <img src="images/slice.png" alt="Cheesecake Slice Graphic">
                </div>
            </div>

            <div class="contact-right">
                <div class="form-card">
                    <div class="form-header">
                        <i class="fa-regular fa-envelope form-icon"></i>
                        <h2 class="form-title">Send Us a Message</h2>
                    </div>
                    <form onsubmit="event.preventDefault(); alert('Thank you! Your message has been sent successfully.');" class="message-form">
                        <div class="form-group">
                            <label for="name">Your Name</label>
                            <input type="text" id="name" name="name" placeholder="Enter your name" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Your Email</label>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required>
                        </div>

                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" rows="4" placeholder="Type your message..." required></textarea>
                        </div>

                        <button type="submit" class="btn-send">
                            <i class="fa-regular fa-paper-plane"></i> SEND MESSAGE
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <div class="footer-bar"></div>

</body>
</html>