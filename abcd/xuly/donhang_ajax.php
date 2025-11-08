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
    
    if ($action === 'create_order') {
        $address_id = $_POST['address_id'] ?? '';
        $payment_method = $_POST['payment_method'] ?? 'cod';
        
        if (empty($address_id)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng chọn địa chỉ giao hàng']);
            exit;
        }
        
        // Lấy thông tin giỏ hàng
        $cartQuery = "SELECT 
                        gh.id_giohang,
                        gh.soluong,
                        asp.id_anhsanpham,
                        asp.gia,
                        asp.gia_giam,
                        sp.id_sanpham,
                        asp.soluong_ton
                      FROM giohang gh
                      INNER JOIN anh_sanpham asp ON gh.id_anhsanpham = asp.id_anhsanpham
                      INNER JOIN sanpham sp ON asp.id_sanpham = sp.id_sanpham
                      WHERE gh.id_nguoidung = ?";
        
        $cartStmt = $db->prepare($cartQuery);
        $cartStmt->execute([$user_id]);
        $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($cartItems)) {
            echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống']);
            exit;
        }
        
        // Kiểm tra tồn kho
        foreach ($cartItems as $item) {
            if ($item['soluong'] > $item['soluong_ton']) {
                echo json_encode(['success' => false, 'message' => 'Sản phẩm ' . $item['id_sanpham'] . ' vượt quá số lượng tồn kho']);
                exit;
            }
        }
        
        // Tính tổng tiền
        $tongtien = 0;
        $giam_gia = 0;
        
        foreach ($cartItems as $item) {
            $gia_ban = $item['gia_giam'] > 0 ? $item['gia_giam'] : $item['gia'];
            $tongtien += $gia_ban * $item['soluong'];
            if ($item['gia_giam'] > 0) {
                $giam_gia += ($item['gia'] - $item['gia_giam']) * $item['soluong'];
            }
        }
        
        $phi_vanchuyen = 0; // Có thể tính dựa trên địa chỉ
        $thanhtien = $tongtien + $phi_vanchuyen - $giam_gia;
        
        // Tạo mã đơn hàng
        $ma_donhang = 'DH' . date('YmdHis') . $user_id;
        
        // Bắt đầu transaction
        $db->beginTransaction();
        
        try {
            // Tạo đơn hàng
            $orderQuery = "INSERT INTO donhang (ma_donhang, id_nguoidung, id_diachi, tongtien, phi_vanchuyen, giam_gia, thanhtien, hinhthuc_thanhtoan) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $orderStmt = $db->prepare($orderQuery);
            $orderStmt->execute([$ma_donhang, $user_id, $address_id, $tongtien, $phi_vanchuyen, $giam_gia, $thanhtien, $payment_method]);
            
            $order_id = $db->lastInsertId();
            
            // Thêm chi tiết đơn hàng
            $detailQuery = "INSERT INTO chitiet_donhang (id_donhang, id_anhsanpham, soluong, gia, giam_gia, thanhtien) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $detailStmt = $db->prepare($detailQuery);
            
            foreach ($cartItems as $item) {
                $gia_ban = $item['gia_giam'] > 0 ? $item['gia_giam'] : $item['gia'];
                $item_discount = $item['gia_giam'] > 0 ? ($item['gia'] - $item['gia_giam']) * $item['soluong'] : 0;
                $item_total = $gia_ban * $item['soluong'];
                
                $detailStmt->execute([$order_id, $item['id_anhsanpham'], $item['soluong'], $item['gia'], $item_discount, $item_total]);
                
                // Cập nhật tồn kho
                $updateStockQuery = "UPDATE anh_sanpham SET soluong_ton = soluong_ton - ? WHERE id_anhsanpham = ?";
                $updateStockStmt = $db->prepare($updateStockQuery);
                $updateStockStmt->execute([$item['soluong'], $item['id_anhsanpham']]);
            }
            
            // Xóa giỏ hàng
            $clearCartQuery = "DELETE FROM giohang WHERE id_nguoidung = ?";
            $clearCartStmt = $db->prepare($clearCartQuery);
            $clearCartStmt->execute([$user_id]);
            
            // Commit transaction
            $db->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Đặt hàng thành công!',
                'order_id' => $order_id,
                'ma_donhang' => $ma_donhang
            ]);
            
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>