<?php

require_once '../includes/functions.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('../pelanggan/dashboard.php');
}

$page_title = 'Data Tarif';

// Proses Tambah Tarif
if (isset($_POST['tambah'])) {
    $daya = clean($_POST['daya']);
    $tarifperkwh = clean($_POST['tarifperkwh']);
    
    $query = "INSERT INTO tarif (daya, tarifperkwh) VALUES ({$daya}, {$tarifperkwh})";
    
    if ($conn->query($query)) {
        setFlashMessage('success', 'Tarif berhasil ditambahkan!');
    } else {
        setFlashMessage('error', 'Gagal menambahkan tarif: ' . $conn->error);
    }
    redirect('tarif.php');
}

// Proses Edit Tarif
if (isset($_POST['edit'])) {
    $id_tarif = clean($_POST['id_tarif']);
    $daya = clean($_POST['daya']);
    $tarifperkwh = clean($_POST['tarifperkwh']);
    
    $query = "UPDATE tarif SET daya = {$daya}, tarifperkwh = {$tarifperkwh} WHERE id_tarif = {$id_tarif}";
    
    if ($conn->query($query)) {
        setFlashMessage('success', 'Tarif berhasil diupdate!');
    } else {
        setFlashMessage('error', 'Gagal mengupdate tarif: ' . $conn->error);
    }
    redirect('tarif.php');
}

// Proses Hapus Tarif
if (isset($_GET['hapus'])) {
    $id_tarif = clean($_GET['hapus']);
    
    $query = "DELETE FROM tarif WHERE id_tarif = {$id_tarif}";
    
    if ($conn->query($query)) {
        setFlashMessage('success', 'Tarif berhasil dihapus!');
    } else {
        setFlashMessage('error', 'Gagal menghapus tarif: ' . $conn->error);
    }
    redirect('tarif.php');
}

// Get sort parameters
$sort = isset($_GET['sort']) ? clean($_GET['sort']) : 'daya';
$order = isset($_GET['order']) ? clean($_GET['order']) : 'asc';

// Build ORDER BY
$order_by = "ORDER BY {$sort} {$order}";

// Ambil semua data tarif
$result = $conn->query("SELECT * FROM tarif {$order_by}");

require_once '../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-plug me-2"></i>Data Tarif
    </h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Data Tarif</li>
        </ol>
    </nav>
</div>

<!-- Action & Filter Section -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">Data</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="">
            <!-- <div class="col-md-3">
                <label class="form-label">Urutkan Berdasarkan</label>
                <select name="sort" class="form-select" onchange="this.form.submit()">
                    <option value="daya" <?php echo $sort == 'daya' ? 'selected' : ''; ?>>Daya</option>
                    <option value="tarifperkwh" <?php echo $sort == 'tarifperkwh' ? 'selected' : ''; ?>>Tarif per kWh</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Order</label>
                <select name="order" class="form-select" onchange="this.form.submit()">
                    <option value="asc" <?php echo $order == 'asc' ? 'selected' : ''; ?>>Terendah → Tertinggi</option>
                    <option value="desc" <?php echo $order == 'desc' ? 'selected' : ''; ?>>Tertinggi → Terendah</option>
                </select>
            </div> -->
            <div class="">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="fas fa-plus me-2"></i>Tambah Tarif
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Daftar Tarif Listrik</h5>
        <span class="text-muted">Total: <?php echo $result->num_rows; ?> tarif</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th width="10%">No</th>
                        <th>
                            <a href="?sort=daya&order=<?php echo ($sort == 'daya' && $order == 'asc') ? 'desc' : 'asc'; ?>" class="text-white text-decoration-none">
                                Daya (Watt) <?php echo ($sort == 'daya') ? ($order == 'asc' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>') : '<i class="fas fa-sort text-white-50"></i>'; ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=tarifperkwh&order=<?php echo ($sort == 'tarifperkwh' && $order == 'asc') ? 'desc' : 'asc'; ?>" class="text-white text-decoration-none">
                                Tarif per kWh <?php echo ($sort == 'tarifperkwh') ? ($order == 'asc' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>') : '<i class="fas fa-sort text-white-50"></i>'; ?>
                            </a>
                        </th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><span class="badge bg-primary fs-6"><?php echo number_format($row['daya']); ?> Watt</span></td>
                                <td><strong class="text-success"><?php echo formatRupiah($row['tarifperkwh']); ?></strong></td>
                                <td>
                                    <button class="btn btn-sm btn-warning btn-icon" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEdit<?php echo $row['id_tarif']; ?>"
                                            title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?hapus=<?php echo $row['id_tarif']; ?>" 
                                       class="btn btn-sm btn-danger btn-icon"
                                       onclick="return confirmDelete('Apakah Anda yakin ingin menghapus tarif ini?')"
                                       title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            
                            <!-- Modal Edit -->
                            <div class="modal fade" id="modalEdit<?php echo $row['id_tarif']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-warning">
                                            <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Tarif</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="id_tarif" value="<?php echo $row['id_tarif']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Daya (Watt)</label>
                                                    <input type="number" name="daya" class="form-control" 
                                                           value="<?php echo $row['daya']; ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Tarif per kWh (Rp)</label>
                                                    <input type="number" step="0.01" name="tarifperkwh" class="form-control" 
                                                           value="<?php echo $row['tarifperkwh']; ?>" required>
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
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p class="mb-0">Tidak ada data tarif</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Info Card -->
<div class="card mt-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Tarif</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <p class="mb-0">
                    Tarif listrik di atas adalah tarif dasar yang diberlakukan untuk setiap pelanggan berdasarkan daya yang dipilih. 
                    Total tagihan dihitung dari <strong>Jumlah Meter (kWh) × Tarif per kWh</strong>. 
                    Biaya administrasi sebesar <strong>Rp 2.500</strong> akan ditambahkan pada saat pembayaran.
                </p>
            </div>
            <div class="col-md-4">
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Perhatian:</strong> Perubahan tarif akan mempengaruhi perhitungan tagihan selanjutnya.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Tambah Tarif Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Daya (Watt)</label>
                        <input type="number" name="daya" class="form-control" required>
                        <small class="text-muted">Contoh: 450, 900, 1300, 2200, dll</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tarif per kWh (Rp)</label>
                        <input type="number" step="0.01" name="tarifperkwh" class="form-control" required>
                        <small class="text-muted">Contoh: 415, 1352, 1444.70, dll</small>
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
