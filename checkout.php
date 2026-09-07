<?php
session_start();
require_once 'db.php';

// Redirect to login if user is not signed in
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION['cart'])) {
    header("Location: Index.php#menu");
    exit;
}

$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['qty'];
}
$shipping_fee = 50.00;
$grand_total  = $subtotal + $shipping_fee;

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    if ($fullname === '' || $email === '' || $address === '' || $phone === '') {
        $errors[] = "Please fill in all mandatory billing and shipping fields.";
    }

    if (empty($errors)) {
        $user_id = $_SESSION['user_id'] ?? null;
        $order_items_json = json_encode($_SESSION['cart']);

        $stmt = $conn->prepare("INSERT INTO orders (user_id, customer_name, email, phone, address, subtotal, shipping_fee, total_amount, order_items) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssddds", $user_id, $fullname, $email, $phone, $address, $subtotal, $shipping_fee, $grand_total, $order_items_json);

        if ($stmt->execute()) {
            $_SESSION['cart'] = [];
            $success = true;
        } else {
            $errors[] = "Unable to process order. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Cheesecake Delight</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Playfair+Display:ital,wght@0,700;1,700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

    <header class="navbar">
        <a href="Index.php#home" class="brand-logo-nav">
            <img src="images/cheesecakeLogo.png" alt="Logo">
            <span class="nav-brand-name">Cheesecake Delight</span>
        </a>
        <nav class="nav-links">
            <a href="Index.php#home" class="nav-item">HOME</a>
            <a href="Index.php#menu" class="nav-item">MENU</a>
            <a href="cart.php" class="nav-item">CART</a>
        </nav>
    </header>

    <main class="cart-page-wrap">
        <div class="cart-container">
            <?php if ($success): ?>
                <div class="cart-empty-box">
                    <i class="fa-solid fa-circle-check" style="font-size: 50px; color: #2ecc71; margin-bottom: 12px;"></i>
                    <h2>Thank you for your order!</h2>
                    <p>Your cheesecakes are being prepped and will be delivered shortly.</p>
                    <a href="Index.php" class="btn btn-primary" style="margin-top: 15px;">Back to Home</a>
                </div>
            <?php else: ?>
                <h1 class="cart-title">Order Checkout</h1>

                <?php if (!empty($errors)): ?>
                    <div class="error-banner" style="max-width: 900px; margin: 0 auto 20px;">
                        <ul>
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="cart-content-grid">
                    <form method="POST" action="checkout.php" class="checkout-form">
                        <h2>Delivery Address</h2>
                        
                        <div class="input-block">
                            <label for="fullname">Full Name</label>
                            <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" required>
                        </div>

                        <div class="input-block">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>" required>
                        </div>

                        <div class="input-block">
                            <label for="phone">Contact Number</label>
                            <input type="tel" id="phone" name="phone" required placeholder="09xxxxxxxxx">
                        </div>

                        <div class="input-block">
                            <label for="address">Delivery Details &amp; Full Address</label>
                            <textarea id="address" name="address" rows="3" required placeholder="House number, Street, Barangay, City"></textarea>
                        </div>

                        <button type="submit" name="place_order" class="btn-proceed-checkout" style="margin-top: 15px;">Place Order Now</button>
                    </form>

                    <div class="cart-summary-box">
                        <h2>Items in Order</h2>
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <div class="summary-line">
                                <span><?php echo htmlspecialchars($item['name']); ?> (&times;<?php echo $item['qty']; ?>)</span>
                                <span>&#8369;<?php echo number_format($item['price'] * $item['qty'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="summary-divider"></div>
                        <div class="summary-line">
                            <span>Subtotal</span>
                            <span>&#8369;<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="summary-line">
                            <span>Shipping Fee</span>
                            <span>&#8369;<?php echo number_format($shipping_fee, 2); ?></span>
                        </div>
                        <div class="summary-divider"></div>
                        <div class="summary-line total-line">
                            <span>Total Payable</span>
                            <span>&#8369;<?php echo number_format($grand_total, 2); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div class="footer-bar"></div>
</body>
</html>