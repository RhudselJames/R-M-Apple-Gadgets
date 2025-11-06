<?php 
session_start();
include 'db_connect.php'; 

// Fetch featured products from database (only products marked as featured)
try {
    $stmt = $conn->prepare("
        SELECT id, name, description, price, original_price, image_url, condition_type, stock_quantity
        FROM products 
        WHERE status = 'active' AND stock_quantity > 0 AND is_featured = 1
        ORDER BY created_at DESC 
        LIMIT 4
    ");
    $stmt->execute();
    $featuredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no featured products, fall back to newest 4 products
    if (count($featuredProducts) === 0) {
        $stmt = $conn->prepare("
            SELECT id, name, description, price, original_price, image_url, condition_type, stock_quantity
            FROM products 
            WHERE status = 'active' AND stock_quantity > 0
            ORDER BY created_at DESC 
            LIMIT 4
        ");
        $stmt->execute();
        $featuredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $featuredProducts = [];
    error_log("Error fetching featured products: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>R&M Apple Gadgets</title>
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #1d1d1f; background: #fff; }

    /* Navigation */
    .navbar { display: flex; justify-content: space-between; align-items: center; padding: 12px 40px; background: rgba(0,0,0,0.8); backdrop-filter: blur(20px); position: sticky; top: 0; z-index: 1000; }
    .navbar .logo { display: flex; align-items: center; gap: 8px; font-size: 1em; font-weight: 500; color: #f5f5f7; cursor: pointer; }
    .navbar .logo img { width: 20px; height: 20px; }
    nav ul { display: flex; gap: 30px; list-style: none; }
    nav ul li a { text-decoration: none; color: #f5f5f7; font-size: 0.875em; transition: 0.3s; opacity: 0.8; }
    nav ul li a:hover { opacity: 1; }
    .icons { display: flex; align-items: center; gap: 20px; font-size: 1.1em; color: #f5f5f7; opacity: 0.8; }
    .icons span:hover { opacity: 1; cursor: pointer; }

    /* Username Button Styling */
    .btn-outline-light.btn-sm {
      border: 1px solid rgba(245, 245, 247, 0.3);
      color: #f5f5f7;
      font-size: 0.9em;
      background: transparent;
      padding: 6px 16px;
      border-radius: 20px;
      transition: all 0.3s;
    }
    .btn-outline-light.btn-sm:hover {
      background: rgba(245, 245, 247, 0.1);
      border-color: rgba(245, 245, 247, 0.5);
    }

    /* Hero Section */
    .hero-section { 
      background: linear-gradient(180deg, #000 0%, #1a1a1a 100%); 
      padding: 80px 40px; 
      text-align: center; 
      color: #fff; 
    }
    .hero-section h1 { 
      font-size: 3.5em; 
      font-weight: 700; 
      margin-bottom: 16px; 
      letter-spacing: -1px; 
    }
    .hero-section p { 
      font-size: 1.5em; 
      font-weight: 300; 
      margin-bottom: 30px; 
      color: #a1a1a6; 
    }
    .hero-section .cta-buttons {
      display: flex;
      gap: 16px;
      justify-content: center;
      margin-top: 30px;
    }
    .hero-section button { 
      background: #0071e3; 
      border: none; 
      padding: 14px 28px; 
      border-radius: 24px; 
      color: #fff; 
      cursor: pointer; 
      font-size: 1em; 
      font-weight: 500; 
      transition: 0.3s; 
    }
    .hero-section button:hover { background: #0077ed; }
    .hero-section button.secondary { 
      background: transparent; 
      border: 2px solid #0071e3; 
      color: #0071e3; 
    }
    .hero-section button.secondary:hover { 
      background: #0071e3; 
      color: #fff; 
    }

    /* Category Showcase Section */
    .category-showcase {
      max-width: 1400px;
      margin: 60px auto;
      padding: 0 40px;
    }
    .category-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-bottom: 12px;
    }
    .category-card {
      background-size: cover;
      background-position: center;
      border-radius: 18px;
      padding: 60px 40px;
      min-height: 500px;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      cursor: pointer;
      transition: transform 0.3s;
      position: relative;
      overflow: hidden;
    }
    .category-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(180deg, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0.6) 100%);
      z-index: 1;
    }
    .category-card:hover {
      transform: scale(1.02);
    }
    .category-card .content {
      position: relative;
      z-index: 2;
      color: #fff;
    }
    .category-card h2 {
      font-size: 2.5em;
      font-weight: 700;
      margin-bottom: 8px;
      letter-spacing: -0.5px;
    }
    .category-card p {
      font-size: 1.2em;
      margin-bottom: 20px;
      font-weight: 400;
    }
    .category-card .links {
      display: flex;
      gap: 30px;
      font-size: 1.05em;
    }
    .category-card .links a {
      color: #2997ff;
      text-decoration: none;
      transition: color 0.3s;
    }
    .category-card .links a:hover {
      color: #147ce5;
      text-decoration: underline;
    }

    /* Category Card Image */
    .category-card-img {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 60%;
      height: auto;
      object-fit: contain;
      z-index: 0;
    }

    /* iPhone Background */
    .iphone-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    /* iPad Background */
    .ipad-card {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    /* MacBook Background */
    .macbook-card {
      background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    /* Featured Products Section */
    .featured-section {
      background: #f5f5f7;
      padding: 80px 40px;
    }
    .featured-container {
      max-width: 1400px;
      margin: 0 auto;
    }
    .section-header {
      text-align: center;
      margin-bottom: 50px;
    }
    .section-header h2 {
      font-size: 2.8em;
      font-weight: 700;
      color: #1d1d1f;
      margin-bottom: 12px;
    }
    .section-header p {
      font-size: 1.3em;
      color: #6e6e73;
    }

    /* Product Grid */
    .product-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 24px;
    }
    .product-card {
      background: #fff;
      border-radius: 18px;
      padding: 40px 30px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      text-decoration: none;
      color: inherit;
      display: block;
    }
    .product-card:hover {
      box-shadow: 0 12px 40px rgba(0,0,0,0.12);
      transform: translateY(-8px);
    }
    .product-card img {
      width: 100%;
      height: 280px;
      object-fit: contain;
      margin-bottom: 24px;
    }
    .product-card .badge {
      display: inline-block;
      font-size: 0.75em;
      color: #bf4800;
      font-weight: 600;
      margin-bottom: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .product-card .badge.new {
      color: #bf4800;
    }
    .product-card .badge.refurbished {
      color: #28a745;
    }
    .product-card .badge.pre-owned {
      color: #ffc107;
    }
    .product-card h3 {
      font-size: 1.6em;
      font-weight: 700;
      color: #1d1d1f;
      margin-bottom: 8px;
    }
    .product-card .description {
      font-size: 0.95em;
      color: #6e6e73;
      margin-bottom: 12px;
      min-height: 44px;
    }
    .product-card .price {
      font-size: 1em;
      color: #1d1d1f;
      font-weight: 600;
      margin-bottom: 16px;
    }
    .product-card .price .original-price {
      text-decoration: line-through;
      color: #86868b;
      font-size: 0.9em;
      margin-right: 8px;
    }
    .product-card .btn-buy {
      background: #0071e3;
      border: none;
      padding: 10px 24px;
      border-radius: 20px;
      color: #fff;
      font-size: 0.9em;
      font-weight: 500;
      cursor: pointer;
      transition: 0.3s;
    }
    .product-card .btn-buy:hover {
      background: #0077ed;
    }

    /* Modal Styling */
    .modal-content {
      border-radius: 18px;
      border: none;
    }
    .modal-header {
      border-bottom: 1px solid #e5e5e7;
      padding: 20px 30px;
    }
    .modal-body {
      padding: 30px;
    }
    .form-control {
      border-radius: 10px;
      padding: 12px 16px;
      border: 1px solid #d2d2d7;
      font-size: 0.95em;
    }
    .form-control:focus {
      border-color: #0071e3;
      box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.1);
    }
    .btn-primary {
      background: #0071e3;
      border: none;
      border-radius: 10px;
      padding: 12px;
      font-weight: 500;
      transition: 0.3s;
    }
    .btn-primary:hover {
      background: #0077ed;
    }
    .nav-tabs {
      border-bottom: 1px solid #e5e5e7;
    }
    .nav-tabs .nav-link {
      color: #6e6e73;
      border: none;
      padding: 12px 24px;
    }
    .nav-tabs .nav-link.active {
      color: #0071e3;
      border-bottom: 2px solid #0071e3;
      background: transparent;
    }

    /* Responsive */
    @media (max-width: 992px) {
      .category-grid {
        grid-template-columns: 1fr;
      }
      .hero-section h1 {
        font-size: 2.5em;
      }
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
        <li class="nav-item"><a class="nav-link text-white" href="#" onclick="handleNavClick(event, 'iphone.php')">iPhone</a></li>
        <li class="nav-item"><a class="nav-link text-white active" href="#" onclick="handleNavClick(event, 'ipad.php')">iPad</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="#" onclick="handleNavClick(event, 'macbook.php')">MacBook</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="#" onclick="handleNavClick(event, 'support.php')">Support</a></li>
      </ul>
    </nav>

    <!-- Icons + Username -->
    <div class="d-flex align-items-center gap-3">
      <span class="fs-5">🔍</span>
      <a href="#" onclick="handleNavClick(event, 'cart.php')" style="text-decoration: none; font-size: 1.1em;">🛒</a>

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
            <a class="dropdown-item text-danger" href="logout.php">
              <i class="fas fa-sign-out-alt"></i> Logout
            </a>
          </li>
        </ul>
      </div>
    <?php endif; ?>
    </div>
  </div>
</header>

  <!-- Hero Section -->
  <section class="hero-section">
    <h1>Welcome to R&M Apple Gadgets</h1>
    <p>The best place for all your Apple needs</p>
    <div class="cta-buttons">
      <button onclick="document.getElementById('featured').scrollIntoView({behavior: 'smooth'})">Shop Now</button>
      <button class="secondary" onclick="document.getElementById('categories').scrollIntoView({behavior: 'smooth'})">Explore</button>
    </div>
  </section>

  <!-- Category Showcase -->
  <section class="category-showcase" id="categories">
    <div class="category-grid">
      <!-- iPhone Card -->
      <div class="category-card iphone-card" onclick="handleCategoryClick('iphone')">
        <img src="images/iphone17max.png" alt="iPhone" class="category-card-img">
        <div class="content">
          <h2>iPhone</h2>
          <p>Powerful. Beautiful. Durable.</p>
          <div class="links">
            <a href="#" onclick="event.stopPropagation(); handleCategoryClick('iphone');">Learn more →</a>
            <a href="#" onclick="event.stopPropagation(); handleCategoryClick('iphone');">Buy →</a>
          </div>
        </div>
      </div>

      <!-- iPad Card -->
      <div class="category-card ipad-card" onclick="handleCategoryClick('ipad')">
        <img src="images/ipadair.png" alt="iPad" class="category-card-img">
        <div class="content">
          <h2>iPad</h2>
          <p>Lovable. Drawable. Magical.</p>
          <div class="links">
            <a href="#" onclick="event.stopPropagation(); handleCategoryClick('ipad');">Learn more →</a>
            <a href="#" onclick="event.stopPropagation(); handleCategoryClick('ipad');">Buy →</a>
          </div>
        </div>
      </div>

      <!-- MacBook Card -->
      <div class="category-card macbook-card" onclick="handleCategoryClick('macbook')">
        <img src="images/macbookm3.png" alt="MacBook" class="category-card-img">
        <div class="content">
          <h2>MacBook</h2>
          <p>Supercharged by M-series.</p>
          <div class="links">
            <a href="#" onclick="event.stopPropagation(); handleCategoryClick('macbook');">Learn more →</a>
            <a href="#" onclick="event.stopPropagation(); handleCategoryClick('macbook');">Buy →</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Featured Products Section -->
  <section class="featured-section" id="featured">
    <div class="featured-container">
      <div class="section-header">
        <h2>Featured Products</h2>
        <p>Discover our latest and most popular devices</p>
      </div>

      <div class="product-grid">
        <?php if (count($featuredProducts) > 0): ?>
          <?php foreach ($featuredProducts as $product): ?>
            <a href="#" onclick="handleProductClick(event, <?= $product['id'] ?>)" class="product-card">
              <?php 
                $badgeText = 'New';
                $badgeClass = 'new';
                if ($product['condition_type'] === 'refurbished') {
                  $badgeText = 'Refurbished';
                  $badgeClass = 'refurbished';
                } elseif ($product['condition_type'] === 'pre-owned') {
                  $badgeText = 'Pre-Owned';
                  $badgeClass = 'pre-owned';
                }
              ?>
              <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
              <img src="<?= htmlspecialchars($product['image_url'] ?? 'images/placeholder.png') ?>" 
                   alt="<?= htmlspecialchars($product['name']) ?>"
                   onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22280%22 height=%22280%22%3E%3Crect fill=%22%23f5f5f7%22 width=%22280%22 height=%22280%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22%2386868b%22 font-size=%2260%22%3E📱%3C/text%3E%3C/svg%3E'">
              <h3><?= htmlspecialchars($product['name']) ?></h3>
              <p class="description"><?= htmlspecialchars($product['description'] ?? 'Premium quality device') ?></p>
              <p class="price">
                <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                  <span class="original-price">₱<?= number_format($product['original_price']) ?></span>
                <?php endif; ?>
                From ₱<?= number_format($product['price']) ?>
              </p>
              <button class="btn-buy" onclick="event.stopPropagation(); handleProductClick(event, <?= $product['id'] ?>)">View Details</button>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #86868b;">
            <p>No featured products available at the moment.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Login/Register Modal -->
  <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="loginModalLabel">Welcome</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <ul class="nav nav-tabs mb-4" id="authTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">Login</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">Register</button>
            </li>
          </ul>

          <div class="tab-content" id="authTabsContent">
            <!-- Login Form -->
            <div class="tab-pane fade show active" id="login" role="tabpanel">
              <form action="login.php" method="POST">

                <!-- ERROR MESSAGE HERE -->
                <?php if (isset($_GET['error']) && $_GET['error'] == 'invalid_credentials'): ?>
                  <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Login Failed!</strong> Invalid username or password.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>
                <?php endif; ?>

                <div class="mb-3">
                  <label for="loginUsername" class="form-label">Username</label>
                  <input type="text" class="form-control" id="loginUsername" name="username" required>
                </div>
                <div class="mb-3">
                  <label for="loginPassword" class="form-label">Password</label>
                  <input type="password" class="form-control" id="loginPassword" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
              </form>
            </div>

            <!-- Register Form -->
            <div class="tab-pane fade" id="register" role="tabpanel">
              <form action="register.php" method="POST" id="registerForm">
                <div class="mb-3">
                  <label for="registerFullName" class="form-label">Full Name</label>
                  <input type="text" class="form-control" id="registerFullName" name="full_name" required>
                </div>
                <div class="mb-3">
                  <label for="registerUsername" class="form-label">Username</label>
                  <input type="text" class="form-control" id="registerUsername" name="username" required minlength="3">
                  <small class="text-muted">At least 3 characters</small>
                </div>
                <div class="mb-3">
                  <label for="registerEmail" class="form-label">Email</label>
                  <input type="email" class="form-control" id="registerEmail" name="email" required pattern=".*@.*">
                  <small class="text-muted">Must contain @ symbol</small>
                </div>
                <div class="mb-3">
                  <label for="registerPhone" class="form-label">Phone Number</label>
                  <input type="tel" class="form-control" id="registerPhone" name="phone" required pattern="[0-9]{10,11}">
                  <small class="text-muted">10-11 digits</small>
                </div>
                <div class="mb-3">
                  <label for="registerPassword" class="form-label">Password</label>
                  <input type="password" class="form-control" id="registerPassword" name="password" required minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[@$!%*?&]).{8,}">
                  <small class="text-muted">Min 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character (@$!%*?&)</small>
                </div>
                <div class="mb-3">
                  <label for="registerConfirmPassword" class="form-label">Confirm Password</label>
                  <input type="password" class="form-control" id="registerConfirmPassword" name="confirm_password" required>
                </div>
                <div id="passwordError" class="text-danger small mb-2" style="display: none;"></div>
                <button type="submit" class="btn btn-primary w-100">Register</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Check if user is logged in
    const isLoggedIn = <?= isset($_SESSION['username']) ? 'true' : 'false' ?>;

    // Handle navigation clicks
    function handleNavClick(event, page) {
      event.preventDefault();
      if (!isLoggedIn) {
        const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
        loginModal.show();
      } else {
        window.location.href = page;
      }
    }

    // Handle category clicks
    function handleCategoryClick(category) {
      if (!isLoggedIn) {
        const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
        loginModal.show();
      } else {
        // Redirect to category page
        window.location.href = `${category}.php`;
      }
    }

    // Handle product clicks
    function handleProductClick(event, productId) {
      event.preventDefault();
      if (!isLoggedIn) {
        const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
        loginModal.show();
      } else {
        window.location.href = `product-details.php?id=${productId}`;
      }
    }

    // Password validation for registration
    const registerForm = document.getElementById('registerForm');
    const password = document.getElementById('registerPassword');
    const confirmPassword = document.getElementById('registerConfirmPassword');
    const passwordError = document.getElementById('passwordError');

    registerForm.addEventListener('submit', function(e) {
      passwordError.style.display = 'none';
      
      // Check if passwords match
      if (password.value !== confirmPassword.value) {
        e.preventDefault();
        passwordError.textContent = 'Passwords do not match!';
        passwordError.style.display = 'block';
        return false;
      }

      // Validate password requirements
      const passwordRegex = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[@$!%*?&]).{8,}$/;
      if (!passwordRegex.test(password.value)) {
        e.preventDefault();
        passwordError.textContent = 'Password must contain at least 8 characters, 1 uppercase, 1 lowercase, 1 number, and 1 special character (@$!%*?&)';
        passwordError.style.display = 'block';
        return false;
      }

      // Validate email contains @
      const email = document.getElementById('registerEmail').value;
      if (!email.includes('@')) {
        e.preventDefault();
        passwordError.textContent = 'Email must contain @ symbol';
        passwordError.style.display = 'block';
        return false;
      }
    });

    // Real-time password match indicator
    confirmPassword.addEventListener('input', function() {
      if (password.value !== confirmPassword.value && confirmPassword.value !== '') {
        confirmPassword.style.borderColor = '#dc3545';
      } else {
        confirmPassword.style.borderColor = '#d2d2d7';
      }
    });

    // Auto-open login modal if there's an error
    window.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      
      if (urlParams.get('error') === 'invalid_credentials') {
        const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
        loginModal.show();
      }
      
      if (urlParams.get('registration') === 'success') {
        const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
        loginModal.show();
      }
    });
  </script>
</body>
</html>