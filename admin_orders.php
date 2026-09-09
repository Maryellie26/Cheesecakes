<?php
session_start();
require_once 'db.php';

// Security Guard: Authenticated administrators only
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

// Handle Admin Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['is_admin']);
    unset($_SESSION['admin_user']);
    header("Location: login.php");
    exit;
}

$notice = '';
$error = '';

// Ensure payment_method column exists
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) NOT NULL DEFAULT 'Cash on Delivery'");

// --- CREATE: Manually Add Order (Admin) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $cust_name      = trim($_POST['customer_name'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $total          = (float)($_POST['total_amount'] ?? 0.00);
    $status         = trim($_POST['status'] ?? 'Pending');
    $payment_method = trim($_POST['payment_method'] ?? 'Cash on Delivery');
    $items_raw      = trim($_POST['order_items_text'] ?? 'Manual Store Order');

    if ($cust_name === '' || $email === '' || $total <= 0) {
        $error = "Please fill in all mandatory fields with a valid total.";
    } else {
        $order_json = json_encode([['name' => $items_raw, 'price' => $total, 'qty' => 1]]);
        $stmt = $conn->prepare("INSERT INTO orders (customer_name, email, phone, address, subtotal, shipping_fee, total_amount, order_items, status, payment_method) VALUES (?, ?, ?, ?, ?, 0.00, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssssddsss", $cust_name, $email, $phone, $address, $total, $total, $order_json, $status, $payment_method);
            if ($stmt->execute()) {
                $notice = "Order #{$stmt->insert_id} created successfully.";
            } else {
                $error = "Failed to create order: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// --- UPDATE: Modify Order Status ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id   = (int)($_POST['order_id'] ?? 0);
    $new_status = trim($_POST['status'] ?? 'Pending');

    $allowed = ['Pending', 'Processing', 'Completed', 'Cancelled'];
    if (in_array($new_status, $allowed, true)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $new_status, $order_id);
            if ($stmt->execute()) {
                $notice = "Order #$order_id status updated to '$new_status'.";
            } else {
                $error = "Failed to update order status.";
            }
            $stmt->close();
        }
    }
}

// --- DELETE: Delete Order ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $order_id);
        if ($stmt->execute()) {
            $notice = "Order #$order_id has been removed.";
        } else {
            $error = "Failed to delete order.";
        }
        $stmt->close();
    }
}

