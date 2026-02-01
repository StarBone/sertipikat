<?php

require_once 'config.php';

/**
 * =====================================================
 * FUNGSI KEAMANAN
 * =====================================================
 */

/**
 * Membersihkan input dari karakter berbahaya
 * @param string $data Data yang akan dibersihkan
 * @return string Data yang sudah dibersihkan
 */
function clean($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $conn->real_escape_string($data);
}

/**
 * Generate CSRF Token untuk keamanan form
 * @return string CSRF Token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifikasi CSRF Token
 * @param string $token Token yang akan diverifikasi
 * @return bool True jika valid, false jika tidak
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * =====================================================
 * FUNGSI AUTENTIKASI
 * =====================================================
 */

/**
 * Cek apakah user sudah login
 * @return bool True jika sudah login, false jika belum
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Cek apakah user adalah admin
 * @return bool True jika admin, false jika tidak
 */
function isAdmin() {
    return isset($_SESSION['level']) && $_SESSION['level'] === 'Administrator';
}

/**
 * Cek apakah user adalah pelanggan
 * @return bool True jika pelanggan, false jika tidak
 */
function isPelanggan() {
    return isset($_SESSION['level']) && $_SESSION['level'] === 'Pelanggan';
}

/**
 * Redirect ke halaman tertentu
 * @param string $url URL tujuan
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * =====================================================
 * FUNGSI PESAN DAN NOTIFIKASI
 * =====================================================
 */

/**
 * Set flash message
 * @param string $type Tipe pesan (success, error, warning, info)
 * @param string $message Isi pesan
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Tampilkan flash message
 * @return string HTML pesan atau kosong jika tidak ada
 */
function showFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'];
        $message = $_SESSION['flash']['message'];
        unset($_SESSION['flash']);
        
        $alertClass = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ];
        
        $icon = [
            'success' => 'check-circle',
            'error' => 'exclamation-circle',
            'warning' => 'exclamation-triangle',
            'info' => 'info-circle'
        ];
        
        $class = isset($alertClass[$type]) ? $alertClass[$type] : 'alert-info';
        $ic = isset($icon[$type]) ? $icon[$type] : 'info-circle';
        
        return "
        <div class=\"alert {$class} alert-dismissible fade show\" role=\"alert\">
            <i class=\"fas fa-{$ic} me-2\"></i> {$message}
            <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>
        </div>";
    }
    return '';
}

/**
 * =====================================================
 * FUNGSI FORMAT DATA
 * =====================================================
 */

/**
 * Format angka ke format Rupiah
 * @param float $angka Angka yang akan diformat
 * @return string Format Rupiah
 */
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Format tanggal ke format Indonesia
 * @param string $tanggal Tanggal dalam format Y-m-d
 * @return string Tanggal dalam format Indonesia
 */
function formatTanggal($tanggal) {
    $bulan = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
        '04' => 'April', '05' => 'Mei', '06' => 'Juni',
        '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
        '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    
    $tgl = date('d', strtotime($tanggal));
    $bln = date('m', strtotime($tanggal));
    $thn = date('Y', strtotime($tanggal));
    
    return $tgl . ' ' . $bulan[$bln] . ' ' . $thn;
}

/**
 * =====================================================
 * FUNGSI DATABASE
 * =====================================================
 */

/**
 * Ambil semua data dari tabel
 * @param string $table Nama tabel
 * @return array Array hasil query
 */
function getAll($table) {
    global $conn;
    $query = "SELECT * FROM {$table}";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Ambil data berdasarkan ID
 * @param string $table Nama tabel
 * @param string $column Nama kolom ID
 * @param mixed $id Nilai ID
 * @return array|null Data hasil query atau null
 */
function getById($table, $column, $id) {
    global $conn;
    $id = clean($id);
    $query = "SELECT * FROM {$table} WHERE {$column} = '{$id}'";
    $result = $conn->query($query);
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

/**
 * Hapus data berdasarkan ID
 * @param string $table Nama tabel
 * @param string $column Nama kolom ID
 * @param mixed $id Nilai ID
 * @return bool True jika berhasil, false jika gagal
 */
function deleteById($table, $column, $id) {
    global $conn;
    $id = clean($id);
    $query = "DELETE FROM {$table} WHERE {$column} = '{$id}'";
    return $conn->query($query);
}

/**
 * Hitung jumlah data dalam tabel
 * @param string $table Nama tabel
 * @param string $where Kondisi WHERE (opsional)
 * @return int Jumlah data
 */
function countData($table, $where = '') {
    global $conn;
    $query = "SELECT COUNT(*) as total FROM {$table}";
    if (!empty($where)) {
        $query .= " WHERE {$where}";
    }
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['total'];
}

/**
 * =====================================================
 * FUNGSI SPESIFIK APLIKASI
 * =====================================================
 */

/**
 * Hitung total tagihan
 * @param int $jumlahMeter Jumlah meter yang digunakan
 * @param float $tarifPerKwh Tarif per kWh
 * @return float Total tagihan
 */
function hitungTagihan($jumlahMeter, $tarifPerKwh) {
    return $jumlahMeter * $tarifPerKwh;
}

/**
 * Hitung total pembayaran (tagihan + biaya admin)
 * @param float $tagihan Total tagihan
 * @param float $biayaAdmin Biaya administrasi
 * @return float Total pembayaran
 */
function hitungTotalBayar($tagihan, $biayaAdmin = 2500) {
    return $tagihan + $biayaAdmin;
}

/**
 * Ambil daftar bulan
 * @return array Array nama bulan
 */
function getBulan() {
    return [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
}

/**
 * Ambil daftar tahun
 * @param int $start Tahun mulai
 * @param int $end Tahun akhir
 * @return array Array tahun
 */
function getTahun($start = 2024, $end = null) {
    if ($end === null) {
        $end = date('Y') + 1;
    }
    return range($start, $end);
}

/**
 * =====================================================
 * FUNGSI VALIDASI
 * =====================================================
 */

/**
 * Validasi email
 * @param string $email Email yang akan divalidasi
 * @return bool True jika valid, false jika tidak
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validasi input tidak kosong
 * @param array $fields Array field yang akan dicek
 * @return bool True jika semua terisi, false jika ada yang kosong
 */
function isRequired($fields) {
    foreach ($fields as $field) {
        if (empty(trim($field))) {
            return false;
        }
    }
    return true;
}

/**
 * =====================================================
 * FUNGSI PAGINATION
 * =====================================================
 */

/**
 * Generate pagination
 * @param int $total Total data
 * @param int $perPage Data per halaman
 * @param int $currentPage Halaman saat ini
 * @param string $url Base URL
 * @return string HTML pagination
 */
function pagination($total, $perPage, $currentPage, $url) {
    $totalPages = ceil($total / $perPage);
    
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous
    $prevClass = $currentPage <= 1 ? 'disabled' : '';
    $html .= "<li class=\"page-item {$prevClass}\">
        <a class=\"page-link\" href=\"{$url}page=" . ($currentPage - 1) . "\">Previous</a>
    </li>";
    
    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i == $currentPage ? 'active' : '';
        $html .= "<li class=\"page-item {$active}\">
            <a class=\"page-link\" href=\"{$url}page={$i}\">{$i}</a>
        </li>";
    }
    
    // Next
    $nextClass = $currentPage >= $totalPages ? 'disabled' : '';
    $html .= "<li class=\"page-item {$nextClass}\">
        <a class=\"page-link\" href=\"{$url}page=" . ($currentPage + 1) . "\">Next</a>
    </li>";
    
    $html .= '</ul></nav>';
    
    return $html;
}
?>
