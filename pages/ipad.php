<?php
session_start();
require_once __DIR__ . '/../backend/config/db_connect.php';
$category = "iPad"; 
$categoryPage = "ipad.php";

// Fetch all iPad products from database
$stmt = $conn->prepare("
    SELECT * FROM products 
    WHERE category = 'ipad' AND status = 'active' 
    ORDER BY condition_type, price DESC
");
$stmt->execute();
$all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separate products by condition
$new_products = [];
$refurbished_products = [];

foreach ($all_products as $product) {
    if ($product['condition_type'] === 'new') {
        $new_products[] = $product;
    } else {
        $refurbished_products[] = $product;
    }
}

// Helper function to get color options
function getColorOptions($colorString) {
    if (empty($colorString)) return [];
    return array_map('trim', explode(',', $colorString));
}

// Helper function to get storage options
function getStorageOptions($storageString) {
    if (empty($storageString)) return [];
    return array_map('trim', explode(',', $storageString));
}

// Helper function to get color hex code

function getColorHex($color) {
    $colorMap = [
        // iPad colors
        'space gray' => '#535150',
        'space grey' => '#535150',
        'space black' => '#1d1d1f',
        'silver' => '#e3e4e5',
        'gold' => '#fad7bd',
        'rose gold' => '#e8c1b7',
        'sky blue' => '#a7d5f4',
        'purple' => '#d1c2e3',
        'pink' => '#f4c7d8',
        'starlight' => '#f5f1e8',
        'midnight' => '#232a31',
        'blue' => '#aec9e5',
        'white' => '#f5f5f5',
        'black' => '#1d1d1f',
        'green' => '#c8ddd0',
        'yellow' => '#fef0c7',
        'cloud white' => '#f5f5f5',
        'light gold' => '#e8d9b8'
    ];
    return $colorMap[strtolower(trim($color))] ?? '#c8c8c8';}

// Helper function to format condition badge
function getConditionBadge($condition) {
    $badges = [
        'new' => '<span class="badge-new">NEW</span>',
        'refurbished' => '<span class="badge-condition excellent">Excellent</span>',
        'pre-owned' => '<span class="badge-condition good">Good</span>'
    ];
    return $badges[$condition] ?? '';
}

// Helper function to calculate savings
function calculateSavings($price, $originalPrice) {
    if (!$originalPrice || $originalPrice <= $price) return 0;
    return $originalPrice - $price;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>iPad - R&M Apple Gadgets</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* Product Image Adjustments */
    .product-card-modern .product-image {
      width: 100%;
      height: 320px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 15px 0;
      padding: 20px;
      overflow: hidden;
      background: #fafafa;
      border-radius: 16px;
      position: relative;
    }

    .product-card-modern .product-image img {
      max-width: 100%;
      max-height: 100%;
      width: auto;
      height: auto;
      object-fit: contain;
      object-position: center;
      filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.08));
      transition: transform 0.3s ease;
    }

    .product-card-modern:hover .product-image img {
      transform: scale(1.05);
    }

    .product-card-modern {
      display: flex;
      flex-direction: column;
      min-height: 520px;
      background: white;
      border-radius: 18px;
      padding: 20px;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
      transition: all 0.3s ease;
    }

    .product-card-modern:hover {
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
      transform: translateY(-4px);
    }

    .color-options {
      display: flex;
      gap: 8px;
      justify-content: center;
      margin: 15px 0;
    }

    .color-dot-modern {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      border: 2px solid rgba(0, 0, 0, 0.1);
      cursor: pointer;
      transition: all 0.2s ease;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .color-dot-modern:hover {
      transform: scale(1.2);
      border: 2px solid rgba(0, 0, 0, 0.3);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .original-price {
      text-decoration: line-through;
      color: #999;
      font-size: 0.9em;
      margin-left: 10px;
    }

    .badge-savings {
      background: #34c759;
      color: white;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 0.85em;
      font-weight: 600;
      box-shadow: 0 2px 6px rgba(52, 199, 89, 0.3);
    }

    .badge-condition {
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 0.85em;
      font-weight: 600;
    }

    .badge-condition.excellent {
      background: #e3f5ff;
      color: #0071e3;
    }

    .badge-condition.good {
      background: #fff4e5;
      color: #f57c00;
    }

    .refurb-subtitle {
      color: #666;
      font-size: 0.9em;
      margin: 5px 0;
      font-weight: 500;
    }

    .badge-container {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: center; /* Changed from flex-start to center */
        align-items: center;
        margin-bottom: 10px;
        min-height: 32px;
    }

    .badge-new {
      background: none !important;
      border: none !important;
      color: #ff3b30 !important;
      font-weight: 700;
      font-size: 0.75em;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      box-shadow: none !important;
    }

    .product-title-group {
        text-align: center; /* Center align the title group */
    }

    .product-title-group h3 {
      font-size: 1.4em;
      font-weight: 700;
      margin: 10px 0 5px;
      color: #1d1d1f;
      text-align: center;
    }

    .product-price {
      font-size: 1.1em;
      font-weight: 600;
      color: #1d1d1f;
      margin: 15px 0;
    }

    .btn-buy {
      background: #0071e3;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 10px;
      font-size: 1em;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: auto;
    }

    .btn-buy:hover {
      background: #0077ed;
      transform: translateY(-2px);  
      box-shadow: 0 4px 12px rgba(0, 113, 227, 0.3);
    }

    /* iPad Hero Section */
    .ipad-hero {
      background: linear-gradient(135deg, #363536ff 0%, #000000ff 100%);
      color: white;
      text-align: center;
      padding: 80px 20px;
      margin-bottom: 50px;
    }

    .ipad-hero h1 {
      font-size: 3.5em;
      font-weight: 700;
      margin-bottom: 20px;
    }

    .ipad-hero p {
      font-size: 1.5em;
      opacity: 0.95;
      margin-bottom: 30px;
    }
  </style>
</head>
<body>
  <!-- Navigation Bar -->
<header class="navbar navbar-expand-lg navbar-dark bg-dark px-4 py-2">
  <div class="container-fluid d-flex align-items-center justify-content-between">
    <!-- Logo -->
    <div class="d-flex align-items-center">
      <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.53 4.09l-.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z'/%3E%3C/svg%3E" alt="Apple" width="24" height="24" class="me-2">
       <span class="text-white">R&M Apple Gadgets</span>
    </div>

    <!-- Navigation Links -->
    <nav>
      <ul class="navbar-nav d-flex flex-row gap-3 mb-0">
        <li class="nav-item"><a class="nav-link text-white" href="../index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="iphone.php">iPhone</a></li>
        <li class="nav-item"><a class="nav-link text-white active" href="ipad.php">iPad</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="macbook.php">MacBook</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="support.php">Support</a></li>
      </ul>
    </nav>

    <!-- Icons + Username -->
    <div class="d-flex align-items-center gap-3">
      <span class="fs-5">🔍</span>
      <a href="cart.php" style="text-decoration: none; font-size: 1.1em;">🛒</a>

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
  </div>
</header>
  
  <!-- iPad Hero Section -->
  <section class="ipad-hero">
    <h1>iPad</h1>
    <p>Lovable. Drawable. Magical.</p>
  </section>

  <!-- Product Section -->
  <section class="product-section">
    <div class="section-header">
      <h2>Which iPad is right for you?</h2>
    </div>

    <!-- Toggle Switch -->
    <div class="toggle-container">
      <button class="toggle-btn active" data-category="new">Brand New</button>
      <button class="toggle-btn" data-category="refurbished">Refurbished / Pre-Owned</button>
    </div>

    <!-- Brand New Products -->
    <div id="new-products" class="product-cards-container">
      <?php foreach ($new_products as $product): 
        $colors = getColorOptions($product['color']);
        $storages = getStorageOptions($product['storage']);
        $savings = calculateSavings($product['price'], $product['original_price']);
      ?>
      <div class="product-card-modern">
        <div class="badge-container">
          <?= getConditionBadge($product['condition_type']) ?>
        </div>
        <div class="product-title-group">
          <h3><?= htmlspecialchars($product['name']) ?></h3>
        </div>
        <div class="product-image">
          <img src="../<?= htmlspecialchars($product['image_url']) ?>" 
               alt="<?= htmlspecialchars($product['name']) ?>"
               onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22280%22 height=%22280%22%3E%3Crect fill=%22%23f5f5f7%22 width=%22280%22 height=%22280%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22%2386868b%22 font-size=%2260%22%3E📱%3C/text%3E%3C/svg%3E'">
        </div>
        <?php if (!empty($colors)): ?>
        <div class="color-options">
          <?php foreach ($colors as $color): ?>
            <span class="color-dot-modern" 
                  style="background: <?= getColorHex($color) ?>" 
                  title="<?= htmlspecialchars($color) ?>"></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <p class="product-price">
          Starts at ₱<?= number_format($product['price'], 2) ?>
          <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
            <span class="original-price">₱<?= number_format($product['original_price'], 2) ?></span>
          <?php endif; ?>
        </p>
        <button 
        class="btn-buy" 
        onclick="location.href='product-details.php?id=<?= $product['id'] ?>&category=<?= urlencode($category) ?>&categoryPage=<?= urlencode(basename($_SERVER['PHP_SELF'])) ?>'">
        Buy
        </button>
      </div>
      <?php endforeach; ?>

      <?php if (empty($new_products)): ?>
        <div class="col-12 text-center py-5">
          <p class="text-muted">No new iPads available at the moment.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Refurbished Products -->
    <div id="refurbished-products" class="product-cards-container" style="display: none;">
      <?php foreach ($refurbished_products as $product): 
        $colors = getColorOptions($product['color']);
        $storages = getStorageOptions($product['storage']);
        $savings = calculateSavings($product['price'], $product['original_price']);
      ?>
      <div class="product-card-modern">
        <div class="badge-container">
          <?= getConditionBadge($product['condition_type']) ?>
          <?php if ($savings > 0): ?>
            <span class="badge-savings">Save ₱<?= number_format($savings) ?></span>
          <?php endif; ?>
        </div>
        <div class="product-title-group">
          <h3><?= htmlspecialchars($product['name']) ?></h3>
          <p class="refurb-subtitle"><?= ucfirst($product['condition_type']) ?></p>
        </div>
        <div class="product-image">
          <img src="../<?= htmlspecialchars($product['image_url']) ?>" 
               alt="<?= htmlspecialchars($product['name']) ?>"
               onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22280%22 height=%22280%22%3E%3Crect fill=%22%23f5f5f7%22 width=%22280%22 height=%22280%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22%2386868b%22 font-size=%2260%22%3E📱%3C/text%3E%3C/svg%3E'">
        </div>
        <?php if (!empty($colors)): ?>
        <div class="color-options">
          <?php foreach ($colors as $color): ?>
            <span class="color-dot-modern" 
                  style="background: <?= getColorHex($color) ?>" 
                  title="<?= htmlspecialchars($color) ?>"></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <p class="product-price">
          From ₱<?= number_format($product['price'], 2) ?>
          <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
            <span class="original-price">₱<?= number_format($product['original_price'], 2) ?></span>
          <?php endif; ?>
        </p>
        <button class="btn-buy" onclick="location.href='product-details.php?id=<?= $product['id'] ?>'">Buy</button>
      </div>
      <?php endforeach; ?>

      <?php if (empty($refurbished_products)): ?>
        <div class="col-12 text-center py-5">
          <p class="text-muted">No refurbished iPads available at the moment.</p>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <script>
    // Product Category Toggle
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButtons = document.querySelectorAll('.toggle-btn');
        const newProductsSection = document.getElementById('new-products');
        const refurbishedProductsSection = document.getElementById('refurbished-products');

        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                toggleButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');

                // Get the category from data attribute
                const category = this.getAttribute('data-category');

                // Toggle visibility
                if (category === 'new') {
                    newProductsSection.style.display = 'grid';
                    refurbishedProductsSection.style.display = 'none';
                } else if (category === 'refurbished') {
                    newProductsSection.style.display = 'none';
                    refurbishedProductsSection.style.display = 'grid';
                }
            });
        });
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
</body>
</html>