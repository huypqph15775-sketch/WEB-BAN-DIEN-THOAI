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
    
    if ($action === 'apply_promo') {
        $promo_code = $_POST['promo_code'] ?? '';
        
        if (empty($promo_code)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã khuyến mãi']);
            exit;
        }
        
        // Kiểm tra mã khuyến mãi
        $promoQuery = "SELECT * FROM khuyenmai 
                      WHERE ma_khuyenmai = ? 
                      AND trangthai = 'active'
                      AND ngay_batdau <= NOW() 
                      AND ngay_ketthuc >= NOW()
                      AND (soluot_sudung IS NULL OR soluot_dasudung < soluot_sudung)";
        $promoStmt = $db->prepare($promoQuery);
        $promoStmt->execute([$promo_code]);
        
        if ($promoStmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Mã khuyến mãi không hợp lệ hoặc đã hết hạn']);
            exit;
        }
        
        $khuyenmai = $promoStmt->fetch(PDO::FETCH_ASSOC);
        
        // Tính tổng giỏ hàng
        $cartSummary = getCartSummary($db, $user_id);
        $tongtien = $cartSummary['tongtien'];
        
        // Kiểm tra điều kiện áp dụng
        if ($tongtien < $khuyenmai['giatri_toithieu']) {
            echo json_encode(['success' => false, 'message' => 'Đơn hàng chưa đạt giá trị tối thiểu để áp dụng mã']);
            exit;
        }
        
        if ($khuyenmai['giatri_toida'] && $tongtien > $khuyenmai['giatri_toida']) {
            echo json_encode(['success' => false, 'message' => 'Đơn hàng vượt quá giá trị tối đa để áp dụng mã']);
            exit;
        }
        
        // Tính giá trị giảm
        $giam_gia = 0;
        if ($khuyenmai['loai_khuyenmai'] === 'phantram') {
            $giam_gia = $tongtien * ($khuyenmai['giatri'] / 100);
        } elseif ($khuyenmai['loai_khuyenmai'] === 'tienmat') {
            $giam_gia = $khuyenmai['giatri'];
        } elseif ($khuyenmai['loai_khuyenmai'] === 'freeship') {
            $giam_gia = 0; // Miễn phí vận chuyển sẽ xử lý riêng
        }
        
        // Giới hạn giá trị giảm nếu cần
        if ($khuyenmai['giatri_toida'] && $giam_gia > $khuyenmai['giatri_toida']) {
            $giam_gia = $khuyenmai['giatri_toida'];
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Áp dụng mã khuyến mãi thành công',
            'khuyenmai' => [
                'id' => $khuyenmai['id_khuyenmai'],
                'ma' => $khuyenmai['ma_khuyenmai'],
                'ten' => $khuyenmai['ten_khuyenmai'],
                'loai' => $khuyenmai['loai_khuyenmai'],
                'giatri' => floatval($khuyenmai['giatri']),
                'giam_gia' => floatval($giam_gia)
            ],
            'cart_summary' => $cartSummary
        ]);
        
    } else {
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