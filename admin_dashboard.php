<?php
session_start();
require_once 'db.php';

// Security Guard: Restrict access to authenticated administrators only
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// Quick stats queries
$total_flavors = 0;
$out_of_stock  = 0;
$total_units   = 0;

$stats_res = $conn->query("SELECT COUNT(*) AS total_products, SUM(stock) AS total_units, SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) AS out_of_stock FROM products");
if ($stats_res && $row = $stats_res->fetch_assoc()) {
    $total_flavors = (int)$row['total_products'];
    $total_units   = (int)$row['total_units'];
    $out_of_stock  = (int)$row['out_of_stock'];
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
        .dashboard-wrapper {
            min-height: 80vh;
            padding: 50px 20px;
            display: flex;
            justify-content: center;
        }

        .dashboard-card {
            background: #fffdf7;
            max-width: 900px;
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
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 35px;
        }

        .stat-box {
            background: #ffffff;
            border: 1.5px solid #f9cad7;
            border-radius: 20px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            background-color: #ffdce6;
            color: #f76e8e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .stat-info h3 {
            font-size: 22px;
            font-weight: 800;
            color: #e65275;
        }

        .stat-info p {
            font-size: 12.5px;
            font-weight: 600;
            color: #63534d;
        }

        /* Dashboard Actions */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .module-card {
            background: #ffffff;
            border: 1.5px solid #fce3ea;
            border-radius: 24px;
            padding: 26px;
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
            gap: 14px;
            margin-bottom: 12px;
        }

        .module-icon {
            width: 42px;
            height: 42px;
            background-color: #fff0f4;
            color: #e65275;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .module-card h2 {
            font-size: 17px;
            font-weight: 800;
            color: #4a3431;
        }

        .module-card p {
            font-size: 13px;
            color: #6d6260;
            line-height: 1.45;
            margin-bottom: 20px;
        }

        .btn-module {
            background-color: #f76e8e;
            color: #ffffff;
            text-decoration: none;
            padding: 11px 18px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 700;
            text-align: center;
            transition: background-color 0.2s ease;
        }

        .btn-module:hover {
            background-color: #e55a7b;
        }

        @media (max-width: 768px) {
            .stats-grid, .modules-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <header class="navbar">
        <a href="index.php" class="brand-logo-nav">
            <img src="images/cheesecakeLogo.png" alt="Logo">
            <span class="nav-brand-name">Cheesecake Delight</span>
        </a>
        <nav class="nav-links">
            <a href="admin_dashboard.php" class="nav-item" style="border-bottom: 2px solid #ffffff;">DASHBOARD</a>
            <a href="admin_inventory.php" class="nav-item">INVENTORY</a>
            <a href="index.php" class="nav-item" target="_blank">VIEW STOREFRONT <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 11px;"></i></a>
        </nav>
    </header>

    <main class="dashboard-wrapper">
        <div class="dashboard-card">
            <div class="dashboard-header">
                <div class="dashboard-title">
                    <h1>Administrator Dashboard</h1>
                    <p>Welcome back! Manage store inventory and monitor operations.</p>
                </div>
                <a href="admin_inventory.php?logout=1" class="admin-logout-btn">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
                </a>
            </div>

            <!-- Store Metrics -->
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-icon"><i class="fa-solid fa-cake-candles"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $total_flavors; ?></h3>
                        <p>Total Flavors</p>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $total_units; ?></h3>
                        <p>Total Units in Stock</p>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-icon" style="background-color: #ffebee; color: #e74c3c;"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div class="stat-info">
                        <h3 style="color: #e74c3c;"><?php echo $out_of_stock; ?></h3>
                        <p>Out of Stock</p>
                    </div>
                </div>
            </div>

            <!-- Action Modules -->
            <div class="modules-grid">
                <div class="module-card">
                    <div>
                        <div class="module-card-top">
                            <div class="module-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                            <h2>Stock &amp; Inventory</h2>
                        </div>
                        <p>Review real-time stock levels, restock flavors, and modify available quantities for customers.</p>
                    </div>
                    <a href="admin_inventory.php" class="btn-module">Manage Inventory &rarr;</a>
                </div>

                <div class="module-card">
                    <div>
                        <div class="module-card-top">
                            <div class="module-icon"><i class="fa-solid fa-store"></i></div>
                            <h2>Customer Storefront</h2>
                        </div>
                        <p>Open the live customer-facing storefront in a new tab to see updated cards and real-time badges.</p>
                    </div>
                    <a href="index.php" class="btn-module" target="_blank">Open Storefront &rarr;</a>
                </div>
            </div>
        </div>
    </main>

    <div class="footer-bar"></div>

</body>
</html>