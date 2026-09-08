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

// Fetch all products from DB for the "Stock Available" summary panel
$all_products = [];
$prod_res = $conn->query("SELECT id, name, stock FROM products ORDER BY id ASC");
if ($prod_res) {
    while ($row = $prod_res->fetch_assoc()) {
        $all_products[] = $row;
    }
}

// Computations
$subtotal = 0;
$total_cart_items = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['qty'];
    $total_cart_items += (int)$item['qty'];
}

$shipping_fee = 0.00;
$grand_total  = $subtotal + $shipping_fee;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Cheesecake Delight</title>
    
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
            font-family: 'Poppins', 'Montserrat', sans-serif;
            color: #332d2c;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-bottom: 50px;
        }

        /* --- Sticky Navigation Bar --- */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background-color: #f77290;
            width: 100%;
            padding: 18px 48px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 35px;
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
            width: 42px;
            height: 42px;
            object-fit: contain;
        }

        .brand-logo-nav .nav-brand-name {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 22px;
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
            transition: opacity 0.2s ease;
        }

        .nav-item:hover {
            opacity: 0.85;
        }

        /* --- Center Mobile-Card Container --- */
        .cart-app-container {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 28px;
            padding: 24px 22px;
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.08);
            border: 1px solid #fde4ec;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* Top Header */
        .cart-app-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 12px;
        }

        .cart-header-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 25px;
            font-weight: 800;
            color: #f14e77;
            font-family: 'Montserrat', sans-serif;
        }

        .cart-header-title i {
            font-size: 24px;
        }

        .cart-item-count-badge {
            width: 32px;
            height: 32px;
            background-color: #ef476f;
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 800;
        }

        /* Item Row Cards */
        .cart-items-wrapper {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .cart-product-card {
            background-color: #fffdf7;
            border: 1px solid #f9e2e8;
            border-radius: 20px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 14px;
            position: relative;
        }

        .product-thumb-box {
            width: 82px;
            height: 82px;
            border-radius: 16px;
            background-color: #fceeed;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }

        .product-thumb-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .product-card-details {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .product-card-title {
            font-size: 14px;
            font-weight: 700;
            color: #4a3431;
            margin-right: 24px;
        }

        .product-card-price {
            font-size: 14px;
            font-weight: 800;
            color: #ff5983;
        }

        .product-card-stock {
            font-size: 11.5px;
            font-weight: 700;
            color: #27ae60;
            display: flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 4px;
        }

        /* Stepper Box [- 1 +] */
        .card-stepper-box {
            display: inline-flex;
            align-items: center;
            background: #ffffff;
            border: 1.5px solid #ff709b;
            border-radius: 6px;
            height: 25px;
            overflow: hidden;
            width: fit-content;
        }

        .card-stepper-btn {
            background-color: #ff709b;
            color: #ffffff;
            border: none;
            width: 24px;
            height: 100%;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
            transition: background-color 0.15s ease;
        }

        .card-stepper-btn:hover {
            background-color: #e8507c;
        }

        .card-stepper-val {
            width: 28px;
            text-align: center;
            font-weight: 700;
            font-size: 13px;
            color: #4a3431;
        }

        /* Trash button */
        .btn-trash-item {
            position: absolute;
            top: 14px;
            right: 14px;
            background: transparent;
            border: none;
            color: #e8507c;
            font-size: 15px;
            cursor: pointer;
            transition: transform 0.15s ease;
        }

        .btn-trash-item:hover {
            transform: scale(1.15);
        }

        /* Divider */
        .bill-divider {
            height: 1px;
            background-color: #f7e1e6;
            margin: 6px 0;
        }

        /* Bill Summary Lines */
        .bill-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13.5px;
            font-weight: 700;
            color: #4a3431;
        }

        .bill-line.total-line {
            background-color: #fff0f4;
            padding: 10px 14px;
            border-radius: 12px;
            color: #e63968;
            font-size: 15px;
            font-weight: 900;
        }

        /* Stock Available Card */
        .stock-summary-card {
            background-color: #fff0f4;
            border-radius: 16px;
            padding: 14px 18px;
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .stock-summary-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13.5px;
            font-weight: 800;
            color: #e63968;
            margin-bottom: 2px;
        }

        .stock-summary-header img {
            width: 18px;
            height: 18px;
        }

        .stock-row {
            display: flex;
            justify-content: space-between;
            font-size: 12.5px;
            font-weight: 600;
            color: #5d4037;
        }

        /* Checkout Action Button */
        .btn-proceed-pill {
            background-color: #f14e77;
            color: #ffffff;
            border: none;
            border-radius: 14px;
            padding: 14px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14.5px;
            font-weight: 800;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(241, 78, 119, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background-color 0.2s ease, transform 0.15s ease;
        }

        .btn-proceed-pill:hover {
            background-color: #de3c65;
            transform: translateY(-1px);
        }

        /* Bottom Info Notice Box */
        .cart-info-notice {
            background-color: #fff0f4;
            border-radius: 14px;
            padding: 12px 14px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 11.5px;
            font-weight: 600;
            color: #8c5b66;
            line-height: 1.45;
        }

        .notice-icon-circle {
            width: 20px;
            height: 20px;
            background-color: #f14e77;
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 800;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .empty-cart-text {
            text-align: center;
            padding: 30px 10px;
            color: #795548;
            font-weight: 600;
            font-size: 14px;
        }

        .cart-error-banner {
            background-color: #ffe8ec;
            color: #d13d60;
            border: 1.5px solid #f7b4c4;
            padding: 10px 14px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 12px;
            margin-bottom: 6px;
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
            <a href="cart.php" class="nav-item" style="border-bottom: 2px solid #ffffff;">CART</a>
        </nav>
    </header>

    <main class="cart-app-container">

        <!-- Header -->
        <div class="cart-app-header">
            <div class="cart-header-title">
                <i class="fa-solid fa-cart-shopping"></i>
                <span>Your Cart</span>
            </div>
            <div class="cart-item-count-badge">
                <?php echo $total_cart_items; ?>
            </div>
        </div>

        <?php if (isset($_SESSION['cart_error'])): ?>
            <div class="cart-error-banner">
                <?php echo htmlspecialchars($_SESSION['cart_error']); ?>
            </div>
            <?php unset($_SESSION['cart_error']); ?>
        <?php endif; ?>

        <!-- Items List -->
        <div class="cart-items-wrapper">
            <?php if (empty($_SESSION['cart'])): ?>
                <div class="empty-cart-text">
                    <p>Your cart is currently empty!</p>
                    <a href="Index.php#menu" style="color: #f14e77; font-weight: 700; text-decoration: none; display: inline-block; margin-top: 8px;">Explore Our Menu &rarr;</a>
                </div>
            <?php else: ?>
                <?php foreach ($_SESSION['cart'] as $item): 
                    $prod_info = getProductStock($conn, $item['id']);
                    $db_stock = $prod_info ? (int)$prod_info['stock'] : 0;
                    $remaining_stock = max(0, $db_stock - (int)$item['qty']);
                ?>
                    <div class="cart-product-card">
                        <div class="product-thumb-box">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        </div>

                        <div class="product-card-details">
                            <h3 class="product-card-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <span class="product-card-price">&#8369;<?php echo number_format($item['price'], 2); ?></span>
                            <span class="product-card-stock">&#9679; <?php echo $remaining_stock; ?> in stock</span>

                            <div class="card-stepper-box">
                                <form method="POST" action="cart.php" style="display:inline;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="op" value="dec">
                                    <button type="submit" class="card-stepper-btn">&minus;</button>
                                </form>

                                <span class="card-stepper-val"><?php echo $item['qty']; ?></span>

                                <form method="POST" action="cart.php" style="display:inline;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="op" value="inc">
                                    <button type="submit" class="card-stepper-btn">&plus;</button>
                                </form>
                            </div>
                        </div>

                        <!-- Trash Icon -->
                        <form method="POST" action="cart.php">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="btn-trash-item" title="Remove item">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="bill-divider"></div>

        <!-- Bill Totals -->
        <div class="bill-line">
            <span>Subtotal</span>
            <span>&#8369;<?php echo number_format($subtotal, 2); ?></span>
        </div>

        <div class="bill-line">
            <span>Shipping</span>
            <span>&#8369;<?php echo number_format($shipping_fee, 2); ?></span>
        </div>

        <div class="bill-line total-line">
            <span>Total</span>
            <span>&#8369;<?php echo number_format($grand_total, 2); ?></span>
        </div>

        <!-- Stock Available Summary Panel -->
        <div class="stock-summary-card">
            <div class="stock-summary-header">
                <span>📦</span>
                <span>Stock Available</span>
            </div>
            <?php foreach ($all_products as $p): 
                $in_cart = isset($_SESSION['cart'][$p['id']]) ? (int)$_SESSION['cart'][$p['id']]['qty'] : 0;
                $avail = max(0, (int)$p['stock'] - $in_cart);
                // Extract single-word name (e.g. "Strawberry Cheesecake" -> "Strawberry")
                $short_name = explode(' ', $p['name'])[0];
            ?>
                <div class="stock-row">
                    <span><?php echo htmlspecialchars($short_name); ?></span>
                    <span><?php echo $avail; ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Proceed to Checkout Button -->
        <?php if (isset($_SESSION['user_email'])): ?>
            <a href="checkout.php" class="btn-proceed-pill">
                <i class="fa-solid fa-cart-shopping"></i> Proceed to Checkout
            </a>
        <?php else: ?>
            <a href="login.php" class="btn-proceed-pill">
                <i class="fa-solid fa-cart-shopping"></i> Sign In to Checkout
            </a>
        <?php endif; ?>

        <!-- Bottom Notice Box -->
        <div class="cart-info-notice">
            <div class="notice-icon-circle">i</div>
            <div>
                When you add a product to your cart, the stock will decrease automatically. Update the stock in the admin panel to increase the available quantity.
            </div>
        </div>

    </main>

</body>
</html>