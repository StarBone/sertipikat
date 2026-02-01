<?php

require_once '../includes/functions.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('../pelanggan/dashboard.php');
}

$page_title = 'Penggunaan Listrik';

// Proses Tambah Penggunaan
if (isset($_POST['tambah'])) {
    $id_pelanggan = clean($_POST['id_pelanggan']);
    $bulan = clean($_POST['bulan']);
    $tahun = clean($_POST['tahun']);
    $meter_awal = clean($_POST['meter_awal']);
    $meter_akhir = clean($_POST['meter_akhir']);
    
    if ($meter_akhir <= $meter_awal) {
        setFlashMessage('error', 'Meter akhir harus lebih besar dari meter awal!');
        redirect('penggunaan.php');
    }
    
    $cek_query = "SELECT * FROM penggunaan 
                  WHERE id_pelanggan = {$id_pelanggan} 
                  AND bulan = '{$bulan}' 
                  AND tahun = {$tahun}";
    $cek_result = $conn->query($cek_query);
    
    if ($cek_result->num_rows > 0) {
        setFlashMessage('error', 'Data penggunaan untuk ' . $bulan . ' ' . $tahun . ' sudah ada!');
        redirect('penggunaan.php');
    }
    
    $query = "INSERT INTO penggunaan (id_pelanggan, bulan, tahun, meter_awal, meter_akhir) 
              VALUES ({$id_pelanggan}, '{$bulan}', {$tahun}, {$meter_awal}, {$meter_akhir})";
    
    if ($conn->query($query)) {
        setFlashMessage('success', 'Penggunaan listrik berhasil ditambahkan! Tagihan otomatis dibuat.');
    } else {
        setFlashMessage('error', 'Gagal menambahkan penggunaan: ' . $conn->error);
    }
    redirect('penggunaan.php');
}

// Proses Edit Penggunaan
if (isset($_POST['edit'])) {
    $id_penggunaan = clean($_POST['id_penggunaan']);
    $id_pelanggan = clean($_POST['id_pelanggan']);
    $bulan = clean($_POST['bulan']);
    $tahun = clean($_POST['tahun']);
    $meter_awal = clean($_POST['meter_awal']);
    $meter_akhir = clean($_POST['meter_akhir']);
    
    if ($meter_akhir <= $meter_awal) {
        setFlashMessage('error', 'Meter akhir harus lebih besar dari meter awal!');
        redirect('penggunaan.php');
    }
    
    $query = "UPDATE penggunaan 
              SET id_pelanggan = {$id_pelanggan}, 
                  bulan = '{$bulan}', 
                  tahun = {$tahun}, 
                  meter_awal = {$meter_awal}, 
                  meter_akhir = {$meter_akhir}
              WHERE id_penggunaan = {$id_penggunaan}";
    
    if ($conn->query($query)) {
        $jumlah_meter = $meter_akhir - $meter_awal;
        $update_tagihan = "UPDATE tagihan SET jumlah_meter = {$jumlah_meter} WHERE id_penggunaan = {$id_penggunaan}";
        $conn->query($update_tagihan);
        
        setFlashMessage('success', 'Penggunaan listrik berhasil diupdate!');
    } else {
        setFlashMessage('error', 'Gagal mengupdate penggunaan: ' . $conn->error);
    }
    redirect('penggunaan.php');
}

// Proses Hapus Penggunaan
if (isset($_GET['hapus'])) {
    $id_penggunaan = clean($_GET['hapus']);
    
    $query = "DELETE FROM penggunaan WHERE id_penggunaan = {$id_penggunaan}";
    
    if ($conn->query($query)) {
        setFlashMessage('success', 'Penggunaan listrik berhasil dihapus!');
    } else {
        setFlashMessage('error', 'Gagal menghapus penggunaan: ' . $conn->error);
    }
    redirect('penggunaan.php');
}

// Ambil data pelanggan untuk dropdown
$pelanggan = getAll('pelanggan');
$daftar_bulan = getBulan();
$daftar_tahun = getTahun(2024, date('Y') + 1);

// Get filter parameters
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$filter_bulan = isset($_GET['bulan']) ? clean($_GET['bulan']) : '';
$filter_tahun = isset($_GET['tahun']) ? clean($_GET['tahun']) : '';
$sort = isset($_GET['sort']) ? clean($_GET['sort']) : 'id_penggunaan';
$order = isset($_GET['order']) ? clean($_GET['order']) : 'desc';

// Validasi sort field
$allowed_sort = ['id_penggunaan', 'nama_pelanggan', 'bulan', 'tahun', 'jumlah_pakai'];
if (!in_array($sort, $allowed_sort)) {
    $sort = 'id_penggunaan';
}

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $per_page;

