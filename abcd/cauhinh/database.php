<?php
class Database {
    private $host = "localhost";
    private $ten_database = "nhomone";
    private $ten_dang_nhap = "root";
    private $mat_khau = "";
    public $ket_noi;

    public function getConnection() {
        $this->ket_noi = null;
        try {
            $this->ket_noi = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->ten_database . ";charset=utf8mb4",
                $this->ten_dang_nhap,
                $this->mat_khau
            );
            $this->ket_noi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Lỗi kết nối: " . $e->getMessage();
        }
        return $this->ket_noi;
    }
}
?>