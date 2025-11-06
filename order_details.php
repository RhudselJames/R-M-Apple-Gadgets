<?php
session_start();
require_once 'db_connect.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$order_id) {
    header('Location: customerdash.php');
    exit;
}

// Fetch order details
$stmt = $conn->prepare("
    SELECT o.*, u.full_name, u.email, u.phone
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.order_id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if order exists and belongs to the user
if (!$order) {
    header('Location: customerdash.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        oi.order_item_id,
        oi.quantity,
        oi.price,
        oi.color,
        oi.storage,
        p.id AS product_id,
        p.name,
        p.image_url,
        p.category
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate subtotal (excluding shipping)
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += floatval($item['price']) * intval($item['quantity']);
}
$shipping = $order['total_amount'] - $subtotal;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - R&M Apple Gadgets</title>
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
        
        .order-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #0071e3;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            transition: gap 0.3s;
        }
        
        .back-button:hover {
            gap: 12px;
            color: #0077ed;
        }
        
        .order-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .order-header h1 {
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .order-meta {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            font-size: 0.95em;
            opacity: 0.95;
        }
        
        .order-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .order-item {
            display: flex;
            gap: 20px;
            padding: 20px;
            border: 1px solid #e5e5e7;
            border-radius: 12px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .order-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .order-item img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 8px;
            background: #f5f5f7;
            padding: 10px;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-details h5 {
            font-size: 1.1em;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .item-specs {
            display: flex;
            gap: 15px;
            font-size: 0.9em;
            color: #6e6e73;
            margin-bottom: 10px;
        }
        
        .item-price {
            text-align: right;
        }
        
        .item-price .unit-price {
            font-size: 0.9em;
            color: #6e6e73;
            margin-bottom: 5px;
        }
        
        .item-price .total-price {
            font-size: 1.2em;
            font-weight: 700;
            color: #1d1d1f;
        }
        
        .info-section {
            margin-bottom: 25px;
        }
        
        .info-label {
            font-size: 0.85em;
            color: #6e6e73;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-size: 1.05em;
            font-weight: 500;
            color: #1d1d1f;
        }
        
        .status-badge {
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending {
            background: #fff4e6;
            color: #d97706;
        }
        
        .status-confirmed, .status-processing {
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
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 1em;
        }
        
        .summary-row.subtotal {
            border-bottom: 1px solid #e5e5e7;
        }
        
        .summary-row.total {
            border-top: 2px solid #e5e5e7;
            padding-top: 20px;
            margin-top: 10px;
            font-size: 1.4em;
            font-weight: 700;
        }
        
        .summary-row.total .amount {
            color: #0071e3;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 30px;
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
        
        .btn-outline {
            background: transparent;
            border: 2px solid #0071e3;
            color: #0071e3;
            border-radius: 10px;
            padding: 12px 28px;
            font-weight: 500;
            transition: 0.3s;
        }
        
        .btn-outline:hover {
            background: #0071e3;
            color: white;
        }
        
        .timeline {
            position: relative;
            padding-left: 40px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e5e5e7;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 25px;
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-dot {
            position: absolute;
            left: -33px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #e5e5e7;
        }
        
        .timeline-item.active .timeline-dot {
            background: #0071e3;
            box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.2);
        }
        
        .timeline-item.completed .timeline-dot {
            background: #34c759;
        }
        
        .timeline-label {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .timeline-date {
            font-size: 0.85em;
            color: #6e6e73;
        }
        
        @media (max-width: 992px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .order-item {
                flex-direction: column;
            }
            
            .order-item img {
                width: 100%;
                height: 200px;
            }
            
            .item-price {
                text-align: left;
            }
            
            .order-meta {
                flex-direction: column;
                gap: 10px;
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
                    <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="customerdash.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>

<div class="order-container">
    <a href="customerdash.php" class="back-button">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
    
    <!-- Order Header -->
    <div class="order-header">
        <h1><i class="fas fa-receipt"></i> Order #<?= htmlspecialchars($order_id); ?></h1>
        <div class="order-meta">
            <div class="order-meta-item">
                <i class="fas fa-calendar"></i>
                <span>Placed on <?= date('F d, Y', strtotime($order['order_date'])); ?></span>
            </div>
            <div class="order-meta-item">
                <i class="fas fa-info-circle"></i>
                <span class="status-badge status-<?= strtolower($order['status']); ?>">
                    <?= ucfirst($order['status']); ?>
                </span>
            </div>
            <div class="order-meta-item">
                <i class="fas fa-credit-card"></i>
                <span><?= htmlspecialchars($order['payment_method']); ?></span>
            </div>
        </div>
    </div>

    <div class="content-grid">
        <!-- Main Content -->
        <div>
            <!-- Order Items -->
            <div class="content-card">
                <h3><i class="fas fa-box"></i> Order Items (<?= count($order_items); ?>)</h3>
                
                <?php foreach ($order_items as $item): ?>
                    <div class="order-item">
                        <img src="<?= htmlspecialchars($item['image_url'] ?? 'images/placeholder.png'); ?>" 
                             alt="<?= htmlspecialchars($item['name']); ?>">
                             <?php $reviewCheck = $conn->prepare("SELECT COUNT(*) FROM reviews WHERE product_id = ? AND user_id = ?");
                                $reviewCheck->execute([$item['product_id'], $user_id]);
                                $hasReviewed = $reviewCheck->fetchColumn() > 0; ?>
                              <?php if (strtolower($order['status']) === 'delivered' && !$hasReviewed): ?>
                                <div class="mt-2">
                                    <a href="write_review.php?order_id=<?= $order_id ?>&product_id=<?= $item['product_id'] ?>" 
                                    class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-star"></i> Write a Review
                                    </a>
                                </div>
                            <?php elseif ($hasReviewed): ?>
                                <p class="text-success mt-2"><i class="fas fa-check-circle"></i> Reviewed</p>
                            <?php endif; ?>
                        <div class="item-details">
                            <h5><?= htmlspecialchars($item['name']); ?></h5>
                            <div class="item-specs">
                                <?php if (!empty($item['color'])): ?>
                                    <span><i class="fas fa-palette"></i> <?= htmlspecialchars($item['color']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($item['storage'])): ?>
                                    <span><i class="fas fa-hdd"></i> <?= htmlspecialchars($item['storage']); ?></span>
                                <?php endif; ?>
                                <span><i class="fas fa-sort-numeric-up"></i> Qty: <?= $item['quantity']; ?></span>
                            </div>
                        </div>
                        
                        <div class="item-price">
                            <div class="unit-price">₱<?= number_format($item['price'], 2); ?> each</div>
                            <div class="total-price">₱<?= number_format($item['price'] * $item['quantity'], 2); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Delivery Information -->
            <div class="content-card">
                <h3><i class="fas fa-truck"></i> Delivery Information</h3>
                
                <div class="info-section">
                    <div class="info-label">Recipient</div>
                    <div class="info-value"><?= htmlspecialchars($order['full_name']); ?></div>
                </div>
                
                <div class="info-section">
                    <div class="info-label">Phone Number</div>
                    <div class="info-value"><?= htmlspecialchars($order['phone']); ?></div>
                </div>
                
                <div class="info-section">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?= htmlspecialchars($order['email']); ?></div>
                </div>
                
                <div class="info-section">
                    <div class="info-label">Delivery Address</div>
                    <div class="info-value"><?= htmlspecialchars($order['address']); ?></div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <!-- Order Summary -->
            <div class="content-card">
                <h3><i class="fas fa-file-invoice-dollar"></i> Order Summary</h3>
                
                <div class="summary-row subtotal">
                    <span>Subtotal:</span>
                    <span>₱<?= number_format($subtotal, 2); ?></span>
                </div>
                
                <div class="summary-row">
                    <span>Shipping Fee:</span>
                    <span><?= $shipping > 0 ? '₱' . number_format($shipping, 2) : 'FREE'; ?></span>
                </div>
                
                <div class="summary-row total">
                    <span>Total Amount:</span>
                    <span class="amount">₱<?= number_format($order['total_amount'], 2); ?></span>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-primary w-100" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Order
                    </button>
                    <a href="index.php" class="btn btn-outline w-100 text-center text-decoration-none">
                        <i class="fas fa-store"></i> Continue Shopping
                    </a>
                </div>
            </div>

            <!-- Order Timeline -->
            <div class="content-card">
                <h3><i class="fas fa-history"></i> Order Timeline</h3>
                
                <div class="timeline">
                    <div class="timeline-item <?= in_array(strtolower($order['status']), ['pending', 'processing', 'confirmed', 'shipped', 'delivered']) ? 'completed' : ''; ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-label">Order Placed</div>
                        <div class="timeline-date"><?= date('M d, Y h:i A', strtotime($order['order_date'])); ?></div>
                    </div>
                    
                    <div class="timeline-item <?= in_array(strtolower($order['status']), ['processing', 'confirmed', 'shipped', 'delivered']) ? 'completed' : (strtolower($order['status']) === 'pending' ? 'active' : ''); ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-label">Order Confirmed</div>
                        <div class="timeline-date">
                            <?= in_array(strtolower($order['status']), ['processing', 'confirmed', 'shipped', 'delivered']) ? 'Processing' : 'Pending confirmation'; ?>
                        </div>
                    </div>
                    
                    <div class="timeline-item <?= in_array(strtolower($order['status']), ['shipped', 'delivered']) ? 'completed' : (in_array(strtolower($order['status']), ['processing', 'confirmed']) ? 'active' : ''); ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-label">Order Shipped</div>
                        <div class="timeline-date">
                            <?= in_array(strtolower($order['status']), ['shipped', 'delivered']) ? 'On the way' : 'Waiting for shipment'; ?>
                        </div>
                    </div>
                    
                    <div class="timeline-item <?= strtolower($order['status']) === 'delivered' ? 'completed' : (strtolower($order['status']) === 'shipped' ? 'active' : ''); ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-label">Order Delivered</div>
                        <div class="timeline-date">
                            <?= strtolower($order['status']) === 'delivered' ? 'Completed' : 'Estimated delivery'; ?>
                        </div>
                    </div>
                    
                    <?php if (strtolower($order['status']) === 'cancelled'): ?>
                        <div class="timeline-item active">
                            <div class="timeline-dot" style="background: #dc2626;"></div>
                            <div class="timeline-label" style="color: #dc2626;">Order Cancelled</div>
                            <div class="timeline-date">This order was cancelled</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Need Help -->
            <div class="content-card">
                <h3><i class="fas fa-question-circle"></i> Need Help?</h3>
                <p style="color: #6e6e73; margin-bottom: 20px;">
                    If you have any questions about your order, please contact our customer support.
                </p>
                <div class="d-grid gap-2">
                    <a href="mailto:support@rmapplegadgets.com" class="btn btn-outline text-decoration-none text-center">
                        <i class="fas fa-envelope"></i> Email Support
                    </a>
                    <a href="tel:+639123456789" class="btn btn-outline text-decoration-none text-center">
                        <i class="fas fa-phone"></i> Call Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>