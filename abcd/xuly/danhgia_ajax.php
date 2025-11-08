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

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($action === 'add_review') {
        $product_id = $_POST['product_id'] ?? '';
        $rating = intval($_POST['rating'] ?? 5);
        $content = $_POST['review_content'] ?? '';
        
        if (empty($product_id) || empty($rating)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin đánh giá']);
            exit;
        }
        
        // Kiểm tra đã đánh giá chưa
        $checkQuery = "SELECT * FROM danhgia_sanpham 
                      WHERE id_nguoidung = ? AND id_sanpham = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$user_id, $product_id]);
        
        if ($checkStmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Bạn đã đánh giá sản phẩm này rồi']);
            exit;
        }
        
        // Thêm đánh giá
        $insertQuery = "INSERT INTO danhgia_sanpham (id_nguoidung, id_sanpham, diem, noidung) 
                       VALUES (?, ?, ?, ?)";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([$user_id, $product_id, $rating, $content]);
        
        echo json_encode(['success' => true, 'message' => 'Gửi đánh giá thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>