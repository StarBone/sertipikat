<?php

require_once '../includes/functions.php';

// Cek apakah user adalah pelanggan
if (!isPelanggan()) {
    redirect('../admin/dashboard.php');
}

$page_title = 'Tagihan Saya';
$id_pelanggan = $_SESSION['id_pelanggan'];

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $per_page;

// Filter status
$filter_status = isset($_GET['status']) ? clean($_GET['status']) : '';

$where = "WHERE t.id_pelanggan = {$id_pelanggan}";
if (!empty($filter_status)) {
    $where .= " AND t.status = '{$filter_status}'";
}

// Hitung total data
$total_query = "SELECT COUNT(*) as total FROM tagihan t {$where}";
$total_result = $conn->query($total_query);
$total_data = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_data / $per_page);

// Ambil data tagihan
$query = "SELECT t.*, p.nama_pelanggan, p.nomor_kwh, p.alamat, tr.daya, tr.tarifperkwh,
                 pg.meter_awal, pg.meter_akhir
          FROM tagihan t 
          JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan
          JOIN tarif tr ON p.id_tarif = tr.id_tarif
          JOIN penggunaan pg ON t.id_penggunaan = pg.id_penggunaan
          {$where}
          ORDER BY t.tahun DESC, 
                   FIELD(t.bulan, 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember') DESC
          LIMIT {$start}, {$per_page}";
$result = $conn->query($query);

require_once '../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-file-invoice me-2"></i>Tagihan Saya
    </h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Tagihan Saya</li>
        </ol>
    </nav>
</div>

<!-- Filter -->
<div class="row mb-4">
    <div class="col-md-6">
        <form method="GET" class="d-flex">
            <select name="status" class="form-select me-2">
                <option value="">Semua Status</option>
                <option value="BELUM DIBAYAR" <?php echo $filter_status == 'BELUM DIBAYAR' ? 'selected' : ''; ?>>
                    Belum Dibayar
                </option>
                <option value="LUNAS" <?php echo $filter_status == 'LUNAS' ? 'selected' : ''; ?>>
                    Lunas
                </option>
            </select>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter me-2"></i>Filter
            </button>
        </form>
    </div>
    <div class="col-md-6 text-end">
        <a href="?" class="btn btn-outline-secondary">
            <i class="fas fa-sync-alt me-2"></i>Reset
        </a>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Daftar Tagihan</h5>
        <span class="text-muted">Total: <?php echo $total_data; ?> tagihan</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No. Tagihan</th>
                        <th>Periode</th>
                        <th>Jumlah Meter</th>
                        <th>Total Tagihan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $no = $start + 1; while ($row = $result->fetch_assoc()): 
                            $total_tagihan = $row['jumlah_meter'] * $row['tarifperkwh'];
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>#TAG<?php echo str_pad($row['id_tagihan'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo $row['bulan'] . ' ' . $row['tahun']; ?></td>
                                <td><?php echo number_format($row['jumlah_meter']); ?> kWh</td>
                                <td><?php echo formatRupiah($total_tagihan); ?></td>
                                <td>
                                    <?php if ($row['status'] == 'LUNAS'): ?>
                                        <span class="badge badge-success">Lunas</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Belum Bayar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info btn-icon" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalDetail<?php echo $row['id_tagihan']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <!-- Modal Detail -->
                                    <div class="modal fade" id="modalDetail<?php echo $row['id_tagihan']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Detail Tagihan</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6 class="mb-3">Informasi Tagihan</h6>
                                                            <table class="table table-borderless">
                                                                <tr>
                                                                    <td width="40%">No. Tagihan</td>
                                                                    <td>: #TAG<?php echo str_pad($row['id_tagihan'], 5, '0', STR_PAD_LEFT); ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Periode</td>
                                                                    <td>: <?php echo $row['bulan'] . ' ' . $row['tahun']; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Meter Awal</td>
                                                                    <td>: <?php echo number_format($row['meter_awal']); ?> kWh</td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Meter Akhir</td>
                                                                    <td>: <?php echo number_format($row['meter_akhir']); ?> kWh</td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Jumlah Pakai</td>
                                                                    <td>: <?php echo number_format($row['jumlah_meter']); ?> kWh</td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6 class="mb-3">Rincian Pembayaran</h6>
                                                            <table class="table">
                                                                <tr>
                                                                    <td>Jumlah Meter</td>
                                                                    <td class="text-end"><?php echo number_format($row['jumlah_meter']); ?> kWh</td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Tarif per kWh</td>
                                                                    <td class="text-end"><?php echo formatRupiah($row['tarifperkwh']); ?></td>
                                                                </tr>
                                                                <tr class="table-primary">
                                                                    <td><strong>Total Tagihan</strong></td>
                                                                    <td class="text-end"><strong><?php echo formatRupiah($total_tagihan); ?></strong></td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="text-center mt-3">
                                                        <?php if ($row['status'] == 'LUNAS'): ?>
                                                            <span class="badge bg-success fs-6 p-2">
                                                                <i class="fas fa-check-circle me-2"></i>LUNAS
                                                            </span>
                                                            <p class="mt-2 text-muted">Tagihan ini telah dibayar.</p>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger fs-6 p-2">
                                                                <i class="fas fa-times-circle me-2"></i>BELUM DIBAYAR
                                                            </span>
                                                            <p class="mt-2 text-muted">
                                                                Silakan hubungi petugas PLN terdekat untuk melakukan pembayaran.
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">Tidak ada data tagihan</td>
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
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo $filter_status; ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $filter_status; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo $filter_status; ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
