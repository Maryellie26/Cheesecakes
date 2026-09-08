<?php
session_start();
require_once 'db.php';

// Security Guard: Authenticated administrators only
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

$notice = '';
$error = '';

// --- CREATE: Manually Add User ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fullname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        $error = "Valid full name, email, and a password (min 6 characters) are required.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "A user account with this email address already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $fullname, $email, $hashed);
            if ($stmt->execute()) {
                $notice = "New user account created successfully!";
            } else {
                $error = "Database error: Could not create user.";
            }
            $stmt->close();
        }
        $check->close();
    }
}

// --- UPDATE: Edit User Details ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id  = (int)($_POST['user_id'] ?? 0);
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $new_pass = $_POST['new_password'] ?? '';

    if ($fullname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid full name and email.";
    } else {
        if (!empty($new_pass)) {
            if (strlen($new_pass) < 6) {
                $error = "New password must be at least 6 characters long.";
            } else {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, password = ? WHERE id = ?");
                $stmt->bind_param("sssi", $fullname, $email, $hashed, $user_id);
                $stmt->execute();
                $stmt->close();
                $notice = "User details and password updated successfully!";
            }
        } else {
            $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $fullname, $email, $user_id);
            if ($stmt->execute()) {
                $notice = "User details updated successfully!";
            } else {
                $error = "Failed to update user.";
            }
            $stmt->close();
        }
    }
}

// --- DELETE: Delete User ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $notice = "User account removed successfully.";
        } else {
            $error = "Failed to delete user.";
        }
        $stmt->close();
    }
}

// --- READ: Retrieve All Users ---
$users = [];
$res = $conn->query("SELECT id, fullname, email, created_at FROM users ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Cheesecake Delight Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Playfair+Display:ital,wght@0,700;1,700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .admin-wrap {
            min-height: 88vh;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            background-image: url('images/cheesecakebgg.png');
            background-size: cover;
            background-position: center;
        }
        .admin-card {
            background: #fffdf5;
            max-width: 1100px;
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
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .users-table th, .users-table td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid #fce3ea;
            font-size: 13.5px;
            vertical-align: middle;
        }
        .users-table th {
            color: #e65275;
            font-weight: 800;
            background: #fff5f7;
        }
        .input-tbl {
            padding: 7px 10px;
            border-radius: 8px;
            border: 1.5px solid #f3d1db;
            font-size: 13px;
            outline: none;
            width: 100%;
        }
        .btn-act {
            border: none;
            border-radius: 8px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-save { background-color: #ff709b; color: #fff; }
        .btn-save:hover { background-color: #e8507c; }
        .btn-del { background: #fff0f4; border: 1px solid #f7b4c4; color: #d13d60; }
        .btn-del:hover { background: #d13d60; color: #fff; }
        
        .add-user-panel {
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
        .grid-inputs input {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #f3d1db;
            border-radius: 8px;
            font-size: 13px;
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
            <a href="admin_orders.php" class="nav-item">ORDERS</a>
            <a href="admin_users.php" class="nav-item" style="border-bottom: 2px solid #ffffff;">USERS</a>
            <a href="admin_messages.php" class="nav-item">MESSAGES</a>
            <a href="Index.php" class="nav-item" target="_blank">STOREFRONT <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 11px;"></i></a>
            <a href="admin_inventory.php?logout=1" class="nav-item" style="color: #ffd6df;">LOGOUT</a>
        </nav>
    </header>

    <main class="admin-wrap">
        <div class="admin-card">
            <div class="top-flex">
                <div>
                    <h1 class="cart-title" style="margin-bottom: 4px; text-align: left;">Customer Accounts</h1>
                    <p style="color: #6d6260; font-size: 13.5px;">Manage registered members, update account credentials, or remove profiles.</p>
                </div>
                <button type="button" class="btn-act btn-save" style="padding: 10px 18px; font-size: 13px;" onclick="toggleUserForm()">
                    <i class="fa-solid fa-user-plus"></i> Add New User
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

            <!-- CREATE: Add New User Form -->
            <div id="addUserBox" class="add-user-panel">
                <h3 style="color: #e65275; margin-bottom: 12px; font-size: 15px;">Create User Account</h3>
                <form method="POST" action="admin_users.php">
                    <div class="grid-inputs">
                        <input type="text" name="fullname" placeholder="Customer Full Name" required>
                        <input type="email" name="email" placeholder="Customer Email" required>
                        <input type="password" name="password" placeholder="Password (min 6 characters)" required>
                    </div>
                    <button type="submit" name="add_user" class="btn-act btn-save">Save User</button>
                </form>
            </div>

            <!-- READ, UPDATE, DELETE Table -->
            <?php if (empty($users)): ?>
                <p style="text-align: center; padding: 30px; color: #8c7b74; font-weight: 600;">No registered users found.</p>
            <?php else: ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th style="width: 8%;">#ID</th>
                            <th style="width: 25%;">Full Name</th>
                            <th style="width: 28%;">Email</th>
                            <th style="width: 22%;">Reset Password</th>
                            <th style="width: 17%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><strong>#<?php echo $u['id']; ?></strong></td>
                                <!-- UPDATE Form -->
                                <form method="POST" action="admin_users.php">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <td>
                                        <input type="text" name="fullname" value="<?php echo htmlspecialchars($u['fullname']); ?>" class="input-tbl" required>
                                    </td>
                                    <td>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($u['email']); ?>" class="input-tbl" required>
                                    </td>
                                    <td>
                                        <input type="password" name="new_password" placeholder="Leave blank to keep" class="input-tbl">
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <button type="submit" name="update_user" class="btn-act btn-save" title="Save changes">Save</button>
                                </form>
                                        <!-- DELETE Form -->
                                        <form method="POST" action="admin_users.php" style="display:inline;" onsubmit="return confirm('Delete user account #<?php echo $u['id']; ?>?');">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn-act btn-del" title="Delete User">
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
    </main>

    <script>
        function toggleUserForm() {
            const el = document.getElementById('addUserBox');
            el.style.display = el.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</body>
</html>