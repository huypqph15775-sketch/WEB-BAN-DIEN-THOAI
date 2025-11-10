<?php
session_start();
include_once '../cauhinh/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_nguoidung'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['id_nguoidung'];
$product_id = $_POST['product_id'] ?? '';

if (empty($product_id)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin sản phẩm']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Kiểm tra sản phẩm tồn tại
    $checkQuery = "SELECT * FROM sanpham WHERE id_sanpham = ? AND trangthai = 'active'";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$product_id]);
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        exit;
    }
    
    switch ($action) {
        case 'add':
            // Kiểm tra đã yêu thích chưa
            $existQuery = "SELECT * FROM sanpham_yeuthich 
                          WHERE id_nguoidung = ? AND id_sanpham = ?";
            $existStmt = $db->prepare($existQuery);
            $existStmt->execute([$user_id, $product_id]);
            
            if ($existStmt->rowCount() === 0) {
                $insertQuery = "INSERT INTO sanpham_yeuthich (id_nguoidung, id_sanpham) VALUES (?, ?)";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([$user_id, $product_id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Đã thêm vào yêu thích']);
            break;
            
        case 'remove':
            $deleteQuery = "DELETE FROM sanpham_yeuthich 
                           WHERE id_nguoidung = ? AND id_sanpham = ?";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->execute([$user_id, $product_id]);
            
            echo json_encode(['success' => true, 'message' => 'Đã xóa khỏi yêu thích']);
            break;
            
        case 'check':
            $checkQuery = "SELECT * FROM sanpham_yeuthich 
                          WHERE id_nguoidung = ? AND id_sanpham = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$user_id, $product_id]);
            
            $is_favorite = $checkStmt->rowCount() > 0;
            echo json_encode(['success' => true, 'is_favorite' => $is_favorite]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>