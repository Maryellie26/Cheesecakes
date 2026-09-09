<?php
session_start();
require_once 'db.php';

// Ensure payment_method column exists in orders table[cite: 11]
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) NOT NULL DEFAULT 'Cash on Delivery'");

// Redirect to login if user is not signed in[cite: 11]
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION['cart'])) {
    header("Location: Index.php#menu");
    exit;
}

// Optional cart modifications directly from the summary sidebar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $pid = (int)($_POST['product_id'] ?? 0);
    $op  = $_POST['op'] ?? '';

    if ($_POST['action'] === 'update' && isset($_SESSION['cart'][$pid])) {
        if ($op === 'inc') {
            $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $pid);
                $stmt->execute();
                $res = $stmt->get_result();
                $p = $res->fetch_assoc();
                $stmt->close();
                if ($p && $_SESSION['cart'][$pid]['qty'] < (int)$p['stock']) {
                    $_SESSION['cart'][$pid]['qty']++;
                }
            }
        } elseif ($op === 'dec') {
            $_SESSION['cart'][$pid]['qty']--;
            if ($_SESSION['cart'][$pid]['qty'] <= 0) {
                unset($_SESSION['cart'][$pid]);
            }
        }
    } elseif ($_POST['action'] === 'remove' && isset($_SESSION['cart'][$pid])) {
        unset($_SESSION['cart'][$pid]);
    }
    header("Location: checkout.php");
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
    $fullname       = trim($_POST['fullname'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'Cash on Delivery');
    $gcash_ref      = trim($_POST['gcash_reference'] ?? '');

    if ($fullname === '' || $email === '' || $address === '' || $phone === '') {
        $errors[] = "Please fill in all mandatory billing and shipping fields.";
    }

    if ($payment_method === 'GCash' && empty($gcash_ref)) {
        $errors[] = "Please provide your GCash Reference Number.";
    }

    $final_payment = $payment_method;
    if ($payment_method === 'GCash' && !empty($gcash_ref)) {
        $final_payment = "GCash (Ref: " . $gcash_ref . ")";
    }

    if (empty($errors)) {
        $user_id = $_SESSION['user_id'] ?? null;
        $order_items_json = json_encode($_SESSION['cart']);

        // Database Transaction: Safely verifies stock and commits permanent deduction[cite: 11]
        $conn->begin_transaction();

        try {
            foreach ($_SESSION['cart'] as $pid => $item) {
                $qty = (int)$item['qty'];

                // Lock row for safe concurrent check[cite: 11]
                $check_stmt = $conn->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
                $check_stmt->bind_param("i", $pid);
                $check_stmt->execute();
                $res = $check_stmt->get_result();
                $prod = $res->fetch_assoc();
                $check_stmt->close();

                if (!$prod || (int)$prod['stock'] < $qty) {
                    throw new Exception("Sorry, " . $item['name'] . " does not have enough stock remaining.");
                }

                // Deduct stock permanently from database[cite: 11]
                $update_stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $update_stmt->bind_param("ii", $qty, $pid);
                $update_stmt->execute();
                $update_stmt->close();
            }

            // Insert into orders table with payment_method[cite: 11]
            $stmt = $conn->prepare("INSERT INTO orders (user_id, customer_name, email, phone, address, subtotal, shipping_fee, total_amount, order_items, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssdddss", $user_id, $fullname, $email, $phone, $address, $subtotal, $shipping_fee, $grand_total, $order_items_json, $final_payment);
            $stmt->execute();
            $stmt->close();

            // Commit all changes safely[cite: 11]
            $conn->commit();

            $_SESSION['cart'] = [];
            $success = true;

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Cheesecake Delight</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800;900&family=Playfair+Display:ital,wght@0,700;1,700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #fbe6b5;
            background-image: url('images/cheesecakebgg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            font-family: 'Poppins', 'Montserrat', sans-serif;
            color: #332d2c;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* --- Navbar Matching Reference Image --- */
        .navbar {
            background-color: #f77290;
            width: 100%;
            padding: 16px 48px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
        }

        .brand-logo-nav {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .brand-logo-nav img {
            width: 38px;
            height: 38px;
            object-fit: contain;
        }

        .brand-logo-nav .nav-brand-name {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.3px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 36px;
        }

        .nav-item {
            color: #ffffff;
            text-decoration: none;
            font-family: 'Montserrat', sans-serif;
            font-size: 14.5px;
            font-weight: 800;
            letter-spacing: 0.8px;
            padding-bottom: 3px;
            transition: opacity 0.2s ease;
        }

        .nav-item:hover {
            opacity: 0.85;
        }

        .nav-item.active {
            border-bottom: 2.5px solid #ffffff;
        }

        /* --- Page Grid Wrapper --- */
        .checkout-view-wrapper {
            flex: 1;
            width: 100%;
            max-width: 1240px;
            margin: 0 auto;
            padding: 30px 24px 50px;
        }

        .checkout-heading {
            font-family: 'Montserrat', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: #e65275;
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
        }

        .checkout-heading i {
            font-size: 30px;
        }

        .error-banner {
            background-color: #ffe8ec;
            color: #d13d60;
            border: 1.5px solid #f7b4c4;
            padding: 12px 18px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .error-banner ul {
            margin: 0;
            padding-left: 18px;
        }

        .checkout-layout-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 26px;
            align-items: start;
        }

        /* --- Card Containers --- */
        .white-card-section {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(4px);
            border-radius: 26px;
            padding: 24px 28px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            border: 1px solid #fde4ec;
            margin-bottom: 22px;
        }

        .section-header-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: #e65275;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .section-header-title i {
            font-size: 18px;
        }

        /* Two-Column Form Layout */
        .form-grid-two-col {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px 20px;
        }

        .input-block {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .input-block label {
            font-size: 12.5px;
            font-weight: 700;
            color: #4a3431;
        }

        .input-block input, 
        .input-block textarea {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid #f3d1db;
            border-radius: 14px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            outline: none;
            background: #ffffff;
            color: #4a3431;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .input-block textarea {
            resize: vertical;
            min-height: 48px;
        }

        .input-block input:focus, 
        .input-block textarea:focus {
            border-color: #f76e8e;
            box-shadow: 0 0 0 3px rgba(247, 110, 142, 0.12);
        }

        /* --- Payment Options Grid --- */
        .payment-options-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .payment-choice-card {
            border: 2px solid #f3d1db;
            border-radius: 16px;
            padding: 16px 14px;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .payment-choice-card input[type="radio"] {
            accent-color: #f76e8e;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .payment-choice-card.active {
            border-color: #f76e8e;
            background: #fff8f9;
        }

        .pay-icon-badge {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: #ffffff;
            flex-shrink: 0;
        }

        .pay-icon-cod {
            background-color: #27ae60;
        }

        .pay-icon-gcash {
            background-color: #007dfe;
            font-weight: 800;
            font-size: 13px;
        }

        .payment-choice-details strong {
            display: block;
            font-size: 13.5px;
            color: #433935;
            font-weight: 700;
        }

        .payment-choice-details span {
            font-size: 11.5px;
            color: #795548;
        }

        .gcash-info-box {
            display: none;
            background: #eef6ff;
            border: 1.5px solid #bddbff;
            border-radius: 14px;
            padding: 14px;
            margin-top: 14px;
        }

        .gcash-info-box p {
            font-size: 12.5px;
            color: #1e3a8a;
            margin-bottom: 8px;
            line-height: 1.45;
        }

        /* Place Order Pill Button */
        .btn-place-order-pill {
            background-color: #f76e8e;
            color: #ffffff;
            border: none;
            border-radius: 14px;
            padding: 14px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            font-weight: 800;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(247, 110, 142, 0.3);
            transition: background-color 0.2s ease, transform 0.15s ease;
        }

        .btn-place-order-pill:hover {
            background-color: #e55a7b;
            transform: translateY(-1px);
        }

        /* --- Right Column: Order Summary --- */
        .order-summary-sidebar {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(4px);
            border-radius: 26px;
            padding: 26px 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            border: 1px solid #fde4ec;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .summary-items-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
            max-height: 380px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .summary-item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding-bottom: 12px;
            border-bottom: 1px solid #fde4ec;
        }

        .summary-item-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .summary-item-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .item-thumb-box {
            width: 58px;
            height: 58px;
            border-radius: 12px;
            background: #fff4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
            border: 1px solid #fce3ea;
        }

        .item-thumb-box img {
            width: 85%;
            height: 85%;
            object-fit: contain;
        }

        .item-meta-titles h4 {
            font-size: 13.5px;
            font-weight: 700;
            color: #433935;
            line-height: 1.25;
            margin-bottom: 2px;
        }

        .item-meta-titles span {
            font-size: 11px;
            color: #8c7b74;
            display: block;
            margin-bottom: 6px;
        }

        .pill-stepper {
            display: inline-flex;
            align-items: center;
            border: 1.5px solid #f9b8c6;
            border-radius: 18px;
            padding: 2px 8px;
            background: #ffffff;
            gap: 8px;
        }

        .pill-stepper-btn {
            background: transparent;
            border: none;
            color: #e65275;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            padding: 0 2px;
        }

        .pill-stepper-btn:hover {
            transform: scale(1.2);
        }

        .pill-stepper-val {
            font-size: 12px;
            font-weight: 700;
            color: #433935;
            min-width: 12px;
            text-align: center;
        }

        .summary-item-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-item-price {
            font-family: 'Montserrat', sans-serif;
            font-size: 13.5px;
            font-weight: 700;
            color: #433935;
            white-space: nowrap;
        }

        .trash-action-btn {
            background: transparent;
            border: none;
            color: #e65275;
            font-size: 14px;
            cursor: pointer;
            transition: color 0.15s ease, transform 0.15s ease;
        }

        .trash-action-btn:hover {
            color: #d13d60;
            transform: scale(1.15);
        }

        /* Totals Block */
        .summary-bill-block {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding-top: 10px;
            border-top: 1px solid #fde4ec;
            font-size: 13.5px;
        }

        .bill-line {
            display: flex;
            justify-content: space-between;
            color: #4a3431;
            font-weight: 600;
        }

        .bill-line .val {
            font-weight: 700;
            color: #4a3431;
        }

        .total-highlight-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 6px;
            margin-top: 4px;
            border-top: 1px dashed #fcd5df;
        }

        .total-highlight-row .label {
            font-family: 'Montserrat', sans-serif;
            font-size: 15px;
            font-weight: 800;
            color: #4a3431;
        }

        .total-highlight-row .amount {
            font-family: 'Montserrat', sans-serif;
            font-size: 20px;
            font-weight: 900;
            color: #e65275;
        }

        .warranty-note {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 11px;
            color: #795548;
            line-height: 1.4;
            margin-top: 2px;
        }

        .warranty-note i {
            color: #e65275;
            font-size: 13px;
            margin-top: 2px;
        }

        .warranty-note a {
            color: #e65275;
            text-decoration: none;
            font-weight: 700;
        }

        .footer-bar {
            height: 70px;
            background-color: #f77290;
            width: 100%;
            flex-shrink: 0;
            margin-top: auto;
        }

        @media (max-width: 960px) {
            .checkout-layout-grid {
                grid-template-columns: 1fr;
            }
            .form-grid-two-col {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Header matching reference navbar design -->
    <header class="navbar">
        <a href="Index.php#home" class="brand-logo-nav">
            <img src="images/cheesecakeLogo.png" alt="Logo">
            <span class="nav-brand-name">Cheesecake Delight</span>
        </a>
        <nav class="nav-links">
            <a href="Index.php#home" class="nav-item">HOME</a>
            <a href="Index.php#menu" class="nav-item">MENU</a>
            <a href="cart.php" class="nav-item active">CART</a>
        </nav>
    </header>

    <main class="checkout-view-wrapper">

        <?php if ($success): ?>
            <div class="white-card-section" style="max-width: 600px; margin: 40px auto; text-align: center; padding: 50px 30px;">
                <i class="fa-solid fa-circle-check" style="font-size: 55px; color: #2ecc71; margin-bottom: 15px;"></i>
                <h2 style="font-family: 'Montserrat', sans-serif; color: #e65275; font-size: 24px; margin-bottom: 8px;">Order Placed Successfully!</h2>
                <p style="font-size: 13.5px; color: #6d6260; margin-bottom: 22px;">Thank you for ordering with Cheesecake Delight. Your order has been scheduled for preparation.</p>
                <a href="Index.php" class="btn-place-order-pill" style="display: inline-flex; width: auto; padding: 12px 28px; text-decoration: none;">Back to Storefront</a>
            </div>
        <?php else: ?>

            <h1 class="checkout-heading">
                <i class="fa-solid fa-cart-shopping"></i> Order Checkout
            </h1>

            <?php if (!empty($errors)): ?>
                <div class="error-banner">
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="checkout.php" id="checkoutForm">
                <div class="checkout-layout-grid">

                    <!-- LEFT COLUMN: Delivery Details & Payment Selection -->
                    <div class="checkout-main-content">

                        <!-- Delivery Address Card -->
                        <div class="white-card-section">
                            <h2 class="section-header-title">
                                <i class="fa-solid fa-location-dot"></i> Delivery Address
                            </h2>

                            <div class="form-grid-two-col">
                                <div class="input-block">
                                    <label for="fullname">Full Name</label>
                                    <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" placeholder="Full Name" required>
                                </div>

                                <div class="input-block">
                                    <label for="email">Email Address</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>" placeholder="Email Address" required>
                                </div>

                                <div class="input-block">
                                    <label for="phone">Contact Number</label>
                                    <input type="tel" id="phone" name="phone" placeholder="09xxxxxxxxx" required>
                                </div>

                                <div class="input-block">
                                    <label for="address">House number, Street, Barangay, City</label>
                                    <input type="text" id="address" name="address" placeholder="123 Sunshine St., Barangay, City" required>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method Card -->
                        <div class="white-card-section">
                            <h2 class="section-header-title">
                                <i class="fa-regular fa-credit-card"></i> Payment Method
                            </h2>

                            <div class="payment-options-grid">
                                <label class="payment-choice-card active" id="label-cod">
                                    <input type="radio" name="payment_method" value="Cash on Delivery" checked onclick="togglePayment('cod')">
                                    <div class="pay-icon-badge pay-icon-cod">
                                        <i class="fa-solid fa-money-bill-wave"></i>
                                    </div>
                                    <div class="payment-choice-details">
                                        <strong>Cash on Delivery</strong>
                                        <span>Pay with cash upon arrival</span>
                                    </div>
                                </label>

                                <label class="payment-choice-card" id="label-gcash">
                                    <input type="radio" name="payment_method" value="GCash" onclick="togglePayment('gcash')">
                                    <div class="pay-icon-badge pay-icon-gcash">G</div>
                                    <div class="payment-choice-details">
                                        <strong>GCash</strong>
                                        <span>Fast e-wallet transfer</span>
                                    </div>
                                </label>
                            </div>

                            <!-- GCash Account Details (Toggles on select) -->
                            <div id="gcashBox" class="gcash-info-box">
                                <p>
                                    <strong>GCash Account:</strong> 0967 534 5725<br>
                                    <strong>Account Name:</strong> Cheesecake Delight
                                </p>
                                <div class="input-block" style="margin-bottom: 0;">
                                    <label for="gcash_reference" style="color: #0d47a1;">GCash Reference Number</label>
                                    <input type="text" id="gcash_reference" name="gcash_reference" placeholder="e.g. 100234567890">
                                </div>
                            </div>
                        </div>

                        <!-- Place Order Submit Button -->
                        <button type="submit" name="place_order" class="btn-place-order-pill">
                            <i class="fa-regular fa-credit-card"></i> Place Order Now
                        </button>

                    </div>

                    <!-- RIGHT COLUMN: Order Summary -->
                    <div class="order-summary-sidebar">
                        <h3 class="section-header-title" style="margin-bottom: 10px;">
                            <i class="fa-solid fa-bag-shopping"></i> Order Summary
                        </h3>

                        <!-- Item Rows -->
                        <div class="summary-items-list">
                            <?php foreach ($_SESSION['cart'] as $pid => $item): 
                                $line_total = $item['price'] * $item['qty'];
                            ?>
                                <div class="summary-item-row">
                                    <div class="summary-item-left">
                                        <div class="item-thumb-box">
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        </div>
                                        <div class="item-meta-titles">
                                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                            <span>Set : Fresh Slice</span>
                                            
                                            <!-- Stepper (+ 1 -) -->
                                            <div class="pill-stepper">
                                                <button type="submit" form="stepper-dec-<?php echo $pid; ?>" class="pill-stepper-btn">&minus;</button>
                                                <span class="pill-stepper-val"><?php echo $item['qty']; ?></span>
                                                <button type="submit" form="stepper-inc-<?php echo $pid; ?>" class="pill-stepper-btn">&plus;</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="summary-item-right">
                                        <span class="summary-item-price">&#8369;<?php echo number_format($line_total, 2); ?></span>
                                        <button type="submit" form="remove-form-<?php echo $pid; ?>" class="trash-action-btn" title="Remove item">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Bill Breakdown -->
                        <div class="summary-bill-block">
                            <div class="bill-line">
                                <span>Subtotal</span>
                                <span class="val">&#8369;<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="bill-line">
                                <span>Shipping Fee</span>
                                <span class="val">&#8369;<?php echo number_format($shipping_fee, 2); ?></span>
                            </div>
                            <div class="total-highlight-row">
                                <span class="label">Total</span>
                                <span class="amount">&#8369;<?php echo number_format($grand_total, 2); ?></span>
                            </div>
                        </div>

                        <div class="warranty-note">
                            <i class="fa-regular fa-circle-check"></i>
                            <span>Freshness &amp; Quality Guarantee on all deliveries. <a href="#">Details</a></span>
                        </div>

                    </div>

                </div>
            </form>

            <!-- Auxiliary forms for item quantity modification directly on checkout -->
            <?php foreach ($_SESSION['cart'] as $pid => $item): ?>
                <form id="stepper-dec-<?php echo $pid; ?>" method="POST" action="checkout.php" style="display:none;">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                    <input type="hidden" name="op" value="dec">
                </form>
                <form id="stepper-inc-<?php echo $pid; ?>" method="POST" action="checkout.php" style="display:none;">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                    <input type="hidden" name="op" value="inc">
                </form>
                <form id="remove-form-<?php echo $pid; ?>" method="POST" action="checkout.php" style="display:none;">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                </form>
            <?php endforeach; ?>

        <?php endif; ?>

    </main>

    <div class="footer-bar"></div>

    <script>
        function togglePayment(type) {
            const labelCod = document.getElementById('label-cod');
            const labelGcash = document.getElementById('label-gcash');
            const gcashBox = document.getElementById('gcashBox');

            if (type === 'gcash') {
                labelGcash.classList.add('active');
                labelCod.classList.remove('active');
                gcashBox.style.display = 'block';
            } else {
                labelCod.classList.add('active');
                labelGcash.classList.remove('active');
                gcashBox.style.display = 'none';
            }
        }
    </script>
</body>
</html>