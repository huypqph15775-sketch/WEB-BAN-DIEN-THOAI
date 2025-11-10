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
    
    switch ($action) {
        case 'add':
            $product_id = $_POST['product_id'] ?? '';
            $quantity = intval($_POST['quantity'] ?? 1);
            
            if (empty($product_id)) {
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin sản phẩm']);
                exit;
            }
            
            // Lấy id_anhsanpham đầu tiên của sản phẩm
            $checkQuery = "SELECT id_anhsanpham FROM anh_sanpham WHERE id_sanpham = ? AND trangthai = 'active' LIMIT 1";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$product_id]);
            
            if ($checkStmt->rowCount() === 0) {
                echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
                exit;
            }
            
            $anh_sanpham = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $id_anhsanpham = $anh_sanpham['id_anhsanpham'];
            
            // Kiểm tra đã có trong giỏ chưa
            $cartQuery = "SELECT * FROM giohang WHERE id_nguoidung = ? AND id_anhsanpham = ?";
            $cartStmt = $db->prepare($cartQuery);
            $cartStmt->execute([$user_id, $id_anhsanpham]);
            
            if ($cartStmt->rowCount() > 0) {
                // Cập nhật số lượng
                $updateQuery = "UPDATE giohang SET soluong = soluong + ? WHERE id_nguoidung = ? AND id_anhsanpham = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$quantity, $user_id, $id_anhsanpham]);
            } else {
                // Thêm mới
                $insertQuery = "INSERT INTO giohang (id_nguoidung, id_anhsanpham, soluong) VALUES (?, ?, ?)";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([$user_id, $id_anhsanpham, $quantity]);
            }
            
            // Lấy tổng số lượng giỏ hàng
            $countQuery = "SELECT SUM(soluong) as total FROM giohang WHERE id_nguoidung = ?";
            $countStmt = $db->prepare($countQuery);
            $countStmt->execute([$user_id]);
            $cart_count = $countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            echo json_encode(['success' => true, 'cart_count' => $cart_count]);
            break;
            
        case 'update':
            $cart_id = $_POST['cart_id'] ?? '';
            $quantity = intval($_POST['quantity'] ?? 1);
            
            if (empty($cart_id)) {
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin giỏ hàng']);
                exit;
            }
            
            // Kiểm tra số lượng tồn kho
            $stockQuery = "SELECT asp.soluong_ton 
                          FROM giohang gh 
                          INNER JOIN anh_sanpham asp ON gh.id_anhsanpham = asp.id_anhsanpham 
                          WHERE gh.id_giohang = ?";
            $stockStmt = $db->prepare($stockQuery);
            $stockStmt->execute([$cart_id]);
            $stock = $stockStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$stock || $quantity > $stock['soluong_ton']) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Số lượng vượt quá tồn kho',
                    'old_quantity' => $quantity - 1
                ]);
                exit;
            }
            
            // Cập nhật số lượng
            $updateQuery = "UPDATE giohang SET soluong = ? WHERE id_giohang = ? AND id_nguoidung = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$quantity, $cart_id, $user_id]);
            
            // Lấy summary giỏ hàng
            $summary = getCartSummary($db, $user_id);
            
            echo json_encode(['success' => true, 'cart_summary' => $summary]);
            break;
            
        case 'remove':
            $cart_id = $_POST['cart_id'] ?? '';
            
            if (empty($cart_id)) {
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin giỏ hàng']);
                exit;
            }
            
            // Xóa item
            $deleteQuery = "DELETE FROM giohang WHERE id_giohang = ? AND id_nguoidung = ?";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->execute([$cart_id, $user_id]);
            
            // Lấy tổng số lượng và summary
            $countQuery = "SELECT SUM(soluong) as total FROM giohang WHERE id_nguoidung = ?";
            $countStmt = $db->prepare($countQuery);
            $countStmt->execute([$user_id]);
            $cart_count = $countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            $summary = getCartSummary($db, $user_id);
            
            echo json_encode([
                'success' => true, 
                'cart_count' => $cart_count,
                'cart_summary' => $summary
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getCartSummary($db, $user_id) {
    $query = "SELECT 
                SUM(gh.soluong * IF(asp.gia_giam > 0, asp.gia_giam, asp.gia)) as tongtien,
                SUM(gh.soluong * (asp.gia - IF(asp.gia_giam > 0, asp.gia_giam, asp.gia))) as tonggiam
              FROM giohang gh
              INNER JOIN anh_sanpham asp ON gh.id_anhsanpham = asp.id_anhsanpham
              WHERE gh.id_nguoidung = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'tongtien' => floatval($result['tongtien'] ?? 0),
        'tonggiam' => floatval($result['tonggiam'] ?? 0)
    ];
}
?>