<?php
session_start();
require_once __DIR__ . '/../backend/config/db_connect.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Helper function for color hex codes
function getColorHex($color) {
    $colorMap = [
        'deep blue'=> '#1a2e45', 'cosmic orange' => '#ff8c30', 'silver' => '#f5f5f7',
        'desert titanium' => '#d6c6b3', 'natural titanium' => '#c8c0b3',
        'white titanium' => '#f5f5f0', 'black titanium' => '#1d1d1f',
        'blue titanium' => '#4b5b78', 'blue' => '#99c7f2', 'pink' => '#ffd6e8',
        'teal' => '#bfe7e0', 'yellow' => '#fff4b1', 'midnight' => '#1d1d1f',
        'starlight' => '#f5e4ca', 'green' => '#c9dcd4', 'purple' => '#dcc5da',
        'red' => '#f54542', 'white' => '#f5f5f0', 'black' => '#1d1d1f',
        'sky blue' => '#8ec6f9', 'light gold' => '#e8d9b8', 'cloud white' => '#f5f5f5',
        'space black' => '#1d1d1f'
    ];
    return $colorMap[strtolower(trim($color))] ?? '#ddd';
}

// Fetch cart items with product details
$stmt = $conn->prepare("
    SELECT 
        c.id as cart_id,
        c.quantity,
        c.color,
        c.storage,
        c.price as cart_price, -- Use the saved price from the cart
        p.id as product_id,
        p.name,
        p.image_url,
        p.price as product_base_price, -- Fallback price
        p.condition_type,
        p.category,
        p.unified_memory
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build a map of base product names to fetch related variants
$category_base_names = [];
foreach ($cart_items as $item) {
    $product_name = $item['name'];
    $category = $item['category'];
    $base_name = preg_replace('/\s*-\s*(128GB|256GB|512GB|1TB|2TB|64GB).*$/i', '', $product_name);
    $base_name = preg_replace('/\s*\(.*\).*$/i', '', $base_name);
    $base_name = trim($base_name);
    if (!isset($category_base_names[$category])) { $category_base_names[$category] = []; }
    $category_base_names[$category][$base_name] = true;
}

// Logic to fetch all options for all product types
$product_options = [];
foreach ($category_base_names as $category => $base_names) {
    foreach (array_keys($base_names) as $base_name) {
        $master_product_stmt = $conn->prepare("SELECT color, storage, price FROM products WHERE category = ? AND name = ? AND status = 'active' LIMIT 1");
        $master_product_stmt->execute([$category, $base_name]);
        $master_product = $master_product_stmt->fetch(PDO::FETCH_ASSOC);
        $colors = []; $storages = []; $price_map = [];
        if ($master_product) {
            $colors = !empty($master_product['color']) ? explode(',', $master_product['color']) : [];
            $storages = !empty($master_product['storage']) ? explode(',', $master_product['storage']) : [];
            $base_price = $master_product['price'];
            $search_pattern = $base_name . '%';
            $variants_stmt = $conn->prepare("SELECT color, storage, price FROM products WHERE category = ? AND name LIKE ? AND status = 'active' AND LOCATE(',', color) = 0 AND LOCATE(',', storage) = 0");
            $variants_stmt->execute([$category, $search_pattern]);
            $variants = $variants_stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($variants) > 0) {
                foreach ($variants as $variant) { $price_map[trim($variant['storage']) . '_' . trim($variant['color'])] = $variant['price']; }
            } else {
                $base_storage_val = 128;
                if (!empty($storages)) {
                    preg_match('/(\d+)(GB|TB)/i', trim($storages[0]), $matches);
                    if ($matches) { $base_storage_val = $matches[1] * (strtoupper($matches[2] ?? 'GB') == 'TB' ? 1024 : 1); }
                }
                foreach ($storages as $storage) {
                    $storage = trim($storage);
                    preg_match('/(\d+)(GB|TB)/i', $storage, $matches);
                    $storage_val = isset($matches[1]) ? $matches[1] * (strtoupper(isset($matches[2]) ? $matches[2] : 'GB') == 'TB' ? 1024 : 1) : $base_storage_val;
                    $stepDifference = ($storage_val - $base_storage_val) / 128;
                    $newPrice = $base_price + (max(0, $stepDifference) * 5000);
                    foreach ($colors as $color) { $price_map[trim($storage) . '_' . trim($color)] = $newPrice; }
                }
            }
        }
        usort($storages, function($a, $b) {
            preg_match('/(\d+)(GB|TB)/i', trim($a), $matchesA);
            preg_match('/(\d+)(GB|TB)/i', trim($b), $matchesB);
            if (empty($matchesA) || empty($matchesB)) return 0;
            $valA = $matchesA[1] * (strtoupper($matchesA[2] ?? 'GB') == 'TB' ? 1024 : 1);
            $valB = $matchesB[1] * (strtoupper($matchesB[2] ?? 'GB') == 'TB' ? 1024 : 1);
            return $valA <=> $valB;
        });
        $product_options[$category][$base_name] = ['colors' => array_map('trim', $colors), 'storages' => array_map('trim', $storages), 'price_map' => $price_map];
    }
}

