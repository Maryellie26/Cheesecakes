<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Function to fetch database stock for validation
function getProductStock($conn, $pid) {
    $stmt = $conn->prepare("SELECT id, name, price, image, stock FROM products WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $pid);
        $stmt->execute();
        $res = $stmt->get_result();
        $prod = $res->fetch_assoc();
        $stmt->close();
        return $prod;
    }
    return null;
}

// Handle Form Posts
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        if (!isset($_SESSION['user_email'])) {
            header("Location: login.php");
            exit;
        }

        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);
        $product = getProductStock($conn, $pid);

        if ($product && $qty > 0) {
            $current_in_cart = isset($_SESSION['cart'][$pid]) ? (int)$_SESSION['cart'][$pid]['qty'] : 0;
            $new_total = $current_in_cart + $qty;

            if ($new_total <= (int)$product['stock']) {
                if (isset($_SESSION['cart'][$pid])) {
                    $_SESSION['cart'][$pid]['qty'] = $new_total;
                } else {
                    $_SESSION['cart'][$pid] = [
                        'id'    => $pid,
                        'name'  => $product['name'],
                        'price' => (float)$product['price'],
                        'image' => $product['image'],
                        'qty'   => $qty
                    ];
                }
            } else {
                $_SESSION['cart_error'] = "Cannot add more than available stock for " . htmlspecialchars($product['name']);
            }
        }
        header("Location: Index.php#menu");
        exit;
    }

    if ($action === 'update') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $op  = $_POST['op'] ?? '';
        $product = getProductStock($conn, $pid);

        if (isset($_SESSION['cart'][$pid]) && $product) {
            if ($op === 'inc') {
                if ($_SESSION['cart'][$pid]['qty'] < (int)$product['stock']) {
                    $_SESSION['cart'][$pid]['qty']++;
                } else {
                    $_SESSION['cart_error'] = "Maximum stock reached for " . htmlspecialchars($product['name']);
                }
            } elseif ($op === 'dec') {
                $_SESSION['cart'][$pid]['qty']--;
                if ($_SESSION['cart'][$pid]['qty'] <= 0) {
                    unset($_SESSION['cart'][$pid]);
                }
            }
        }
        header("Location: cart.php");
        exit;
    }

    if ($action === 'remove') {
        $pid = (int)($_POST['product_id'] ?? 0);
        unset($_SESSION['cart'][$pid]);
        header("Location: cart.php");
        exit;
    }
}

// Computations
$subtotal = 0;
$total_cart_items = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['qty'];
    $total_cart_items += (int)$item['qty'];
}

