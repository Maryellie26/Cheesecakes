<?php
session_start();
require_once 'db.php';

// Ensure payment_method column exists in orders table
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) NOT NULL DEFAULT 'Cash on Delivery'");

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

        // Database Transaction: Safely verifies stock and commits permanent deduction
        $conn->begin_transaction();

        try {
            foreach ($_SESSION['cart'] as $pid => $item) {
                $qty = (int)$item['qty'];

                // Lock row for safe concurrent check
                $check_stmt = $conn->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
                $check_stmt->bind_param("i", $pid);
                $check_stmt->execute();
                $res = $check_stmt->get_result();
                $prod = $res->fetch_assoc();
                $check_stmt->close();

                if (!$prod || (int)$prod['stock'] < $qty) {
                    throw new Exception("Sorry, " . $item['name'] . " does not have enough stock remaining.");
                }

                // Deduct stock permanently from database
                $update_stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $update_stmt->bind_param("ii", $qty, $pid);
                $update_stmt->execute();
                $update_stmt->close();
            }

            // Insert into orders table with payment_method
            $stmt = $conn->prepare("INSERT INTO orders (user_id, customer_name, email, phone, address, subtotal, shipping_fee, total_amount, order_items, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssdddss", $user_id, $fullname, $email, $phone, $address, $subtotal, $shipping_fee, $grand_total, $order_items_json, $final_payment);
            $stmt->execute();
            $stmt->close();

            // Commit all changes safely
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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Playfair+Display:ital,wght@0,700;1,700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .payment-options-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 8px;
            margin-bottom: 12px;
        }

        .payment-choice-card {
            border: 2px solid #f3d1db;
            border-radius: 14px;
            padding: 14px;
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
        }

        .payment-choice-card:hover {
            border-color: #f76e8e;
            background: #fff8f9;
        }

        .payment-choice-card.active {
            border-color: #f76e8e;
            background: #fff3f6;
        }

        .payment-choice-details strong {
            display: block;
            font-size: 13.5px;
            color: #433935;
        }

        .payment-choice-details span {
            font-size: 11.5px;
            color: #795548;
        }

        .gcash-info-box {
            display: none;
            background: #eef6ff;
            border: 1.5px solid #bddbff;
            border-radius: 12px;
            padding: 14px;
            margin-top: 6px;
            margin-bottom: 15px;
        }

        .gcash-info-box p {
            font-size: 12.5px;
            color: #1e3a8a;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .gcash-info-box strong {
            color: #0d47a1;
        }
    </style>
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

                        <h2 style="margin-top: 25px;">Payment Method</h2>
                        <div class="payment-options-grid">
                            <label class="payment-choice-card active" id="label-cod">
                                <input type="radio" name="payment_method" value="Cash on Delivery" checked onclick="togglePaymentMethod('cod')">
                                <div class="payment-choice-details">
                                    <strong><i class="fa-solid fa-money-bill-wave" style="color: #27ae60;"></i> Cash on Delivery</strong>
                                    <span>Pay with cash upon arrival</span>
                                </div>
                            </label>

                            <label class="payment-choice-card" id="label-gcash">
                                <input type="radio" name="payment_method" value="GCash" onclick="togglePaymentMethod('gcash')">
                                <div class="payment-choice-details">
                                    <strong><i class="fa-solid fa-mobile-screen-button" style="color: #007dfe;"></i> GCash</strong>
                                    <span>Fast e-wallet transfer</span>
                                </div>
                            </label>
                        </div>

                        <!-- GCash Transfer Info Box -->
                        <div id="gcashBox" class="gcash-info-box">
                            <p>
                                <strong>GCash Account:</strong> 0967 534 5725<br>
                                <strong>Account Name:</strong> Cheesecake Delight
                            </p>
                            <div class="input-block" style="margin-bottom: 0;">
                                <label for="gcash_reference" style="color: #0d47a1;">GCash Reference Number (Optional / Upon Sending)</label>
                                <input type="text" id="gcash_reference" name="gcash_reference" placeholder="e.g. 100234567890">
                            </div>
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

    <script>
        function togglePaymentMethod(method) {
            const gcashBox = document.getElementById('gcashBox');
            const labelCod = document.getElementById('label-cod');
            const labelGcash = document.getElementById('label-gcash');

            if (method === 'gcash') {
                gcashBox.style.display = 'block';
                labelGcash.classList.add('active');
                labelCod.classList.remove('active');
            } else {
                gcashBox.style.display = 'none';
                labelCod.classList.add('active');
                labelGcash.classList.remove('active');
            }
        }
    </script>
</body>
</html>