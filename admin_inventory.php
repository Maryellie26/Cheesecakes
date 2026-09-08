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

// --- CREATE: Add New Product ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name  = trim($_POST['name'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0.00);
    $stock = (int)($_POST['stock'] ?? 0);
    $image = trim($_POST['image'] ?? 'images/strawberry.png.png');

    if ($name === '' || $price <= 0 || $stock < 0) {
        $error = "Please provide a valid product name, price, and stock.";
    } else {
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, image, stock) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssdsi", $name, $desc, $price, $image, $stock);
            if ($stmt->execute()) {
                $message = "Product added successfully!";
            } else {
                $error = "Database error: Could not add product.";
            }
            $stmt->close();
        }
    }
}

// --- UPDATE: Modify Existing Product ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $name       = trim($_POST['name'] ?? '');
    $price      = (float)($_POST['price'] ?? 0.00);
    $new_stock  = (int)($_POST['new_stock'] ?? 0);

    if ($new_stock < 0 || $price <= 0 || $name === '') {
        $error = "Invalid product parameters.";
    } else {
        $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, stock = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sdii", $name, $price, $new_stock, $product_id);
            if ($stmt->execute()) {
                $message = "Product details updated successfully!";
            } else {
                $error = "Failed to update product details.";
            }
            $stmt->close();
        }
    }
}

// --- DELETE: Remove Product ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        if ($stmt->execute()) {
            $message = "Product removed successfully.";
        } else {
            $error = "Failed to delete product.";
        }
        $stmt->close();
    }
}

// --- READ: Fetch all products ---
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
            align-items: flex-start;
            background-image: url('images/cheesecakebgg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .admin-card {
            background: #fffdf5;
            max-width: 1100px;
            width: 100%;
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            border: 1.5px solid #fce3ea;
        }
        .card-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .inventory-table th, .inventory-table td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid #fce3ea;
            font-size: 13.5px;
            vertical-align: middle;
        }
        .inventory-table th {
            color: #e65275;
            font-weight: 800;
            background: #fff5f7;
        }
        .stock-input, .text-input-table {
            padding: 6px 10px;
            border: 1.5px solid #f3d1db;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            outline: none;
            font-family: 'Montserrat', sans-serif;
        }
        .stock-input {
            width: 70px;
        }
        .price-input {
            width: 85px;
        }
        .btn-action {
            border: none;
            border-radius: 8px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-update {
            background-color: #ff709b;
            color: #ffffff;
        }
        .btn-update:hover {
            background-color: #e8507c;
        }
        .btn-delete {
            background-color: #ffebee;
            color: #e74c3c;
            border: 1px solid #f5c2c7;
        }
        .btn-delete:hover {
            background-color: #e74c3c;
            color: #ffffff;
        }
        .btn-add-toggle {
            background-color: #27ae60;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
        }
        .btn-add-toggle:hover {
            background-color: #219653;
        }
        .item-thumb {
            width: 40px;
            height: 40px;
            object-fit: contain;
            vertical-align: middle;
            margin-right: 8px;
        }
        .add-product-panel {
            background: #ffffff;
            border: 1.5px dashed #f7b4c4;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 25px;
            display: none;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 12px;
        }
        .form-row input, .form-row textarea {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #f3d1db;
            border-radius: 8px;
            font-size: 13px;
            outline: none;
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
            <div class="card-top-bar">
                <div>
                    <h1 class="cart-title" style="margin-bottom: 4px; text-align: left;">Stock &amp; Inventory Management</h1>
                    <p style="color: #6d6260; font-size: 13.5px;">Manage product catalog, prices, and available counts.</p>
                </div>
                <button type="button" class="btn-add-toggle" onclick="toggleAddForm()">
                    <i class="fa-solid fa-plus"></i> Add New Product
                </button>
            </div>

            <?php if ($message): ?>
                <div style="background-color: #e6f9ed; color: #1b873f; border: 1.5px solid #a3e9be; padding: 10px 16px; border-radius: 10px; font-weight: 700; font-size: 13px; margin-bottom: 15px;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background-color: #ffe8ec; color: #d13d60; border: 1.5px solid #f7b4c4; padding: 10px 16px; border-radius: 10px; font-weight: 700; font-size: 13px; margin-bottom: 15px;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- CREATE: Add New Product Form -->
            <div id="addProductBox" class="add-product-panel">
                <h3 style="color: #e65275; margin-bottom: 12px; font-size: 16px;">Add New Cheesecake Flavor</h3>
                <form method="POST" action="admin_inventory.php">
                    <div class="form-row">
                        <input type="text" name="name" placeholder="Flavor Name (e.g. Raspberry Swirl)" required>
                        <input type="number" step="0.01" name="price" placeholder="Price (PHP)" required>
                        <input type="number" name="stock" placeholder="Initial Stock" required min="0">
                        <input type="text" name="image" placeholder="Image Path (e.g. images/strawberry.png.png)">
                    </div>
                    <div class="form-row">
                        <textarea name="description" rows="2" placeholder="Product short description..."></textarea>
                    </div>
                    <button type="submit" name="add_product" class="btn-action btn-update" style="padding: 9px 18px;">Save Product</button>
                </form>
            </div>

            <!-- READ & UPDATE & DELETE Table -->
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product Name</th>
                        <th>Price (&#8369;)</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $prod): ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($prod['image']); ?>" alt="" class="item-thumb">
                            </td>
                            <!-- UPDATE Form -->
                            <form method="POST" action="admin_inventory.php">
                                <input type="hidden" name="product_id" value="<?php echo $prod['id']; ?>">
                                <td>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($prod['name']); ?>" class="text-input-table" style="width: 100%; min-width: 140px;" required>
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($prod['price']); ?>" class="text-input-table price-input" required>
                                </td>
                                <td>
                                    <input type="number" name="new_stock" value="<?php echo (int)$prod['stock']; ?>" min="0" max="999" class="stock-input" required>
                                </td>
                                <td style="white-space: nowrap;">
                                    <button type="submit" name="update_product" class="btn-action btn-update" title="Save changes">Save</button>
                            </form>
                                    <!-- DELETE Form -->
                                    <form method="POST" action="admin_inventory.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete this product?');">
                                        <input type="hidden" name="product_id" value="<?php echo $prod['id']; ?>">
                                        <button type="submit" name="delete_product" class="btn-action btn-delete" title="Delete product">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        function toggleAddForm() {
            const box = document.getElementById('addProductBox');
            box.style.display = box.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</body>
</html>