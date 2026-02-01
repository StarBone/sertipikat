<?php

require_once '../includes/functions.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('../pelanggan/dashboard.php');
}

$page_title = 'Pembayaran';

// Proses Pembayaran
if (isset($_POST['bayar'])) {
    $id_tagihan = clean($_POST['id_tagihan']);
    $id_pelanggan = clean($_POST['id_pelanggan']);
    $tanggal_pembayaran = clean($_POST['tanggal_pembayaran']);
    $bulan_bayar = clean($_POST['bulan_bayar']);
    $biaya_admin = clean($_POST['biaya_admin']);
    $total_bayar = clean($_POST['total_bayar']);
    $id_user = $_SESSION['user_id'];
    
    $db->beginTransaction();
    
    try {
        $query = "INSERT INTO pembayaran (id_tagihan, id_pelanggan, tanggal_pembayaran, bulan_bayar, biaya_admin, total_bayar, id_user) 
                  VALUES ({$id_tagihan}, {$id_pelanggan}, '{$tanggal_pembayaran}', '{$bulan_bayar}', {$biaya_admin}, {$total_bayar}, {$id_user})";
        
        if (!$conn->query($query)) {
            throw new Exception('Gagal menyimpan pembayaran: ' . $conn->error);
        }
        
        $update_tagihan = "UPDATE tagihan SET status = 'LUNAS' WHERE id_tagihan = {$id_tagihan}";
        if (!$conn->query($update_tagihan)) {
            throw new Exception('Gagal update status tagihan: ' . $conn->error);
        }
        
        $db->commit();
        setFlashMessage('success', 'Pembayaran berhasil diproses!');
        redirect('pembayaran.php');
        
    } catch (Exception $e) {
        $db->rollback();
        setFlashMessage('error', $e->getMessage());
        redirect('pembayaran.php');
    }
}

// Ambil data tagihan yang akan dibayar
$tagihan_bayar = null;
if (isset($_GET['bayar'])) {
    $id_tagihan = clean($_GET['bayar']);
    $query = "SELECT t.*, p.nama_pelanggan, p.nomor_kwh, p.alamat, tr.daya, tr.tarifperkwh
              FROM tagihan t 
              JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan
              JOIN tarif tr ON p.id_tarif = tr.id_tarif
              WHERE t.id_tagihan = {$id_tagihan} AND t.status = 'BELUM DIBAYAR'";
    $result = $conn->query($query);
    $tagihan_bayar = $result->fetch_assoc();
}

// Get filter parameters
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$sort = isset($_GET['sort']) ? clean($_GET['sort']) : 'id_pembayaran';
$order = isset($_GET['order']) ? clean($_GET['order']) : 'desc';

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $per_page;

// Build WHERE clause
$where = [];
if (!empty($search)) {
    $where[] = "(p.nama_pelanggan LIKE '%{$search}%' OR p.nomor_kwh LIKE '%{$search}%')";
}
$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Hitung total data
$total_query = "SELECT COUNT(*) as total FROM pembayaran pb JOIN pelanggan p ON pb.id_pelanggan = p.id_pelanggan {$where_clause}";
$total_result = $conn->query($total_query);
$total_data = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_data / $per_page);

// Build ORDER BY
$order_by = "ORDER BY {$sort} {$order}";

// Ambil data pembayaran
$query = "SELECT pb.*, p.nama_pelanggan, p.nomor_kwh, t.bulan, t.tahun, t.jumlah_meter,
                 tr.tarifperkwh, u.nama_admin
          FROM pembayaran pb 
          JOIN pelanggan p ON pb.id_pelanggan = p.id_pelanggan
          JOIN tagihan t ON pb.id_tagihan = t.id_tagihan
          JOIN tarif tr ON p.id_tarif = tr.id_tarif
          JOIN user u ON pb.id_user = u.id_user
          {$where_clause}
          {$order_by}
          LIMIT {$start}, {$per_page}";
$result = $conn->query($query);

require_once '../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-money-bill-wave me-2"></i>Pembayaran
    </h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Pembayaran</li>
        </ol>
    </nav>
</div>

