<?php
session_start();
if (!isset($_SESSION['id_nguoidung'])) {
    header("Location: ../xacthuc/dangnhap.php");
    exit;
}
include_once "../cauhinh/database.php";
include_once "../cauhinh/csrf.php";

$db = (new Database())->getConnection();
$id = $_SESSION['id_nguoidung'];

// Lấy giỏ hàng
$stmt = $db->prepare("SELECT g.*, a.url_anh, a.gia, a.gia_giam, sp.ten_sanpham
                      FROM giohang g
                      JOIN anh_sanpham a ON g.id_anhsanpham = a.id_anhsanpham
                      JOIN sanpham sp ON a.id_sanpham = sp.id_sanpham
                      WHERE g.id_nguoidung = ?");
$stmt->execute([$id]);
$cart = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Thanh toán</title>
<link rel="stylesheet" href="../thuvien/css/styletrangchu.css">
<script>
const CSRF_TOKEN = "<?php echo csrf_token(); ?>";
</script>
</head>
<body>
<?php include "../thanhphan/header.php"; ?>

<div class="container">
    <h2>Thanh toán</h2>

    <?php if(empty($cart)): ?>
        <p>Giỏ hàng của bạn trống.</p>
        <a href="../trangchu/index.php">⬅️ Quay lại mua hàng</a>
    <?php else: ?>
        <form id="formThanhToan">
            <?php echo csrf_field(); ?>
            <table border="1" width="100%" cellpadding="10">
                <tr><th>Ảnh</th><th>Tên</th><th>Giá</th><th>SL</th><th>Tổng</th></tr>
                <?php $tong = 0;
                foreach($cart as $item):
                    $gia = $item['gia_giam'] > 0 ? $item['gia_giam'] : $item['gia'];
                    $line = $gia * $item['soluong'];
                    $tong += $line;
                ?>
                <tr>
                    <td><img src="../thuvien/hinhanh/<?php echo htmlspecialchars($item['url_anh']); ?>" width="70"></td>
                    <td><?php echo htmlspecialchars($item['ten_sanpham']); ?></td>
                    <td><?php echo number_format($gia,0,',','.'); ?>đ</td>
                    <td><?php echo $item['soluong']; ?></td>
                    <td><?php echo number_format($line,0,',','.'); ?>đ</td>
                </tr>
                <?php endforeach; ?>
            </table>
            <p><strong>Tổng: <?php echo number_format($tong,0,',','.'); ?>đ</strong></p>

            <label>Hình thức thanh toán:</label><br>
            <input type="radio" name="hinhthuc" value="cod" checked> Thanh toán khi nhận hàng (COD)<br>
            <input type="radio" name="hinhthuc" value="momo"> MoMo (chưa kích hoạt)<br><br>

            <button type="button" id="btnDatHang">Đặt hàng</button>
        </form>
    <?php endif; ?>
</div>

<script>
document.getElementById("btnDatHang")?.addEventListener("click", ()=>{
    const fd = new FormData(document.getElementById("formThanhToan"));
    fd.append("action", "create_order");
    fetch("../xuly/donhang_ajax.php", {
        method: "POST",
        body: fd,
        headers: {"X-CSRF-TOKEN": CSRF_TOKEN}
    })
    .then(r=>r.json())
    .then(data=>{
        alert(data.message);
        if(data.success){
            window.location.href="../taikhoan/donhang.php";
        }
    })
    .catch(e=>{
        alert("Lỗi kết nối!");
        console.error(e);
    });
});
</script>

<?php include "../thanhphan/footer.php"; ?>
</body>
</html>
