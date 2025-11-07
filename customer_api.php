<?php
session_start();
require_once 'db_connect.php';
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
            getAllCustomers();
            break;
        
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getAllCustomers() {
    global $conn;
    
    $search = $_GET['search'] ?? '';
    
    $sql = "
        SELECT 
            u.id,
            u.username,
            u.email,
            u.full_name,
            u.phone,
            u.created_at,
            COUNT(o.order_id) as order_count
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        WHERE 1=1
    ";
    

    $sql .= " AND u.role = 'customer'";
    
    $params = [];
    
    if ($search) {
        $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    $sql .= " GROUP BY u.id ORDER BY u.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $customers
    ]);
}
?>