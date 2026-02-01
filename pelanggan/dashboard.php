<?php

require_once '../includes/functions.php';

// Cek apakah user adalah pelanggan
if (!isPelanggan()) {
    redirect('../admin/dashboard.php');
}

$page_title = 'Dashboard Pelanggan';
$id_pelanggan = $_SESSION['id_pelanggan'];

// Ambil data pelanggan lengkap
$query_pelanggan = "SELECT p.*, t.daya, t.tarifperkwh 
                    FROM pelanggan p 
                    JOIN tarif t ON p.id_tarif = t.id_tarif 
                    WHERE p.id_pelanggan = {$id_pelanggan}";
$result_pelanggan = $conn->query($query_pelanggan);
$data_pelanggan = $result_pelanggan->fetch_assoc();

// Hitung statistik tagihan
$total_tagihan = countData('tagihan', "id_pelanggan = {$id_pelanggan}");
$tagihan_belum_bayar = countData('tagihan', "id_pelanggan = {$id_pelanggan} AND status = 'BELUM DIBAYAR'");
$tagihan_lunas = countData('tagihan', "id_pelanggan = {$id_pelanggan} AND status = 'LUNAS'");

// Hitung total pembayaran
$query_total_bayar = "SELECT SUM(total_bayar) as total FROM pembayaran WHERE id_pelanggan = {$id_pelanggan}";
$result_total_bayar = $conn->query($query_total_bayar);
$total_bayar = $result_total_bayar->fetch_assoc()['total'] ?? 0;

// Ambil tagihan terbaru
$query_tagihan = "SELECT t.*, tr.tarifperkwh 
                   FROM tagihan t 
                   JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan
                   JOIN tarif tr ON p.id_tarif = tr.id_tarif
                   WHERE t.id_pelanggan = {$id_pelanggan}
                   ORDER BY t.id_tagihan DESC 
                   LIMIT 5";
$result_tagihan = $conn->query($query_tagihan);

// Ambil riwayat pembayaran terbaru
$query_pembayaran = "SELECT pb.*, t.bulan, t.tahun, t.jumlah_meter, tr.tarifperkwh
                     FROM pembayaran pb 
                     JOIN tagihan t ON pb.id_tagihan = t.id_tagihan
                     JOIN pelanggan p ON pb.id_pelanggan = p.id_pelanggan
                     JOIN tarif tr ON p.id_tarif = tr.id_tarif
                     WHERE pb.id_pelanggan = {$id_pelanggan}
                     ORDER BY pb.id_pembayaran DESC 
                     LIMIT 5";
$result_pembayaran = $conn->query($query_pembayaran);

require_once '../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
    </h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </nav>
</div>

<!-- Info Pelanggan -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-user-circle me-2"></i>Informasi Pelanggan
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <p class="mb-1"><strong>No. KWH</strong></p>
                <p class="text-muted"><?php echo $data_pelanggan['nomor_kwh']; ?></p>
            </div>
            <div class="col-md-3">
                <p class="mb-1"><strong>Nama</strong></p>
                <p class="text-muted"><?php echo $data_pelanggan['nama_pelanggan']; ?></p>
            </div>
            <div class="col-md-3">
                <p class="mb-1"><strong>Daya</strong></p>
                <p class="text-muted"><?php echo number_format($data_pelanggan['daya']); ?> Watt</p>
            </div>
            <div class="col-md-3">
                <p class="mb-1"><strong>Tarif/kWh</strong></p>
                <p class="text-muted"><?php echo formatRupiah($data_pelanggan['tarifperkwh']); ?></p>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-12">
                <p class="mb-1"><strong>Alamat</strong></p>
                <p class="text-muted"><?php echo $data_pelanggan['alamat']; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Statistik Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div class="stat-info">
                <h5 class="fw-bold"><?php echo number_format($total_tagihan); ?></h5 class="fw-bold">
                <p>Total Tagihan</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="stat-info">
                <h5 class="fw-bold"><?php echo number_format($tagihan_belum_bayar); ?></h5 class="fw-bold">
                <p>Belum Dibayar</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h5 class="fw-bold"><?php echo number_format($tagihan_lunas); ?></h5 class="fw-bold">
                <p>Lunas</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-info">
                <h5 class="fw-bold"><?php echo formatRupiah($total_bayar); ?></h5 class="fw-bold">
                <p>Total Bayar</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Tagihan Terbaru -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-file-invoice me-2"></i>Tagihan Terbaru
                </h5>
                <a href="tagihan.php" class="btn btn-sm btn-primary">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body">
                <?php if ($result_tagihan->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Periode</th>
                                    <th>Jumlah Meter</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($tagihan = $result_tagihan->fetch_assoc()): 
                                    $total = $tagihan['jumlah_meter'] * $tagihan['tarifperkwh'];
                                ?>
                                    <tr>
                                        <td><?php echo $tagihan['bulan'] . ' ' . $tagihan['tahun']; ?></td>
                                        <td><?php echo number_format($tagihan['jumlah_meter']); ?> kWh</td>
                                        <td><?php echo formatRupiah($total); ?></td>
                                        <td>
                                            <?php if ($tagihan['status'] == 'LUNAS'): ?>
                                                <span class="badge badge-success">Lunas</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Belum Bayar</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">Tidak ada data tagihan</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Riwayat Pembayaran Terbaru -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>Riwayat Pembayaran
                </h5>
                <a href="riwayat.php" class="btn btn-sm btn-primary">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body">
                <?php if ($result_pembayaran->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Periode</th>
                                    <th>Total Bayar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($pembayaran = $result_pembayaran->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo formatTanggal($pembayaran['tanggal_pembayaran']); ?></td>
                                        <td><?php echo $pembayaran['bulan'] . ' ' . $pembayaran['tahun']; ?></td>
                                        <td><?php echo formatRupiah($pembayaran['total_bayar']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">Tidak ada riwayat pembayaran</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
