<?php
session_start();
require_once __DIR__ . '/../backend/config/db_connect.php';

// Redirect to login if not logged in
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
        p.stock_quantity,
        p.condition_type
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $item_price = floatval($item['price'] ?? 0);
    $item_qty = intval($item['quantity'] ?? 0);
    $subtotal += $item_price * $item_qty;
}
$subtotal = floatval($subtotal);
$shipping = $subtotal > 50000 ? 0 : 200; // Free shipping over ₱50,000
$total = $subtotal + $shipping;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - R&M Apple Gadgets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: #f5f5f7;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .cart-container {
            max-width: 1200px;
            margin: 60px auto;
            padding: 0 20px;
        }

        .cart-title {
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 30px;
        }

        .cart-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }

        .cart-items {
            background: #fff;
            border-radius: 18px;
            padding: 30px;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 120px 1fr auto;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid #e8e8ed;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 120px;
            height: 120px;
            object-fit: contain;
            background: #f5f5f7;
            border-radius: 12px;
            padding: 10px;
        }

        .item-details h3 {
            font-size: 1.2em;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .item-specs {
            color: #6e6e73;
            font-size: 0.9em;
            margin-bottom: 15px;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .qty-btn {
            width: 32px;
            height: 32px;
            border: 1px solid #d2d2d7;
            background: #fff;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }

        .qty-btn:hover {
            background: #f5f5f7;
            border-color: #0071e3;
        }

        .qty-display {
            min-width: 40px;
            text-align: center;
            font-weight: 600;
        }

        .remove-btn {
            color: #ff3b30;
            font-size: 0.9em;
            cursor: pointer;
            margin-top: 10px;
            background: none;
            border: none;
        }

        .remove-btn:hover {
            text-decoration: underline;
        }

        .item-price-section {
            text-align: right;
        }

        .item-price {
            font-size: 1.3em;
            font-weight: 700;
            color: #1d1d1f;
        }

        .cart-summary {
            background: #fff;
            border-radius: 18px;
            padding: 30px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .summary-title {
            font-size: 1.5em;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f5f5f7;
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

        .checkout-btn {
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

        .checkout-btn:hover {
            background: #0077ed;
        }

        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 18px;
        }

        .empty-cart h2 {
            font-size: 2em;
            margin-bottom: 15px;
        }

        .empty-cart p {
            color: #6e6e73;
            margin-bottom: 30px;
        }

        .continue-shopping {
            background: #0071e3;
            color: #fff;
            padding: 12px 30px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }

        .continue-shopping:hover {
            background: #0077ed;
            color: #fff;
        }

        .badge-condition {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: 600;
            margin-left: 8px;
        }

        .badge-refurbished {
            background: #34c759;
            color: white;
        }

        .badge-new {
            background: #000;
            color: white;
        }

        @media (max-width: 992px) {
            .cart-content {
                grid-template-columns: 1fr;
            }

            .cart-summary {
                position: relative;
                top: 0;
            }

            .cart-item {
                grid-template-columns: 100px 1fr;
            }

            .item-price-section {
                grid-column: 2;
                text-align: left;
                margin-top: 10px;
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
        <nav>
            <ul>
                <li><a href="../index.php">Home</a></li>
                <li><a href="iphone.php">iPhone</a></li>
                <li><a href="ipad.php">iPad</a></li>
                <li><a href="macbook.php">MacBook</a></li>
                <li><a href="support.php">Support</a></li>
            </ul>
        </nav>
        <div class="icons">
            <span>🔍</span>
            <span style="position: relative;">
                🛒
                <?php if (count($cart_items) > 0): ?>
                    <span id="cart-badge" style="position: absolute; top: -8px; right: -8px; background: #ff3b30; color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 0.7em; display: flex; align-items: center; justify-content: center;">
                        <?= array_sum(array_column($cart_items, 'quantity')) ?>
                    </span>
                <?php endif; ?>
            </span>
            <?php if (isset($_SESSION['username'])): ?>
            <div class="dropdown d-inline-block ms-2">
                <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                <?= htmlspecialchars($_SESSION['username']); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                <li>
                    <a class="dropdown-item" href="customerdash.php">
                    <i class="fas fa-tachometer-alt"></i> My Dashboard
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="../backend/auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <div class="cart-container">
        <h1 class="cart-title">Shopping Cart</h1>

        <?php if (empty($cart_items)): ?>
            <!-- Empty Cart -->
            <div class="empty-cart">
                <h2>Your cart is empty</h2>
                <p>Start shopping to add items to your cart!</p>
                <a href="../index.php" class="continue-shopping">Continue Shopping</a>
            </div>
        <?php else: ?>
            <!-- Cart Content -->
            <div class="cart-content">
                <!-- Cart Items -->
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item" data-cart-id="<?= $item['cart_id'] ?>" data-price="<?= $item['price'] ?>">
                            <img src="../<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="item-image">
                            
                            <div class="item-details">
                                <h3>
                                    <?= htmlspecialchars($item['name']) ?>
                                    <?php if ($item['condition_type'] === 'refurbished'): ?>
                                        <span class="badge-condition badge-refurbished">Refurbished</span>
                                    <?php else: ?>
                                        <span class="badge-condition badge-new">New</span>
                                    <?php endif; ?>
                                </h3>
                                <div class="item-specs">
                                    Color: <?= htmlspecialchars($item['color']) ?> | Storage: <?= htmlspecialchars($item['storage']) ?>
                                </div>
                                <div class="quantity-control">
                                    <button class="qty-btn" onclick="updateQuantity(<?= $item['cart_id'] ?>, -1)">−</button>
                                    <span class="qty-display" id="qty-<?= $item['cart_id'] ?>"><?= $item['quantity'] ?></span>
                                    <button class="qty-btn" onclick="updateQuantity(<?= $item['cart_id'] ?>, 1)">+</button>
                                </div>
                                <button class="remove-btn" onclick="removeItem(<?= $item['cart_id'] ?>)">Remove</button>
                            </div>

                            <div class="item-price-section">
                                <div class="item-price" id="price-<?= $item['cart_id'] ?>">
                                    ₱<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Cart Summary -->
                <div class="cart-summary">
                    <h2 class="summary-title">Order Summary</h2>
                    
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span id="subtotal">₱<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span id="shipping">₱<?= number_format($shipping, 2) ?></span>
                    </div>
                    <?php if ($shipping === 0): ?>
                        <div style="color: #34c759; font-size: 0.9em; margin-top: 5px;">🎉 Free shipping!</div>
                    <?php endif; ?>
                    
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span id="total">₱<?= number_format($total, 2) ?></span>
                    </div>

                    <button class="checkout-btn" onclick="proceedToCheckout()">Proceed to Checkout</button>
                    <a href="../index.php" style="display: block; text-align: center; margin-top: 15px; text-decoration: none; color: #0071e3; font-weight: 500;">Continue Shopping</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update quantity
        async function updateQuantity(cartId, change) {
            const qtyElement = document.getElementById(`qty-${cartId}`);
            const currentQty = parseInt(qtyElement.textContent);
            const newQty = currentQty + change;

            if (newQty < 1) return;

            try {
                const response = await fetch('../backend/api/update_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cart_id: cartId, quantity: newQty })
                });

                const result = await response.json();

                if (result.success) {
                    qtyElement.textContent = newQty;
                    
                    // Update item price
                    const cartItem = document.querySelector(`[data-cart-id="${cartId}"]`);
                    const itemPrice = parseFloat(cartItem.dataset.price);
                    document.getElementById(`price-${cartId}`).textContent = `₱${(itemPrice * newQty).toLocaleString('en-PH', {minimumFractionDigits: 2})}`;
                    
                    // Recalculate totals
                    recalculateTotals();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to update cart');
            }
        }

        // Remove item
        async function removeItem(cartId) {
            if (!confirm('Remove this item from cart?')) return;

            try {
                const response = await fetch('../backend/api/remove_from_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cart_id: cartId })
                });

                const result = await response.json();

                if (result.success) {
                    // Remove item from DOM
                    document.querySelector(`[data-cart-id="${cartId}"]`).remove();
                    
                    // Update cart badge
                    const badge = document.getElementById('cart-badge');
                    if (badge) {
                        badge.textContent = result.cart_count;
                        if (result.cart_count === 0) badge.remove();
                    }

                    // Recalculate or reload if empty
                    const remainingItems = document.querySelectorAll('.cart-item').length;
                    if (remainingItems === 0) {
                        location.reload();
                    } else {
                        recalculateTotals();
                    }
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to remove item');
            }
        }

        // Recalculate totals
        function recalculateTotals() {
            let subtotal = 0;
            
            document.querySelectorAll('.cart-item').forEach(item => {
                const price = parseFloat(item.dataset.price) || 0;
                const qty = parseInt(item.querySelector('.qty-display').textContent) || 0;
                subtotal += price * qty;
            });

            const shipping = subtotal > 50000 ? 0 : 200;
            const total = subtotal + shipping;

            document.getElementById('subtotal').textContent = `₱${(subtotal || 0).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            document.getElementById('shipping').textContent = `₱${(shipping || 0).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            document.getElementById('total').textContent = `₱${(total || 0).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        }

        // Proceed to checkout
        function proceedToCheckout() {
            window.location.href = 'checkout.php';
        }
    </script>
</body>
</html>