$shipping_fee = 50.00;
$grand_total  = $subtotal > 0 ? ($subtotal + $shipping_fee) : 0.00;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Cheesecake Delight</title>
    
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
            background-image: url('images/cakebg.png');
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

        /* --- Navbar --- */
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

        /* --- Layout Grid --- */
        .cart-view-wrapper {
            flex: 1;
            width: 100%;
            max-width: 1240px;
            margin: 0 auto;
            padding: 30px 24px 50px;
        }

        .cart-page-heading {
            font-family: 'Montserrat', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: #e65275;
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
        }

        .cart-error-banner {
            background-color: #ffe8ec;
            color: #d13d60;
            border: 1.5px solid #f7b4c4;
            padding: 12px 18px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 18px;
        }

        .cart-layout-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 26px;
            align-items: start;
        }

        /* --- Left Side: Product Table Card --- */
        .cart-table-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(4px);
            border-radius: 26px;
            padding: 24px 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            border: 1px solid #fde4ec;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cart-table th {
            font-family: 'Montserrat', sans-serif;
            font-size: 13.5px;
            font-weight: 700;
            color: #e65275;
            text-align: left;
            padding-bottom: 20px;
            border-bottom: 1px solid #fde4ec;
        }

        .cart-table td {
            padding: 20px 0;
            border-bottom: 1px solid #fde4ec;
            vertical-align: middle;
        }

        .cart-table tr:last-child td {
            border-bottom: none;
        }

        .product-col {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .item-img-card {
            width: 72px;
            height: 72px;
            border-radius: 14px;
            background: #fff4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
            border: 1px solid #fce3ea;
        }

        .item-img-card img {
            width: 85%;
            height: 85%;
            object-fit: contain;
        }

        .item-title-meta {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .item-title-meta h4 {
            font-size: 15px;
            font-weight: 700;
            color: #e65275;
            margin-bottom: 1px;
        }

        .item-meta-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
        }

        .item-unit-price {
            color: #ff5983;
            font-weight: 700;
        }

        .item-meta-stock {
            font-size: 11.5px;
            font-weight: 700;
            color: #27ae60;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .item-meta-stock.low-stock {
            color: #e67e22;
        }

        .item-meta-stock.out-of-stock {
            color: #e74c3c;
        }

        /* Pill Stepper: (+ 4 -) */
        .pill-stepper {
            display: inline-flex;
            align-items: center;
            border: 1.5px solid #f9b8c6;
            border-radius: 20px;
            padding: 3px 10px;
            background: #ffffff;
            gap: 10px;
        }

        .pill-stepper-btn {
            background: transparent;
            border: none;
            color: #e65275;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            padding: 0 4px;
            transition: transform 0.15s ease;
        }

        .pill-stepper-btn:hover {
            transform: scale(1.25);
        }

        .pill-stepper-val {
            font-size: 13px;
            font-weight: 700;
            color: #433935;
            min-width: 14px;
            text-align: center;
        }

        .price-text {
            font-family: 'Montserrat', sans-serif;
            font-size: 16px;
            font-weight: 800;
            color: #e65275;
        }

        .trash-action-btn {
            background: transparent;
            border: none;
            color: #e65275;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.15s ease, color 0.15s ease;
        }

        .trash-action-btn:hover {
            transform: scale(1.15);
            color: #d13d60;
        }

        /* --- Right Side: Order Summary Card --- */
        .order-summary-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(4px);
            border-radius: 26px;
            padding: 26px 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            border: 1px solid #fde4ec;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .order-summary-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: #e65275;
        }

        .summary-details {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 6px 0;
            font-size: 13.5px;
        }

        .sum-line {
            display: flex;
            justify-content: space-between;
            color: #4a3431;
            font-weight: 600;
        }

        .sum-line .val {
            font-weight: 700;
            color: #4a3431;
        }

        .sum-divider {
            height: 1px;
            background: #fde4ec;
            margin: 4px 0;
        }

        .total-highlight-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 4px;
        }

        .total-highlight-row .label {
            font-family: 'Montserrat', sans-serif;
            font-size: 14.5px;
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
            font-size: 11.5px;
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

        .btn-checkout-now {
            background-color: #f76e8e;
            color: #ffffff;
            border: none;
            border-radius: 24px;
            padding: 13px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            font-weight: 800;
            text-align: center;
            text-decoration: none;
            display: block;
            width: 100%;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(247, 110, 142, 0.3);
            transition: background-color 0.2s ease, transform 0.15s ease;
        }

        .btn-checkout-now:hover {
            background-color: #e55a7b;
            transform: translateY(-1px);
        }

        .footer-bar {
            height: 70px;
            background-color: #f77290;
            width: 100%;
            flex-shrink: 0;
            margin-top: auto;
        }

        @media (max-width: 960px) {
            .cart-layout-grid {
                grid-template-columns: 1fr;
            }
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
            <a href="cart.php" class="nav-item active">CART</a>
        </nav>
    </header>

    <main class="cart-view-wrapper">

        <h1 class="cart-page-heading">
            <i class="fa-solid fa-cart-shopping"></i> Shopping Cart
        </h1>

        <?php if (isset($_SESSION['cart_error'])): ?>
            <div class="cart-error-banner">
                <?php echo htmlspecialchars($_SESSION['cart_error']); ?>
            </div>
            <?php unset($_SESSION['cart_error']); ?>
        <?php endif; ?>

        <div class="cart-layout-grid">

            <!-- LEFT: Products Table -->
            <div class="cart-table-card">
                <?php if (empty($_SESSION['cart'])): ?>
                    <div style="text-align: center; padding: 50px 20px; color: #795548;">
                        <p style="font-size: 15px; font-weight: 600; margin-bottom: 12px;">Your shopping cart is currently empty.</p>
                        <a href="Index.php#menu" style="color: #e65275; font-weight: 800; text-decoration: none;">&larr; Return to Menu</a>
                    </div>
                <?php else: ?>
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th style="width: 48%;">Product Code</th>
                                <th style="width: 24%; text-align: center;">Quantity</th>
                                <th style="width: 20%; text-align: right;">Total</th>
                                <th style="width: 8%; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['cart'] as $item): 
                                $item_total = (float)$item['price'] * (int)$item['qty'];
                                $prod_info = getProductStock($conn, $item['id']);
                                $db_stock = $prod_info ? (int)$prod_info['stock'] : 0;
                                $remaining_stock = max(0, $db_stock - (int)$item['qty']);
                            ?>
                                <tr>
                                    <td>
                                        <div class="product-col">
                                            <div class="item-img-card">
                                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            </div>
                                            <div class="item-title-meta">
                                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                                <div class="item-meta-info">
                                                    <span class="item-unit-price">&#8369;<?php echo number_format((float)$item['price'], 2); ?></span>
                                                    <span>&bull;</span>
                                                    <?php if ($remaining_stock > 3): ?>
                                                        <span class="item-meta-stock">&#9679; <?php echo $remaining_stock; ?> in stock</span>
                                                    <?php elseif ($remaining_stock > 0): ?>
                                                        <span class="item-meta-stock low-stock">&#9679; Only <?php echo $remaining_stock; ?> left</span>
                                                    <?php else: ?>
                                                        <span class="item-meta-stock out-of-stock">&#9679; Out of stock</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="pill-stepper">
                                            <form method="POST" action="cart.php" style="display:inline;">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="op" value="inc">
                                                <button type="submit" class="pill-stepper-btn">&plus;</button>
                                            </form>

                                            <span class="pill-stepper-val"><?php echo (int)$item['qty']; ?></span>

                                            <form method="POST" action="cart.php" style="display:inline;">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="op" value="dec">
                                                <button type="submit" class="pill-stepper-btn">&minus;</button>
                                            </form>
                                        </div>
                                    </td>
                                    <td style="text-align: right;">
                                        <span class="price-text">&#8369;<?php echo number_format($item_total, 2); ?></span>
                                    </td>
                                    <td style="text-align: right;">
                                        <form method="POST" action="cart.php" style="display:inline;">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="trash-action-btn" title="Remove item">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- RIGHT: Order Summary Card -->
            <div class="order-summary-card">
                <h3 class="order-summary-title">Order Summary</h3>

                <div class="summary-details">
                    <div class="sum-line">
                        <span>Sub Total</span>
                        <span class="val">&#8369;<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="sum-line">
                        <span>Discount (0%)</span>
                        <span class="val">-&#8369;0.00</span>
                    </div>
                    <div class="sum-line">
                        <span>Delivery fee</span>
                        <span class="val">&#8369;<?php echo number_format($shipping_fee, 2); ?></span>
                    </div>

                    <div class="sum-divider"></div>

                    <div class="total-highlight-row">
                        <span class="label">Total</span>
                        <span class="amount">&#8369;<?php echo number_format($grand_total, 2); ?></span>
                    </div>
                </div>

                <div class="warranty-note">
                    <i class="fa-regular fa-circle-check"></i>
                    <span>Freshness &amp; Quality Guarantee on all orders. <a href="#">Details</a></span>
                </div>

                <?php if (!empty($_SESSION['cart'])): ?>
                    <?php if (isset($_SESSION['user_email'])): ?>
                        <a href="checkout.php" class="btn-checkout-now">Checkout Now</a>
                    <?php else: ?>
                        <a href="login.php" class="btn-checkout-now">Sign In to Checkout</a>
                    <?php endif; ?>
                <?php else: ?>
                    <button class="btn-checkout-now" style="opacity: 0.6; cursor: not-allowed;" disabled>Checkout Now</button>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <div class="footer-bar"></div>

</body>
</html>