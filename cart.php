<?php
session_start();

$catalog = [
    1 => ["name" => "Strawberry Cheesecake", "price" => 180.00, "image" => "images/strawberry.png.png"],
    2 => ["name" => "Blueberry Cheesecake", "price" => 180.00, "image" => "images/blueberry.png.png"],
    3 => ["name" => "Chocolate Cheesecake", "price" => 180.00, "image" => "images/chocolate.png.png"],
    4 => ["name" => "Mango Cheesecake", "price" => 180.00, "image" => "images/mango.png.png"],
    5 => ["name" => "Oreo Cheesecake", "price" => 180.00, "image" => "images/oreo.png.png"],
    6 => ["name" => "Caramel Cheesecake", "price" => 180.00, "image" => "images/caramel.png.png"],
    7 => ["name" => "Matcha Cheesecake", "price" => 180.00, "image" => "images/matcha.png.png"],
    8 => ["name" => "Red Velvet Cheesecake", "price" => 180.00, "image" => "images/redvelvet.png"]
];

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Form Posts
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Handle single item added from menu
    if ($action === 'add') {
        // Require login before adding to cart / ordering
        if (!isset($_SESSION['user_email'])) {
            header("Location: login.php");
            exit;
        }

        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);

        if ($qty > 0 && isset($catalog[$pid])) {
            if (isset($_SESSION['cart'][$pid])) {
                $_SESSION['cart'][$pid]['qty'] += $qty;
            } else {
                $_SESSION['cart'][$pid] = [
                    'id'    => $pid,
                    'name'  => $catalog[$pid]['name'],
                    'price' => $catalog[$pid]['price'],
                    'image' => $catalog[$pid]['image'],
                    'qty'   => $qty
                ];
            }
        }
        header("Location: Index.php#menu");
        exit;
    }

    if ($action === 'update') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $op  = $_POST['op'] ?? '';

        if (isset($_SESSION['cart'][$pid])) {
            if ($op === 'inc') {
                $_SESSION['cart'][$pid]['qty']++;
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
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['qty'];
}

$shipping_fee = $subtotal > 0 ? 50.00 : 0.00;
$grand_total  = $subtotal + $shipping_fee;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Shopping Cart - Cheesecake Delight</title>
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
            <h1 class="cart-title">Your Order Summary</h1>

            <?php if (empty($_SESSION['cart'])): ?>
                <div class="cart-empty-box">
                    <i class="fa-solid fa-basket-shopping empty-icon"></i>
                    <h2>Your cart is currently empty!</h2>
                    <p>Head back to the menu and select your favorite slices.</p>
                    <a href="Index.php#menu" class="btn btn-primary" style="margin-top: 15px;">Explore Menu</a>
                </div>
            <?php else: ?>
                <div class="cart-content-grid">
                    <div class="cart-items-list">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <div class="cart-item-row">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-img">
                                <div class="cart-item-details">
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <span class="cart-item-single-price">&#8369;<?php echo number_format($item['price'], 2); ?> each</span>
                                    
                                    <div class="cart-quantity-row">
                                        <form method="POST" action="cart.php" style="display:inline;">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                            <input type="hidden" name="op" value="dec">
                                            <button type="submit" class="cart-qty-btn">-</button>
                                        </form>

                                        <span class="cart-qty-val"><?php echo $item['qty']; ?></span>

                                        <form method="POST" action="cart.php" style="display:inline;">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                            <input type="hidden" name="op" value="inc">
                                            <button type="submit" class="cart-qty-btn">+</button>
                                        </form>
                                    </div>
                                </div>

                                <div class="cart-item-total-col">
                                    <span class="item-line-total">&#8369;<?php echo number_format($item['price'] * $item['qty'], 2); ?></span>
                                    <form method="POST" action="cart.php">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn-remove-item" title="Remove Item"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="cart-summary-box">
                        <h2>Receipt</h2>
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
                            <span>Total</span>
                            <span>&#8369;<?php echo number_format($grand_total, 2); ?></span>
                        </div>

                        <?php if (isset($_SESSION['user_email'])): ?>
                            <a href="checkout.php" class="btn-proceed-checkout">Proceed to Checkout</a>
                        <?php else: ?>
                            <a href="login.php" class="btn-proceed-checkout">Sign In to Checkout</a>
                        <?php endif; ?>
                        
                        <a href="Index.php#menu" class="btn-continue-shopping">&larr; Continue Ordering</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div class="footer-bar"></div>
</body>
</html>