// --- READ: Retrieve all orders ---
$orders = [];
$res = $conn->query("SELECT * FROM orders ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Cheesecake Delight Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Playfair+Display:ital,wght@0,700;1,700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        html, body {
            height: 100%;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .admin-wrap {
            flex: 1;
            padding: 40px 20px;
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
            max-width: 1250px;
            width: 100%;
            border-radius: 28px;
            padding: 36px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            border: 1.5px solid #fce3ea;
        }

        .top-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .orders-table th, .orders-table td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid #fce3ea;
            font-size: 13px;
            vertical-align: top;
        }

        .orders-table th {
            color: #e65275;
            font-weight: 800;
            background: #fff5f7;
        }

        .status-select {
            padding: 6px 10px;
            border-radius: 8px;
            border: 1.5px solid #f3d1db;
            font-size: 12px;
            font-weight: 700;
            outline: none;
            background: #fff;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
        }

        .status-Pending { background: #fff3cd; color: #856404; }
        .status-Processing { background: #cce5ff; color: #004085; }
        .status-Completed { background: #d4edda; color: #155724; }
        .status-Cancelled { background: #f8d7da; color: #721c24; }

        /* Payment Badges */
        .badge-pay {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 9px;
            border-radius: 8px;
            font-size: 11.5px;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge-cod {
            background: #e8f8f0;
            color: #1e824c;
            border: 1px solid #a3e9be;
        }

        .badge-gcash {
            background: #eef6ff;
            color: #0b5ed7;
            border: 1px solid #bddbff;
        }

        .btn-action {
            border: none;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-update { background-color: #ff709b; color: #fff; }
        .btn-update:hover { background-color: #e8507c; }
        .btn-del { background: #fff0f4; border: 1px solid #f7b4c4; color: #d13d60; }
        .btn-del:hover { background: #d13d60; color: #fff; }

        .items-list {
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 12px;
            color: #5d4037;
        }

        .new-order-panel {
            display: none;
            background: #ffffff;
            border: 1.5px dashed #f7b4c4;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 25px;
        }

        .grid-inputs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 12px;
        }

        .grid-inputs input, .grid-inputs select {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #f3d1db;
            border-radius: 8px;
            font-size: 13px;
            box-sizing: border-box;
        }

        .footer-bar {
            height: 70px;
            background-color: #f77290;
            width: 100%;
            flex-shrink: 0;
            margin-top: auto;
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
            <a href="admin_inventory.php" class="nav-item">INVENTORY</a>
            <a href="admin_orders.php" class="nav-item" style="border-bottom: 2px solid #ffffff;">ORDERS</a>
            <a href="admin_history.php" class="nav-item">HISTORY</a>
            <a href="admin_users.php" class="nav-item">USERS</a>
            <a href="admin_messages.php" class="nav-item">MESSAGES</a>
            <a href="Index.php" class="nav-item" target="_blank">STOREFRONT <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 11px;"></i></a>
            <a href="admin_orders.php?logout=1" class="nav-item" style="color: #ffd6df;">LOGOUT</a>
        </nav>
    </header>

    <main class="admin-wrap">
        <div class="admin-card">
            <div class="top-flex">
                <div>
                    <h1 class="cart-title" style="margin-bottom: 4px; text-align: left;">Customer Orders</h1>
                    <p style="color: #6d6260; font-size: 13.5px;">Track incoming orders, change shipment status, and monitor customer payment methods.</p>
                </div>
                <button type="button" class="btn-action btn-update" style="padding: 10px 18px; font-size: 13px;" onclick="toggleOrderForm()">
                    <i class="fa-solid fa-plus"></i> Manual Order
                </button>
            </div>

            <?php if ($notice): ?>
                <div style="background-color: #e6f9ed; color: #1b873f; border: 1.5px solid #a3e9be; padding: 10px 16px; border-radius: 10px; font-weight: 700; font-size: 13px; margin-bottom: 15px;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($notice); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background-color: #ffe8ec; color: #d13d60; border: 1.5px solid #f7b4c4; padding: 10px 16px; border-radius: 10px; font-weight: 700; font-size: 13px; margin-bottom: 15px;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- CREATE Form (Admin Manual Order Entry) -->
            <div id="addOrderBox" class="new-order-panel">
                <h3 style="color: #e65275; margin-bottom: 12px; font-size: 15px;">Create Manual Order</h3>
                <form method="POST" action="admin_orders.php">
                    <div class="grid-inputs">
                        <input type="text" name="customer_name" placeholder="Customer Name" required>
                        <input type="email" name="email" placeholder="Customer Email" required>
                        <input type="text" name="phone" placeholder="Contact Phone">
                        <input type="text" name="address" placeholder="Delivery Address">
                        <input type="number" step="0.01" name="total_amount" placeholder="Total Amount (PHP)" required>
                        <input type="text" name="order_items_text" placeholder="Items (e.g. 2x Strawberry, 1x Oreo)">
                        <select name="payment_method">
                            <option value="Cash on Delivery">Cash on Delivery</option>
                            <option value="GCash">GCash</option>
                        </select>
                        <select name="status">
                            <option value="Pending">Pending</option>
                            <option value="Processing">Processing</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" name="create_order" class="btn-action btn-update">Save Order</button>
                </form>
            </div>

            <!-- READ, UPDATE, DELETE Table -->
            <?php if (empty($orders)): ?>
                <p style="text-align: center; padding: 30px; color: #8c7b74; font-weight: 600;">No orders found.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Customer Details</th>
                                <th>Items</th>
                                <th>Payment Method</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $ord): 
                                $raw_items = json_decode($ord['order_items'] ?? '[]', true);
                                $current_status = $ord['status'] ?? 'Pending';

                                $pay_method = $ord['payment_method'] ?? 'Cash on Delivery';
                                $is_gcash = stripos($pay_method, 'GCash') !== false;
                            ?>
                                <tr>
                                    <td><strong>#<?php echo $ord['id']; ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($ord['customer_name']); ?></strong><br>
                                        <span style="font-size: 11.5px; color: #8c7b74;"><?php echo htmlspecialchars($ord['email']); ?></span><br>
                                        <span style="font-size: 11.5px; color: #8c7b74;"><?php echo htmlspecialchars($ord['phone'] ?? ''); ?></span>
                                    </td>
                                    <td>
                                        <ul class="items-list">
                                            <?php if (is_array($raw_items)): ?>
                                                <?php foreach ($raw_items as $itm): ?>
                                                    <li>&bull; <?php echo htmlspecialchars($itm['name'] ?? 'Item'); ?> (&times;<?php echo (int)($itm['qty'] ?? 1); ?>)</li>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <li><?php echo htmlspecialchars($ord['order_items']); ?></li>
                                            <?php endif; ?>
                                        </ul>
                                    </td>
                                    <td>
                                        <span class="badge-pay <?php echo $is_gcash ? 'badge-gcash' : 'badge-cod'; ?>">
                                            <i class="<?php echo $is_gcash ? 'fa-solid fa-mobile-screen-button' : 'fa-solid fa-money-bill-wave'; ?>"></i>
                                            <?php echo htmlspecialchars($pay_method); ?>
                                        </span>
                                    </td>
                                    <td><strong>&#8369;<?php echo number_format((float)$ord['total_amount'], 2); ?></strong></td>
                                    
                                    <!-- UPDATE Form -->
                                    <td>
                                        <form method="POST" action="admin_orders.php" style="display:flex; gap: 6px; align-items: center;">
                                            <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                            <select name="status" class="status-select">
                                                <option value="Pending" <?php echo $current_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Processing" <?php echo $current_status === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="Completed" <?php echo $current_status === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="Cancelled" <?php echo $current_status === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn-action btn-update" title="Save Status">Save</button>
                                        </form>
                                    </td>

                                    <!-- DELETE Form -->
                                    <td>
                                        <form method="POST" action="admin_orders.php" onsubmit="return confirm('Delete order #<?php echo $ord['id']; ?>?');">
                                            <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                            <button type="submit" name="delete_order" class="btn-action btn-del" title="Delete Order">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div class="footer-bar"></div>

    <script>
        function toggleOrderForm() {
            const el = document.getElementById('addOrderBox');
            el.style.display = el.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</body>
</html>