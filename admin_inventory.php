<?php
session_start();
require_once 'db.php';

// Security Guard: Only authenticated administrators can access
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// Handle Admin Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['is_admin']);
    unset($_SESSION['admin_user']);
    header("Location: admin_login.php");
    exit;
}

$message = '';
$error = '';

// Handle Stock Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $new_stock  = (int)($_POST['new_stock'] ?? 0);

    if ($new_stock < 0) {
        $error = "Stock cannot be negative.";
    } else {
        $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_stock, $product_id);
        if ($stmt->execute()) {
            $message = "Stock updated successfully!";
        } else {
            $error = "Failed to update stock.";
        }
        $stmt->close();
    }
}

// Fetch all products
$products = [];
$res = $conn->query("SELECT id, name, price, image, stock FROM products ORDER BY id ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $products[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Cheesecake Delight</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Playfair+Display:ital,wght@0,700;1,700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .admin-wrap {
            min-height: 88vh;
            padding: 50px 20px;
            display: flex;
            justify-content: center;
        }
        .admin-card {
            background: #fffdf5;
            max-width: 950px;
            width: 100%;
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            border: 1.5px solid #fce3ea;
        }
        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }
        .inventory-table th, .inventory-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #fce3ea;
            font-size: 14px;
        }
        .inventory-table th {
            color: #e65275;
            font-weight: 800;
            background: #fff5f7;
        }
        .stock-input {
            width: 80px;
            padding: 8px 12px;
            border: 1.5px solid #f3d1db;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            outline: none;
            font-family: 'Montserrat', sans-serif;
            transition: border-color 0.2s ease;
        }
        .stock-input:focus {
            border-color: #f76e8e;
        }
        .btn-update-stock {
            background-color: #ff709b;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.15s ease;
        }
        .btn-update-stock:hover {
            background-color: #e8507c;
            transform: translateY(-1px);
        }
        .item-thumb {
            width: 44px;
            height: 44px;
            object-fit: contain;
            vertical-align: middle;
            margin-right: 12px;
        }
        .stock-tag {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 700;
            margin-left: 6px;
        }
        .stock-tag.out {
            background-color: #fde8e8;
            color: #e74c3c;
        }
    </style>
</head>
<body>

    <header class="navbar">
        <a href="admin_dashboard.php" class="brand-logo-nav">
            <img src="images/cheesecakeLogo.png" alt="Logo">
            <span class="nav-brand-name">Cheesecake Delight</span>
        </a>
        <nav class="nav-links">
            <a href="admin_dashboard.php" class="nav-item">DASHBOARD</a>
            <a href="admin_inventory.php" class="nav-item" style="border-bottom: 2px solid #ffffff;">INVENTORY</a>
            <a href="admin_messages.php" class="nav-item">MESSAGES</a>
            <a href="Index.php" class="nav-item" target="_blank">STOREFRONT <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 11px;"></i></a>
            <a href="admin_inventory.php?logout=1" class="nav-item" style="color: #ffd6df;">LOGOUT ADMIN</a>
        </nav>
    </header>

    <main class="admin-wrap">
        <div class="admin-card">
            <h1 class="cart-title" style="margin-bottom: 10px;">Stock &amp; Inventory Management</h1>
            <p style="text-align: center; color: #6d6260; font-size: 14px; margin-bottom: 20px;">
                Logged in as <strong>Administrator</strong>. Real-time updates reflect instantly on customer cards.
            </p>

            <?php if ($message): ?>
                <div style="background-color: #e6f9ed; color: #1b873f; border: 1.5px solid #a3e9be; padding: 12px 18px; border-radius: 12px; font-weight: 700; font-size: 13.5px; margin-bottom: 20px;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background-color: #ffe8ec; color: #d13d60; border: 1.5px solid #f7b4c4; padding: 12px 18px; border-radius: 12px; font-weight: 700; font-size: 13.5px; margin-bottom: 20px;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Current Stock</th>
                        <th>New Quantity</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $prod): ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($prod['image']); ?>" alt="" class="item-thumb">
                                <strong><?php echo htmlspecialchars($prod['name']); ?></strong>
                            </td>
                            <td>&#8369;<?php echo number_format($prod['price'], 2); ?></td>
                            <td>
                                <strong style="color: <?php echo $prod['stock'] > 0 ? '#27ae60' : '#e74c3c'; ?>;">
                                    <?php echo (int)$prod['stock']; ?>
                                </strong>
                                <?php if ((int)$prod['stock'] <= 0): ?>
                                    <span class="stock-tag out">Out of Stock</span>
                                <?php endif; ?>
                            </td>
                            <form method="POST" action="admin_inventory.php">
                                <input type="hidden" name="product_id" value="<?php echo $prod['id']; ?>">
                                <td>
                                    <input type="number" name="new_stock" value="<?php echo (int)$prod['stock']; ?>" min="0" max="999" class="stock-input" required>
                                </td>
                                <td>
                                    <button type="submit" name="update_stock" class="btn-update-stock">Update Stock</button>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div class="footer-bar"></div>
</body>
</html>