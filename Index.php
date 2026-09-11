<?php
session_start();
require_once 'db.php';

// Ensure table supports sender_type and user association
$conn->query("ALTER TABLE messages ADD COLUMN IF NOT EXISTS sender_type ENUM('user', 'admin') NOT NULL DEFAULT 'user'");
$conn->query("ALTER TABLE messages ADD COLUMN IF NOT EXISTS user_id INT NULL DEFAULT NULL");

// Detect customer identity[cite: 10]
$user_id    = $_SESSION['user_id'] ?? null;
$user_email = $_SESSION['user_email'] ?? ($_SESSION['guest_chat_email'] ?? '');
$user_name  = $_SESSION['username'] ?? ($_SESSION['guest_chat_name'] ?? '');

// Handle Contact Form & Chat Message Submission[cite: 10]
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['send_message']) || isset($_POST['send_chat_message']))) {
    $name    = trim($_POST['name'] ?? ($user_name ?: 'Customer'));
    $email   = trim($_POST['email'] ?? ($user_email ?: 'guest@cheesecakedelight.com'));
    $message = trim($_POST['message'] ?? ($_POST['chat_message'] ?? ''));

    if (!empty($message)) {
        $_SESSION['guest_chat_email'] = $email;
        $_SESSION['guest_chat_name']  = $name;
        $user_email = $email;
        $user_name  = $name;

        $stmt = $conn->prepare("INSERT INTO messages (user_id, name, email, message, sender_type) VALUES (?, ?, ?, ?, 'user')");
        if ($stmt) {
            $stmt->bind_param("isss", $user_id, $name, $email, $message);
            if ($stmt->execute()) {
                $_SESSION['msg_status'] = 'success';
            } else {
                $_SESSION['msg_status'] = 'error';
            }
            $stmt->close();
        } else {
            $_SESSION['msg_status'] = 'error';
        }
    } else {
        $_SESSION['msg_status'] = 'empty';
    }

    header("Location: Index.php#contact");
    exit;
}

// Fetch Thread for Customer[cite: 10]
$contact_thread = [];
if (!empty($user_email)) {
    $stmt = $conn->prepare("SELECT id, name, email, message, sender_type, created_at FROM messages WHERE email = ? ORDER BY id ASC");
    if ($stmt) {
        $stmt->bind_param("s", $user_email);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $contact_thread[] = $row;
        }
        $stmt->close();
    }
}

// Fallback catalog in case database table is temporarily empty[cite: 10]
$default_catalog = [
    1 => ["name" => "Strawberry Cheesecake", "desc" => "Creamy cheesecake with fresh strawberry topping.", "price" => "180.00", "image" => "images/strawberry.png.png", "stock" => 10],
    2 => ["name" => "Blueberry Cheesecake", "desc" => "Smooth cheesecake with sweet blueberry compote.", "price" => "180.00", "image" => "images/blueberry.png.png", "stock" => 8],
    3 => ["name" => "Chocolate Cheesecake", "desc" => "Rich chocolate cheesecake on chocolate crust.", "price" => "180.00", "image" => "images/chocolate.png.png", "stock" => 12],
    4 => ["name" => "Mango Cheesecake", "desc" => "Creamy cheesecake with real mango goodness.", "price" => "180.00", "image" => "images/mango.png.png", "stock" => 6],
    5 => ["name" => "Oreo Cheesecake", "desc" => "Classic cheesecake with crunchy Oreo cookies.", "price" => "180.00", "image" => "images/oreo.png.png", "stock" => 9],
    6 => ["name" => "Caramel Cheesecake", "desc" => "Creamy cheesecake topped with rich caramel.", "price" => "180.00", "image" => "images/caramel.png.png", "stock" => 7],
    7 => ["name" => "Matcha Cheesecake", "desc" => "Smooth matcha cheesecake with a hint of green tea.", "price" => "180.00", "image" => "images/matcha.png.png", "stock" => 5],
    8 => ["name" => "Red Velvet Cheesecake", "desc" => "Red velvet cake with creamy cheese cake layer.", "price" => "180.00", "image" => "images/redvelvet.png", "stock" => 4]
];

