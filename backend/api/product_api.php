<?php
session_start();
require_once __DIR__ . '/../config/db_connect.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'getAll':
            getAllProducts();
            break;
        
        case 'getOne':
            getProduct();
            break;
        
        case 'add':
            addProduct();
            break;
        
        case 'update':
            updateProduct();
            break;
        
        case 'delete':
            deleteProduct();
            break;
        
        case 'updateStock':
            updateStock();
            break;
        
        case 'toggleFeatured':
            toggleFeatured();
            break;
        
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getAllProducts() {
    global $conn;
    
    $search = $_GET['search'] ?? '';
    
    $sql = "SELECT * FROM products WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (name LIKE :search OR category LIKE :search OR sku LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    $sql .= " ORDER BY is_featured DESC, created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $products]);
}

function getProduct() {
    global $conn;
    
    $id = $_GET['id'] ?? 0;
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo json_encode(['success' => true, 'data' => $product]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
}

function addProduct() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $sql = "INSERT INTO products (
        name, category, condition_type, price, original_price, 
        stock_quantity, sku, color, storage, model_year, 
        chip, unified_memory, screen_size,
        description, image_url, status, is_featured
    ) VALUES (
        :name, :category, :condition_type, :price, :original_price,
        :stock_quantity, :sku, :color, :storage, :model_year,
        :chip, :unified_memory, :screen_size,
        :description, :image_url, :status, :is_featured
    )";

    
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([
        ':name' => $data['name'],
        ':category' => $data['category'],
        ':condition_type' => $data['condition_type'] ?? 'new',
        ':price' => $data['price'],
        ':original_price' => $data['original_price'] ?? null,
        ':stock_quantity' => $data['stock_quantity'] ?? 0,
        ':sku' => $data['sku'] ?? null,
        ':color' => $data['color'] ?? null,
        ':storage' => $data['storage'] ?? null,
        ':model_year' => $data['model_year'] ?? null,
        ':description' => $data['description'] ?? null,
        ':image_url' => $data['image_url'] ?? null,
        ':chip' => $data['chip'] ?? null,
        ':unified_memory' => $data['unified_memory'] ?? null,
        ':screen_size' => $data['screen_size'] ?? null,
        ':status' => $data['status'] ?? 'active',
        ':is_featured' => $data['is_featured'] ?? 0
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Product added successfully',
            'id' => $conn->lastInsertId()
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add product']);
    }
}

function updateProduct() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        return;
    }
    
    $sql = "UPDATE products SET 
        name = :name,
        category = :category,
        condition_type = :condition_type,
        price = :price,
        original_price = :original_price,
        stock_quantity = :stock_quantity,
        sku = :sku,
        color = :color,
        storage = :storage,
        model_year = :model_year,
        description = :description,
        chip = :chip,
        unified_memory = :unified_memory,
        screen_size = :screen_size,
        image_url = :image_url,
        status = :status,
        is_featured = :is_featured,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = :id";
    
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([
        ':id' => $id,
        ':name' => $data['name'],
        ':category' => $data['category'],
        ':condition_type' => $data['condition_type'],
        ':price' => $data['price'],
        ':original_price' => $data['original_price'] ?? null,
        ':stock_quantity' => $data['stock_quantity'],
        ':sku' => $data['sku'] ?? null,
        ':color' => $data['color'] ?? null,
        ':storage' => $data['storage'] ?? null,
        ':model_year' => $data['model_year'] ?? null,
        ':description' => $data['description'] ?? null,
        ':image_url' => $data['image_url'] ?? null,
        ':chip' => $data['chip'] ?? null,
        ':unified_memory' => $data['unified_memory'] ?? null,
        ':screen_size' => $data['screen_size'] ?? null,
        ':status' => $data['status'] ?? 'active',
        ':is_featured' => $data['is_featured'] ?? 0
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update product']);
    }
}

function deleteProduct() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? $_GET['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM products WHERE id = :id");
    $result = $stmt->execute([':id' => $id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
    }
}

function updateStock() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $quantity = $data['quantity'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        return;
    }
    
    $stmt = $conn->prepare("UPDATE products SET stock_quantity = :quantity, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
    $result = $stmt->execute([':id' => $id, ':quantity' => $quantity]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Stock updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update stock']);
    }
}

function toggleFeatured() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $is_featured = $data['is_featured'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        return;
    }
    
    // Check how many products are already featured
    if ($is_featured == 1) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE is_featured = 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] >= 4) {
            echo json_encode(['success' => false, 'message' => 'Maximum 4 products can be featured. Please unfeature another product first.']);
            return;
        }
    }
    
    $stmt = $conn->prepare("UPDATE products SET is_featured = :is_featured, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
    $result = $stmt->execute([':id' => $id, ':is_featured' => $is_featured]);
    
    if ($result) {
        $message = $is_featured == 1 ? 'Product featured successfully' : 'Product unfeatured successfully';
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update featured status']);
    }
}
?>