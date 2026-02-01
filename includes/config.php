<?php

// Konfigurasi Database
define('DB_HOST', 'localhost');      // Host database
define('DB_USER', 'root');           // Username database
define('DB_PASS', '');               // Password database (kosong untuk XAMPP default)
define('DB_NAME', 'listrik_pascabayar'); // Nama database

// Konfigurasi Aplikasi
define('APP_NAME', 'Listrik Pascabayar');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/listrik_pascabayar/');

// Konfigurasi Session
session_start();

// Konfigurasi Timezone
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Class Database
 * Mengelola koneksi ke database MySQL
 */
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $conn;
    private $error;

    /**
     * Constructor - Membuat koneksi database
     */
    public function __construct() {
        // Membuat koneksi mysqli
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);

        // Cek koneksi
        if ($this->conn->connect_error) {
            $this->error = "Koneksi gagal: " . $this->conn->connect_error;
            die($this->error);
        }

        // Set charset untuk mendukung karakter UTF-8
        $this->conn->set_charset("utf8mb4");
    }

    /**
     * Mendapatkan objek koneksi
     * @return mysqli Objek koneksi database
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Menutup koneksi database
     */
    public function close() {
        $this->conn->close();
    }

    /**
     * Escape string untuk mencegah SQL Injection
     * @param string $string String yang akan di-escape
     * @return string String yang sudah di-escape
     */
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }

    /**
     * Mendapatkan ID terakhir yang di-insert
     * @return int ID terakhir
     */
    public function lastInsertId() {
        return $this->conn->insert_id;
    }

    /**
     * Memulai transaksi
     */
    public function beginTransaction() {
        $this->conn->begin_transaction();
    }

    /**
     * Commit transaksi
     */
    public function commit() {
        $this->conn->commit();
    }

    /**
     * Rollback transaksi
     */
    public function rollback() {
        $this->conn->rollback();
    }
}

// Global database instance
$db = new Database();
$conn = $db->getConnection();
?>
