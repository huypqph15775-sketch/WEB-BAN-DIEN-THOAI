<?php
session_start();
include_once '../cauhinh/csrf.php';
include_once '../cauhinh/database.php';

header('Content-Type: application/json');

$token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!validate_csrf($token)) {
    echo json_encode(['success'=>false, 'message'=>'CSRF token không hợp lệ']);
    exit;
}

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
            $id_anhsanpham = $_POST['id_anhsanpham'] ?? 0;
    $id_nguoidung = $_SESSION['id_nguoidung'];

    // ✅ Kiểm tra tồn kho
    $check = $db->prepare("SELECT soluong_ton FROM anh_sanpham WHERE id_anhsanpham = ?");
    $check->execute([$id_anhsanpham]);
    $sp = $check->fetch(PDO::FETCH_ASSOC);
    if (!$sp || $sp['soluong_ton'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm tạm hết hàng']);
        exit;
    }

    // Kiểm tra nếu đã có trong giỏ
    $kt = $db->prepare("SELECT soluong FROM giohang WHERE id_nguoidung = ? AND id_anhsanpham = ?");
    $kt->execute([$id_nguoidung, $id_anhsanpham]);
    $row = $kt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $tongsl = $row['soluong'] + 1;
        if ($tongsl > $sp['soluong_ton']) {
            echo json_encode(['success' => false, 'message' => 'Số lượng vượt quá tồn kho']);
            exit;
        }
        $update = $db->prepare("UPDATE giohang SET soluong = ? WHERE id_nguoidung = ? AND id_anhsanpham = ?");
        $update->execute([$tongsl, $id_nguoidung, $id_anhsanpham]);
    } else {
        $insert = $db->prepare("INSERT INTO giohang (id_nguoidung, id_anhsanpham, soluong) VALUES (?, ?, 1)");
        $insert->execute([$id_nguoidung, $id_anhsanpham]);
    }

    echo json_encode(['success' => true, 'message' => 'Đã thêm vào giỏ hàng']);
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