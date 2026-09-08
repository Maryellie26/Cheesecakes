<?php
session_start();
require_once 'db.php';

// Security Guard: Restrict access to authenticated administrators only
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

$notice = '';
$error  = '';

// Ensure status column exists in orders table
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS status VARCHAR(50) NOT NULL DEFAULT 'Completed'");

// ==========================================
// C - CREATE: Manual Order Entry
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $cust_name = trim($_POST['customer_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $status    = trim($_POST['status'] ?? 'Completed');
    $item_name = trim($_POST['item_name'] ?? 'Cheesecake Order');
    $qty       = max(1, (int)($_POST['qty'] ?? 1));
    $price     = max(0.00, (float)($_POST['price'] ?? 180.00));
    
    $subtotal     = $price * $qty;
    $shipping_fee = 50.00;
    $grand_total  = $subtotal + $shipping_fee;

    // Build standard JSON items structure
    $items_array = [
        [
            'name'  => $item_name,
            'price' => $price,
            'qty'   => $qty
        ]
    ];
    $order_items_json = json_encode($items_array);

    if (!empty($cust_name) && !empty($email) && !empty($phone) && !empty($address)) {
        $stmt = $conn->prepare("INSERT INTO orders (customer_name, email, phone, address, subtotal, shipping_fee, total_amount, order_items, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssssdddss", $cust_name, $email, $phone, $address, $subtotal, $shipping_fee, $grand_total, $order_items_json, $status);
            if ($stmt->execute()) {
                $notice = "New order successfully recorded.";
            } else {
                $error = "Failed to create order record.";
            }
            $stmt->close();
        }
    } else {
        $error = "Please fill in all mandatory customer fields.";
    }
}

// ==========================================
// U - UPDATE: Modify Order Details or Status
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $order_id  = (int)($_POST['order_id'] ?? 0);
    $status    = trim($_POST['status'] ?? 'Completed');
    $cust_name = trim($_POST['customer_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');

    if ($order_id > 0 && !empty($cust_name)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ?, customer_name = ?, phone = ?, address = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ssssi", $status, $cust_name, $phone, $address, $order_id);
            if ($stmt->execute()) {
                $notice = "Order #$order_id updated successfully.";
            } else {
                $error = "Failed to update order #$order_id.";
            }
            $stmt->close();
        }
    }
}

// ==========================================
// D - DELETE: Remove Order History Record
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $order_id = (int)($_POST['order_id'] ?? 0);
    if ($order_id > 0) {
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $order_id);
            if ($stmt->execute()) {
                $notice = "Order #$order_id has been removed.";
            } else {
                $error = "Failed to delete order #$order_id.";
            }
            $stmt->close();
        }
    }
}

// ==========================================
// R - READ: Fetch Order History
// ==========================================
$orders = [];
$filter_status = trim($_GET['status_filter'] ?? 'all');

$sql = "SELECT id, customer_name, email, phone, address, subtotal, shipping_fee, total_amount, order_items, status, created_at FROM orders";
if ($filter_status !== 'all' && !empty($filter_status)) {
    $sql .= " WHERE status = '" . $conn->real_escape_string($filter_status) . "'";
}
$sql .= " ORDER BY id DESC";

