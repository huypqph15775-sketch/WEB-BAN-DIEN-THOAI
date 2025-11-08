<?php
session_start();
include_once '../cauhinh/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_nguoidung']) || $_SESSION['capdo'] < 2) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    switch ($action) {
        case 'get_order_details':
            $order_id = $_POST['order_id'] ?? '';
            
            if (empty($order_id)) {
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin đơn hàng']);
                exit;
            }
            
            // Lấy thông tin đơn hàng
            $orderQuery = "SELECT 
                            dh.*,
                            nd.hoten as ten_khachhang,
                            nd.email,
                            nd.sodienthoai,
                            dc.ten_nguoinhan,
                            dc.sdt_nguoinhan,
                            dc.diachi_chitiet,
                            dc.tinh_thanh,
                            dc.quan_huyen,
                            dc.phuong_xa
                         FROM donhang dh
                         LEFT JOIN nguoidung nd ON dh.id_nguoidung = nd.id_nguoidung
                         LEFT JOIN dia_chi dc ON dh.id_diachi = dc.id_diachi
                         WHERE dh.id_donhang = ?";
            $orderStmt = $db->prepare($orderQuery);
            $orderStmt->execute([$order_id]);
            $donhang = $orderStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$donhang) {
                echo json_encode(['success' => false, 'message' => 'Đơn hàng không tồn tại']);
                exit;
            }
            
            // Lấy chi tiết đơn hàng
            $detailsQuery = "SELECT 
                                ctdh.*,
                                sp.ten_sanpham,
                                asp.url_anh,
                                ms.ten_mausac
                             FROM chitiet_donhang ctdh
                             INNER JOIN anh_sanpham asp ON ctdh.id_anhsanpham = asp.id_anhsanpham
                             INNER JOIN sanpham sp ON asp.id_sanpham = sp.id_sanpham
                             INNER JOIN mausac_sanpham ms ON asp.id_mausac = ms.id_mausac
                             WHERE ctdh.id_donhang = ?";
            $detailsStmt = $db->prepare($detailsQuery);
            $detailsStmt->execute([$order_id]);
            $chitiet = $detailsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Lấy lịch sử trạng thái
            $historyQuery = "SELECT * FROM theodoi_donhang 
                           WHERE id_donhang = ? 
                           ORDER BY thoigian_capnhat DESC";
            $historyStmt = $db->prepare($historyQuery);
            $historyStmt->execute([$order_id]);
            $lichsu = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Tạo HTML response
            $html = '
            <div class="row">
                <div class="col-md-6">
                    <h6>Thông tin đơn hàng</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Mã đơn:</strong></td><td>' . $donhang['ma_donhang'] . '</td></tr>
                        <tr><td><strong>Ngày đặt:</strong></td><td>' . date('d/m/Y H:i', strtotime($donhang['ngaydathang'])) . '</td></tr>
                        <tr><td><strong>Trạng thái:</strong></td><td><span class="badge bg-primary">' . ucfirst($donhang['trangthai']) . '</span></td></tr>
                        <tr><td><strong>Thanh toán:</strong></td><td>' . strtoupper($donhang['hinhthuc_thanhtoan']) . '</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Thông tin khách hàng</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Họ tên:</strong></td><td>' . htmlspecialchars($donhang['ten_khachhang']) . '</td></tr>
                        <tr><td><strong>Email:</strong></td><td>' . htmlspecialchars($donhang['email'] ?? '') . '</td></tr>
                        <tr><td><strong>Điện thoại:</strong></td><td>' . htmlspecialchars($donhang['sodienthoai'] ?? '') . '</td></tr>
                    </table>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Địa chỉ giao hàng</h6>
                    <p>' . htmlspecialchars($donhang['diachi_chitiet']) . ', ' . 
                       htmlspecialchars($donhang['phuong_xa'] ?? '') . ', ' . 
                       htmlspecialchars($donhang['quan_huyen'] ?? '') . ', ' . 
                       htmlspecialchars($donhang['tinh_thanh'] ?? '') . '</p>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Chi tiết sản phẩm</h6>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Màu</th>
                                    <th>Số lượng</th>
                                    <th>Đơn giá</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>';
            
            foreach ($chitiet as $item) {
                $html .= '
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="' . $item['url_anh'] . '" 
                                                 alt="' . htmlspecialchars($item['ten_sanpham']) . '" 
                                                 style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px;">
                                            <div>
                                                <strong>' . htmlspecialchars($item['ten_sanpham']) . '</strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>' . $item['ten_mausac'] . '</td>
                                    <td>' . $item['soluong'] . '</td>
                                    <td>' . number_format($item['gia'], 0, ',', '.') . '₫</td>
                                    <td>' . number_format($item['thanhtien'], 0, ',', '.') . '₫</td>
                                </tr>';
            }
            
            $html .= '
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Tạm tính:</strong></td>
                                    <td>' . number_format($donhang['tongtien'], 0, ',', '.') . '₫</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Phí vận chuyển:</strong></td>
                                    <td>' . number_format($donhang['phi_vanchuyen'], 0, ',', '.') . '₫</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Giảm giá:</strong></td>
                                    <td class="text-danger">-' . number_format($donhang['giam_gia'], 0, ',', '.') . '₫</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Tổng cộng:</strong></td>
                                    <td><strong>' . number_format($donhang['thanhtien'], 0, ',', '.') . '₫</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>';
            
            if (!empty($lichsu)) {
                $html .= '
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Lịch sử trạng thái</h6>
                        <div class="timeline">
                ';
                
                foreach ($lichsu as $history) {
                    $html .= '
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <strong>' . $history['trangthai'] . '</strong>
                                    <small class="text-muted"> - ' . date('d/m/Y H:i', strtotime($history['thoigian_capnhat'])) . '</small>
                                    <p class="mb-0">' . htmlspecialchars($history['mota'] ?? '') . '</p>
                                </div>
                            </div>
                    ';
                }
                
                $html .= '
                        </div>
                    </div>
                </div>';
            }
            
            echo json_encode(['success' => true, 'html' => $html]);
            break;
            
        case 'update_status':
            $order_id = $_POST['order_id'] ?? '';
            $new_status = $_POST['new_status'] ?? '';
            $user_id = $_SESSION['id_nguoidung'];
            
            if (empty($order_id) || empty($new_status)) {
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
                exit;
            }
            
            // Các trạng thái hợp lệ
            $valid_statuses = ['choduyet', 'daxacnhan', 'danggiaohang', 'hoanthanh', 'huy'];
            if (!in_array($new_status, $valid_statuses)) {
                echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
                exit;
            }
            
            // Bắt đầu transaction
            $db->beginTransaction();
            
            try {
                // Cập nhật trạng thái đơn hàng
                $updateQuery = "UPDATE donhang SET trangthai = ? WHERE id_donhang = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$new_status, $order_id]);
                
                // Thêm vào lịch sử
                $status_names = [
                    'choduyet' => 'Chờ duyệt',
                    'daxacnhan' => 'Đã xác nhận', 
                    'danggiaohang' => 'Đang giao hàng',
                    'hoanthanh' => 'Hoàn thành',
                    'huy' => 'Đã hủy'
                ];
                
                $historyQuery = "INSERT INTO theodoi_donhang (id_donhang, trangthai, mota, id_nguoidung_capnhat) 
                               VALUES (?, ?, ?, ?)";
                $historyStmt = $db->prepare($historyQuery);
                $historyStmt->execute([
                    $order_id, 
                    $status_names[$new_status], 
                    'Trạng thái được cập nhật bởi ' . $_SESSION['hoten'],
                    $user_id
                ]);
                
                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>

<style>
.timeline {
    position: relative;
    padding-left: 20px;
}

.timeline-item {
    position: relative;
    margin-bottom: 15px;
}

.timeline-marker {
    position: absolute;
    left: -20px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: #007bff;
}

.timeline-content {
    padding-bottom: 10px;
    border-left: 2px solid #e9ecef;
    padding-left: 15px;
}
</style>