// Build WHERE clause
$where = [];
if (!empty($search)) {
    $where[] = "(p.nama_pelanggan LIKE '%{$search}%' OR p.nomor_kwh LIKE '%{$search}%')";
}
if (!empty($filter_bulan)) {
    $where[] = "pg.bulan = '{$filter_bulan}'";
}
if (!empty($filter_tahun)) {
    $where[] = "pg.tahun = {$filter_tahun}";
}
$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Hitung total data
$total_query = "SELECT COUNT(*) as total FROM penggunaan pg 
                JOIN pelanggan p ON pg.id_pelanggan = p.id_pelanggan {$where_clause}";
$total_result = $conn->query($total_query);
$total_data = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_data / $per_page);

// Build ORDER BY
$order_by = "ORDER BY ";
if ($sort == 'nama_pelanggan') {
    $order_by .= "p.nama_pelanggan {$order}";
} elseif ($sort == 'bulan') {
    $order_by .= "FIELD(pg.bulan, 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember') {$order}";
} elseif ($sort == 'jumlah_pakai') {
    $order_by .= "(pg.meter_akhir - pg.meter_awal) {$order}";
} else {
    $order_by .= "pg.{$sort} {$order}";
}

// Ambil data penggunaan
$query = "SELECT pg.*, p.nama_pelanggan, p.nomor_kwh, t.daya, t.tarifperkwh 
          FROM penggunaan pg 
          JOIN pelanggan p ON pg.id_pelanggan = p.id_pelanggan
          JOIN tarif t ON p.id_tarif = t.id_tarif
          {$where_clause}
          {$order_by}
          LIMIT {$start}, {$per_page}";
$result = $conn->query($query);

require_once '../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-bolt me-2"></i>Penggunaan Listrik
    </h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Penggunaan Listrik</li>
        </ol>
    </nav>
