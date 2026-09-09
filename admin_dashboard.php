<?php
session_start();
require_once 'db.php';

// Security Guard: Restrict access to authenticated administrators only
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

// Quick stats queries
$total_flavors  = 0;
$out_of_stock   = 0;
$total_units    = 0;
$total_messages = 0;
$total_orders   = 0;
$total_users    = 0;

$stats_res = $conn->query("SELECT COUNT(*) AS total_products, SUM(stock) AS total_units, SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) AS out_of_stock FROM products");
if ($stats_res && $row = $stats_res->fetch_assoc()) {
    $total_flavors = (int)$row['total_products'];
    $total_units   = (int)$row['total_units'];
    $out_of_stock  = (int)$row['out_of_stock'];
}

$msg_count_res = $conn->query("SELECT COUNT(*) AS total_msgs FROM messages");
if ($msg_count_res && $mrow = $msg_count_res->fetch_assoc()) {
    $total_messages = (int)$mrow['total_msgs'];
}

$order_count_res = $conn->query("SELECT COUNT(*) AS total_orders FROM orders");
if ($order_count_res && $orow = $order_count_res->fetch_assoc()) {
    $total_orders = (int)$orow['total_orders'];
}

$user_count_res = $conn->query("SELECT COUNT(*) AS total_users FROM users");
if ($user_count_res && $urow = $user_count_res->fetch_assoc()) {
    $total_users = (int)$urow['total_users'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Cheesecake Delight</title>
    
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

        .dashboard-wrapper {
            flex: 1;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: url('images/cheesecakebgg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .dashboard-card {
            background: #fffdf7;
            max-width: 1100px;
            width: 100%;
            border-radius: 32px;
            padding: 42px 38px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.06);
            border: 1.5px solid #fce3ea;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1.5px solid #fde4ec;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .dashboard-title h1 {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            color: #e65275;
            font-weight: 700;
        }

        .dashboard-title p {
            color: #795548;
            font-size: 13.5px;
            font-weight: 500;
            margin-top: 4px;
        }

        .admin-logout-btn {
            background-color: #ffffff;
            color: #e65275;
            border: 1.5px solid #f7b4c4;
            padding: 8px 18px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .admin-logout-btn:hover {
            background-color: #ffeef3;
            border-color: #e65275;
        }

        /* Stat Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 12px;
            margin-bottom: 35px;
        }

        .stat-box {
            background: #ffffff;
            border: 1.5px solid #f9cad7;
            border-radius: 20px;
            padding: 16px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            background-color: #ffdce6;
            color: #f76e8e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .stat-info h3 {
            font-size: 18px;
            font-weight: 800;
            color: #e65275;
        }

        .stat-info p {
            font-size: 11px;
            font-weight: 600;
            color: #63534d;
        }

        /* Action Modules */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .module-card {
            background: #ffffff;
            border: 1.5px solid #fce3ea;
            border-radius: 24px;
            padding: 22px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .module-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(230, 82, 117, 0.1);
        }

        .module-card-top {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .module-icon {
            width: 38px;
            height: 38px;
            background-color: #fff0f4;
            color: #e65275;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .module-card h2 {
            font-size: 15px;
            font-weight: 800;
            color: #4a3431;
        }

        .module-card p {
            font-size: 12px;
            color: #6d6260;
            line-height: 1.45;
            margin-bottom: 18px;
        }

        .btn-module {
            background-color: #f76e8e;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 12.5px;
            font-weight: 700;
            text-align: center;
            transition: background-color 0.2s ease;
        }

        .btn-module:hover {
            background-color: #e55a7b;
        }

        .footer-bar {
            margin-top: auto;
        }

        @media (max-width: 1050px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .modules-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .modules-grid {
                grid-template-columns: 1fr;
            }
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
            <a href="admin_dashboard.php" class="nav-item" style="border-bottom: 2px solid #ffffff;">DASHBOARD</a>
            <a href="admin_inventory.php" class="nav-item">INVENTORY</a>
            <a href="admin_orders.php" class="nav-item">ORDERS</a>
            <a href="admin_history.php" class="nav-item">HISTORY</a>
            <a href="admin_users.php" class="nav-item">USERS</a>
            <a href="admin_messages.php" class="nav-item">MESSAGES</a>
            <a href="Index.php" class="nav-item" target="_blank">STOREFRONT <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 11px;"></i></a>
        </nav>
    </header>

    <main class="dashboard-wrapper">
        <div class="dashboard-card">
            <div class="dashboard-header">
                <div class="dashboard-title">
                    <h1>Administrator Dashboard</h1>
                    <p>Manage stock levels, orders, registered users, feedback, and website inventory.</p>
                </div>
                <a href="admin_inventory.php?logout=1" class="admin-logout-btn">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
                </a>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-icon"><i class="fa-solid fa-cake-candles"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $total_flavors; ?></h3>
                        <p>Flavors</p>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $total_units; ?></h3>
                        <p>In Stock</p>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-icon" style="background-color: #ffebee; color: #e74c3c;"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div class="stat-info">
                        <h3 style="color: #e74c3c;"><?php echo $out_of_stock; ?></h3>
                        <p>Out of Stock</p>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-icon" style="background-color: #e8f7ee; color: #27ae60;"><i class="fa-solid fa-bag-shopping"></i></div>
                    <div class="stat-info">
                        <h3 style="color: #27ae60;"><?php echo $total_orders; ?></h3>
                        <p>Orders</p>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-icon" style="background-color: #ede7f6; color: #673ab7;"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-info">
                        <h3 style="color: #673ab7;"><?php echo $total_users; ?></h3>
                        <p>Users</p>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-icon" style="background-color: #e8f4fd; color: #2980b9;"><i class="fa-solid fa-comments"></i></div>
                    <div class="stat-info">
                        <h3 style="color: #2980b9;"><?php echo $total_messages; ?></h3>
                        <p>Inquiries</p>
                    </div>
                </div>
            </div>

            <!-- Modules -->
            <div class="modules-grid">
                <div class="module-card">
                    <div>
                        <div class="module-card-top">
                            <div class="module-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                            <h2>Stock &amp; Inventory</h2>
                        </div>
                        <p>Add flavors, modify prices, and edit live inventory counts.</p>
                    </div>
                    <a href="admin_inventory.php" class="btn-module">Manage Inventory &rarr;</a>
                </div>

                <div class="module-card">
                    <div>
                        <div class="module-card-top">
                            <div class="module-icon" style="background-color: #e8f7ee; color: #27ae60;"><i class="fa-solid fa-receipt"></i></div>
                            <h2>Customer Orders</h2>
                        </div>
                        <p>Track purchases, manage order fulfillment, and update statuses.</p>
                    </div>
                    <a href="admin_orders.php" class="btn-module" style="background-color: #27ae60;">Manage Orders &rarr;</a>
                </div>

                <div class="module-card">
                    <div>
                        <div class="module-card-top">
                            <div class="module-icon" style="background-color: #fff3e0; color: #e67e22;"><i class="fa-solid fa-clock-rotate-left"></i></div>
                            <h2>Order History</h2>
                        </div>
                        <p>Track past completed purchases, filter by status, and review order records.</p>
                    </div>
                    <a href="admin_history.php" class="btn-module" style="background-color: #e67e22;">View History &rarr;</a>
                </div>

                <div class="module-card">
                    <div>
                        <div class="module-card-top">
                            <div class="module-icon" style="background-color: #ede7f6; color: #673ab7;"><i class="fa-solid fa-user-gear"></i></div>
                            <h2>User Accounts</h2>
                        </div>
                        <p>View registered customers, reset credentials, or create accounts.</p>
                    </div>
                    <a href="admin_users.php" class="btn-module" style="background-color: #673ab7;">Manage Users &rarr;</a>
                </div>

                <div class="module-card">
                    <div>
                        <div class="module-card-top">
                            <div class="module-icon"><i class="fa-solid fa-envelope-open-text"></i></div>
                            <h2>User Inquiries</h2>
                        </div>
                        <p>Read customer questions and feedback sent from Contact Us.</p>
                    </div>
                    <a href="admin_messages.php" class="btn-module">Read Messages &rarr;</a>
                </div>

                <div class="module-card">
                    <div>
                        <div class="module-card-top">
                            <div class="module-icon"><i class="fa-solid fa-store"></i></div>
                            <h2>Live Storefront</h2>
                        </div>
                        <p>View your public menu and test ordering workflows as a customer.</p>
                    </div>
                    <a href="Index.php" class="btn-module" target="_blank">Open Storefront &rarr;</a>
                </div>
            </div>
        </div>
    </main>

    <div class="footer-bar"></div>
</body>
</html>