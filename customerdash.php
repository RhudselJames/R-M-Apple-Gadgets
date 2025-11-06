<?php
session_start();
include 'db_connect.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']) ?? '';
    
    try {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $phone, $user_id]);
        
        $success_message = "Profile updated successfully!";
        $_SESSION['username'] = $full_name; // Update session if needed
    } catch (PDOException $e) {
        $error_message = "Error updating profile: " . $e->getMessage();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Fetch current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (password_verify($current_password, $user_data['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 8) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "New password must be at least 8 characters long.";
            }
        } else {
            $error_message = "New passwords do not match.";
        }
    } else {
        $error_message = "Current password is incorrect.";
    }
}

// Fetch user details
$stmt = $conn->prepare("SELECT username, email, full_name, phone, role, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch cart items with product details
$cart_stmt = $conn->prepare("
    SELECT c.id as cart_id, p.name, p.price, p.image_url, c.quantity, c.color, c.storage,
           (p.price * c.quantity) as subtotal
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$cart_stmt->execute([$user_id]);
$cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate cart total
$cart_total = 0;
foreach ($cart_items as $item) {
    $cart_total += $item['subtotal'];
}

// Fetch order history
$orders_stmt = $conn->prepare("
    SELECT o.order_id, o.order_date, o.status, o.total_amount, o.payment_method, o.address,
           COUNT(oi.order_item_id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.order_id
    ORDER BY o.order_date DESC
");
$orders_stmt->execute([$user_id]);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - R&M Apple Gadgets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
            background: #f5f5f7;
            color: #1d1d1f;
        }

        /* Navigation */
        .navbar-custom {
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(20px);
            padding: 12px 40px;
        }
        
        .dashboard-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .dashboard-header h1 {
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .dashboard-header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .dashboard-tabs {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .nav-tabs {
            border: none;
            gap: 10px;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #6e6e73;
            font-weight: 500;
            padding: 12px 24px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .nav-tabs .nav-link:hover {
            background: #f5f5f7;
            color: #1d1d1f;
        }
        
        .nav-tabs .nav-link.active {
            background: #0071e3;
            color: white;
        }
        
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .content-card h3 {
            font-size: 1.5em;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1d1d1f;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #d2d2d7;
            padding: 12px 16px;
            font-size: 0.95em;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #0071e3;
            box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.1);
        }
        
        .btn-primary {
            background: #0071e3;
            border: none;
            border-radius: 10px;
            padding: 12px 28px;
            font-weight: 500;
            transition: 0.3s;
        }
        
        .btn-primary:hover {
            background: #0077ed;
        }
        
        .btn-danger {
            border-radius: 10px;
            padding: 8px 16px;
        }
        
        .cart-item {
            display: flex;
            gap: 20px;
            padding: 20px;
            border: 1px solid #e5e5e7;
            border-radius: 12px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .cart-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .cart-item img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 8px;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-details h5 {
            font-size: 1.1em;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .cart-item-meta {
            display: flex;
            gap: 15px;
            font-size: 0.9em;
            color: #6e6e73;
            margin-bottom: 10px;
        }
        
        .cart-total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
        }
        
        .cart-total h4 {
            font-size: 1.8em;
            font-weight: 700;
        }
        
        .order-card {
            border: 1px solid #e5e5e7;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .order-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e5e7;
        }
        
        .order-id {
            font-weight: 600;
            font-size: 1.1em;
        }
        
        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff4e6;
            color: #d97706;
        }
        
        .status-confirmed {
            background: #e6f4ff;
            color: #0066cc;
        }
        
        .status-shipped {
            background: #f0e6ff;
            color: #7c3aed;
        }
        
        .status-delivered {
            background: #e6ffe6;
            color: #059669;
        }
        
        .status-cancelled {
            background: #ffe6e6;
            color: #dc2626;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .order-detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .order-detail-label {
            font-size: 0.85em;
            color: #6e6e73;
        }
        
        .order-detail-value {
            font-weight: 600;
            color: #1d1d1f;
        }
        
        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-item {
            padding: 15px;
            background: #f5f5f7;
            border-radius: 10px;
        }
        
        .info-label {
            font-size: 0.85em;
            color: #6e6e73;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 1.05em;
            font-weight: 600;
            color: #1d1d1f;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6e6e73;
        }
        
        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h4 {
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 40px 20px;
            }
            
            .dashboard-header h1 {
                font-size: 1.8em;
            }
            
            .cart-item {
                flex-direction: column;
            }
            
            .cart-item img {
                width: 100%;
                height: 200px;
            }
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<header class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.53 4.09l-.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z'/%3E%3C/svg%3E" width="24" height="24" class="me-2" alt="Apple">
            <span class="text-white fw-bold">R&M Apple Gadgets</span>
        </a>
        
        <div class="d-flex align-items-center gap-3">
            <a href="index.php" class="text-white text-decoration-none">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="cart.php" class="text-white text-decoration-none">
                <i class="fas fa-shopping-cart"></i> Cart
            </a>
            <div class="dropdown">
                <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($user['username']); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item active" href="customerdash.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>

<div class="dashboard-container">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <h1>Welcome back, <?= htmlspecialchars($user['full_name']); ?>!</h1>
        <p>Manage your profile, orders, and shopping cart all in one place</p>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?= $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?= $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Dashboard Tabs -->
    <div class="dashboard-tabs">
        <ul class="nav nav-tabs" id="dashboardTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button">
                    <i class="fas fa-user"></i> My Profile
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="cart-tab" data-bs-toggle="tab" data-bs-target="#cart" type="button">
                    <i class="fas fa-shopping-cart"></i> My Cart
                    <?php if (count($cart_items) > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= count($cart_items); ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button">
                    <i class="fas fa-box"></i> My Orders
                    <?php if (count($orders) > 0): ?>
                        <span class="badge bg-primary rounded-pill"><?= count($orders); ?></span>
                    <?php endif; ?>
                </button>
            </li>
        </ul>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="dashboardTabContent">
        
        <!-- Profile Tab -->
        <div class="tab-pane fade show active" id="profile" role="tabpanel">
            <div class="row">
                <!-- Profile Information -->
                <div class="col-lg-8">
                    <div class="content-card">
                        <h3><i class="fas fa-user-edit"></i> Profile Information</h3>
                        
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($user['full_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']); ?>" disabled>
                                    <small class="text-muted">Username cannot be changed</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($user['phone']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Delivery Address</label>
                                <textarea class="form-control" name="address" rows="3" placeholder="Enter your delivery address"></textarea>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>

                    <!-- Change Password -->
                    <div class="content-card mt-4">
                        <h3><i class="fas fa-lock"></i> Change Password</h3>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password" minlength="8" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" name="confirm_password" minlength="8" required>
                                </div>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Account Summary -->
                <div class="col-lg-4">
                    <div class="content-card">
                        <h3><i class="fas fa-info-circle"></i> Account Summary</h3>
                        
                        <div class="profile-info">
                            <div class="info-item">
                                <div class="info-label">Account Type</div>
                                <div class="info-value"><?= ucfirst($user['role']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Member Since</div>
                                <div class="info-value"><?= date('M d, Y', strtotime($user['created_at'])); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Total Orders</div>
                                <div class="info-value"><?= count($orders); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Cart Items</div>
                                <div class="info-value"><?= count($cart_items); ?></div>
                            </div>
                        </div>
                        
                        <div class="d-grid mt-3">
                            <a href="logout.php" class="btn btn-danger">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cart Tab -->
        <div class="tab-pane fade" id="cart" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="content-card">
                        <h3><i class="fas fa-shopping-cart"></i> Shopping Cart (<?= count($cart_items); ?> items)</h3>
                        
                        <?php if (count($cart_items) > 0): ?>
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item">
                                    <img src="<?= htmlspecialchars($item['image_url'] ?? 'images/placeholder.png'); ?>" alt="<?= htmlspecialchars($item['name']); ?>">
                                    
                                    <div class="cart-item-details">
                                        <h5><?= htmlspecialchars($item['name']); ?></h5>
                                        <div class="cart-item-meta">
                                            <?php if ($item['color']): ?>
                                                <span><i class="fas fa-palette"></i> <?= htmlspecialchars($item['color']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($item['storage']): ?>
                                                <span><i class="fas fa-hdd"></i> <?= htmlspecialchars($item['storage']); ?></span>
                                            <?php endif; ?>
                                            <span><i class="fas fa-sort-numeric-up"></i> Qty: <?= $item['quantity']; ?></span>
                                        </div>
                                        <p class="mb-0"><strong>₱<?= number_format($item['price'], 2); ?></strong> × <?= $item['quantity']; ?> = <strong class="text-primary">₱<?= number_format($item['subtotal'], 2); ?></strong></p>
                                    </div>
                                    
                                    <div>
                                        <button class="btn btn-danger btn-sm" onclick="removeFromCart(<?= $item['cart_id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-shopping-cart"></i>
                                <h4>Your cart is empty</h4>
                                <p>Start shopping to add items to your cart!</p>
                                <a href="index.php" class="btn btn-primary mt-3">
                                    <i class="fas fa-store"></i> Continue Shopping
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (count($cart_items) > 0): ?>
                <div class="col-lg-4">
                    <div class="content-card">
                        <div class="cart-total">
                            <p class="mb-2">Total Amount</p>
                            <h4>₱<?= number_format($cart_total, 2); ?></h4>
                            <p class="small mb-0 mt-2">+ Shipping fees (calculated at checkout)</p>
                        </div>
                        
                        <div class="d-grid gap-2 mt-3">
                            <a href="checkout.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-credit-card"></i> Proceed to Checkout
                            </a>
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left"></i> Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Orders Tab -->
        <div class="tab-pane fade" id="orders" role="tabpanel">
            <div class="content-card">
                <h3><i class="fas fa-box"></i> Order History</h3>
                
                <?php if (count($orders) > 0): ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <div class="order-id">Order #<?= htmlspecialchars($order['order_id']); ?></div>
                                    <small class="text-muted">Placed on <?= date('M d, Y', strtotime($order['order_date'])); ?></small>
                                </div>
                                <span class="status-badge status-<?= strtolower($order['status']); ?>">
                                    <?= ucfirst($order['status']); ?>
                                </span>
                            </div>
                            
                            <div class="order-details">
                                <div class="order-detail-item">
                                    <span class="order-detail-label">Delivery Address</span>
                                    <span class="order-detail-value"><?= htmlspecialchars($order['address']); ?></span>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <button class="btn btn-outline-primary btn-sm" onclick="viewOrderDetails(<?= $order['order_id']; ?>)">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h4>No orders yet</h4>
                        <p>You haven't placed any orders. Start shopping now!</p>
                        <a href="index.php" class="btn btn-primary mt-3">
                            <i class="fas fa-store"></i> Start Shopping
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function removeFromCart(cartId) {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        window.location.href = `remove_from_cart.php?id=${cartId}`;
    }
}

function viewOrderDetails(orderId) {
    window.location.href = `order_details.php?id=${orderId}`;
}

// Auto-dismiss alerts after 5 seconds
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

</body>
</html>