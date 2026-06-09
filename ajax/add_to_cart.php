<?php
session_start();
require '../includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'unauthorized']);
    exit();
}

// Get JSON POST data
$data = json_decode(file_get_contents('php://input'), true);
$product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
$user_id = $_SESSION['user_id'];
$quantity = 1;

if ($product_id > 0) {
    // 1. Check stock
    $stock_stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
    $stock_stmt->bind_param("i", $product_id);
    $stock_stmt->execute();
    $result = $stock_stmt->get_result();
    
    if ($result->num_rows === 1) {
        $product = $result->fetch_assoc();
        
        // 2. Update or Insert Cart
        $check_stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $check_stmt->bind_param("ii", $user_id, $product_id);
        $check_stmt->execute();
        $cart_result = $check_stmt->get_result();

        if ($cart_result->num_rows > 0) {
            $cart_row = $cart_result->fetch_assoc();
            $new_quantity = $cart_row['quantity'] + $quantity;
            if ($new_quantity > $product['stock']) $new_quantity = $product['stock'];
            
            $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $new_quantity, $cart_row['id']);
            $update_stmt->execute();
        } else {
            $insert_qty = ($quantity > $product['stock']) ? $product['stock'] : $quantity;
            $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("iii", $user_id, $product_id, $insert_qty);
            $insert_stmt->execute();
        }

        // 3. Get new total cart count for the header badge
        $cq = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $cq->bind_param("i", $user_id);
        $cq->execute();
        $total_items = $cq->get_result()->fetch_assoc()['total'];

        echo json_encode(['status' => 'success', 'cart_count' => $total_items, 'message' => 'Added to cart successfully!']);
        exit();
    }
}

echo json_encode(['status' => 'error', 'message' => 'Product not found or out of stock.']);