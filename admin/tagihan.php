<?php

require_once '../includes/functions.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('../pelanggan/dashboard.php');
}

$page_title = 'Data Tagihan';

// Get filter parameters
$filter_status = isset($_GET['status']) ? clean($_GET['status']) : '';
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$sort = isset($_GET['sort']) ? clean($_GET['sort']) : 'id_tagihan';
$order = isset($_GET['order']) ? clean($_GET['order']) : 'desc';

// Validasi sort field
$allowed_sort = ['id_tagihan', 'nama_pelanggan', 'bulan', 'tahun', 'jumlah_meter', 'status'];
if (!in_array($sort, $allowed_sort)) {
    $sort = 'id_tagihan';
}

// Build WHERE clause
$where = [];
if (!empty($filter_status)) {
    $where[] = "t.status = '{$filter_status}'";
}
if (!empty($search)) {
    $where[] = "(p.nama_pelanggan LIKE '%{$search}%' OR p.nomor_kwh LIKE '%{$search}%')";
}
$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $per_page;

// Hitung total data
$total_query = "SELECT COUNT(*) as total FROM tagihan t 
                JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan 
                {$where_clause}";
$total_result = $conn->query($total_query);
$total_data = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_data / $per_page);

// Build ORDER BY
$order_by = "ORDER BY ";
if ($sort == 'nama_pelanggan') {
    $order_by .= "p.nama_pelanggan {$order}";
} elseif ($sort == 'bulan') {
    $order_by .= "FIELD(t.bulan, 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember') {$order}";
} else {
    $order_by .= "t.{$sort} {$order}";
}

// Ambil data tagihan dengan JOIN
$query = "SELECT t.*, p.nama_pelanggan, p.nomor_kwh, p.alamat, tr.daya, tr.tarifperkwh,
                 pg.meter_awal, pg.meter_akhir
          FROM tagihan t 
          JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan
          JOIN tarif tr ON p.id_tarif = tr.id_tarif
          JOIN penggunaan pg ON t.id_penggunaan = pg.id_penggunaan
          {$where_clause}
          {$order_by}
          LIMIT {$start}, {$per_page}";
$result = $conn->query($query);

// Hitung statistik
$total_belum_bayar = countData('tagihan', "status = 'BELUM DIBAYAR'");
$total_lunas = countData('tagihan', "status = 'LUNAS'");

require_once '../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-file-invoice me-2"></i>Data Tagihan
    </h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Data Tagihan</li>
        </ol>
    </nav>
</div>