$res = $conn->query($sql);
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
    <title>Order History &amp; Tracking - Cheesecake Delight Admin</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
            background-image: url('images/admin_bg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .admin-card {
            background: #fffdf5;
            max-width: 1200px;
            width: 100%;
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
            border: 1.5px solid #fce3ea;
        }

        .action-bar-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 25px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .select-filter {
            padding: 8px 14px;
            border-radius: 10px;
            border: 1.5px solid #f3d1db;
            font-family: 'Montserrat', sans-serif;
            font-size: 13px;
            outline: none;
            background: #ffffff;
            color: #4a3431;
            font-weight: 600;
        }

        .btn-add-order {
            background-color: #f76e8e;
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 9px 18px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.2s ease;
        }

        .btn-add-order:hover {
            background-color: #e55a7b;
        }

        /* Order Table */
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .orders-table th, .orders-table td {
            padding: 14px 14px;
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

        .badge-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .badge-completed {
            background: #e6f9ed;
            color: #1b873f;
            border: 1px solid #a3e9be;
        }

        .badge-processing {
            background: #fff8e1;
            color: #b78103;
            border: 1px solid #ffe082;
        }

        .badge-pending {
            background: #e8f4fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }

        .badge-cancelled {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .items-bullet-list {
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 12px;
            color: #5d4037;
        }

        .items-bullet-list li {
            margin-bottom: 3px;
        }

        .table-actions {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-action-icon {
            background: #ffffff;
            border: 1px solid #f7b4c4;
            border-radius: 8px;
            padding: 6px 10px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s ease;
        }

        .btn-action-edit {
            color: #2980b9;
        }

        .btn-action-edit:hover {
            background: #2980b9;
            color: #ffffff;
        }

        .btn-action-del {
            color: #d13d60;
        }

        .btn-action-del:hover {
            background: #d13d60;
            color: #ffffff;
        }

        /* Modal Overlay for Create / Edit Form */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
        }

        .modal-box {
            background: #fffdf7;
            max-width: 500px;
            width: 100%;
            border-radius: 24px;
            padding: 30px;
            border: 1.5px solid #fce3ea;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .modal-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            color: #e65275;
        }

        .close-modal-btn {
            background: none;
            border: none;
            font-size: 20px;
            color: #8c7b74;
            cursor: pointer;
        }

        .form-row {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 14px;
        }

        .form-row label {
            font-size: 12.5px;
            font-weight: 700;
            color: #4a3431;
        }

        .form-row input, .form-row select, .form-row textarea {
            padding: 9px 12px;
            border-radius: 10px;
            border: 1.5px solid #f3d1db;
            font-family: 'Montserrat', sans-serif;
            font-size: 13px;
            outline: none;
        }

        .btn-modal-save {
            background-color: #f76e8e;
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-size: 13.5px;
            font-weight: 800;
            width: 100%;
            cursor: pointer;
            margin-top: 8px;
        }

        .btn-modal-save:hover {
            background-color: #e55a7b;
        }

        .footer-bar {
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
            <a href="admin_messages.php" class="nav-item">MESSAGES</a>
            <a href="admin_history.php" class="nav-item" style="border-bottom: 2px solid #ffffff;">ORDERS</a>
            <a href="Index.php" class="nav-item" target="_blank">STOREFRONT <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 11px;"></i></a>
            <a href="admin_inventory.php?logout=1" class="nav-item" style="color: #ffd6df;">LOGOUT</a>
        </nav>
    </header>

    <main class="admin-wrap">
        <div class="admin-card">
            <h1 class="cart-title" style="margin-bottom: 6px;">Order History &amp; Tracking</h1>
            <p style="text-align: center; color: #6d6260; font-size: 14px; margin-bottom: 10px;">
                Track completed, pending, and past customer orders.
            </p>

            <?php if ($notice): ?>
                <div style="background-color: #e6f9ed; color: #1b873f; border: 1.5px solid #a3e9be; padding: 12px 18px; border-radius: 12px; font-weight: 700; font-size: 13.5px; margin: 15px 0;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($notice); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background-color: #ffe8ec; color: #d13d60; border: 1.5px solid #f7b4c4; padding: 12px 18px; border-radius: 12px; font-weight: 700; font-size: 13.5px; margin: 15px 0;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Action Toolbar -->
            <div class="action-bar-top">
                <form method="GET" action="admin_history.php" class="filter-group">
                    <label for="status_filter" style="font-size: 13px; font-weight: 700; color: #4a3431;">Filter Status:</label>
                    <select name="status_filter" id="status_filter" class="select-filter" onchange="this.form.submit()">
                        <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="Completed" <?php echo $filter_status === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="Processing" <?php echo $filter_status === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="Pending" <?php echo $filter_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Cancelled" <?php echo $filter_status === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </form>

                <button type="button" class="btn-add-order" onclick="openCreateModal()">
                    <i class="fa-solid fa-plus"></i> Record Manual Order
                </button>
            </div>

            <!-- Orders Table -->
            <?php if (empty($orders)): ?>
                <div style="text-align: center; padding: 50px 20px; color: #8c7b74;">
                    <i class="fa-solid fa-receipt" style="font-size: 40px; color: #f77290; margin-bottom: 12px;"></i>
                    <p>No orders found matching this filter.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer Details</th>
                                <th>Items Ordered</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date Placed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $ord): 
                                $status = $ord['status'] ?? 'Completed';
                                $badge_class = 'badge-completed';
                                if ($status === 'Processing') $badge_class = 'badge-processing';
                                if ($status === 'Pending') $badge_class = 'badge-pending';
                                if ($status === 'Cancelled') $badge_class = 'badge-cancelled';

                                $items = json_decode($ord['order_items'], true) ?? [];
                            ?>
                                <tr>
                                    <td><strong>#<?php echo $ord['id']; ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($ord['customer_name']); ?></strong><br>
                                        <span style="font-size: 11.5px; color: #8c7b74;"><?php echo htmlspecialchars($ord['email']); ?></span><br>
                                        <span style="font-size: 11.5px; color: #8c7b74;"><?php echo htmlspecialchars($ord['phone']); ?></span><br>
                                        <span style="font-size: 11.5px; color: #6d6260;"><?php echo htmlspecialchars($ord['address']); ?></span>
                                    </td>
                                    <td>
                                        <ul class="items-bullet-list">
                                            <?php foreach ($items as $it): ?>
                                                <li>&bull; <?php echo htmlspecialchars($it['name'] ?? 'Cheesecake'); ?> (&times;<?php echo $it['qty'] ?? 1; ?>)</li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </td>
                                    <td>
                                        <strong style="color: #ff5983;">&#8369;<?php echo number_format($ord['total_amount'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge-status <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </td>
                                    <td style="color: #8c7b74; font-size: 12px; white-space: nowrap;">
                                        <?php echo htmlspecialchars($ord['created_at'] ?? 'Recent'); ?>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <button type="button" class="btn-action-icon btn-action-edit" title="Edit Order Status" onclick='openEditModal(<?php echo json_encode($ord); ?>)'>
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <form method="POST" action="admin_history.php" onsubmit="return confirm('Permanently delete order #<?php echo $ord['id']; ?>?');" style="display:inline;">
                                                <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                                <button type="submit" name="delete_order" class="btn-action-icon btn-action-del" title="Delete Record">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- CREATE ORDER MODAL -->
    <div id="createModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Record Manual Order</h2>
                <button type="button" class="close-modal-btn" onclick="closeCreateModal()">&times;</button>
            </div>
            <form method="POST" action="admin_history.php">
                <div class="form-row">
                    <label for="create_name">Customer Full Name</label>
                    <input type="text" id="create_name" name="customer_name" required>
                </div>
                <div class="form-row">
                    <label for="create_email">Email</label>
                    <input type="email" id="create_email" name="email" required>
                </div>
                <div class="form-row">
                    <label for="create_phone">Phone Number</label>
                    <input type="text" id="create_phone" name="phone" required>
                </div>
                <div class="form-row">
                    <label for="create_address">Address</label>
                    <textarea id="create_address" name="address" rows="2" required></textarea>
                </div>
                <div class="form-row">
                    <label for="create_flavor">Cheesecake Flavor</label>
                    <input type="text" id="create_flavor" name="item_name" value="Strawberry Cheesecake" required>
                </div>
                <div style="display: flex; gap: 10px;">
                    <div class="form-row" style="flex: 1;">
                        <label for="create_qty">Quantity</label>
                        <input type="number" id="create_qty" name="qty" value="1" min="1" required>
                    </div>
                    <div class="form-row" style="flex: 1;">
                        <label for="create_price">Price (&#8369;)</label>
                        <input type="number" step="0.01" id="create_price" name="price" value="180.00" required>
                    </div>
                </div>
                <div class="form-row">
                    <label for="create_status">Order Status</label>
                    <select id="create_status" name="status">
                        <option value="Completed">Completed</option>
                        <option value="Processing">Processing</option>
                        <option value="Pending">Pending</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <button type="submit" name="create_order" class="btn-modal-save">Save Order</button>
            </form>
        </div>
    </div>

    <!-- EDIT ORDER MODAL -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Edit Order Status &amp; Info</h2>
                <button type="button" class="close-modal-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST" action="admin_history.php">
                <input type="hidden" id="edit_id" name="order_id">
                <div class="form-row">
                    <label for="edit_status">Order Status</label>
                    <select id="edit_status" name="status">
                        <option value="Completed">Completed</option>
                        <option value="Processing">Processing</option>
                        <option value="Pending">Pending</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-row">
                    <label for="edit_name">Customer Name</label>
                    <input type="text" id="edit_name" name="customer_name" required>
                </div>
                <div class="form-row">
                    <label for="edit_phone">Phone</label>
                    <input type="text" id="edit_phone" name="phone" required>
                </div>
                <div class="form-row">
                    <label for="edit_address">Address</label>
                    <textarea id="edit_address" name="address" rows="2" required></textarea>
                </div>
                <button type="submit" name="update_order" class="btn-modal-save">Update Order Record</button>
            </form>
        </div>
    </div>

    <div class="footer-bar"></div>

    <script>
        function openCreateModal() {
            document.getElementById('createModal').style.display = 'flex';
        }
        function closeCreateModal() {
            document.getElementById('createModal').style.display = 'none';
        }

        function openEditModal(order) {
            document.getElementById('edit_id').value = order.id;
            document.getElementById('edit_status').value = order.status || 'Completed';
            document.getElementById('edit_name').value = order.customer_name || '';
            document.getElementById('edit_phone').value = order.phone || '';
            document.getElementById('edit_address').value = order.address || '';
            document.getElementById('editModal').style.display = 'flex';
        }
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close on backdrop click
        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                closeCreateModal();
                closeEditModal();
            }
        }
    </script>
</body>
</html>