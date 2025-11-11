
<?php
session_start();
require_once __DIR__ . '/../backend/config/db_connect.php';
$category = "iPhone"; 
$categoryPage = "iphone.php"; 

// Fetch all iPhone products from database
$stmt = $conn->prepare("
    SELECT * FROM products 
    WHERE category = 'iphone' AND status = 'active' 
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
        'deep blue' => '#1a2e45',
        'cosmic orange' => '#ff8c30',
        'silver' => '#f5f5f7',
        'desert titanium' => '#d7c5a0',
        'natural titanium' => '#c8c0b3',
        'white titanium' => '#f5f5f0',
        'black titanium' => '#1d1d1f',
        'blue titanium' => '#4b5b78',
        'teal' => '#a7d5d3',
        'pink' => '#fbd3db',
        'white' => '#f5f5f0',
        'black' => '#1d1d1f',
        'ultramarine' => '#6a8eb6',
        'blue' => '#a9c9f5',
        'yellow' => '#fff4c2',
        'green' => '#b6e3c4',
        'purple' => '#b7a2cc',
        'starlight' => '#f6e5ca',
        'midnight' => '#1d1d1f',
        'red' => '#e43c3c',
        'sky blue' => '#8ec6f9',
        'light gold' => '#e8d9b8',
        'cloud white' => '#f5f5f5',
        'space black' => '#1d1d1f',
        'lavender' => '#b49ac1',
        'sage' => '#9cb698',
        'mist blue' => '#a8c5dd'
    ];
    return $colorMap[strtolower(trim($color))] ?? '#ddd';
}

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
  <title>iPhone - R&M Apple Gadgets</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* Product Image Adjustments */
    .product-card-modern .product-image {
      width: 100%;
      height: 280px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 15px 0;
      padding: 0 10px;
      overflow: hidden;
    }

    .product-card-modern .product-image img {
      width: 95%;
      height: 100%;
      object-fit: contain;
      object-position: center;
    }

    .product-card-modern {
      display: flex;
      flex-direction: column;
      min-height: 500px;
    }

    .product-price {
      font-size: 1.1em;
      font-weight: 600;
      color: #1d1d1f;
      margin: 15px 0;
    }

    .color-options {
      display: flex;
      gap: 8px;
      justify-content: center;
      margin: 15px 0;
    }

    .color-dot-modern {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      border: 1px solid rgba(0, 0, 0, 0.1);
      cursor: pointer;
      transition: transform 0.2s;
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

    .color-dot-modern:hover {
      transform: scale(1.15);
      border: 2px solid rgba(0, 0, 0, 0.3);
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
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.85em;
      font-weight: 600;
    }

    .refurb-subtitle {
      color: #666;
      font-size: 0.9em;
      margin: 5px 0;
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
        <li class="nav-item"><a class="nav-link text-white active" href="iphone.php">iPhone</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="ipad.php">iPad</a></li>
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

  <!-- Hero Slider -->
  <section class="slider">
    <div class="slides">
      <div class="slide active" style="background-image: url('../assets/images/iphone13.png');">
        <div class="content">
          <h1>iPhone 13</h1>
          <p>Powerful performance. Classic design.</p>
          <button>Learn More</button>
          <button class="secondary">Buy</button>
        </div>
      </div>
      <div class="slide" style="background-image: url('../assets/images/iphone14.png');">
        <div class="content">
          <h1>iPhone 14</h1>
          <p>Big and bigger.</p>
          <button>Learn More</button>
          <button class="secondary">Buy</button>
        </div>
      </div>
      <div class="slide" style="background-image: url('../assets/images/Ip15.png');">
        <div class="content">
          <h1>iPhone 15</h1>
          <p>Dynamic and durable.</p>
          <button>Learn More</button>
          <button class="secondary">Buy</button>
        </div>
      </div>
      <div class="slide" style="background-image: url('../assets/images/ip16.png');">
        <div class="content">
          <h1>iPhone 16</h1>
          <p>Smarter. Faster. Stronger.</p>
          <button>Learn More</button>
          <button class="secondary">Buy</button>
        </div>
      </div>
    </div>
    <div class="navigation">
      <span class="prev">&#10094;</span>
      <span class="next">&#10095;</span>
    </div>
  </section>

  <!-- Product Section -->
  <section class="product-section">
    <div class="section-header">
      <h2>Which iPhone is right for you?</h2>
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
        <button class="btn-buy" onclick="location.href='product-details.php?id=<?= $product['id'] ?>'">Buy</button>
      </div>
      <?php endforeach; ?>

      <?php if (empty($new_products)): ?>
        <div class="col-12 text-center py-5">
          <p class="text-muted">No new iPhones available at the moment.</p>
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
          <p class="text-muted">No refurbished iPhones available at the moment.</p>
        </div>
      <?php endif; ?>
    </div>
  </section>
  <script src="../assets/js/script.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>  
</body>
</html>