<!-- Statistik Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h5 class="fw-bold"><?php echo number_format($total_belum_bayar); ?></h5 class="fw-bold">
                <p>Belum Dibayar</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h5 class="fw-bold"><?php echo number_format($total_lunas); ?></h5 class="fw-bold">
                <p>Lunas</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div class="stat-info">
                <h5 class="fw-bold"><?php echo number_format($total_data); ?></h5 class="fw-bold">
                <p>Total Tagihan</p>
            </div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-filter me-2"></i>Filter & Pencarian
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="BELUM DIBAYAR" <?php echo $filter_status == 'BELUM DIBAYAR' ? 'selected' : ''; ?>>
                        Belum Dibayar
                    </option>
                    <option value="LUNAS" <?php echo $filter_status == 'LUNAS' ? 'selected' : ''; ?>>
                        Lunas
                    </option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Cari</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Nama pelanggan / No. KWH..." value="<?php echo $search; ?>">
                </div>
            </div>
            <!-- <div class="col-md-3">
                <label class="form-label">Urutkan</label>
                <select name="sort" class="form-select" onchange="this.form.submit()">
                    <option value="id_tagihan" <?php echo $sort == 'id_tagihan' ? 'selected' : ''; ?>>ID Tagihan</option>
                    <option value="nama_pelanggan" <?php echo $sort == 'nama_pelanggan' ? 'selected' : ''; ?>>Nama Pelanggan</option>
                    <option value="bulan" <?php echo $sort == 'bulan' ? 'selected' : ''; ?>>Bulan</option>
                    <option value="tahun" <?php echo $sort == 'tahun' ? 'selected' : ''; ?>>Tahun</option>
                    <option value="jumlah_meter" <?php echo $sort == 'jumlah_meter' ? 'selected' : ''; ?>>Jumlah Meter</option>
                    <option value="status" <?php echo $sort == 'status' ? 'selected' : ''; ?>>Status</option>
                </select>
            </div> -->
            <div class="col-md-3">
                <label class="form-label">Aksi</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i><span class="p-1">Filter</span>
                    </button>
                    <a href="?" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i>
                    </a>
                </div>
            </div>
            <input type="hidden" name="order" value="<?php echo $order; ?>">
        </form>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Daftar Tagihan</h5>
        <span class="text-muted">Menampilkan <?php echo $result->num_rows; ?> dari <?php echo $total_data; ?> data</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>
                            <a href="<?php echo sortUrl('nama_pelanggan', $sort, $order); ?>" class="text-white text-decoration-none">
                                Pelanggan <?php echo sortIcon('nama_pelanggan', $sort, $order); ?>
                            </a>
                        </th>
                        <th>No. KWH</th>
                        <th>
                            <a href="<?php echo sortUrl('bulan', $sort, $order); ?>" class="text-white text-decoration-none">
                                Periode <?php echo sortIcon('bulan', $sort, $order); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo sortUrl('jumlah_meter', $sort, $order); ?>" class="text-white text-decoration-none">
                                Jumlah Meter <?php echo sortIcon('jumlah_meter', $sort, $order); ?>
                            </a>
                        </th>
                        <th>Total Tagihan</th>
                        <th>
                            <a href="<?php echo sortUrl('status', $sort, $order); ?>" class="text-white text-decoration-none">
                                Status <?php echo sortIcon('status', $sort, $order); ?>
                            </a>
                        </th>
                        <th width="12%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $no = $start + 1; while ($row = $result->fetch_assoc()): 
                            $total_tagihan = $row['jumlah_meter'] * $row['tarifperkwh'];
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <strong><?php echo $row['nama_pelanggan']; ?></strong>
                                </td>
                                <td><?php echo $row['nomor_kwh']; ?></td>
                                <td><?php echo $row['bulan'] . ' ' . $row['tahun']; ?></td>
                                <td><?php echo number_format($row['jumlah_meter']); ?> kWh</td>
                                <td><strong><?php echo formatRupiah($total_tagihan); ?></strong></td>
                                <td>
                                    <?php if ($row['status'] == 'LUNAS'): ?>
                                        <span class="badge badge-success"><i class="fas fa-check me-1"></i>Lunas</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><i class="fas fa-clock me-1"></i>Belum Bayar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info btn-icon" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalDetail<?php echo $row['id_tagihan']; ?>"
                                            title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($row['status'] == 'BELUM DIBAYAR'): ?>
                                        <a href="pembayaran.php?bayar=<?php echo $row['id_tagihan']; ?>" 
                                           class="btn btn-sm btn-success btn-icon"
                                           title="Bayar">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary btn-icon" disabled title="Sudah Lunas">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                    <!-- Modal Detail -->
                                    <div class="modal fade" id="modalDetail<?php echo $row['id_tagihan']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-file-invoice me-2"></i>Detail Tagihan
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6 class="mb-3 text-primary"><i class="fas fa-user me-2"></i>Informasi Pelanggan</h6>
                                                            <table class="table table-sm table-borderless">
                                                                <tr>
                                                                    <td width="40%" class="text-muted">No. KWH</td>
                                                                    <td class="fw-bold">: <?php echo $row['nomor_kwh']; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-muted">Nama</td>
                                                                    <td class="fw-bold">: <?php echo $row['nama_pelanggan']; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-muted">Alamat</td>
                                                                    <td>: <?php echo $row['alamat']; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-muted">Daya</td>
                                                                    <td>: <?php echo number_format($row['daya']); ?> Watt</td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6 class="mb-3 text-primary"><i class="fas fa-bolt me-2"></i>Informasi Penggunaan</h6>
                                                            <table class="table table-sm table-borderless">
                                                                <tr>
                                                                    <td width="40%" class="text-muted">Periode</td>
                                                                    <td class="fw-bold">: <?php echo $row['bulan'] . ' ' . $row['tahun']; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-muted">Meter Awal</td>
                                                                    <td>: <?php echo number_format($row['meter_awal']); ?> kWh</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-muted">Meter Akhir</td>
                                                                    <td>: <?php echo number_format($row['meter_akhir']); ?> kWh</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-muted">Jumlah Pakai</td>
                                                                    <td class="fw-bold text-primary">: <?php echo number_format($row['jumlah_meter']); ?> kWh</td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    
                                                    <hr class="my-4">
                                                    
                                                    <h6 class="mb-3 text-primary"><i class="fas fa-calculator me-2"></i>Rincian Pembayaran</h6>
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered">
                                                            <tr>
                                                                <td class="bg-light">Jumlah Meter</td>
                                                                <td class="text-end"><?php echo number_format($row['jumlah_meter']); ?> kWh</td>
                                                            </tr>
                                                            <tr>
                                                                <td class="bg-light">Tarif per kWh</td>
                                                                <td class="text-end"><?php echo formatRupiah($row['tarifperkwh']); ?></td>
                                                            </tr>
                                                            <tr class="table-primary">
                                                                <td class="fw-bold">Total Tagihan</td>
                                                                <td class="text-end fw-bold fs-5"><?php echo formatRupiah($total_tagihan); ?></td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                    
                                                    <div class="text-center mt-4">
                                                        <?php if ($row['status'] == 'LUNAS'): ?>
                                                            <div class="alert alert-success mb-0">
                                                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                                                <h5 class="mb-0">LUNAS</h5>
                                                                <p class="mb-0 mt-2">Tagihan ini telah dibayar</p>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="alert alert-warning mb-0">
                                                                <i class="fas fa-clock fa-2x mb-2 text-warning"></i>
                                                                <h5 class="mb-0">BELUM DIBAYAR</h5>
                                                                <p class="mb-0 mt-2">Silakan lakukan pembayaran</p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        <i class="fas fa-times me-2"></i>Tutup
                                                    </button>
                                                    <?php if ($row['status'] == 'BELUM DIBAYAR'): ?>
                                                        <a href="pembayaran.php?bayar=<?php echo $row['id_tagihan']; ?>" class="btn btn-success">
                                                            <i class="fas fa-money-bill-wave me-2"></i>Proses Pembayaran
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p class="mb-0">Tidak ada data tagihan</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="p-3 border-top">
                <nav>
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo $search; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1&status=<?php echo $filter_status; ?>&search=<?php echo $search; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $filter_status; ?>&search=<?php echo $search; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?>&status=<?php echo $filter_status; ?>&search=<?php echo $search; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>"><?php echo $total_pages; ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo $search; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