</div>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-filter me-2"></i>Filter & Pencarian
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="w-50">
                <label class="form-label">Cari Pelanggan</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Nama / No. KWH..." value="<?php echo $search; ?>">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Bulan</label>
                <select name="bulan" class="form-select">
                    <option value="">Semua Bulan</option>
                    <?php foreach ($daftar_bulan as $b): ?>
                        <option value="<?php echo $b; ?>" <?php echo $filter_bulan == $b ? 'selected' : ''; ?>>
                            <?php echo $b; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Tahun</label>
                <select name="tahun" class="form-select">
                    <option value="">Semua Tahun</option>
                    <?php foreach ($daftar_tahun as $t): ?>
                        <option value="<?php echo $t; ?>" <?php echo $filter_tahun == $t ? 'selected' : ''; ?>>
                            <?php echo $t; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- <div class="col-md-2">
                <label class="form-label">Urutkan</label>
                <select name="sort" class="form-select">
                    <option value="id_penggunaan" <?php echo $sort == 'id_penggunaan' ? 'selected' : ''; ?>>ID</option>
                    <option value="nama_pelanggan" <?php echo $sort == 'nama_pelanggan' ? 'selected' : ''; ?>>Nama</option>
                    <option value="bulan" <?php echo $sort == 'bulan' ? 'selected' : ''; ?>>Bulan</option>
                    <option value="tahun" <?php echo $sort == 'tahun' ? 'selected' : ''; ?>>Tahun</option>
                    <option value="jumlah_pakai" <?php echo $sort == 'jumlah_pakai' ? 'selected' : ''; ?>>Jumlah Pakai</option>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label">Order</label>
                <select name="order" class="form-select">
                    <option value="asc" <?php echo $order == 'asc' ? 'selected' : ''; ?>>A-Z</option>
                    <option value="desc" <?php echo $order == 'desc' ? 'selected' : ''; ?>>Z-A</option>
                </select>
            </div> -->
            <div class="col-md-3">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i><span class="p-1">Filter</span>
                    </button>
                    <a href="?" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i>
                    </a>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Daftar Penggunaan Listrik</h5>
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
                        <th>
                            <a href="<?php echo sortUrl('nomor_kwh', $sort, $order); ?>" class="text-white text-decoration-none">
                                No. KWH <?php echo sortIcon('nomor_kwh', $sort, $order); ?>
                            </a>    
                        </th>
                        <th>
                            <a href="<?php echo sortUrl('bulan', $sort, $order); ?>" class="text-white text-decoration-none">
                                Priode <?php echo sortIcon('bulan', $sort, $order); ?>
                            </a>
                        </th>
                        <th>Meter Awal</th>
                        <th>Meter Akhir</th>
                        <th>
                            <a href="<?php echo sortUrl('jumlah_pakai', $sort, $order); ?>" class="text-white text-decoration-none">
                                Jumlah Pakai <?php echo sortIcon('jumlah_pakai', $sort, $order); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo sortUrl('total_tagihan', $sort, $order); ?>" class="text-white text-decoration-none">
                                Total Tagihan <?php echo sortIcon('total_tagihan', $sort, $order); ?>
                            </a>
                        </th>
                        <th width="12%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $no = $start + 1; while ($row = $result->fetch_assoc()): 
                            $jumlah_pakai = $row['meter_akhir'] - $row['meter_awal'];
                            $total_tagihan = $jumlah_pakai * $row['tarifperkwh'];
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><strong><?php echo $row['nama_pelanggan']; ?></strong></td>
                                <td><span class="badge bg-light text-dark"><?php echo $row['nomor_kwh']; ?></span></td>
                                <td><?php echo $row['bulan'] . ' ' . $row['tahun']; ?></td>
                                <td><?php echo number_format($row['meter_awal']); ?></td>
                                <td><?php echo number_format($row['meter_akhir']); ?></td>
                                <td><span class="badge bg-info"><?php echo number_format($jumlah_pakai); ?> kWh</span></td>
                                <td><strong><?php echo formatRupiah($total_tagihan); ?></strong></td>
                                <td>
                                    <button class="btn btn-sm btn-warning btn-icon" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEdit<?php echo $row['id_penggunaan']; ?>"
                                            title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?hapus=<?php echo $row['id_penggunaan']; ?>" 
                                       class="btn btn-sm btn-danger btn-icon"
                                       onclick="return confirmDelete()"
                                       title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            
                            <!-- Modal Edit -->
                            <div class="modal fade" id="modalEdit<?php echo $row['id_penggunaan']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-warning">
                                            <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Penggunaan</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="id_penggunaan" value="<?php echo $row['id_penggunaan']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Pelanggan</label>
                                                    <select name="id_pelanggan" class="form-select" required>
                                                        <?php foreach ($pelanggan as $pl): ?>
                                                            <option value="<?php echo $pl['id_pelanggan']; ?>" 
                                                                    <?php echo $pl['id_pelanggan'] == $row['id_pelanggan'] ? 'selected' : ''; ?>>
                                                                <?php echo $pl['nama_pelanggan']; ?> (<?php echo $pl['nomor_kwh']; ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Bulan</label>
                                                        <select name="bulan" class="form-select" required>
                                                            <?php foreach ($daftar_bulan as $b): ?>
                                                                <option value="<?php echo $b; ?>" 
                                                                        <?php echo $b == $row['bulan'] ? 'selected' : ''; ?>>
                                                                    <?php echo $b; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Tahun</label>
                                                        <select name="tahun" class="form-select" required>
                                                            <?php foreach ($daftar_tahun as $t): ?>
                                                                <option value="<?php echo $t; ?>" 
                                                                        <?php echo $t == $row['tahun'] ? 'selected' : ''; ?>>
                                                                    <?php echo $t; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Meter Awal (kWh)</label>
                                                        <input type="number" name="meter_awal" class="form-control" 
                                                               value="<?php echo $row['meter_awal']; ?>" required>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Meter Akhir (kWh)</label>
                                                        <input type="number" name="meter_akhir" class="form-control" 
                                                               value="<?php echo $row['meter_akhir']; ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="edit" class="btn btn-warning">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p class="mb-0">Tidak ada data penggunaan</p>
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
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page-1])); ?>"><i class="fas fa-chevron-left"></i></a>
                            </li>
                        <?php endif; ?>
                        
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a></li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"><?php echo $total_pages; ?></a></li>
                        <?php endif; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page+1])); ?>"><i class="fas fa-chevron-right"></i></a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Tambah Penggunaan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pelanggan</label>
                        <select name="id_pelanggan" class="form-select" required>
                            <option value="">Pilih Pelanggan</option>
                            <?php foreach ($pelanggan as $pl): ?>
                                <option value="<?php echo $pl['id_pelanggan']; ?>">
                                    <?php echo $pl['nama_pelanggan']; ?> (<?php echo $pl['nomor_kwh']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bulan</label>
                            <select name="bulan" class="form-select" required>
                                <option value="">Pilih Bulan</option>
                                <?php foreach ($daftar_bulan as $b): ?>
                                    <option value="<?php echo $b; ?>"><?php echo $b; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tahun</label>
                            <select name="tahun" class="form-select" required>
                                <option value="">Pilih Tahun</option>
                                <?php foreach ($daftar_tahun as $t): ?>
                                    <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Meter Awal (kWh)</label>
                            <input type="number" name="meter_awal" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Meter Akhir (kWh)</label>
                            <input type="number" name="meter_akhir" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
