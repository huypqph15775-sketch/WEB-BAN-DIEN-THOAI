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
        case 'add_address':
            $ten_nguoinhan = $_POST['ten_nguoinhan'] ?? '';
            $sdt_nguoinhan = $_POST['sdt_nguoinhan'] ?? '';
            $tinh_thanh = $_POST['tinh_thanh'] ?? '';
            $quan_huyen = $_POST['quan_huyen'] ?? '';
            $phuong_xa = $_POST['phuong_xa'] ?? '';
            $diachi_chitiet = $_POST['diachi_chitiet'] ?? '';
            $loai_diachi = $_POST['loai_diachi'] ?? 'nha_rieng';
            $mac_dinh = isset($_POST['mac_dinh']) ? 1 : 0;
            
            // Validate
            if (empty($ten_nguoinhan) || empty($sdt_nguoinhan) || empty($diachi_chitiet)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc']);
                exit;
            }
            
            // Nếu đặt làm mặc định, bỏ mặc định của các địa chỉ khác
            if ($mac_dinh) {
                $resetDefaultQuery = "UPDATE dia_chi SET mac_dinh = 0 WHERE id_nguoidung = ?";
                $resetDefaultStmt = $db->prepare($resetDefaultQuery);
                $resetDefaultStmt->execute([$user_id]);
            }
            
            // Thêm địa chỉ mới
            $insertQuery = "INSERT INTO dia_chi (id_nguoidung, ten_nguoinhan, sdt_nguoinhan, tinh_thanh, quan_huyen, phuong_xa, diachi_chitiet, loai_diachi, mac_dinh) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->execute([$user_id, $ten_nguoinhan, $sdt_nguoinhan, $tinh_thanh, $quan_huyen, $phuong_xa, $diachi_chitiet, $loai_diachi, $mac_dinh]);
            
            echo json_encode(['success' => true, 'message' => 'Thêm địa chỉ thành công']);
            break;
            
        case 'set_default':
            $address_id = $_POST['address_id'] ?? '';
            
            if (empty($address_id)) {
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin địa chỉ']);
                exit;
            }
            
            // Bắt đầu transaction
            $db->beginTransaction();
            
            try {
                // Bỏ mặc định tất cả địa chỉ
                $resetQuery = "UPDATE dia_chi SET mac_dinh = 0 WHERE id_nguoidung = ?";
                $resetStmt = $db->prepare($resetQuery);
                $resetStmt->execute([$user_id]);
                
                // Đặt địa chỉ này làm mặc định
                $setQuery = "UPDATE dia_chi SET mac_dinh = 1 WHERE id_diachi = ? AND id_nguoidung = ?";
                $setStmt = $db->prepare($setQuery);
                $setStmt->execute([$address_id, $user_id]);
                
                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Đã đặt làm địa chỉ mặc định']);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;
            
        case 'delete':
            $address_id = $_POST['address_id'] ?? '';
            
            if (empty($address_id)) {
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin địa chỉ']);
                exit;
            }
            
            // Kiểm tra xem đây có phải là địa chỉ mặc định không
            $checkQuery = "SELECT mac_dinh FROM dia_chi WHERE id_diachi = ? AND id_nguoidung = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$address_id, $user_id]);
            $address = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$address) {
                echo json_encode(['success' => false, 'message' => 'Địa chỉ không tồn tại']);
                exit;
            }
            
            // Xóa địa chỉ
            $deleteQuery = "DELETE FROM dia_chi WHERE id_diachi = ? AND id_nguoidung = ?";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->execute([$address_id, $user_id]);
            
            // Nếu đây là địa chỉ mặc định, đặt một địa chỉ khác làm mặc định
            if ($address['mac_dinh']) {
                $setNewDefaultQuery = "UPDATE dia_chi SET mac_dinh = 1 
                                     WHERE id_nguoidung = ? AND id_diachi != ? 
                                     ORDER BY ngay_tao DESC LIMIT 1";
                $setNewDefaultStmt = $db->prepare($setNewDefaultQuery);
                $setNewDefaultStmt->execute([$user_id, $address_id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Xóa địa chỉ thành công']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>