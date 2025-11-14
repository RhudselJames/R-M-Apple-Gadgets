<?php
session_start();
require_once __DIR__ . '/../backend/config/db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    header('Location: ../../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch order details
$stmt = $conn->prepare("
    SELECT * FROM orders 
    WHERE order_id = ? AND user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: ../../index.php');
    exit;
}

// Fetch order items
$stmt = $conn->prepare("
    SELECT 
        oi.*,
        p.name,
        p.image_url
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - R&M Apple Gadgets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f5f7;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .confirmation-container {
            max-width: 900px;
            margin: 60px auto;
            padding: 0 20px;
        }

        .success-card {
            background: #fff;
            border-radius: 18px;
            padding: 50px 40px;
            text-align: center;
            margin-bottom: 30px;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #34c759;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 3em;
            color: white;
        }

        .success-title {
            font-size: 2.2em;
            font-weight: 700;
            color: #1d1d1f;
            margin-bottom: 12px;
        }

        .success-message {
            font-size: 1.1em;
            color: #6e6e73;
            margin-bottom: 25px;
        }

        .order-number {
            font-size: 1.3em;
            font-weight: 600;
            color: #0071e3;
            background: #e8f4fd;
            padding: 15px 25px;
            border-radius: 12px;
            display: inline-block;
        }

        .order-details-card {
            background: #fff;
            border-radius: 18px;
            padding: 40px;
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.5em;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f5f5f7;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f7;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #6e6e73;
            font-weight: 500;
        }

        .info-value {
            font-weight: 600;
            color: #1d1d1f;
            text-align: right;
        }

        .order-item {
            display: flex;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid #f5f5f7;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 80px;
            height: 80px;
            object-fit: contain;
            background: #f5f5f7;
            border-radius: 12px;
            padding: 8px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            font-size: 1.1em;
            margin-bottom: 8px;
        }

        .item-qty {
            color: #6e6e73;
            font-size: 0.95em;
        }

        .item-price {
            font-weight: 700;
            font-size: 1.2em;
            color: #1d1d1f;
            text-align: right;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
        }

        .btn-primary-custom {
            background: #0071e3;
            color: #fff;
            border: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-size: 1.05em;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }

        .btn-primary-custom:hover {
            background: #0077ed;
            color: #fff;
        }

        .btn-secondary-custom {
            background: #fff;
            color: #0071e3;
            border: 2px solid #0071e3;
            padding: 14px 32px;
            border-radius: 12px;
            font-size: 1.05em;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-secondary-custom:hover {
            background: #0071e3;
            color: #fff;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 40px;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(20px);
        }

        .navbar .logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1em;
            font-weight: 500;
            color: #f5f5f7;
            cursor: pointer;
            text-decoration: none;
        }

        .navbar .logo img {
            width: 20px;
            height: 20px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        @media (max-width: 768px) {
            .order-item {
                flex-direction: column;
            }

            .item-price {
                text-align: left;
                margin-top: 10px;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <header class="navbar">
        <a href="../index.php" class="logo">
            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.53 4.09l-.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z'/%3E%3C/svg%3E" alt="Apple">
            <span>R&M Apple Gadgets</span>
        </a>
        <div style="color: #f5f5f7;">
            <?php if (isset($_SESSION['username'])): ?>
                👤 <?= htmlspecialchars($_SESSION['username']); ?>
            <?php endif; ?>
        </div>
    </header>

    <div class="confirmation-container">
        <!-- Success Message -->
        <div class="success-card">
            <div class="success-icon">✓</div>
            <h1 class="success-title">Order Placed Successfully!</h1>
            <p class="success-message">Thank you for your order. We'll send you a confirmation email shortly.</p>
            <div class="order-number">Order #<?= htmlspecialchars($order_id) ?></div>
        </div>

        <!-- Order Summary -->
        <div class="order-details-card">
            <h2 class="section-title">Order Details</h2>
            
            <div class="info-row">
                <span class="info-label">Order Date:</span>
                <span class="info-value"><?= date('F j, Y', strtotime($order['order_date'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    <span class="status-badge status-pending"><?= htmlspecialchars($order['status']) ?></span>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Payment Method:</span>
                <span class="info-value"><?= htmlspecialchars($order['payment_method']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Delivery Address:</span>
                <span class="info-value"><?= htmlspecialchars($order['address']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Total Amount:</span>
                <span class="info-value" style="font-size: 1.3em; color: #0071e3;">
                    ₱<?= number_format($order['total_amount'], 2) ?>
                </span>
            </div>
        </div>

        <!-- Order Items -->
        <div class="order-details-card">
            <h2 class="section-title">Order Items</h2>
            
            <?php foreach ($order_items as $item): ?>
                <div class="order-item">
                    <img src="../<?= htmlspecialchars($item['image_url']) ?>" 
                         alt="<?= htmlspecialchars($item['name']) ?>" 
                         class="item-image">
                    <div class="item-details">
                        <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="item-qty">Quantity: <?= $item['quantity'] ?></div>
                    </div>
                    <div class="item-price">
                        ₱<?= number_format($item['price'] * $item['quantity'], 2) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="../index.php" class="btn-primary-custom">Continue Shopping</a>
            <a href="order_details.php?id=<?= htmlspecialchars($order_id) ?>" class="btn-secondary-custom">View Order Status</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>