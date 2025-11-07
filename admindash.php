<?php 
// Include authentication check
include 'auth_check.php'; 
require_once 'db_connect.php';

try {
    // Fetch total users
    $stmt = $conn->query("SELECT COUNT(*) AS total_users FROM users");
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

    // Fetch total products
    $stmt = $conn->query("SELECT COUNT(*) AS total_products FROM products");
    $productCount = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];

    // Fetch total orders
    $stmt = $conn->query("SELECT COUNT(*) AS total_orders FROM orders");
    $orderCount = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];

    // Fetch pending orders
    $stmt = $conn->query("SELECT COUNT(*) AS pending_orders FROM orders WHERE status='Pending'");
    $pendingCount = $stmt->fetch(PDO::FETCH_ASSOC)['pending_orders'];

    // Fetch total revenue
    $stmt = $conn->query("SELECT SUM(total_amount) AS total_revenue FROM orders WHERE status IN ('Processing','Shipped','Delivered')");
    $revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;

    // Fetch recent orders for dashboard (last 5 orders)
    $stmt = $conn->prepare("
        SELECT 
            o.order_id,
            o.total_amount,
            o.status,
            o.order_date,
            u.full_name,
            GROUP_CONCAT(p.name SEPARATOR ', ') as products
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        GROUP BY o.order_id
        ORDER BY o.order_date DESC
    LIMIT 5
");
$stmt->execute();
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);


    $totalProducts = $productCount;
    $totalOrders = $orderCount;
    $totalCustomers = $userCount;
    

} catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - R&M Apple Gadgets</title>
  <style>
    /* Multi-select styling */
    select[multiple].form-select {
      padding: 8px;
    }

    select[multiple].form-select option {
      padding: 8px 12px;
      border-radius: 6px;
      margin-bottom: 4px;
      cursor: pointer;
    }

    select[multiple].form-select option:checked {
      background: linear-gradient(135deg, #0071e3 0%, #0077ed 100%);
      color: white;
      font-weight: 600;
    }

    select[multiple].form-select option:hover {
      background: #e3f2fd;
    }

    select[multiple].form-select option:checked:hover {
      background: linear-gradient(135deg, #0077ed 0%, #0082ff 100%);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'SF Pro Display', sans-serif;
      background: #f5f5f7;
      color: #1d1d1f;
    }

    /* Sidebar */
    .sidebar {
      position: fixed;
      left: 0;
      top: 0;
      width: 260px;
      height: 100vh;
      background: #1d1d1f;
      color: #f5f5f7;
      padding: 30px 0;
      z-index: 1000;
    }

    .sidebar-header {
      padding: 0 30px 30px;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .sidebar-header h2 {
      font-size: 1.5em;
      font-weight: 600;
      margin-bottom: 5px;
    }

    .sidebar-header p {
      font-size: 0.85em;
      color: #86868b;
    }

    .sidebar-nav {
      margin-top: 30px;
    }

    .nav-item {
      padding: 15px 30px;
      display: flex;
      align-items: center;
      gap: 15px;
      cursor: pointer;
      transition: all 0.3s;
      color: #f5f5f7;
      text-decoration: none;
      border-left: 3px solid transparent;
    }

    .nav-item:hover {
      background: rgba(255,255,255,0.05);
      border-left-color: #0071e3;
    }

    .nav-item.active {
      background: rgba(0,113,227,0.1);
      border-left-color: #0071e3;
      color: #0071e3;
    }

    .nav-icon {
      font-size: 1.3em;
    }

    /* Main Content */
    .main-content {
      margin-left: 260px;
      padding: 30px 40px;
      min-height: 100vh;
    }

    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 40px;
      background: #fff;
      padding: 20px 30px;
      border-radius: 15px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .top-bar h1 {
      font-size: 2em;
      font-weight: 600;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .user-avatar {
      width: 45px;
      height: 45px;
      border-radius: 50%;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-weight: 600;
    }

    /* Stats Cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }

    .stat-card {
      background: #fff;
      padding: 25px;
      border-radius: 15px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      transition: transform 0.3s;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }

    .stat-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }

    .stat-icon {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5em;
    }

    .stat-icon.blue { background: #e3f2fd; color: #1976d2; }
    .stat-icon.green { background: #e8f5e9; color: #388e3c; }
    .stat-icon.orange { background: #fff3e0; color: #f57c00; }
    .stat-icon.purple { background: #f3e5f5; color: #7b1fa2; }

    .stat-value {
      font-size: 2em;
      font-weight: 600;
      color: #1d1d1f;
      margin-bottom: 5px;
    }

    .stat-label {
      color: #86868b;
      font-size: 0.9em;
    }

    /* Content Sections */
    .content-section {
      display: none;
    }

    .content-section.active {
      display: block;
    }

    .section-card {
      background: #fff;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      margin-bottom: 30px;
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f5f5f7;
      flex-wrap: wrap;
      gap: 15px;
    }

    .section-title {
      font-size: 1.5em;
      font-weight: 600;
    }

    .header-actions {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .btn-primary {
      background: #0071e3;
      color: #fff;
      border: none;
      padding: 12px 24px;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.3s;
      font-size: 0.95em;
    }

    .btn-primary:hover {
      background: #0077ed;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0,113,227,0.3);
    }

    .btn-secondary {
      background: #f5f5f7;
      color: #1d1d1f;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.3s;
      margin-left: 10px;
    }

    .btn-secondary:hover {
      background: #e8e8ed;
    }

    .btn-danger {
      background: #ff3b30;
      color: #fff;
    }

    .btn-danger:hover {
      background: #ff2d20;
    }

    /* Table */
    .data-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    .data-table thead {
      background: #f5f5f7;
    }

    .data-table th,
    .data-table td {
      padding: 15px;
      text-align: left;
      border-bottom: 1px solid #e8e8ed;
    }

    .data-table th {
      font-weight: 600;
      color: #1d1d1f;
      font-size: 0.9em;
    }

    .data-table tbody tr {
      transition: background 0.2s;
    }

    .data-table tbody tr:hover {
      background: #fafafa;
    }

    .product-img {
      width: 50px;
      height: 50px;
      object-fit: contain;
      border-radius: 8px;
      background: #f5f5f7;
      padding: 5px;
    }

    .stock-badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.85em;
      font-weight: 500;
    }

    .stock-high { background: #e8f5e9; color: #388e3c; }
    .stock-medium { background: #fff3e0; color: #f57c00; }
    .stock-low { background: #ffebee; color: #d32f2f; }

    /* Form */
    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
      margin-top: 25px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .form-group.full-width {
      grid-column: 1 / -1;
    }

    .form-label {
      font-weight: 500;
      color: #1d1d1f;
      font-size: 0.95em;
    }

    .form-input,
    .form-select,
    .form-textarea {
      padding: 12px 15px;
      border: 2px solid #e8e8ed;
      border-radius: 10px;
      font-size: 0.95em;
      transition: all 0.3s;
      font-family: inherit;
    }

    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
      outline: none;
      border-color: #0071e3;
      box-shadow: 0 0 0 4px rgba(0,113,227,0.1);
    }

    .form-textarea {
      resize: vertical;
      min-height: 100px;
    }

    /* Modal */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 2000;
      align-items: center;
      justify-content: center;
    }

    .modal.active {
      display: flex;
    }

    .modal-content {
      background: #fff;
      padding: 40px;
      border-radius: 20px;
      max-width: 700px;
      width: 90%;
      max-height: 90vh;
      overflow-y: auto;
      animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .modal-header {
      margin-bottom: 30px;
    }

    .modal-header h2 {
      font-size: 1.8em;
      font-weight: 600;
    }

    .modal-actions {
      display: flex;
      gap: 15px;
      margin-top: 30px;
      justify-content: flex-end;
    }

    .action-btn {
      padding: 8px 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 0.9em;
      transition: all 0.3s;
    }

    .edit-btn {
      background: #0071e3;
      color: #fff;
    }

    .delete-btn {
      background: #ff3b30;
      color: #fff;
    }

    .action-btn:hover {
      opacity: 0.8;
      transform: scale(1.05);
    }

    .search-bar {
      padding: 12px 20px;
      border: 2px solid #e8e8ed;
      border-radius: 10px;
      width: 300px;
      font-size: 0.95em;
    }

    .search-bar:focus {
      outline: none;
      border-color: #0071e3;
    }

    .loading {
      text-align: center;
      padding: 40px;
      color: #86868b;
    }

    .no-data {
      text-align: center;
      padding: 40px;
      color: #86868b;
      font-size: 1.1em;
    }

    .alert {
      padding: 15px 20px;
      border-radius: 10px;
      margin-bottom: 20px;
      display: none;
    }

    .alert.success {
      background: #e8f5e9;
      color: #388e3c;
      border-left: 4px solid #388e3c;
    }

    .alert.error {
      background: #ffebee;
      color: #d32f2f;
      border-left: 4px solid #d32f2f;
    }

    .alert.show {
      display: block;
      animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @media (max-width: 768px) {
      .sidebar {
        width: 70px;
      }

      .sidebar-header h2,
      .sidebar-header p,
      .nav-item span {
        display: none;
      }

      .main-content {
        margin-left: 70px;
        padding: 20px;
      }

      .form-grid {
        grid-template-columns: 1fr;
      }

      .top-bar {
        flex-direction: column;
        gap: 15px;
      }

      .search-bar {
        width: 100%;
      }

      .section-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .header-actions {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <!-- Alert Message -->
  <div id="alert" class="alert"></div>

  <!-- Sidebar -->
  <div class="sidebar">
    <div class="sidebar-header">
      <h2>R&M Admin</h2>
      <p>Apple Gadgets</p>
    </div>
    <nav class="sidebar-nav">
      <a class="nav-item active" onclick="showSection('dashboard')">
        <span class="nav-icon">📊</span>
        <span>Dashboard</span>
      </a>
      <a class="nav-item" onclick="showSection('products')">
        <span class="nav-icon">📱</span>
        <span>Products</span>
      </a>
      <a class="nav-item" onclick="showSection('inventory')">
        <span class="nav-icon">📦</span>
        <span>Inventory</span>
      </a>
      <a class="nav-item" onclick="showSection('orders')">
        <span class="nav-icon">🛒</span>
        <span>Orders</span>
      </a>
      <a class="nav-item" onclick="showSection('customers')">
        <span class="nav-icon">👥</span>
        <span>Customers</span>
      </a>
      <a class="nav-item" onclick="showSection('reports')">
        <span class="nav-icon">📈</span>
        <span>Reports</span>
      </a>
      <a class="nav-item" onclick="showSection('settings')">
        <span class="nav-icon">⚙️</span>
        <span>Settings</span>
      </a>
      <a href="logout.php" class="nav-item" style="color: #ff3b30;">
        <span class="nav-icon">🚪</span>
        <span>Logout</span>
      </a>
    </nav>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Top Bar -->
    <div class="top-bar">
      <h1 id="page-title">Dashboard</h1>
      <div class="user-info">
        <div>
          <div style="font-weight: 600;">Admin User</div>
          <div style="font-size: 0.85em; color: #86868b;">admin@rmgadgets.com</div>
        </div>
        <div class="user-avatar">RM</div>
      </div>
    </div>

    <!-- Dashboard Section -->
    <div id="dashboard" class="content-section active">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-header">
            <div>
              <div class="stat-value"><?php echo $totalProducts; ?></div>
              <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-icon blue">📱</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-header">
            <div>
              <div class="stat-value">₱<?php echo number_format($revenue, 2); ?></div>
              <div class="stat-label">Total Sales</div>
            </div>
            <div class="stat-icon green">💰</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-header">
            <div>
              <div class="stat-value"><?php echo $totalOrders; ?></div>
              <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-icon orange">🛒</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-header">
            <div>
              <div class="stat-value"><?php echo $totalCustomers; ?></div>
              <div class="stat-label">Customers</div>
            </div>
            <div class="stat-icon purple">👥</div>
          </div>
        </div>
      </div>

      <div class="section-card">
        <h3 class="section-title">Recent Orders</h3>
        <?php if (empty($recentOrders)): ?>
          <div class="no-data">No orders yet</div>
        <?php else: ?>
          <table class="data-table">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Products</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentOrders as $order): ?>
                <tr>
                  <td><strong>#<?= $order['order_id'] ?></strong></td>
                  <td><?= htmlspecialchars($order['full_name'] ?? 'N/A') ?></td>
                  <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($order['products']) ?>">
                    <?= htmlspecialchars($order['products']) ?>
                  </td>
                  <td><strong>₱<?= number_format($order['total_amount'], 2) ?></strong></td>
                  <td>
                    <?php
                      $statusClass = '';
                      switch($order['status']) {
                        case 'Pending': $statusClass = 'stock-medium'; break;
                        case 'Processing': $statusClass = 'stock-badge'; break;
                        case 'Shipped':
                        case 'Delivered': $statusClass = 'stock-high'; break;
                        case 'Cancelled': $statusClass = 'stock-low'; break;
                      }
                    ?>
                    <span class="stock-badge <?= $statusClass ?>"><?= $order['status'] ?></span>
                  </td>
                  <td><?= date('M j, Y', strtotime($order['order_date'])) ?></td>
                  <td>
                    <button class="action-btn edit-btn" onclick="showSection('orders'); setTimeout(() => viewOrderDetails(<?= $order['order_id'] ?>), 100)">View</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

      <div class="section-card">
        <h3 class="section-title">Low Stock Alert</h3>
        <table class="data-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>SKU</th>
              <th>Current Stock</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="low-stock-table">
            <tr><td colspan="5" class="loading">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Products Section -->
    <div id="products" class="content-section">
      <div class="section-card">
        <div class="section-header">
          <h3 class="section-title">Product Management</h3>
          <div class="header-actions">
            <input type="text" id="product-search" class="search-bar" placeholder="Search products...">
            <button class="btn-primary" onclick="openAddProductModal()">+ Add New Product</button>
          </div>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Image</th>
              <th>Product Name</th>
              <th>Category</th>
              <th>Price</th>
              <th>Stock</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="products-table-body">
            <tr><td colspan="7" class="loading">Loading products...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Inventory Section -->
    <div id="inventory" class="content-section">
      <div class="section-card">
        <div class="section-header">
          <h3 class="section-title">Inventory Management</h3>
          <button class="btn-primary" onclick="exportInventory()">Export Inventory</button>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Image</th>
              <th>Product Name</th>
              <th>Category</th>
              <th>Price</th>
              <th>Stock</th>
              <th>Status</th>
              <th>Featured</th>
              <th>Actions</th>
            </tr>
        </thead>
          <tbody id="inventory-table-body">
            <tr><td colspan="7" class="loading">Loading inventory...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Orders Section -->
    <div id="orders" class="content-section">
      <div class="section-card">
        <div class="section-header">
          <h3 class="section-title">Order Management</h3>
          <div class="header-actions">
            <select id="order-status-filter" class="form-select" style="width: 180px; padding: 10px; margin-right: 10px;">
              <option value="">All Status</option>
              <option value="Pending">Pending</option>
              <option value="Processing">Processing</option>
              <option value="Shipped">Shipped</option>
              <option value="Delivered">Delivered</option>
              <option value="Cancelled">Cancelled</option>
            </select>
            <input type="text" id="order-search" class="search-bar" placeholder="Search orders...">
          </div>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Items</th>
              <th>Total</th>
              <th>Payment</th>
              <th>Status</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="orders-table-body">
            <tr><td colspan="8" class="loading">Loading orders...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Order Details Modal -->
    <div id="orderModal" class="modal">
      <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
          <h2>Order Details</h2>
        </div>
        
        <div id="order-details-content">
          <!-- Order Info Section -->
          <div style="background: #f5f5f7; padding: 20px; border-radius: 12px; margin-bottom: 25px;">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
              <div>
                <div style="color: #86868b; font-size: 0.9em; margin-bottom: 5px;">Order ID</div>
                <div style="font-weight: 600; font-size: 1.1em;" id="modal-order-id">-</div>
              </div>
              <div>
                <div style="color: #86868b; font-size: 0.9em; margin-bottom: 5px;">Order Date</div>
                <div style="font-weight: 600;" id="modal-order-date">-</div>
              </div>
              <div>
                <div style="color: #86868b; font-size: 0.9em; margin-bottom: 5px;">Payment Method</div>
                <div style="font-weight: 600;" id="modal-payment-method">-</div>
              </div>
              <div>
                <div style="color: #86868b; font-size: 0.9em; margin-bottom: 5px;">Total Amount</div>
                <div style="font-weight: 700; font-size: 1.2em; color: #0071e3;" id="modal-total-amount">-</div>
              </div>
            </div>
          </div>

          <!-- Customer Info -->
          <div style="margin-bottom: 25px;">
            <h3 style="font-size: 1.2em; font-weight: 600; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #f5f5f7;">Customer Information</h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
              <div>
                <div style="color: #86868b; font-size: 0.9em; margin-bottom: 5px;">Name</div>
                <div style="font-weight: 600;" id="modal-customer-name">-</div>
              </div>
              <div>
                <div style="color: #86868b; font-size: 0.9em; margin-bottom: 5px;">Email</div>
                <div id="modal-customer-email">-</div>
              </div>
              <div>
                <div style="color: #86868b; font-size: 0.9em; margin-bottom: 5px;">Phone</div>
                <div id="modal-customer-phone">-</div>
              </div>
              <div>
                <div style="color: #86868b; font-size: 0.9em; margin-bottom: 5px;">Username</div>
                <div id="modal-customer-username">-</div>
              </div>
            </div>
            <div style="margin-top: 15px;">
              <div style="color: #86868b; font-size: 0.9em; margin-bottom: 5px;">Delivery Address</div>
              <div style="font-weight: 500;" id="modal-delivery-address">-</div>
            </div>
          </div>

          <!-- Order Items -->
          <div style="margin-bottom: 25px;">
            <h3 style="font-size: 1.2em; font-weight: 600; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #f5f5f7;">Order Items</h3>
            <div id="modal-order-items">
              <!-- Items will be loaded here -->
            </div>
          </div>

          <!-- Order Status -->
          <div style="margin-bottom: 25px;">
            <h3 style="font-size: 1.2em; font-weight: 600; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #f5f5f7;">Update Order Status</h3>
            <div style="display: flex; gap: 15px; align-items: center;">
              <select id="modal-status-select" class="form-select" style="flex: 1; max-width: 300px;">
                <option value="Pending">Pending</option>
                <option value="Processing">Processing</option>
                <option value="Shipped">Shipped</option>
                <option value="Delivered">Delivered</option>
                <option value="Cancelled">Cancelled</option>
              </select>
              <button class="btn-primary" onclick="updateOrderStatus()">Update Status</button>
            </div>
          </div>
        </div>

        <div class="modal-actions">
          <button class="btn-secondary" onclick="closeOrderModal()">Close</button>
          <button class="btn-danger" onclick="deleteOrderConfirm()">Delete Order</button>
        </div>
      </div>
    </div>

    <style>
    /* Additional styles for order items in modal */
    .order-item-row {
      display: flex;
      gap: 15px;
      padding: 15px;
      border: 1px solid #e8e8ed;
      border-radius: 10px;
      margin-bottom: 10px;
      align-items: center;
    }

    .order-item-image {
      width: 60px;
      height: 60px;
      object-fit: contain;
      background: #f5f5f7;
      border-radius: 8px;
      padding: 5px;
    }

    .order-item-details {
      flex: 1;
    }

    .order-item-name {
      font-weight: 600;
      margin-bottom: 5px;
    }

    .order-item-specs {
      color: #86868b;
      font-size: 0.9em;
    }

    .order-item-price {
      text-align: right;
      font-weight: 700;
      font-size: 1.1em;
    }

    .status-badge-pending {
      background: #fff3cd;
      color: #856404;
    }

    .status-badge-processing {
      background: #cfe2ff;
      color: #084298;
    }

    .status-badge-shipped {
      background: #d1e7dd;
      color: #0f5132;
    }

    .status-badge-delivered {
      background: #d1e7dd;
      color: #0a3622;
    }

    .status-badge-cancelled {
      background: #f8d7da;
      color: #842029;
    }
    </style>

    <script>
    // Order Management Functions
    let allOrders = [];
    let currentOrderId = null;

    // Fetch all orders
    async function fetchOrders(search = '', status = '') {
      try {
        const response = await fetch(`order_api.php?action=getAll&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`);
        const result = await response.json();
        
        if (result.success) {
          allOrders = result.data;
          renderOrders(allOrders);
        } else {
          showAlert('Error loading orders: ' + result.message, 'error');
        }
      } catch (error) {
        showAlert('Error connecting to server', 'error');
        console.error(error);
      }
    }

    // Render orders table
    function renderOrders(orders) {
      const tbody = document.getElementById('orders-table-body');
      
      if (orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="no-data">No orders found</td></tr>';
        return;
      }
      
      tbody.innerHTML = orders.map(order => {
        const statusClass = getStatusClass(order.status);
        const formattedDate = new Date(order.order_date).toLocaleDateString('en-US', {
          month: 'short',
          day: 'numeric',
          year: 'numeric'
        });
        
        return `
          <tr>
            <td><strong>#${order.order_id}</strong></td>
            <td>
              <div style="font-weight: 600;">${order.full_name || 'N/A'}</div>
              <div style="font-size: 0.85em; color: #86868b;">${order.email || ''}</div>
            </td>
            <td>${order.item_count} item(s)</td>
            <td><strong>₱${parseFloat(order.total_amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}</strong></td>
            <td>${order.payment_method}</td>
            <td><span class="stock-badge ${statusClass}">${order.status}</span></td>
            <td>${formattedDate}</td>
            <td>
              <button class="action-btn edit-btn" onclick="viewOrderDetails(${order.order_id})">View</button>
            </td>
          </tr>
        `;
      }).join('');
    }

    // View order details
    async function viewOrderDetails(orderId) {
      currentOrderId = orderId;
      
      try {
        const response = await fetch(`order_api.php?action=getDetails&order_id=${orderId}`);
        const result = await response.json();
        
        if (result.success) {
          displayOrderDetails(result.order, result.items);
          document.getElementById('orderModal').classList.add('active');
        } else {
          showAlert('Error loading order details: ' + result.message, 'error');
        }
      } catch (error) {
        showAlert('Error connecting to server', 'error');
        console.error(error);
      }
    }

    // Display order details in modal
    function displayOrderDetails(order, items) {
      // Order info
      document.getElementById('modal-order-id').textContent = '#' + order.order_id;
      document.getElementById('modal-order-date').textContent = new Date(order.order_date).toLocaleString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
      document.getElementById('modal-payment-method').textContent = order.payment_method;
      document.getElementById('modal-total-amount').textContent = '₱' + parseFloat(order.total_amount).toLocaleString('en-PH', {minimumFractionDigits: 2});
      
      // Customer info
      document.getElementById('modal-customer-name').textContent = order.full_name || 'N/A';
      document.getElementById('modal-customer-email').textContent = order.email || 'N/A';
      document.getElementById('modal-customer-phone').textContent = order.phone || 'N/A';
      document.getElementById('modal-customer-username').textContent = order.username || 'N/A';
      document.getElementById('modal-delivery-address').textContent = order.address || 'N/A';
      
      // Order status
      document.getElementById('modal-status-select').value = order.status;
      
      // Order items
      const itemsContainer = document.getElementById('modal-order-items');
      itemsContainer.innerHTML = items.map(item => `
        <div class="order-item-row">
          <img src="${item.image_url || 'images/placeholder.png'}" alt="${item.name}" class="order-item-image" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2260%22 height=%2260%22%3E%3Crect fill=%22%23f5f5f7%22 width=%2260%22 height=%2260%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22%2386868b%22 font-size=%2224%22%3E📱%3C/text%3E%3C/svg%3E'">
          <div class="order-item-details">
            <div class="order-item-name">${item.name}</div>
            <div class="order-item-specs">
              Quantity: ${item.quantity}
              ${item.color ? ' | Color: ' + item.color : ''}
              ${item.storage ? ' | Storage: ' + item.storage : ''}
            </div>
          </div>
          <div class="order-item-price">
            ₱${(parseFloat(item.price) * parseInt(item.quantity)).toLocaleString('en-PH', {minimumFractionDigits: 2})}
          </div>
        </div>
      `).join('');
    }

    // Update order status
    async function updateOrderStatus() {
      if (!currentOrderId) return;
      
      const newStatus = document.getElementById('modal-status-select').value;
      
      if (!confirm(`Update order status to "${newStatus}"?`)) return;
      
      try {
        const response = await fetch('order_api.php?action=updateStatus', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            order_id: currentOrderId,
            status: newStatus
          })
        });
        
        const result = await response.json();
        
        if (result.success) {
          showAlert(result.message, 'success');
          closeOrderModal();
          fetchOrders();
        } else {
          showAlert('Error: ' + result.message, 'error');
        }
      } catch (error) {
        showAlert('Error updating order status', 'error');
        console.error(error);
      }
    }

    // Delete order
    async function deleteOrderConfirm() {
      if (!currentOrderId) return;
      
      if (!confirm('Are you sure you want to delete this order? This action cannot be undone.')) return;
      
      try {
        const response = await fetch('order_api.php?action=delete', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ order_id: currentOrderId })
        });
        
        const result = await response.json();
        
        if (result.success) {
          showAlert(result.message, 'success');
          closeOrderModal();
          fetchOrders();
        } else {
          showAlert('Error: ' + result.message, 'error');
        }
      } catch (error) {
        showAlert('Error deleting order', 'error');
        console.error(error);
      }
    }

    // Close order modal
    function closeOrderModal() {
      document.getElementById('orderModal').classList.remove('active');
      currentOrderId = null;
    }

    // Get status badge class
    function getStatusClass(status) {
      const classes = {
        'Pending': 'status-badge-pending stock-medium',
        'Processing': 'status-badge-processing stock-badge',
        'Shipped': 'status-badge-shipped stock-high',
        'Delivered': 'status-badge-delivered stock-high',
        'Cancelled': 'status-badge-cancelled stock-low'
      };
      return classes[status] || 'stock-badge';
    }

    // Search orders
    let orderSearchTimeout;
    document.getElementById('order-search').addEventListener('input', function(e) {
      clearTimeout(orderSearchTimeout);
      orderSearchTimeout = setTimeout(() => {
        const status = document.getElementById('order-status-filter').value;
        fetchOrders(e.target.value, status);
      }, 500);
    });

    // Filter by status
    document.getElementById('order-status-filter').addEventListener('change', function(e) {
      const search = document.getElementById('order-search').value;
      fetchOrders(search, e.target.value);
    });

    // Close modal when clicking outside
    document.getElementById('orderModal').addEventListener('click', function(e) {
      if (e.target === this) closeOrderModal();
    });

</script>
      <!-- Customers Section -->
      <div id="customers" class="content-section">
        <div class="section-card">
          <div class="section-header">
            <h3 class="section-title">Customer Management</h3>
          </div>
          <table class="data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Total Orders</th>
                <th>Joined Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="customers-table-body">
              <tr><td colspan="7" class="loading">Loading customers...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    <!-- Reports Section -->
    <div id="reports" class="content-section">
      <div class="section-card">
        <h3 class="section-title">Sales Reports</h3>
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-label">Today's Sales</div>
            <div class="stat-value">₱145,980</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">This Week</div>
            <div class="stat-value">₱892,450</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">This Month</div>
            <div class="stat-value">₱2.4M</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">This Year</div>
            <div class="stat-value">₱18.5M</div>
          </div>
        </div>
      </div>

      <div class="section-card">
        <h3 class="section-title">Top Selling Products</h3>
        <table class="data-table">
          <thead>
            <tr>
              <th>Rank</th>
              <th>Product</th>
              <th>Units Sold</th>
              <th>Revenue</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>1</td>
              <td>iPhone 16 Pro Max</td>
              <td>124</td>
              <td>₱10,786,760</td>
            </tr>
            <tr>
              <td>2</td>
              <td>iPhone 15</td>
              <td>98</td>
              <td>₱4,899,020</td>
            </tr>
            <tr>
              <td>3</td>
              <td>iPhone 16</td>
              <td>87</td>
              <td>₱3,479,130</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Settings Section -->
    <div id="settings" class="content-section">
      <div class="section-card">
        <h3 class="section-title">Store Settings</h3>
        <div class="form-grid">
          <div class="form-group full-width">
            <label class="form-label">Store Name</label>
            <input type="text" class="form-input" value="R&M Apple Gadgets">
          </div>
          <div class="form-group">
            <label class="form-label">Tax Rate (%)</label>
            <input type="number" class="form-input" value="12">
          </div>
        </div>
        <div style="margin-top: 30px;">
          <button class="btn-primary">Save Changes</button>
          <button class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Add/Edit Product Modal -->
  <div id="productModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modal-title">Add New Product</h2>
      </div>
      <form id="productForm">
        <input type="hidden" id="product-id">
        <div class="form-grid">
          <div class="form-group full-width">
            <label class="form-label">Product Name *</label>
            <input type="text" id="product-name" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label">Category *</label>
            <select id="product-category" class="form-select" required>
              <option value="">Select Category</option>
              <option value="iphone">iPhone</option>
              <option value="ipad">iPad</option>
              <option value="macbook">MacBook</option>
              <option value="accessories">Accessories</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Condition *</label>
            <select id="product-condition" class="form-select" required>
              <option value="new">Brand New</option>
              <option value="refurbished">Refurbished</option>
              <option value="pre-owned">Pre-Owned</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Price (₱) *</label>
            <input type="number" id="product-price" class="form-input" step="0.01" required>
          </div>
          <div class="form-group">
            <label class="form-label">Original Price (₱)</label>
            <input type="number" id="product-original-price" class="form-input" step="0.01">
          </div>
          <div class="form-group">
            <label class="form-label">Stock Quantity *</label>
            <input type="number" id="product-stock" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label">SKU</label>
            <input type="text" id="product-sku" class="form-input" placeholder="e.g., IP17PM-DT-512">
          </div>
          <div class="form-group">
            <label class="form-label">Color</label>
            <input type="text" id="product-color" class="form-input" placeholder="e.g., Desert Titanium">
          </div>
          <div class="form-group">
            <label class="form-label">Storage Options (Hold Ctrl/Cmd to select multiple)</label>
            <select id="product-storage" class="form-select" multiple style="height: 120px;">
              <option value="64GB">64GB</option>
              <option value="128GB">128GB</option>
              <option value="256GB">256GB</option>
              <option value="512GB">512GB</option>
              <option value="1TB">1TB</option>
            </select>
            <small style="color: #86868b; font-size: 0.85em; margin-top: 5px; display: block;">
              Selected: <span id="selected-storage-display">None</span>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label">Model Year</label>
            <input type="number" id="product-year" class="form-input" placeholder="2025">
          </div>
          <div class="form-group" id="macbook-chip-field" style="display: none;">
            <label class="form-label">Chip (MacBook only)</label>
            <select id="product-chip" class="form-select">
              <option value="">None</option>
              <option value="M5">M5</option>
              <option value="M4">M4</option>
              <option value="M4 Pro">M4 Pro</option>
              <option value="M4 Max">M4 Max</option>
              <option value="M3">M3</option>
            </select>
          </div>

          <div class="form-group" id="macbook-memory-field" style="display: none;">
            <label class="form-label">Unified Memory (MacBook only)</label>
            <select id="product-unified-memory" class="form-select">
              <option value="">None</option>
              <option value="16GB">16GB</option>
              <option value="24GB">24GB</option>
              <option value="36GB">36GB</option>
              <option value="48GB">48GB</option>
              <option value="64GB">64GB</option>
              <option value="96GB">96GB</option>
              <option value="128GB">128GB</option>
            </select>
          </div>

          <div class="form-group" id="macbook-screen-field" style="display: none;">
            <label class="form-label">Screen Size (MacBook only)</label>
            <select id="product-screen-size" class="form-select">
              <option value="">None</option>
              <option value="13-inch">13-inch</option>
              <option value="14-inch">14-inch</option>
              <option value="15-inch">15-inch</option>
              <option value="16-inch">16-inch</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select id="product-status" class="form-select">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="form-group full-width">
            <label class="form-label">Description</label>
            <textarea id="product-description" class="form-textarea" placeholder="Enter product description..."></textarea>
          </div>
          <div class="form-group full-width">
            <label class="form-label">Product Image URL</label>
            <input type="text" id="product-image" class="form-input" placeholder="images/product.png">
          </div>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" onclick="closeProductModal()">Cancel</button>
          <button type="submit" class="btn-primary">Save Product</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Quick Stock Update Modal -->
  <div id="stockModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
      <div class="modal-header">
        <h2>Update Stock</h2>
      </div>
      <form id="stockForm">
        <input type="hidden" id="stock-product-id">
        <div class="form-group">
          <label class="form-label">Product</label>
          <input type="text" id="stock-product-name" class="form-input" readonly style="background: #f5f5f7;">
        </div>
        <div class="form-group">
          <label class="form-label">Current Stock</label>
          <input type="text" id="stock-current" class="form-input" readonly style="background: #f5f5f7;">
        </div>
        <div class="form-group">
          <label class="form-label">New Stock Quantity *</label>
          <input type="number" id="stock-new" class="form-input" required min="0">
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" onclick="closeStockModal()">Cancel</button>
          <button type="submit" class="btn-primary">Update Stock</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Global variables
    let allProducts = [];
    let currentEditId = null;

    // API Functions
    async function fetchProducts(search = '') {
      try {
        const response = await fetch(`product_api.php?action=getAll&search=${encodeURIComponent(search)}`);
        const result = await response.json();
        
        if (result.success) {
          allProducts = result.data;
          renderProducts(allProducts);
          renderInventory(allProducts);
          renderLowStock(allProducts);
        } else {
          showAlert('Error loading products: ' + result.message, 'error');
        }
      } catch (error) {
        showAlert('Error connecting to server', 'error');
        console.error(error);
      }
    }

    async function saveProduct(formData) {
      const action = currentEditId ? 'update' : 'add';
      
      try {
        const response = await fetch(`product_api.php?action=${action}`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
          showAlert(result.message, 'success');
          closeProductModal();
          fetchProducts();
        } else {
          showAlert('Error: ' + result.message, 'error');
        }
      } catch (error) {
        showAlert('Error saving product', 'error');
        console.error(error);
      }
    }

    async function deleteProduct(id) {
      if (!confirm('Are you sure you want to delete this product?')) return;
      
      try {
        const response = await fetch('product_api.php?action=delete', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id })
        });
        
        const result = await response.json();
        
        if (result.success) {
          showAlert(result.message, 'success');
          fetchProducts();
        } else {
          showAlert('Error: ' + result.message, 'error');
        }
      } catch (error) {
        showAlert('Error deleting product', 'error');
        console.error(error);
      }
    }

    async function updateStock(id, quantity) {
      try {
        const response = await fetch('product_api.php?action=updateStock', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id, quantity })
        });
        
        const result = await response.json();
        
        if (result.success) {
          showAlert(result.message, 'success');
          closeStockModal();
          fetchProducts();
        } else {
          showAlert('Error: ' + result.message, 'error');
        }
      } catch (error) {
        showAlert('Error updating stock', 'error');
        console.error(error);
      }
    }

    // Render Functions
    function renderProducts(products) {
      const tbody = document.getElementById('products-table-body');
      
      if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="no-data">No products found</td></tr>';
        return;
      }
      
      tbody.innerHTML = products.map(product => {
        const stock = parseInt(product.stock_quantity);
        const stockClass = stock > 20 ? 'stock-high' : stock > 10 ? 'stock-medium' : 'stock-low';
        const statusText = stock > 10 ? 'In Stock' : stock > 0 ? 'Low Stock' : 'Out of Stock';
        const isFeatured = parseInt(product.is_featured) === 1;
        
        return `
          <tr>
            <td><img src="${product.image_url || 'images/placeholder.png'}" alt="${product.name}" class="product-img" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2250%22 height=%2250%22%3E%3Crect fill=%22%23f5f5f7%22 width=%2250%22 height=%2250%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22%2386868b%22 font-size=%2220%22%3E📱%3C/text%3E%3C/svg%3E'"></td>
            <td>
              <strong>${product.name}</strong><br>
              <small style="color: #86868b;">${formatCondition(product.condition_type)}</small>
              ${isFeatured ? '<br><span style="background: #ffd700; color: #000; padding: 2px 8px; border-radius: 4px; font-size: 0.75em; font-weight: 600;">⭐ FEATURED</span>' : ''}
            </td>
            <td>${product.category}</td>
            <td>₱${parseFloat(product.price).toLocaleString()}</td>
            <td>${stock}</td>
            <td><span class="stock-badge ${stockClass}">${statusText}</span></td>
            <td>
              <button class="action-btn ${isFeatured ? 'btn-warning' : 'btn-success'}" 
                      onclick="toggleFeatured(${product.id}, ${isFeatured ? 0 : 1})"
                      style="background: ${isFeatured ? '#ffc107' : '#28a745'}; color: #fff; margin-bottom: 5px;">
                ${isFeatured ? '⭐ Unfeature' : '⭐ Feature'}
              </button>
            </td>
            <td>
              <button class="action-btn edit-btn" onclick="editProduct(${product.id})">Edit</button>
              <button class="action-btn delete-btn" onclick="deleteProduct(${product.id})">Delete</button>
            </td>
          </tr>
        `;
      }).join('');
    }
    async function toggleFeatured(id, isFeatured) {
      try {
        const response = await fetch('product_api.php?action=toggleFeatured', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id, is_featured: isFeatured })
        });
        
        const result = await response.json();
        
        if (result.success) {
          showAlert(result.message, 'success');
          fetchProducts();
        } else {
          showAlert('Error: ' + result.message, 'error');
        }
      } catch (error) {
        showAlert('Error updating featured status', 'error');
        console.error(error);
      }
    }

    function renderInventory(products) {
      const tbody = document.getElementById('inventory-table-body');
      
      if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="no-data">No inventory items found</td></tr>';
        return;
      }
      
      tbody.innerHTML = products.map(product => {
        const stock = parseInt(product.stock_quantity);
        const stockClass = stock > 20 ? 'stock-high' : stock > 10 ? 'stock-medium' : 'stock-low';
        const statusText = stock > 10 ? 'In Stock' : stock > 0 ? 'Low Stock' : 'Out of Stock';
        
        return `
          <tr>
            <td>${product.sku || 'N/A'}</td>
            <td>${product.name}</td>
            <td>${product.color || 'N/A'}</td>
            <td>${product.storage || 'N/A'}</td>
            <td>${stock}</td>
            <td><span class="stock-badge ${stockClass}">${statusText}</span></td>
            <td>
              <button class="action-btn edit-btn" onclick="openStockModal(${product.id}, '${product.name}', ${stock})">Update Stock</button>
            </td>
          </tr>
        `;
      }).join('');
    }

    function renderLowStock(products) {
      const tbody = document.getElementById('low-stock-table');
      const lowStockProducts = products.filter(p => parseInt(p.stock_quantity) <= 10);
      
      if (lowStockProducts.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="no-data">No low stock items</td></tr>';
        return;
      }
      
      tbody.innerHTML = lowStockProducts.map(product => {
        const stock = parseInt(product.stock_quantity);
        const stockClass = stock > 5 ? 'stock-medium' : 'stock-low';
        const statusText = stock > 5 ? 'Medium' : 'Low Stock';
        
        return `
          <tr>
            <td>${product.name}</td>
            <td>${product.sku || 'N/A'}</td>
            <td>${stock}</td>
            <td><span class="stock-badge ${stockClass}">${statusText}</span></td>
            <td><button class="action-btn edit-btn" onclick="openStockModal(${product.id}, '${product.name}', ${stock})">Restock</button></td>
          </tr>
        `;
      }).join('');
    }

    // Modal Functions
    function openAddProductModal() {
      currentEditId = null;
      document.getElementById('modal-title').textContent = 'Add New Product';
      document.getElementById('productForm').reset();
      document.getElementById('product-id').value = '';
      document.getElementById('product-status').value = 'active';
      document.getElementById('productModal').classList.add('active');
    }

    function editProduct(id) {
      const product = allProducts.find(p => p.id == id);
      if (!product) return;
      
      currentEditId = id;
      document.getElementById('modal-title').textContent = 'Edit Product';
      document.getElementById('product-id').value = product.id;
      document.getElementById('product-name').value = product.name;
      document.getElementById('product-category').value = product.category;
      document.getElementById('product-condition').value = product.condition_type;
      document.getElementById('product-price').value = product.price;
      document.getElementById('product-original-price').value = product.original_price || '';
      document.getElementById('product-stock').value = product.stock_quantity;
      document.getElementById('product-sku').value = product.sku || '';
      document.getElementById('product-color').value = product.color || '';
      document.getElementById('product-category').addEventListener('change', function() {
        const isMacBook = this.value === 'macbook';
        document.getElementById('macbook-chip-field').style.display = isMacBook ? 'block' : 'none';
        document.getElementById('macbook-memory-field').style.display = isMacBook ? 'block' : 'none';
        document.getElementById('macbook-screen-field').style.display = isMacBook ? 'block' : 'none';
      });
      document.getElementById('product-chip').value = product.chip || '';
      document.getElementById('product-unified-memory').value = product.unified_memory || '';
      document.getElementById('product-screen-size').value = product.screen_size || '';

      if (product.category === 'macbook') {
        document.getElementById('product-category').dispatchEvent(new Event('change'));
      }
      
      // STORAGE HANDLING
      const storageSelect = document.getElementById('product-storage');
      const storageValues = product.storage ? product.storage.split(',').map(s => s.trim()) : [];
      Array.from(storageSelect.options).forEach(option => {
        option.selected = storageValues.includes(option.value);
      });
      // Trigger change event to update display
      storageSelect.dispatchEvent(new Event('change'));
      
      document.getElementById('product-year').value = product.model_year || '';
      document.getElementById('product-status').value = product.status;
      document.getElementById('product-description').value = product.description || '';
      document.getElementById('product-image').value = product.image_url || '';
      
      document.getElementById('productModal').classList.add('active');
    }

    function closeProductModal() {
      document.getElementById('productModal').classList.remove('active');
      document.getElementById('productForm').reset();
      document.getElementById('selected-storage-display').textContent = 'None';
      document.getElementById('selected-storage-display').style.color = '#86868b';
      document.getElementById('selected-storage-display').style.fontWeight = 'normal';
      currentEditId = null;
    }

    function openStockModal(id, name, currentStock) {
      document.getElementById('stock-product-id').value = id;
      document.getElementById('stock-product-name').value = name;
      document.getElementById('stock-current').value = currentStock;
      document.getElementById('stock-new').value = currentStock;
      document.getElementById('stockModal').classList.add('active');
    }

    function closeStockModal() {
      document.getElementById('stockModal').classList.remove('active');
      document.getElementById('stockForm').reset();
    }

    // Form Handlers
    document.getElementById('productForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Get selected storage options
      const storageSelect = document.getElementById('product-storage');
      const selectedStorage = Array.from(storageSelect.selectedOptions)
        .map(opt => opt.value)
        .join(',');
      
      const formData = {
        name: document.getElementById('product-name').value,
        category: document.getElementById('product-category').value,
        condition_type: document.getElementById('product-condition').value,
        price: document.getElementById('product-price').value,
        original_price: document.getElementById('product-original-price').value || null,
        stock_quantity: document.getElementById('product-stock').value,
        sku: document.getElementById('product-sku').value || null,
        color: document.getElementById('product-color').value || null,
        storage: selectedStorage || null,  // UPDATED: Now supports multiple values
        model_year: document.getElementById('product-year').value || null,
        status: document.getElementById('product-status').value,
        description: document.getElementById('product-description').value || null,
        image_url: document.getElementById('product-image').value || null,
        chip: document.getElementById('product-chip').value || null,
        unified_memory: document.getElementById('product-unified-memory').value || null,
        screen_size: document.getElementById('product-screen-size').value || null
      };
      
      if (currentEditId) {
        formData.id = currentEditId;
      }
      
      saveProduct(formData);
    });

    document.getElementById('stockForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const id = document.getElementById('stock-product-id').value;
      const quantity = document.getElementById('stock-new').value;
      
      updateStock(id, quantity);
    });

    // Search Handler
    let searchTimeout;
    document.getElementById('product-search').addEventListener('input', function(e) {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        fetchProducts(e.target.value);
      }, 500);
    });

    // Navigation
    function showSection(sectionId) {
      // Update active nav item
      document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
        if (item.getAttribute('onclick') && item.getAttribute('onclick').includes(sectionId)) {
          item.classList.add('active');
        }
      });

      // Update page title
      const titles = {
        'dashboard': 'Dashboard',
        'products': 'Products',
        'inventory': 'Inventory',
        'orders': 'Orders',
        'customers': 'Customers',
        'reports': 'Reports',
        'settings': 'Settings'
      };
      document.getElementById('page-title').textContent = titles[sectionId] || 'Dashboard';

      // Show selected section
      document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
      });
      document.getElementById(sectionId).classList.add('active');

      // Load data for specific sections
      if (sectionId === 'products' || sectionId === 'inventory') {
        if (allProducts.length === 0) {
          fetchProducts();
        }
      }
      
      // Load orders when orders section is shown
      if (sectionId === 'orders') {
        fetchOrders();
      }
      
      // Load customers when customers section is shown
      if (sectionId === 'customers') {
        fetchCustomers();
      }
    }

    // Utility Functions
    function formatCondition(condition) {
      const map = {
        'new': 'Brand New',
        'refurbished': 'Refurbished',
        'pre-owned': 'Pre-Owned'
      };
      return map[condition] || condition;
    }

    function showAlert(message, type = 'success') {
      const alert = document.getElementById('alert');
      alert.textContent = message;
      alert.className = `alert ${type} show`;
      
      setTimeout(() => {
        alert.classList.remove('show');
      }, 5000);
    }

    function exportInventory() {
      // Create CSV content
      let csv = 'SKU,Product,Color,Storage,Stock,Status\n';
      allProducts.forEach(product => {
        const stock = parseInt(product.stock_quantity);
        const status = stock > 10 ? 'In Stock' : stock > 0 ? 'Low Stock' : 'Out of Stock';
        csv += `"${product.sku || 'N/A'}","${product.name}","${product.color || 'N/A'}","${product.storage || 'N/A'}",${stock},"${status}"\n`;
      });
      
      // Download CSV
      const blob = new Blob([csv], { type: 'text/csv' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'inventory_' + new Date().toISOString().split('T')[0] + '.csv';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
      
      showAlert('Inventory exported successfully!', 'success');
    }

    // Close modals when clicking outside
    document.getElementById('productModal').addEventListener('click', function(e) {
      if (e.target === this) closeProductModal();
    });

    document.getElementById('stockModal').addEventListener('click', function(e) {
      if (e.target === this) closeStockModal();
    });

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
      fetchProducts();
      
      // Storage multi-select display handler
      document.getElementById('product-storage').addEventListener('change', function() {
        const selected = Array.from(this.selectedOptions).map(opt => opt.value);
        const display = document.getElementById('selected-storage-display');
        display.textContent = selected.length > 0 ? selected.join(', ') : 'None';
        display.style.color = selected.length > 0 ? '#0071e3' : '#86868b';
        display.style.fontWeight = selected.length > 0 ? '600' : 'normal';
      });
    });

    // Customer Management Functions
    let allCustomers = [];

    // Fetch all customers
    async function fetchCustomers(search = '') {
      try {
        const response = await fetch(`customer_api.php?action=getAll&search=${encodeURIComponent(search)}`);
        const result = await response.json();
        
        if (result.success) {
          allCustomers = result.data;
          renderCustomers(allCustomers);
        } else {
          showAlert('Error loading customers: ' + result.message, 'error');
        }
      } catch (error) {
        showAlert('Error connecting to server', 'error');
        console.error(error);
      }
    }

    // Render customers table
    function renderCustomers(customers) {
      const tbody = document.getElementById('customers-table-body');
      
      if (customers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="no-data">No customers found</td></tr>';
        return;
      }
      
      tbody.innerHTML = customers.map(customer => {
        const joinedDate = new Date(customer.created_at).toLocaleDateString('en-US', {
          month: 'short',
          day: 'numeric',
          year: 'numeric'
        });
        
        return `
          <tr>
            <td><strong>#${customer.id}</strong></td>
            <td>
              <div style="font-weight: 600;">${customer.full_name || 'N/A'}</div>
              <div style="font-size: 0.85em; color: #86868b;">@${customer.username}</div>
            </td>
            <td>${customer.email}</td>
            <td>${customer.phone || 'N/A'}</td>
            <td>${customer.order_count || 0}</td>
            <td>${joinedDate}</td>
            <td>
              <button class="action-btn edit-btn" onclick="viewCustomerDetails(${customer.id})">View</button>
            </td>
          </tr>
        `;
      }).join('');
    }

    function viewCustomerDetails(customerId) {
      alert('Customer details view coming soon for customer #' + customerId);
    }

    // Search customers
    let customerSearchTimeout;
    if (document.getElementById('customer-search')) {
      document.getElementById('customer-search').addEventListener('input', function(e) {
        clearTimeout(customerSearchTimeout);
        customerSearchTimeout = setTimeout(() => {
          fetchCustomers(e.target.value);
        }, 500);
      });
    }
  </script>
</body>
</html>
            <label class="form-label">Contact Email</label>
            <input type="email" class="form-input" value="info@rmgadgets.com">
          </div>
          <div class="form-group">
            <label class="form-label">Contact Phone</label>
            <input type="tel" class="form-input" value="+63 912 345 6789">
          </div>
          <div class="form-group full-width">
            <label class="form-label">Store Address</label>
            <textarea class="form-textarea">123 Apple Street, Quezon City, Metro Manila, Philippines</textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Currency</label>
            <select class="form-select">
              <option value="PHP">Philippine Peso (₱)</option>
              <option value="USD">US Dollar ($)</option>
            </select>
          </div>
          <div class="form-group">