<?php if ($tagihan_bayar): ?>
<!-- Form Pembayaran -->
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Form Pembayaran</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3"><i class="fas fa-user me-2"></i>Informasi Pelanggan</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td class="text-muted">No. KWH</td><td class="fw-bold">: <?php echo $tagihan_bayar['nomor_kwh']; ?></td></tr>
                            <tr><td class="text-muted">Nama</td><td class="fw-bold">: <?php echo $tagihan_bayar['nama_pelanggan']; ?></td></tr>
                            <tr><td class="text-muted">Daya</td><td>: <?php echo number_format($tagihan_bayar['daya']); ?> Watt</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3"><i class="fas fa-file-invoice me-2"></i>Informasi Tagihan</h6>
                        <table class="table table-sm table-borderless">
                            <tr><td class="text-muted">Periode</td><td class="fw-bold">: <?php echo $tagihan_bayar['bulan'] . ' ' . $tagihan_bayar['tahun']; ?></td></tr>
                            <tr><td class="text-muted">Jumlah Meter</td><td>: <?php echo number_format($tagihan_bayar['jumlah_meter']); ?> kWh</td></tr>
                            <tr><td class="text-muted">Tarif/kWh</td><td>: <?php echo formatRupiah($tagihan_bayar['tarifperkwh']); ?></td></tr>
                        </table>
                    </div>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="id_tagihan" value="<?php echo $tagihan_bayar['id_tagihan']; ?>">
                    <input type="hidden" name="id_pelanggan" value="<?php echo $tagihan_bayar['id_pelanggan']; ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Pembayaran</label>
                            <input type="date" name="tanggal_pembayaran" class="form-control" value="<?php echo date('Y-m-d'); ?>" readonly required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bulan Bayar</label>
                            <input type="text" name="bulan_bayar" class="form-control" value="<?php echo $tagihan_bayar['bulan'] . ' ' . $tagihan_bayar['tahun']; ?>" readonly>
                        </div>
                    </div>
                    
                    <?php 
                        $total_tagihan = $tagihan_bayar['jumlah_meter'] * $tagihan_bayar['tarifperkwh'];
                        $biaya_admin = 2500;
                        $total_bayar = $total_tagihan + $biaya_admin;
                    ?>
                    
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 text-center border-end">
                                    <p class="text-muted mb-1">Total Tagihan</p>
                                    <h5 class="text-primary"><?php echo formatRupiah($total_tagihan); ?></h5>
                                </div>
                                <div class="col-md-4 text-center border-end">
                                    <p class="text-muted mb-1">Biaya Admin</p>
                                    <h5><?php echo formatRupiah($biaya_admin); ?></h5>
                                    <input type="hidden" name="biaya_admin" value="<?php echo $biaya_admin; ?>">
                                </div>
                                <div class="col-md-4 text-center">
                                    <p class="text-muted mb-1">Total Bayar</p>
                                    <h4 class="text-success fw-bold"><?php echo formatRupiah($total_bayar); ?></h4>
                                    <input type="hidden" name="total_bayar" value="<?php echo $total_bayar; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="tagihan.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
                        <button type="submit" name="bayar" class="btn btn-success btn-lg">
                            <i class="fas fa-check-circle me-2"></i>Proses Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php else: ?>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Pencarian</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Cari Pelanggan</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Nama / No. KWH..." value="<?php echo $search; ?>">
                </div>
            </div>
            <!-- <div class="col-md-2">
                <label class="form-label">Urutkan</label>
                <select name="sort" class="form-select">
                    <option value="id_pembayaran" <?php echo $sort == 'id_pembayaran' ? 'selected' : ''; ?>>ID</option>
                    <option value="tanggal_pembayaran" <?php echo $sort == 'tanggal_pembayaran' ? 'selected' : ''; ?>>Tanggal</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Order</label>
                <select name="order" class="form-select">
                    <option value="desc" <?php echo $order == 'desc' ? 'selected' : ''; ?>>Terbaru</option>
                    <option value="asc" <?php echo $order == 'asc' ? 'selected' : ''; ?>>Terlama</option>
                </select>
            </div> -->
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i><span class="p-1">Filter</span>
                </button>
                <a href="?" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Riwayat Pembayaran -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Riwayat Pembayaran</h5>
        <span class="text-muted">Total: <?php echo $total_data; ?> pembayaran</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>
                            <a href="<?php echo sortUrl('id_pembayaran', $sort, $order); ?>" class="text-white text-decoration-none">
                                No. Pembayaran <?php echo sortIcon('id_pembayaran', $sort, $order); ?>
                            </a>
                        </th>
                        <th>No. KWH</th>
                        <th>
                            <a href="<?php echo sortUrl('nama_pelanggan', $sort, $order); ?>" class="text-white text-decoration-none">
                                Nama Pelanggan <?php echo sortIcon('nama_pelanggan', $sort, $order); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo sortUrl('bulan', $sort, $order); ?>" class="text-white text-decoration-none">
                                Periode <?php echo sortIcon('bulan', $sort, $order); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo sortUrl('tanggal_pembayaran', $sort, $order); ?>" class="text-white text-decoration-none">
                                Tanggal Bayar <?php echo sortIcon('tanggal_pembayaran', $sort, $order); ?>
                            </a>
                        </th>
                        <th>Total Tagihan</th>
                        <th>Biaya Admin</th>
                        <th>
                            <a href="<?php echo sortUrl('total_bayar', $sort, $order); ?>" class="text-white text-decoration-none">
                                Total Bayar <?php echo sortIcon('total_bayar', $sort, $order); ?>
                            </a>
                        </th>
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
                                <td><span class="badge bg-success">#BYR<?php echo str_pad($row['id_pembayaran'], 5, '0', STR_PAD_LEFT); ?></span></td>
                                <td><?php echo $row['nomor_kwh']; ?></td>
                                <td><strong><?php echo $row['nama_pelanggan']; ?></strong></td>
                                <td><?php echo $row['bulan'] . ' ' . $row['tahun']; ?></td>
                                <td><?php echo formatTanggal($row['tanggal_pembayaran']); ?></td>
                                <td><?php echo formatRupiah($total_tagihan); ?></td>
                                <td><?php echo formatRupiah($row['biaya_admin']); ?></td>
                                <td><strong class="text-success"><?php echo formatRupiah($row['total_bayar']); ?></strong></td>
                                <td><span class="badge bg-info"><?php echo $row['nama_admin']; ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p class="mb-0">Tidak ada data pembayaran</p>
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
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
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

<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
