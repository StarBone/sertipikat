-- =====================================================
-- APLIKASI PEMBAYARAN LISTRIK PASCABAYAR
-- Database Schema (Physical Data Model - PDM)
-- =====================================================

-- Buat Database
CREATE DATABASE IF NOT EXISTS listrik_pascabayar 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE listrik_pascabayar;

-- =====================================================
-- TABEL LEVEL (Hak Akses)
-- =====================================================
CREATE TABLE level (
    id_level INT PRIMARY KEY AUTO_INCREMENT,
    nama_level VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL USER (Administrator)
-- =====================================================
CREATE TABLE user (
    id_user INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_admin VARCHAR(100) NOT NULL,
    id_level INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_level) REFERENCES level(id_level) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL TARIF (Daya Listrik)
-- =====================================================
CREATE TABLE tarif (
    id_tarif INT PRIMARY KEY AUTO_INCREMENT,
    daya INT NOT NULL COMMENT 'Daya listrik dalam Watt',
    tarifperkwh DECIMAL(10,2) NOT NULL COMMENT 'Tarif per kWh dalam Rupiah'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL PELANGGAN
-- =====================================================
CREATE TABLE pelanggan (
    id_pelanggan INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nomor_kwh VARCHAR(50) NOT NULL UNIQUE COMMENT 'Nomor meter KWH pelanggan',
    nama_pelanggan VARCHAR(100) NOT NULL,
    alamat TEXT NOT NULL,
    id_tarif INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_tarif) REFERENCES tarif(id_tarif) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL PENGGUNAAN (Pemakaian Listrik per Bulan)
-- =====================================================
CREATE TABLE penggunaan (
    id_penggunaan INT PRIMARY KEY AUTO_INCREMENT,
    id_pelanggan INT NOT NULL,
    bulan VARCHAR(20) NOT NULL,
    tahun INT NOT NULL,
    meter_awal INT NOT NULL COMMENT 'Meteran awal dalam kWh',
    meter_akhir INT NOT NULL COMMENT 'Meteran akhir dalam kWh',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan) ON DELETE CASCADE,
    UNIQUE KEY unique_penggunaan (id_pelanggan, bulan, tahun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL TAGIHAN
-- =====================================================
CREATE TABLE tagihan (
    id_tagihan INT PRIMARY KEY AUTO_INCREMENT,
    id_penggunaan INT NOT NULL,
    id_pelanggan INT NOT NULL,
    bulan VARCHAR(20) NOT NULL,
    tahun INT NOT NULL,
    jumlah_meter INT NOT NULL COMMENT 'Total penggunaan dalam kWh',
    status ENUM('BELUM DIBAYAR', 'LUNAS') DEFAULT 'BELUM DIBAYAR',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_penggunaan) REFERENCES penggunaan(id_penggunaan) ON DELETE CASCADE,
    FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL PEMBAYARAN
-- =====================================================
CREATE TABLE pembayaran (
    id_pembayaran INT PRIMARY KEY AUTO_INCREMENT,
    id_tagihan INT NOT NULL,
    id_pelanggan INT NOT NULL,
    tanggal_pembayaran DATE NOT NULL,
    bulan_bayar VARCHAR(20) NOT NULL,
    biaya_admin DECIMAL(10,2) DEFAULT 2500.00 COMMENT 'Biaya administrasi',
    total_bayar DECIMAL(12,2) NOT NULL COMMENT 'Total pembayaran',
    id_user INT NOT NULL COMMENT 'ID admin yang memproses pembayaran',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_tagihan) REFERENCES tagihan(id_tagihan) ON DELETE RESTRICT,
    FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan) ON DELETE RESTRICT,
    FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- INSERT DATA LEVEL
-- =====================================================
INSERT INTO level (nama_level) VALUES 
('Administrator'),
('Pelanggan');

-- =====================================================
-- INSERT DATA USER (Admin)
-- Password: admin123
-- =====================================================
INSERT INTO user (username, password, nama_admin, id_level) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator Utama', 1),
('operator1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operator 1', 1);

-- =====================================================
-- INSERT DATA TARIF
-- =====================================================
INSERT INTO tarif (daya, tarifperkwh) VALUES 
(450, 415),
(900, 1352),
(1300, 1444.70),
(2200, 1444.70),
(3500, 1699.53),
(5500, 1699.53),
(6600, 1699.53);

-- =====================================================
-- INSERT DATA PELANGGAN
-- Password untuk semua pelanggan: pelanggan123
-- =====================================================
INSERT INTO pelanggan (username, password, nomor_kwh, nama_pelanggan, alamat, id_tarif) VALUES 
('budi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KWH001', 'Budi Santoso', 'Jl. Merdeka No. 1, Jakarta', 1),
('ani', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KWH002', 'Ani Wulandari', 'Jl. Sudirman No. 15, Jakarta', 2),
('dodi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KWH003', 'Dodi Permana', 'Jl. Thamrin No. 8, Bandung', 3),
('sari', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KWH004', 'Sari Indah', 'Jl. Gatot Subroto No. 22, Surabaya', 2),
('rudi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KWH005', 'Rudi Hartono', 'Jl. Ahmad Yani No. 45, Medan', 4);

-- =====================================================
-- INSERT DATA PENGGUNAAN
-- =====================================================
INSERT INTO penggunaan (id_pelanggan, bulan, tahun, meter_awal, meter_akhir) VALUES 
(1, 'Januari', 2026, 1000, 1150),
(1, 'Februari', 2026, 1150, 1300),
(2, 'Januari', 2026, 500, 750),
(2, 'Februari', 2026, 750, 980),
(3, 'Januari', 2026, 2000, 2300),
(4, 'Januari', 2026, 800, 1100),
(5, 'Januari', 2026, 1500, 1800);

-- =====================================================
-- INSERT DATA TAGIHAN
-- =====================================================
INSERT INTO tagihan (id_penggunaan, id_pelanggan, bulan, tahun, jumlah_meter, status) VALUES 
(1, 1, 'Januari', 2026, 150, 'LUNAS'),
(2, 1, 'Februari', 2026, 150, 'BELUM DIBAYAR'),
(3, 2, 'Januari', 2026, 250, 'LUNAS'),
(4, 2, 'Februari', 2026, 230, 'BELUM DIBAYAR'),
(5, 3, 'Januari', 2026, 300, 'BELUM DIBAYAR'),
(6, 4, 'Januari', 2026, 300, 'LUNAS'),
(7, 5, 'Januari', 2026, 300, 'BELUM DIBAYAR');

-- =====================================================
-- INSERT DATA PEMBAYARAN
-- =====================================================
INSERT INTO pembayaran (id_tagihan, id_pelanggan, tanggal_pembayaran, bulan_bayar, biaya_admin, total_bayar, id_user) VALUES 
(1, 1, '2026-01-15', 'Januari', 2500, 64750.00, 1),
(3, 2, '2026-01-20', 'Januari', 2500, 340500.00, 1),
(6, 4, '2026-01-25', 'Januari', 2500, 408910.00, 1);

-- =====================================================
-- VIEW: INFORMASI PENGGUNAAN LISTRIK
-- =====================================================
CREATE VIEW view_penggunaan_listrik AS
SELECT 
    p.id_penggunaan,
    pl.nama_pelanggan,
    pl.nomor_kwh,
    t.daya,
    t.tarifperkwh,
    p.bulan,
    p.tahun,
    p.meter_awal,
    p.meter_akhir,
    (p.meter_akhir - p.meter_awal) AS jumlah_pakai,
    ((p.meter_akhir - p.meter_awal) * t.tarifperkwh) AS total_tagihan
FROM penggunaan p
JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
JOIN tarif t ON pl.id_tarif = t.id_tarif;

-- =====================================================
-- VIEW: INFORMASI TAGIHAN LENGKAP
-- =====================================================
CREATE VIEW view_tagihan_lengkap AS
SELECT 
    tg.id_tagihan,
    pl.nama_pelanggan,
    pl.nomor_kwh,
    t.daya,
    t.tarifperkwh,
    p.bulan,
    p.tahun,
    p.meter_awal,
    p.meter_akhir,
    tg.jumlah_meter,
    (tg.jumlah_meter * t.tarifperkwh) AS total_tagihan,
    tg.status,
    CASE 
        WHEN tg.status = 'LUNAS' THEN 'Sudah Dibayar'
        ELSE 'Belum Dibayar'
    END AS keterangan_status
FROM tagihan tg
JOIN pelanggan pl ON tg.id_pelanggan = pl.id_pelanggan
JOIN penggunaan p ON tg.id_penggunaan = p.id_penggunaan
JOIN tarif t ON pl.id_tarif = t.id_tarif;

-- =====================================================
-- STORED PROCEDURE: TAMPIL PELANGGAN DENGAN DAYA 900 WATT
-- =====================================================
DELIMITER //

CREATE PROCEDURE sp_pelanggan_daya_900()
BEGIN
    SELECT 
        pl.id_pelanggan,
        pl.nama_pelanggan,
        pl.nomor_kwh,
        pl.alamat,
        t.daya,
        t.tarifperkwh
    FROM pelanggan pl
    JOIN tarif t ON pl.id_tarif = t.id_tarif
    WHERE t.daya = 900;
END //

-- =====================================================
-- FUNCTION: HITUNG TOTAL PENGGUNAAN LISTRIK PER BULAN
-- =====================================================
CREATE FUNCTION fn_total_penggunaan_bulan(bulan_param VARCHAR(20), tahun_param INT)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE total INT;
    
    SELECT SUM(jumlah_meter) INTO total
    FROM tagihan
    WHERE bulan = bulan_param AND tahun = tahun_param;
    
    RETURN IFNULL(total, 0);
END //

-- =====================================================
-- TRIGGER: AUTO CREATE TAGIHAN SETELAH INSERT PENGGUNAAN
-- =====================================================
CREATE TRIGGER trg_after_insert_penggunaan
AFTER INSERT ON penggunaan
FOR EACH ROW
BEGIN
    DECLARE v_jumlah_meter INT;
    
    SET v_jumlah_meter = NEW.meter_akhir - NEW.meter_awal;
    
    INSERT INTO tagihan (id_penggunaan, id_pelanggan, bulan, tahun, jumlah_meter, status)
    VALUES (NEW.id_penggunaan, NEW.id_pelanggan, NEW.bulan, NEW.tahun, v_jumlah_meter, 'BELUM DIBAYAR');
END //

DELIMITER ;

-- =====================================================
-- INDEX UNTUK OPTIMASI QUERY
-- =====================================================
CREATE INDEX idx_penggunaan_pelanggan ON penggunaan(id_pelanggan);
CREATE INDEX idx_penggunaan_bulan_tahun ON penggunaan(bulan, tahun);
CREATE INDEX idx_tagihan_pelanggan ON tagihan(id_pelanggan);
CREATE INDEX idx_tagihan_status ON tagihan(status);
CREATE INDEX idx_pembayaran_tagihan ON pembayaran(id_tagihan);
CREATE INDEX idx_pembayaran_pelanggan ON pembayaran(id_pelanggan);

-- =====================================================
-- CONTOH COMMIT DAN ROLLBACK
-- =====================================================

-- COMMIT setelah insert data tarif
START TRANSACTION;
INSERT INTO tarif (daya, tarifperkwh) VALUES (10000, 2000.00);
COMMIT;

-- ROLLBACK setelah hapus data pelanggan (simulasi)
-- START TRANSACTION;
-- DELETE FROM pelanggan WHERE id_pelanggan = 1;
-- ROLLBACK; -- Data tidak jadi dihapus