// Calculate totals using the saved cart price
$subtotal = 0;
foreach ($cart_items as $item) {
    $item_price = floatval($item['cart_price'] ?? $item['product_base_price'] ?? 0);
    $item_qty = intval($item['quantity'] ?? 0);
    $subtotal += $item_price * $item_qty;
}
$shipping = $subtotal > 50000 ? 0 : 200;
$total = $subtotal + $shipping;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - R&M Apple Gadgets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* --- Styles for Page Layout --- */
    body { background: #f5f5f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
    .cart-container { max-width: 1200px; margin: 60px auto; padding: 0 20px; }
    .cart-title { font-size: 2.5em; font-weight: 700; margin-bottom: 30px; }
    .cart-content { display: grid; grid-template-columns: 1fr 400px; gap: 30px; }
    .cart-items { background: #fff; border-radius: 18px; padding: 30px; }
    .cart-item { display: grid; grid-template-columns: 120px 1fr; gap: 20px; padding: 20px 0; border-bottom: 1px solid #e8e8ed; }
    .cart-item:last-child { border-bottom: none; }
    .item-image { width: 120px; height: 120px; object-fit: contain; background: #f5f5f7; border-radius: 12px; padding: 10px; }
    
    /* --- STYLES FOR THE REQUESTED LAYOUT --- */
    .item-details {
        display: grid;
        grid-template-columns: 1fr auto; /* Main info on left, price/actions on right */
        gap: 20px;
    }
    .item-info {
        display: flex;
        flex-direction: column;
    }
    .item-info h3 {
        font-size: 1.2em;
        font-weight: 600;
        margin-bottom: 15px; 
    }
    .item-price-actions {
        text-align: right;
        display: flex;
        flex-direction: column;
        justify-content: space-between; /* Pushes price to top, actions to bottom */
        align-items: flex-end;
    }
    .item-price {
        font-size: 1.4em;
        font-weight: 700;
    }
    .item-actions {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 10px; /* Space between quantity and remove button */
    }
    .cart-item-meta {
        display: flex;
        gap: 15px;
        font-size: 0.9em;
        color: #6e6e73;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }
    .cart-item-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .quantity-control {
        display: flex;
        align-items: center;
    }
    .remove-btn {
        color: #ff3b30;
        font-size: 0.9em;
        cursor: pointer;
        background: none;
        border: none;
        padding: 0;
    }
    .remove-btn:hover {
        text-decoration: underline;
    }
    .color-option { 
    width: 28px; 
    height: 28px; 
    border-radius: 50%; 
    border: 2px solid #ccc; 
    cursor: pointer; 
    transition: 0.25s; 
    margin-right: 5px; 
    display: inline-block;
    }
    .color-option.selected { 
        border: 3px solid #0071e3; 
        transform: scale(1.1); 
    }
    .storage-option { 
        padding: 8px 12px; 
        border: 2px solid #ddd; 
        border-radius: 8px; 
        cursor: pointer; 
        transition: 0.25s; 
        background: #fff; 
        font-weight: 500; 
        font-size: 0.9em; 
        margin-right: 5px; 
        display: inline-block;
    }
    .storage-option.selected { 
        background: #0071e3; 
        color: #fff; 
        border-color: #0071e3; 
    }
    .options-flex { 
        display: flex; 
        gap: 8px; 
        flex-wrap: wrap; 
        margin-top: 5px; 
    }
    .option-group { 
        margin-bottom: 15px; 
    }
    .option-label { 
        font-size: 0.85em; 
        color: #6e6e73; 
        margin-bottom: 6px; 
        font-weight: 500; 
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
        
    .qty-btn { width: 32px; height: 32px; border: 1px solid #d2d2d7; background: #fff; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; }
    .qty-display { min-width: 40px; text-align: center; font-weight: 600; font-size: 1.1em; }

    /* --- Styles for Cart Summary, Badges, Toasts etc. --- */
    .cart-summary { background: #fff; border-radius: 18px; padding: 30px; height: fit-content; position: sticky; top: 20px; }
    .summary-title { font-size: 1.5em; font-weight: 700; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f5f5f7; }
    .summary-row { display: flex; justify-content: space-between; padding: 12px 0; font-size: 1em; }
    .summary-row.total { border-top: 2px solid #f5f5f7; padding-top: 20px; margin-top: 15px; font-size: 1.3em; font-weight: 700; }
    .checkout-btn { width: 100%; background: #0071e3; color: #fff; border: none; padding: 16px; border-radius: 12px; font-size: 1.1em; font-weight: 600; margin-top: 20px; cursor: pointer; transition: background 0.3s; }
    .checkout-btn:hover { background: #0077ed; }
    .checkout-btn:disabled { background: #d2d2d7; cursor: not-allowed; }
    .empty-cart { text-align: center; padding: 60px 20px; background: #fff; border-radius: 18px; }
    .empty-cart h2 { font-size: 2em; margin-bottom: 15px; }
    .empty-cart p { color: #6e6e73; margin-bottom: 30px; }
    .continue-shopping { background: #0071e3; color: #fff; padding: 12px 30px; border-radius: 10px; text-decoration: none; display: inline-block; transition: background 0.3s; }
    .continue-shopping:hover { background: #0077ed; color: #fff; }
    .badge-condition { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 0.75em; font-weight: 600; margin-left: 8px; }
    .badge-refurbished { background: #34c759; color: white; }
    .badge-new { background: #000; color: white; }
    .toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
    .custom-toast { background: white; border-radius: 12px; padding: 16px 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); margin-bottom: 10px; min-width: 300px; display: flex; align-items: center; gap: 12px; animation: slideIn 0.3s ease-out; }
    @keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    .toast-success { border-left: 4px solid #34c759; }
    .toast-error { border-left: 4px solid #ff3b30; }
    .toast-info { border-left: 4px solid #0071e3; }
    
    /* Responsive adjustments */
    @media (max-width: 992px) { 
        .cart-content { grid-template-columns: 1fr; } 
        .cart-summary { position: relative; top: 0; } 
        .item-details { grid-template-columns: 1fr; }
        .item-price-actions { text-align: left; margin-top: 20px; flex-direction: row; justify-content: space-between; align-items: center; }
        .item-actions, .quantity-control { justify-content: flex-start; }
        .item-actions { flex-direction: row-reverse; gap: 20px; }
    }
    </style>
</head>
<body>

    <header class="navbar navbar-expand-lg navbar-dark" style="background: rgba(0,0,0,0.8); backdrop-filter: blur(20px);">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items: center" href="../index.php">
                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.53 4.09l-.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z'/%3E%3C/svg%3E" width="24" height="24" class="me-2" alt="Apple">
                <span class="text-white fw-bold">R&M Apple Gadgets</span>
            </a>
            <div class="d-flex align-items: center gap-3">
                <a href="../index.php" class="text-white text-decoration-none"> <i class="fas fa-home"></i> Home </a>
                <?php if (isset($_SESSION['username'])): ?>
                <div class="dropdown d-inline-block">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="customerdash.php"><i class="fas fa-tachometer-alt"></i> My Dashboard</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../backend/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="toast-container" id="toastContainer"></div>

    <div class="cart-container">
        <h1 class="cart-title">Shopping Cart</h1>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart" style="font-size: 4em; color: #d2d2d7; margin-bottom: 20px;"></i>
                <h2>Your cart is empty</h2>
                <p>Start shopping to add items to your cart!</p>
                <a href="../index.php" class="continue-shopping"><i class="fas fa-store"></i> Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-content">
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): 
                        $base_name = trim(preg_replace(['/\s*-\s*(128GB|256GB|512GB|1TB|2TB|64GB).*$/i', '/\s*\(.*\).*$/i'], '', $item['name']));
                        $category = $item['category'];
                        $options = $product_options[$category][$base_name] ?? ['colors' => [], 'storages' => [], 'price_map' => []];
                        $is_macbook = strtolower($category) === 'macbook';
                        $display_price = $item['cart_price'] ?? $item['product_base_price'] ?? 0;
                    ?>
                        <div class="cart-item" 
                             data-cart-id="<?= $item['cart_id'] ?>" 
                             data-price="<?= $display_price ?>" 
                             data-category="<?= htmlspecialchars($category) ?>"
                             data-base-name="<?= htmlspecialchars($base_name) ?>">
                            <img src="../<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="item-image">
                            
                            <div class="item-details">
                                <div class="item-info">
                                    <h3>
                                        <?= htmlspecialchars($item['name']) ?>
                                        <?php if ($item['condition_type'] === 'refurbished'): ?>
                                            <span class="badge-condition badge-refurbished">Refurbished</span>
                                        <?php else: ?>
                                            <span class="badge-condition badge-new">New</span>
                                        <?php endif; ?>
                                    </h3>
                                
                                    <?php if ($is_macbook): ?> 
                                        <!-- MacBook: Show memory/storage info BELOW product name, then color options -->
                                        <div class="cart-item-meta">
                                            <?php if ($item['unified_memory']): ?>
                                                <span><i class="fas fa-memory"></i> <?= htmlspecialchars($item['unified_memory']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($item['storage']): ?>
                                                <span><i class="fas fa-hdd"></i> <?= htmlspecialchars($item['storage']); ?> Storage</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="option-group color-options">
                                            <div class="option-label"><i class="fas fa-palette"></i> Color: <span class="current-color"><?= htmlspecialchars($item['color']) ?></span></div>
                                            <div class="options-flex">
                                                <?php
                                                $current_color = trim($item['color']);
                                                foreach ($options['colors'] as $color):
                                                    $trimmed_color = trim($color);
                                                ?>
                                                    <div class="color-option change-option <?= $trimmed_color === $current_color ? 'selected' : '' ?>" 
                                                        style="background: <?= getColorHex($trimmed_color) ?>" 
                                                        data-type="color"
                                                        data-value="<?= htmlspecialchars($trimmed_color) ?>"
                                                        data-cart-id="<?= $item['cart_id'] ?>"
                                                        title="<?= htmlspecialchars($trimmed_color) ?>">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <!-- iPhone/iPad interactive options -->
                                        <div class="option-group color-options">
                                            <div class="option-label"><i class="fas fa-palette"></i> Color: <span class="current-color"><?= htmlspecialchars($item['color']) ?></span></div>
                                            <div class="options-flex">
                                                <?php
                                                $current_color = trim($item['color']);
                                                foreach ($options['colors'] as $color):
                                                    $trimmed_color = trim($color);
                                                ?>
                                                    <div class="color-option change-option <?= $trimmed_color === $current_color ? 'selected' : '' ?>" 
                                                        style="background: <?= getColorHex($trimmed_color) ?>" 
                                                        data-type="color"
                                                        data-value="<?= htmlspecialchars($trimmed_color) ?>"
                                                        data-cart-id="<?= $item['cart_id'] ?>"
                                                        title="<?= htmlspecialchars($trimmed_color) ?>">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <?php if (!empty($options['storages'])): ?>
                                        <div class="option-group storage-options">
                                            <div class="option-label"><i class="fas fa-hdd"></i> Storage: <span class="current-storage"><?= htmlspecialchars($item['storage']) ?></span></div>
                                            <div class="options-flex">
                                                <?php
                                                $current_storage = trim($item['storage']);
                                                foreach ($options['storages'] as $storage):
                                                    $trimmed_storage = trim($storage);
                                                ?>
                                                    <div class="storage-option change-option <?= $trimmed_color === $current_storage ? 'selected' : '' ?>" 
                                                        data-type="storage"
                                                        data-value="<?= htmlspecialchars($trimmed_storage) ?>" 
                                                        data-cart-id="<?= $item['cart_id'] ?>">
                                                        <?= htmlspecialchars($trimmed_storage) ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="item-price-actions">
                                    <div class="item-price" id="price-<?= $item['cart_id'] ?>">
                                        ₱<?= number_format($display_price * $item['quantity'], 2) ?>
                                    </div>
                                    <div class="item-actions">
                                        <div class="quantity-control">
                                            <button class="qty-btn" onclick="updateQuantity(<?= $item['cart_id'] ?>, -1)"> <i class="fas fa-minus"></i> </button>
                                            <span class="qty-display" id="qty-<?= $item['cart_id'] ?>"><?= $item['quantity'] ?></span>
                                            <button class="qty-btn" onclick="updateQuantity(<?= $item['cart_id'] ?>, 1)"> <i class="fas fa-plus"></i> </button>
                                        </div>
                                        <button class="remove-btn" onclick="removeItem(<?= $item['cart_id'] ?>)">Remove</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <h2 class="summary-title">Order Summary</h2>
                    <div class="summary-row"> <span>Subtotal:</span> <span id="subtotal">₱<?= number_format($subtotal, 2) ?></span> </div>
                    <div class="summary-row"> <span>Shipping:</span> <span id="shipping">₱<?= number_format($shipping, 2) ?></span> </div>
                    <div class="free-shipping-msg" style="<?= $shipping === 0 ? '' : 'display: none;' ?> color: #34c759; font-size: 0.9em; margin-top: 5px;"> <i class="fas fa-check-circle"></i> Free shipping! </div>
                    <div class="summary-row total"> <span>Total:</span> <span id="total">₱<?= number_format($total, 2) ?></span> </div>
                    <button class="checkout-btn" onclick="proceedToCheckout()"> <i class="fas fa-credit-card"></i> Proceed to Checkout </button>
                    <a href="../index.php" style="display: block; text-align: center; margin-top: 15px; text-decoration: none; color: #0071e3; font-weight: 500;"> <i class="fas fa-arrow-left"></i> Continue Shopping </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // The JavaScript logic is the same as the last version.
        // It's already correct and will work with this new layout.
        const priceMap = <?= json_encode($product_options) ?>;

        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `custom-toast toast-${type} align-items-center`;
            const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
            toast.innerHTML = `<i class="fas fa-${icon}" style="font-size: 1.2em; margin-right: 10px;"></i><span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => { 
                toast.style.transition = 'opacity 0.5s ease-out';
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }

        document.querySelectorAll('.change-option').forEach(option => {
            option.addEventListener('click', async function() {
                if (this.classList.contains('selected')) return;

                const cartId = this.dataset.cartId;
                const type = this.dataset.type;
                const cartItemElement = document.querySelector(`[data-cart-id="${cartId}"]`);

                // 1. Update UI first
                if (type === 'color') {
                    cartItemElement.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
                    this.classList.add('selected');
                } else if (type === 'storage') {
                    cartItemElement.querySelectorAll('.storage-option').forEach(o => o.classList.remove('selected'));
                    this.classList.add('selected');
                }
                
                // 2. NOW, read the *current* state from the DOM
                let selectedColor = cartItemElement.querySelector('.color-option.selected')?.dataset.value;
                let selectedStorage = cartItemElement.querySelector('.storage-option.selected')?.dataset.value;
                
                // 3. Handle edge cases
                const category = cartItemElement.dataset.category.toLowerCase();
                if (!selectedStorage) {
                    if (category === 'macbook') {
                        const itemName = cartItemElement.querySelector('.item-info h3').textContent;
                        const match = itemName.match(/(\d+GB|\d+TB)/i);
                        selectedStorage = match ? match[0].toUpperCase() : 'N/A';
                    } else {
                        const storageLabel = cartItemElement.querySelector('.current-storage');
                        if (storageLabel) selectedStorage = storageLabel.textContent;
                    }
                }
                if (!selectedColor) {
                    const colorLabel = cartItemElement.querySelector('.current-color');
                    if(colorLabel) selectedColor = colorLabel.textContent;
                }

                // 4. Final check before sending
                if (!selectedColor || !selectedStorage) {
                    showToast('Could not determine selection. Please reload.', 'error');
                    console.error('Missing params:', { cartId, selectedColor, selectedStorage });
                    return;
                }
                
                // 5. Send to backend
                cartItemElement.style.opacity = '0.5';
                try {
                    const response = await fetch('../backend/api/update_cart_details.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ cart_id: cartId, color: selectedColor, storage: selectedStorage })
                    });
                    const result = await response.json();
                    
                    if (result.success && result.new_price_per_unit != null) {
                        const newPrice = parseFloat(result.new_price_per_unit);
                        const currentQty = parseInt(cartItemElement.querySelector('.qty-display').textContent);
                        cartItemElement.dataset.price = newPrice;
                        document.getElementById(`price-${cartId}`).textContent = `₱${(newPrice * currentQty).toLocaleString('en-PH', {minimumFractionDigits: 2})}`;
                        if(type === 'color') cartItemElement.querySelector('.current-color').textContent = selectedColor;
                        const storageLabel = cartItemElement.querySelector('.current-storage');
                        if(storageLabel && type === 'storage') storageLabel.textContent = selectedStorage;
                        recalculateTotals();
                        showToast('Cart updated!', 'success');
                    } else {
                        showToast(result.message || 'Failed to update', 'error');
                        setTimeout(() => location.reload(), 1500); 
                    }
                } catch (error) {
                    showToast('An error occurred.', 'error');
                } finally {
                    cartItemElement.style.opacity = '1';
                }
            });
        });

        async function updateQuantity(cartId, change) {
            const qtyElement = document.getElementById(`qty-${cartId}`);
            const newQty = parseInt(qtyElement.textContent) + change;
            if (newQty < 1) { 
                if (confirm('Remove this item from cart?')) removeItem(cartId);
                return; 
            }
            try {
                const response = await fetch('../backend/api/update_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cart_id: cartId, quantity: newQty })
                });
                const result = await response.json();
                if (result.success) {
                    qtyElement.textContent = newQty;
                    const cartItem = document.querySelector(`[data-cart-id="${cartId}"]`);
                    const itemPrice = parseFloat(cartItem.dataset.price);
                    document.getElementById(`price-${cartId}`).textContent = `₱${(itemPrice * newQty).toLocaleString('en-PH', {minimumFractionDigits: 2})}`;
                    recalculateTotals();
                } else { showToast(result.message, 'error'); }
            } catch (error) { showToast('Failed to update cart', 'error'); }
        }

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
                    const itemToRemove = document.querySelector(`[data-cart-id="${cartId}"]`);
                    itemToRemove.style.transition = 'opacity 0.3s ease';
                    itemToRemove.style.opacity = '0';
                    setTimeout(() => {
                        itemToRemove.remove();
                        if (document.querySelectorAll('.cart-item').length === 0) location.reload();
                        else recalculateTotals();
                        showToast('Item removed', 'success');
                    }, 300);
                } else { showToast(result.message, 'error'); }
            } catch (error) { showToast('Failed to remove item', 'error'); }
        }

        function recalculateTotals() {
            let subtotal = 0;
            document.querySelectorAll('.cart-item').forEach(item => {
                subtotal += (parseFloat(item.dataset.price) || 0) * (parseInt(item.querySelector('.qty-display').textContent) || 0);
            });
            const shipping = subtotal > 50000 ? 0 : 200;
            const total = subtotal + shipping;
            document.getElementById('subtotal').textContent = `₱${subtotal.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            document.getElementById('shipping').textContent = `₱${shipping.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            document.getElementById('total').textContent = `₱${total.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            document.querySelector('.free-shipping-msg').style.display = shipping === 0 ? 'block' : 'none';
        }

        function proceedToCheckout() { window.location.href = 'checkout.php'; }
    </script>
</body>
</html>