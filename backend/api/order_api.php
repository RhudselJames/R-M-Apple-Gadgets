<?php
session_start();
require_once __DIR__ . '/../config/db_connect.php';
header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'getAll':
            getAllOrders();
            break;
        
        case 'getDetails':
            getOrderDetails();
            break;
        
        case 'updateStatus':
            updateOrderStatus();
            break;
        
        case 'delete':
            deleteOrder();
            break;
        
        case 'getStats':
            getOrderStats();
            break;
        
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getAllOrders() {
    global $conn;
    
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    
    $sql = "
        SELECT 
            o.order_id,
            o.user_id,
            o.address,
            o.payment_method,
            o.total_amount,
            o.status,
            o.order_date,
            u.full_name,
            u.email,
            u.phone,
            COUNT(oi.order_item_id) as item_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($search) {
        $sql .= " AND (o.order_id LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if ($status) {
        $sql .= " AND o.status = ?";
        $params[] = $status;
    }
    
    $sql .= " GROUP BY o.order_id ORDER BY o.order_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $orders
    ]);
}

function getOrderDetails() {
    global $conn;
    
    $order_id = $_GET['order_id'] ?? null;
    
    if (!$order_id) {
        throw new Exception('Order ID required');
    }
    
    // Get order info
    $stmt = $conn->prepare("
        SELECT 
            o.*,
            u.full_name,
            u.email,
            u.phone,
            u.username
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    // Get order items
    $stmt = $conn->prepare("
        SELECT 
            oi.*,
            p.name,
            p.image_url,
            p.color,
            p.storage,
            p.category
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'order' => $order,
        'items' => $items
    ]);
}

function updateOrderStatus() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $order_id = $data['order_id'] ?? null;
    $status = $data['status'] ?? null;
    
    if (!$order_id || !$status) {
        throw new Exception('Order ID and status required');
    }
    
    // Validate status
    $validStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
    if (!in_array($status, $validStatuses)) {
        throw new Exception('Invalid status');
    }
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->execute([$status, $order_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Order status updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update order status');
    }
}

function deleteOrder() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $order_id = $data['order_id'] ?? null;
    
    if (!$order_id) {
        throw new Exception('Order ID required');
    }
    
    $conn->beginTransaction();
    
    try {
        // Delete order items first
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        // Delete order
        $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Order deleted successfully'
        ]);
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

function getOrderStats() {
    global $conn;
    
    // Total orders
    $stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
    $totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pending orders
    $stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'Pending'");
    $pendingOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Processing orders
    $stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'Processing'");
    $processingOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total revenue
    $stmt = $conn->query("SELECT SUM(total_amount) as revenue FROM orders WHERE status IN ('Processing', 'Shipped', 'Delivered')");
    $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;
    
    // Recent orders (last 7 days)
    $stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $recentOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_orders' => $totalOrders,
            'pending_orders' => $pendingOrders,
            'processing_orders' => $processingOrders,
            'total_revenue' => $totalRevenue,
            'recent_orders' => $recentOrders
        ]
    ]);
}
?>