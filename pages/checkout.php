<?php
session_start();
require_once __DIR__ . '/../backend/config/db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch cart items
$stmt = $conn->prepare("
    SELECT 
        c.id as cart_id,
        c.quantity,
        c.color,
        c.storage,
        p.id as product_id,
        p.name,
        p.price,
        p.image_url,
        p.stock_quantity
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Redirect if cart is empty
if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += floatval($item['price']) * intval($item['quantity']);
}
$shipping = $subtotal > 50000 ? 0 : 200;
$total = $subtotal + $shipping;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - R&M Apple Gadgets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f5f7;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .checkout-container {
            max-width: 1200px;
            margin: 60px auto;
            padding: 0 20px;
        }

        .checkout-title {
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 30px;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }

        .checkout-form {
            background: #fff;
            border-radius: 18px;
            padding: 40px;
        }

        .section-title {
            font-size: 1.5em;
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f5f5f7;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1d1d1f;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d2d2d7;
            border-radius: 10px;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #0071e3;
            box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .payment-methods {
            display: grid;
            gap: 12px;
            margin-top: 15px;
        }

        .payment-option {
            border: 2px solid #d2d2d7;
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .payment-option:hover {
            border-color: #0071e3;
            background: #f5f5f7;
        }

        .payment-option input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .payment-option.selected {
            border-color: #0071e3;
            background: #e8f4fd;
        }

        .order-summary {
            background: #fff;
            border-radius: 18px;
            padding: 30px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .summary-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #f5f5f7;
        }

        .summary-item:last-of-type {
            border-bottom: 2px solid #f5f5f7;
            margin-bottom: 15px;
        }

        .item-image {
            width: 60px;
            height: 60px;
            object-fit: contain;
            background: #f5f5f7;
            border-radius: 8px;
            padding: 5px;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            font-size: 0.95em;
            margin-bottom: 4px;
        }

        .item-specs {
            font-size: 0.85em;
            color: #6e6e73;
        }

        .item-price {
            font-weight: 600;
            color: #1d1d1f;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 1em;
        }

        .summary-row.total {
            border-top: 2px solid #f5f5f7;
            padding-top: 20px;
            margin-top: 15px;
            font-size: 1.3em;
            font-weight: 700;
        }

        .place-order-btn {
            width: 100%;
            background: #0071e3;
            color: #fff;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 1.1em;
            font-weight: 600;
            margin-top: 20px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .place-order-btn:hover:not(:disabled) {
            background: #0077ed;
        }

        .place-order-btn:disabled {
            background: #d2d2d7;
            cursor: not-allowed;
        }

        .error-message {
            background: #fff3cd;
            border: 1px solid #ffecb5;
            color: #856404;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
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
        }

        .navbar .logo img {
            width: 20px;
            height: 20px;
        }

        .icons {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 1.1em;
            color: #f5f5f7;
        }

        @media (max-width: 992px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: relative;
                top: 0;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <header class="navbar">
        <div class="logo" onclick="window.location.href='index.php'">
            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.53 4.09l-.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z'/%3E%3C/svg%3E" alt="Apple">
            <span>R&M Apple Gadgets</span>
        </div>
        <div class="icons">
            <?php if (isset($_SESSION['username'])): ?>
                <span>👤 <?= htmlspecialchars($_SESSION['username']); ?></span>
            <?php endif; ?>
        </div>
    </header>

    <div class="checkout-container">
        <h1 class="checkout-title">Checkout</h1>

        <div class="checkout-grid">
            <!-- Checkout Form -->
            <div class="checkout-form">
                <div class="error-message" id="errorMessage"></div>

                <form id="checkoutForm">
                    <!-- Shipping Information -->
                    <h2 class="section-title">Shipping Information</h2>
                    
                    <div class="form-group">
                        <label for="fullName">Full Name *</label>
                        <input type="text" class="form-control" id="fullName" name="full_name" 
                               value="<?= htmlspecialchars($user['full_name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?= htmlspecialchars($user['phone']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="address">Complete Address *</label>
                        <textarea class="form-control" id="address" name="address" rows="3" 
                                  placeholder="Street, Barangay, City, Province, ZIP Code" required></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City *</label>
                            <input type="text" class="form-control" id="city" name="city" required>
                        </div>
                        <div class="form-group">
                            <label for="zipCode">ZIP Code *</label>
                            <input type="text" class="form-control" id="zipCode" name="zip_code" required>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <h2 class="section-title" style="margin-top: 40px;">Payment Method</h2>
                    
                    <div class="payment-methods">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="Cash on Delivery" checked>
                            <div>
                                <strong>Cash on Delivery</strong>
                                <div style="font-size: 0.9em; color: #6e6e73;">Pay when you receive your order</div>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="GCash">
                            <div>
                                <strong>GCash</strong>
                                <div style="font-size: 0.9em; color: #6e6e73;">Mobile wallet payment</div>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="Bank Transfer">
                            <div>
                                <strong>Bank Transfer</strong>
                                <div style="font-size: 0.9em; color: #6e6e73;">Direct bank deposit</div>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="Credit Card">
                            <div>
                                <strong>Credit/Debit Card</strong>
                                <div style="font-size: 0.9em; color: #6e6e73;">Visa, Mastercard accepted</div>
                            </div>
                        </label>
                    </div>

                    <div class="form-group" style="margin-top: 30px;">
                        <label for="notes">Order Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Any special instructions for your order?"></textarea>
                    </div>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <h2 class="section-title">Order Summary</h2>

                <?php foreach ($cart_items as $item): ?>
                    <div class="summary-item">
                        <img src="../<?= htmlspecialchars($item['image_url']) ?>" 
                             alt="<?= htmlspecialchars($item['name']) ?>" 
                             class="item-image">
                        <div class="item-info">
                            <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="item-specs">
                                <?= htmlspecialchars($item['color']) ?> | 
                                <?= htmlspecialchars($item['storage']) ?> | 
                                Qty: <?= $item['quantity'] ?>
                            </div>
                        </div>
                        <div class="item-price">
                            ₱<?= number_format($item['price'] * $item['quantity'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>₱<?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping:</span>
                    <span>₱<?= number_format($shipping, 2) ?></span>
                </div>
                <?php if ($shipping === 0): ?>
                    <div style="color: #34c759; font-size: 0.9em; margin-top: 5px;">🎉 Free shipping!</div>
                <?php endif; ?>

                <div class="summary-row total">
                    <span>Total:</span>
                    <span>₱<?= number_format($total, 2) ?></span>
                </div>

                <button type="button" class="place-order-btn" onclick="placeOrder()">
                    Place Order
                </button>

                <a href="cart.php" style="display: block; text-align: center; margin-top: 15px; 
                         text-decoration: none; color: #0071e3; font-weight: 500;">
                    « Back to Cart
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Payment method selection highlighting
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.payment-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
            });
        });

        // Initialize first payment option as selected
        document.querySelector('.payment-option').classList.add('selected');

        // Place order function
        async function placeOrder() {
            const form = document.getElementById('checkoutForm');
            const errorMessage = document.getElementById('errorMessage');
            const btn = document.querySelector('.place-order-btn');

            // Validate form
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Get form data
            const formData = new FormData(form);
            const data = {
                full_name: formData.get('full_name'),
                phone: formData.get('phone'),
                email: formData.get('email'),
                address: formData.get('address') + ', ' + formData.get('city') + ', ' + formData.get('zip_code'),
                payment_method: formData.get('payment_method'),
                notes: formData.get('notes')
            };

            // Disable button
            btn.disabled = true;
            btn.textContent = 'Processing...';
            errorMessage.style.display = 'none';

            try {
                const response = await fetch('../backend/api/process_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    // Redirect to order confirmation
                    window.location.href = 'order_confirmation.php?order_id=' + result.order_id;
                } else {
                    errorMessage.textContent = result.message || 'Failed to place order. Please try again.';
                    errorMessage.style.display = 'block';
                    btn.disabled = false;
                    btn.textContent = 'Place Order';
                }
            } catch (error) {
                console.error('Error:', error);
                errorMessage.textContent = 'An error occurred. Please try again.';
                errorMessage.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'Place Order';
            }
        }
    </script>
</body>
</html>