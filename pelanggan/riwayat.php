<?php

require_once '../includes/functions.php';

// Cek apakah user adalah pelanggan
if (!isPelanggan()) {
    redirect('../admin/dashboard.php');
}

$page_title = 'Riwayat Pembayaran';
$id_pelanggan = $_SESSION['id_pelanggan'];

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $per_page;

// Hitung total data
$total_query = "SELECT COUNT(*) as total FROM pembayaran WHERE id_pelanggan = {$id_pelanggan}";
$total_result = $conn->query($total_query);
$total_data = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_data / $per_page);

// Ambil data pembayaran
$query = "SELECT pb.*, t.bulan, t.tahun, t.jumlah_meter, tr.tarifperkwh, u.nama_admin
          FROM pembayaran pb 
          JOIN tagihan t ON pb.id_tagihan = t.id_tagihan
          JOIN pelanggan p ON pb.id_pelanggan = p.id_pelanggan
          JOIN tarif tr ON p.id_tarif = tr.id_tarif
          JOIN user u ON pb.id_user = u.id_user
          WHERE pb.id_pelanggan = {$id_pelanggan}
          ORDER BY pb.id_pembayaran DESC 
          LIMIT {$start}, {$per_page}";
$result = $conn->query($query);

require_once '../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-history me-2"></i>Riwayat Pembayaran
    </h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Riwayat Pembayaran</li>
        </ol>
    </nav>
</div>

<!-- Data Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Daftar Pembayaran</h5>
        <span class="text-muted">Total: <?php echo $total_data; ?> pembayaran</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No. Pembayaran</th>
                        <th>Periode</th>
                        <th>Tanggal Bayar</th>
                        <th>Jumlah Meter</th>
                        <th>Total Tagihan</th>
                        <th>Biaya Admin</th>
                        <th>Total Bayar</th>
                        <th>Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $no = $start + 1; while ($row = $result->fetch_assoc()): 
                            $total_tagihan = $row['jumlah_meter'] * $row['tarifperkwh'];
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>#BYR<?php echo str_pad($row['id_pembayaran'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo $row['bulan'] . ' ' . $row['tahun']; ?></td>
                                <td><?php echo formatTanggal($row['tanggal_pembayaran']); ?></td>
                                <td><?php echo number_format($row['jumlah_meter']); ?> kWh</td>
                                <td><?php echo formatRupiah($total_tagihan); ?></td>
                                <td><?php echo formatRupiah($row['biaya_admin']); ?></td>
                                <td><strong><?php echo formatRupiah($row['total_bayar']); ?></strong></td>
                                <td><?php echo $row['nama_admin']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">Tidak ada riwayat pembayaran</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Summary Card -->
<div class="card mt-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            <i class="fas fa-chart-line me-2"></i>Ringkasan Pembayaran
        </h5>
    </div>
    <div class="card-body">
        <?php
        // Hitung total pembayaran
        $query_total = "SELECT 
                            SUM(total_bayar) as total_bayar,
                            SUM(biaya_admin) as total_admin,
                            COUNT(*) as jumlah_transaksi
                        FROM pembayaran 
                        WHERE id_pelanggan = {$id_pelanggan}";
        $result_total = $conn->query($query_total);
        $total = $result_total->fetch_assoc();
        ?>
        <div class="row">
            <div class="col-md-4 text-center">
                <h4 class="text-primary"><?php echo number_format($total['jumlah_transaksi']); ?></h4>
                <p class="text-muted">Jumlah Transaksi</p>
            </div>
            <div class="col-md-4 text-center">
                <h4 class="text-warning"><?php echo formatRupiah($total['total_admin']); ?></h4>
                <p class="text-muted">Total Biaya Admin</p>
            </div>
            <div class="col-md-4 text-center">
                <h4 class="text-success"><?php echo formatRupiah($total['total_bayar']); ?></h4>
                <p class="text-muted">Total Pembayaran</p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