// Fetch all products dynamically from database[cite: 10]
$menu_items = [];
$prod_query = $conn->query("SELECT id, name, description AS `desc`, price, image, stock FROM products ORDER BY id ASC");
if ($prod_query && $prod_query->num_rows > 0) {
    while ($row = $prod_query->fetch_assoc()) {
        $menu_items[] = $row;
    }
} else {
    foreach ($default_catalog as $id => $item) {
        $item['id'] = $id;
        $menu_items[] = $item;
    }
}

$total_items = 0;
$total_price = 0.00;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_items += (int)$item['qty'];
        $total_price += ((float)$item['price'] * (int)$item['qty']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cheesecake Delight</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Caveat:wght@600;700&family=Montserrat:wght@400;600;700;800;900&family=Playfair+Display:ital,wght@0,600;0,700;1,600;1,700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .form-feedback {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-feedback.success {
            background-color: #e6f9ed;
            color: #1b873f;
            border: 1.5px solid #a3e9be;
        }
        .form-feedback.error {
            background-color: #ffe8ec;
            color: #d13d60;
            border: 1.5px solid #f7b4c4;
        }

        .stock-badge {
            font-size: 11.5px;
            font-weight: 700;
            margin: 2px 0 10px 0;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .stock-badge.in-stock {
            color: #27ae60;
        }
        .stock-badge.out-of-stock {
            color: #e74c3c;
        }

        .btn-disabled {
            background-color: #e0b2bd !important;
            cursor: not-allowed !important;
            box-shadow: none !important;
            transform: none !important;
        }
    </style>
</head>
<body>

    <header class="navbar">
        <a href="#home" class="brand-logo-nav">
            <img src="images/cheesecakeLogo.png" alt="Logo">
            <span class="nav-brand-name">Cheesecake Delight</span>
        </a>
        <nav class="nav-links">
            <a href="#home" class="nav-item">HOME</a>
            <a href="#about" class="nav-item">ABOUT US</a>
            <a href="#menu" class="nav-item">MENU</a>
            <a href="#reviews" class="nav-item">REVIEWS</a>
            <a href="#contact" class="nav-item">CONTACT</a>
        </nav>
        
        <div class="nav-icons">
            <a href="cart.php" class="icon-link cart-icon" aria-label="Shopping Cart">
                <i class="fa-solid fa-cart-shopping"></i>
                <?php if ($total_items > 0): ?>
                    <span class="cart-badge"><?php echo $total_items; ?></span>
                <?php endif; ?>
            </a>

            <?php if (isset($_SESSION['user_email'])): ?>
                <span style="color: #ffffff; font-size: 13.5px; font-weight: 700; margin-left: 6px;">
                    <?php echo htmlspecialchars($_SESSION['user_email']); ?>
                </span>
                <a href="logout.php" class="btn-signin" style="padding: 7px 18px; font-size: 13.5px;">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn-signin">Sign In</a>
            <?php endif; ?>
        </div>
    </header>

    <section id="home" class="hero-section">
        <div class="hero-content">
            <div class="hero-text">
                <h1 class="main-title">A LITTLE SLICE</h1>
                <h2 class="sub-script">of happiness in every bite</h2>
                <p class="tagline">Made with love, served with joy!</p>
            </div>
            <div class="cta-group">
                <a href="#menu" class="btn btn-primary">Shop Now</a>
                <a href="#contact" class="btn btn-secondary">Contact Us</a>
            </div>
        </div>
    </section>
    <div class="footer-bar"></div>

    <section id="about" class="about-section">
        <div class="about-container">
            <div class="about-left">
                <div class="about-header">
                    <span class="about-script">About</span>
                    <h2 class="about-title">Cheesecake Delight</h2>
                    <div class="heart-divider">
                        <span class="line"></span>
                        <span class="heart">&#9825;</span>
                        <span class="line"></span>
                    </div>
                </div>
                <p class="about-description">
                    Cheesecake Delight was born from a simple love for baking and a dream to share happiness in every slice.
                </p>
                <div class="about-btn-group">
                    <a href="#menu" class="btn btn-primary">Explore Our Flavors</a>
                </div>
            </div>

            <div class="about-right">
                <div class="img-large-card">
                    <img src="images/girlbaking.png" alt="Baker decorating cheesecake" class="about-img">
                </div>
                <div class="img-small-row">
                    <div class="img-small-card">
                        <img src="images/pouring.png" alt="Pouring batter into pan" class="about-img">
                    </div>
                    <div class="img-small-card">
                        <img src="images/cream.png" alt="Whisking cream mixture" class="about-img">
                    </div>
                </div>
            </div>
        </div>
    </section>
    <div class="footer-bar"></div>

    <section id="menu" class="menu-section">
    <div class="menu-container">
        <h2 class="section-title">OUR MENU</h2>
        
        <div class="menu-grid">
            <?php foreach ($menu_items as $item): 
                $in_cart = isset($_SESSION['cart'][$item['id']]) ? (int)$_SESSION['cart'][$item['id']]['qty'] : 0;
                $available_stock = max(0, (int)$item['stock'] - $in_cart);
            ?>
                <div class="menu-card">
                    <div class="card-image">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    </div>
                    <div class="card-body">
                        <h3 class="item-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p class="item-desc"><?php echo htmlspecialchars($item['desc']); ?></p>
                        
                        <span class="item-price">&#8369;<?php echo htmlspecialchars(number_format((float)$item['price'], 2)); ?></span>

                        <?php if ($available_stock > 0): ?>
                            <div class="stock-badge in-stock">&#9679; <?php echo $available_stock; ?> in stock</div>
                        <?php else: ?>
                            <div class="stock-badge out-of-stock">&#9679; Out of Stock</div>
                        <?php endif; ?>

                        <form action="cart.php" method="POST" class="card-footer menu-action-row">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">

                            <div class="action-controls-wrap">
                                <div class="stepper-box">
                                    <button type="button" class="stepper-btn" onclick="stepQty('qty_<?php echo $item['id']; ?>', -1, <?php echo $available_stock; ?>)" <?php echo $available_stock === 0 ? 'disabled' : ''; ?>>&minus;</button>
                                    <input type="number" id="qty_<?php echo $item['id']; ?>" name="quantity" value="<?php echo $available_stock > 0 ? 1 : 0; ?>" min="1" max="<?php echo $available_stock; ?>" class="stepper-input" readonly>
                                    <button type="button" class="stepper-btn" onclick="stepQty('qty_<?php echo $item['id']; ?>', 1, <?php echo $available_stock; ?>)" <?php echo $available_stock === 0 ? 'disabled' : ''; ?>>&plus;</button>
                                </div>

                                <button type="submit" class="add-cart-pill-btn <?php echo $available_stock === 0 ? 'btn-disabled' : ''; ?>" <?php echo $available_stock === 0 ? 'disabled' : ''; ?>>
                                    Add <i class="fa-solid fa-cart-shopping"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_items > 0): ?>
            <div class="floating-cart-bar">
                <div class="floating-cart-details">
                    <div class="cart-icon-circle">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </div>
                    <div class="floating-cart-text">
                        <span class="floating-cart-count"><?php echo $total_items; ?> Items in Bag</span>
                        <span class="floating-cart-total">&#8369;<?php echo number_format($total_price, 2); ?></span>
                    </div>
                </div>
                <a href="cart.php" class="floating-checkout-btn" style="text-decoration: none;">
                    Open Cart &rarr;
                </a>
            </div>
        <?php endif; ?>

    </div>
</section>
    <div class="footer-bar"></div>

    <section id="reviews" class="reviews-section">
        <div class="reviews-container">
            <h2 class="section-title">OUR HAPPY CUSTOMERS</h2>
            <div class="reviews-grid">
                <div class="review-card">
                    <div class="avatar-box">
                        <img src="images/emily.png" alt="Emily Carter" class="avatar-img">
                    </div>
                    <h3 class="customer-name">Emily Carter</h3>
                    <div class="star-rating">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                    <p class="review-text">
                        The perfect balance of sweetness and creaminess. I'll definitely be coming back for more!
                    </p>
                </div>

                <div class="review-card">
                    <div class="avatar-box">
                        <img src="images/jamie.png" alt="Jamie Smith" class="avatar-img">
                    </div>
                    <h3 class="customer-name">Jamie Smith</h3>
                    <div class="star-rating">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                    <p class="review-text">
                        Absolutely delicious! The cheesecake was creamy, fresh, and had the perfect balance of sweetness.
                    </p>
                </div>

                <div class="review-card">
                    <div class="avatar-box">
                        <img src="images/olivia.png" alt="Olivia Miller" class="avatar-img">
                    </div>
                    <h3 class="customer-name">Olivia Miller</h3>
                    <div class="star-rating">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                    <p class="review-text">
                        The cheesecake tasted amazing and looked beautiful too. Definitely worth every bite!
                    </p>
                </div>
            </div>
        </div>

        <div class="bottom-photo-strip">
            <div class="photo-strip-item">
                <img src="images/satoberi.png" alt="Strawberry Cheesecake">
            </div>
            <div class="photo-strip-item">
                <img src="images/variety.png" alt="Variety of Cheesecakes">
            </div>
            <div class="photo-strip-item">
                <img src="images/slice.png" alt="Berry Cheesecake Slice">
            </div>
        </div>
    </section>
    <div class="footer-bar"></div>

    <!-- ================= UNIFIED CONTACT US SECTION ================= -->
    <section id="contact" class="contact-section">
        <div class="contact-container">
            <div class="decor-title-box">
                <div class="title-with-rays">
                    <h2 class="main-contact-title">CONTACT US</h2>
                </div>
                <p class="script-subtitle">We'd love to hear from you!</p>
            </div>

            <div class="contact-content">
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
                                <p>+63 875 945 7256</p>
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
                </div>

                <div class="contact-right">
                    <div class="chat-form-card">
                        <div class="chat-card-top">
                            <div class="pink-speech-icon">
                                <i class="fa-solid fa-comment-dots"></i>
                            </div>
                            <h3 class="chat-card-title">Your Message</h3>
                        </div>

                        <div class="chat-stream-window" id="mainChatScroll">
                            <?php if (empty($contact_thread)): ?>
                                <div style="text-align: center; margin: auto; color: #8c7b74; padding: 20px;">
                                    <i class="fa-regular fa-paper-plane" style="font-size: 28px; color: #f76e8e; margin-bottom: 6px;"></i>
                                    <p style="font-weight: 700; color: #e65275; font-size: 13.5px;">Have a question?</p>
                                    <p style="font-size: 12px; margin-top: 3px;">Type your message below. Admin replies will appear right here!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($contact_thread as $msg): 
                                    $is_admin = ($msg['sender_type'] === 'admin');
                                ?>
                                    <div class="msg-chat-item <?php echo $is_admin ? 'admin-item' : 'user-item'; ?>">
                                        <div class="avatar-holder">
                                            <?php if ($is_admin): ?>
                                                <img src="images/cheesecakeLogo.png" alt="Admin" style="width: 80%; height: 80%; object-fit: contain;">
                                            <?php else: ?>
                                                <i class="fa-solid fa-user"></i>
                                            <?php endif; ?>
                                        </div>

                                        <div class="bubble-and-time">
                                            <div class="msg-bubble-box">
                                                <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                            </div>
                                            <div class="timestamp-row">
                                                <span><?php echo date('h:i A', strtotime($msg['created_at'] ?? 'now')); ?></span>
                                                <?php if (!$is_admin): ?>
                                                    <i class="fa-solid fa-check-double" style="font-size: 8.5px; color: #f76e8e;"></i>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <form method="POST" action="Index.php#contact" class="chat-form-bottom">
                            <?php if (empty($user_email)): ?>
                                <input type="hidden" name="name" value="Guest Customer">
                                <input type="hidden" name="email" value="guest_<?php echo substr(session_id(), 0, 8); ?>@cheesecake.com">
                            <?php else: ?>
                                <input type="hidden" name="name" value="<?php echo htmlspecialchars($user_name ?: 'Customer'); ?>">
                                <input type="hidden" name="email" value="<?php echo htmlspecialchars($user_email); ?>">
                            <?php endif; ?>

                            <div class="relative-input-wrapper">
                                <i class="fa-solid fa-paperclip clip-symbol"></i>
                                <input type="text" name="message" placeholder="Type your message..." required autocomplete="off">
                            </div>
                            <button type="submit" name="send_message" class="round-pink-send" title="Send message">
                                <i class="fa-solid fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <div class="footer-bar"></div>

    <footer id="footer" class="site-footer">
        <div class="footer-container">
            <div class="footer-col footer-brand">
                <div class="footer-logo">
                    <img src="images/logors.png" alt="Cheesecake Delight Logo" class="footer-logo-img">
                    <div class="footer-brand-name">
                        <span>Cheesecake</span>
                        <span>Delight</span>
                    </div>
                </div>
                <p class="footer-tagline">
                    Made with love, served with joy!<br>
                    We bake a little slice of happiness in every bite
                </p>
                <div class="footer-social-box">
                    <h4>Follow Us</h4>
                    <div class="social-icons">
                        <a href="#" class="social-circle"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="social-circle"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" class="social-circle"><i class="fa-brands fa-tiktok"></i></a>
                    </div>
                </div>
            </div>

            <div class="footer-col">
                <h3 class="footer-heading">Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="#home"><i class="fa-solid fa-house"></i> Home</a></li>
                    <li><a href="#menu"><i class="fa-solid fa-cake-candles"></i> Menu</a></li>
                    <li><a href="#about"><i class="fa-regular fa-heart"></i> About Us</a></li>
                    <li><a href="#contact"><i class="fa-regular fa-comment-dots"></i> Contact Us</a></li>
                    <li><a href="#menu"><i class="fa-solid fa-bag-shopping"></i> Order Now</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h3 class="footer-heading">Customer Info</h3>
                <ul class="footer-links">
                    <li><a href="#"><i class="fa-solid fa-truck"></i> Delivery &amp; Shipping</a></li>
                    <li><a href="#"><i class="fa-solid fa-rotate-left"></i> Return &amp; Refunds</a></li>
                    <li><a href="#"><i class="fa-solid fa-shield-halved"></i> Privacy and Policy</a></li>
                    <li><a href="#"><i class="fa-regular fa-file-lines"></i> Terms &amp; Conditions</a></li>
                    <li><a href="#"><i class="fa-regular fa-circle-question"></i> FAQ</a></li>
                </ul>
            </div>

            <div class="footer-col footer-newsletter">
                <h3 class="footer-heading">Stay Sweet!</h3>
                <p class="newsletter-desc">
                    Subscribe to get the latest updates, offers, and sweet surprises!
                </p>
                <form class="newsletter-form" onsubmit="event.preventDefault(); alert('Thank you for subscribing!');">
                    <input type="email" placeholder="Enter your email" required>
                    <button type="submit" aria-label="Subscribe">
                        <i class="fa-regular fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
        <div class="footer-bottom"></div>
    </footer>
    <div class="footer-bar"></div>

    <script>
        function stepQty(inputId, delta, maxStock) {
            const input = document.getElementById(inputId);
            if (!input || maxStock <= 0) return;
            let val = parseInt(input.value) || 1;
            val = Math.max(1, Math.min(maxStock, val + delta));
            input.value = val;
        }

        const chatScroll = document.getElementById('mainChatScroll');
        if (chatScroll) {
            chatScroll.scrollTop = chatScroll.scrollHeight;
        }
    </script>
</body>
</html>