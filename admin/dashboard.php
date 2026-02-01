<?php

require_once '../includes/functions.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('../pelanggan/dashboard.php');
}

$page_title = 'Dashboard Admin';

// Ambil statistik data
$total_pelanggan = countData('pelanggan');
$total_penggunaan = countData('penggunaan');
$total_tagihan_belum_bayar = countData('tagihan', "status = 'BELUM DIBAYAR'");
$total_tagihan_lunas = countData('tagihan', "status = 'LUNAS'");

// Ambil data tagihan terbaru
$query_tagihan = "SELECT t.*, p.nama_pelanggan, p.nomor_kwh, tr.daya, tr.tarifperkwh 
                  FROM tagihan t 
                  JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan
                  JOIN tarif tr ON p.id_tarif = tr.id_tarif
                  ORDER BY t.id_tagihan DESC 
                  LIMIT 5";
$result_tagihan = $conn->query($query_tagihan);

// Ambil data pelanggan terbaru
$query_pelanggan = "SELECT p.*, t.daya, t.tarifperkwh 
                     FROM pelanggan p 
                     JOIN tarif t ON p.id_tarif = t.id_tarif
                     ORDER BY p.id_pelanggan DESC 
                     LIMIT 5";
$result_pelanggan = $conn->query($query_pelanggan);

// Hitung total pendapatan
$query_pendapatan = "SELECT SUM(total_bayar) as total FROM pembayaran";
$result_pendapatan = $conn->query($query_pendapatan);
$total_pendapatan = $result_pendapatan->fetch_assoc()['total'] ?? 0;

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

<!-- Statistik Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h5 class="fw-bold"><?php echo number_format($total_pelanggan); ?></h5 class="fw-bold">
                <p>Total Pelanggan</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-bolt"></i>
            </div>
            <div class="stat-info">
                <h5 class="fw-bold"><?php echo number_format($total_penggunaan); ?></h5 class="fw-bold">
                <p>Data Penggunaan</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div class="stat-info">
                <h5 class="fw-bold"><?php echo number_format($total_tagihan_belum_bayar); ?></h5 class="fw-bold">
                <p>Tagihan Belum Bayar</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-info">
                <h5 class="fw-bold"><?php echo formatRupiah($total_pendapatan); ?></h5 class="fw-bold">
                <p>Total Pendapatan</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Tagihan Terbaru -->
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Tagihan Terbaru</h5>
                <a href="tagihan.php" class="btn btn-sm btn-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>No. KWH</th>
                                <th>Pelanggan</th>
                                <th>Periode</th>
                                <th>Jumlah Meter</th>
                                <th>Total Tagihan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_tagihan->num_rows > 0): ?>
                                <?php while ($tagihan = $result_tagihan->fetch_assoc()): 
                                    $total = $tagihan['jumlah_meter'] * $tagihan['tarifperkwh'];
                                ?>
                                    <tr>
                                        <td><span class="badge bg-light text-dark"><?php echo $tagihan['nomor_kwh']; ?></span></td>
                                        <td><strong><?php echo $tagihan['nama_pelanggan']; ?></strong></td>
                                        <td><?php echo $tagihan['bulan'] . ' ' . $tagihan['tahun']; ?></td>
                                        <td><?php echo number_format($tagihan['jumlah_meter']); ?> kWh</td>
                                        <td><?php echo formatRupiah($total); ?></td>
                                        <td>
                                            <?php if ($tagihan['status'] == 'LUNAS'): ?>
                                                <span class="badge badge-success"><i class="fas fa-check me-1"></i>Lunas</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger"><i class="fas fa-clock me-1"></i>Belum Bayar</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Tidak ada data tagihan</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pelanggan Terbaru -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Pelanggan Terbaru</h5>
                <a href="pelanggan.php" class="btn btn-sm btn-primary">Lihat</a>
            </div>
            <div class="card-body">
                <?php if ($result_pelanggan->num_rows > 0): ?>
                    <?php while ($pelanggan = $result_pelanggan->fetch_assoc()): ?>
                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                            <div class="flex-shrink-0">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 45px; height: 45px;">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1"><?php echo $pelanggan['nama_pelanggan']; ?></h6>
                                <p class="mb-0 text-muted small">
                                    <?php echo $pelanggan['nomor_kwh']; ?> | 
                                    <?php echo number_format($pelanggan['daya']); ?> Watt
                                </p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center text-muted">Tidak ada data pelanggan</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Ringkasan Status Tagihan -->
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Status Tagihan</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 border-end">
                        <h3 class="text-success mb-1"><?php echo $total_tagihan_lunas; ?></h3>
                        <p class="text-muted mb-0"><i class="fas fa-check-circle me-1"></i>Lunas</p>
                    </div>
                    <div class="col-6">
                        <h3 class="text-danger mb-1"><?php echo $total_tagihan_belum_bayar; ?></h3>
                        <p class="text-muted mb-0"><i class="fas fa-clock me-1"></i>Belum Bayar</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
