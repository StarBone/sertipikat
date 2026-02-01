<?php

require_once '../includes/functions.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('../pelanggan/dashboard.php');
}

$page_title = 'Data Pelanggan';

// Proses Tambah Pelanggan
if (isset($_POST['tambah'])) {
    $username = clean($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nomor_kwh = clean($_POST['nomor_kwh']);
    $nama_pelanggan = clean($_POST['nama_pelanggan']);
    $alamat = clean($_POST['alamat']);
    $id_tarif = clean($_POST['id_tarif']);
    
    $query = "INSERT INTO pelanggan (username, password, nomor_kwh, nama_pelanggan, alamat, id_tarif) 
              VALUES ('{$username}', '{$password}', '{$nomor_kwh}', '{$nama_pelanggan}', '{$alamat}', {$id_tarif})";
    
    if ($conn->query($query)) {
        setFlashMessage('success', 'Pelanggan berhasil ditambahkan!');
    } else {
        setFlashMessage('error', 'Gagal menambahkan pelanggan: ' . $conn->error);
    }
    redirect('pelanggan.php');
}

// Proses Edit Pelanggan
if (isset($_POST['edit'])) {
    $id_pelanggan = clean($_POST['id_pelanggan']);
    $username = clean($_POST['username']);
    $nomor_kwh = clean($_POST['nomor_kwh']);
    $nama_pelanggan = clean($_POST['nama_pelanggan']);
    $alamat = clean($_POST['alamat']);
    $id_tarif = clean($_POST['id_tarif']);
    
    $query = "UPDATE pelanggan 
              SET username = '{$username}', 
                  nomor_kwh = '{$nomor_kwh}', 
                  nama_pelanggan = '{$nama_pelanggan}', 
                  alamat = '{$alamat}', 
                  id_tarif = {$id_tarif}";
    
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $query .= ", password = '{$password}'";
    }
    
    $query .= " WHERE id_pelanggan = {$id_pelanggan}";
    
    if ($conn->query($query)) {
        setFlashMessage('success', 'Pelanggan berhasil diupdate!');
    } else {
        setFlashMessage('error', 'Gagal mengupdate pelanggan: ' . $conn->error);
    }
    redirect('pelanggan.php');
}

// Proses Hapus Pelanggan
if (isset($_GET['hapus'])) {
    $id_pelanggan = clean($_GET['hapus']);
    
    $query = "DELETE FROM pelanggan WHERE id_pelanggan = {$id_pelanggan}";
    
    if ($conn->query($query)) {
        setFlashMessage('success', 'Pelanggan berhasil dihapus!');
    } else {
        setFlashMessage('error', 'Gagal menghapus pelanggan: ' . $conn->error);
    }
    redirect('pelanggan.php');
}

// Ambil data tarif untuk dropdown
$tarif = getAll('tarif');

// Get filter parameters
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$filter_daya = isset($_GET['daya']) ? clean($_GET['daya']) : '';
$sort = isset($_GET['sort']) ? clean($_GET['sort']) : 'id_pelanggan';
$order = isset($_GET['order']) ? clean($_GET['order']) : 'desc';

// Validasi sort field
$allowed_sort = ['id_pelanggan', 'nama_pelanggan', 'nomor_kwh', 'daya'];
if (!in_array($sort, $allowed_sort)) {
    $sort = 'id_pelanggan';
}

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $per_page;

// Build WHERE clause
$where = [];
if (!empty($search)) {
    $where[] = "(p.nama_pelanggan LIKE '%{$search}%' OR p.nomor_kwh LIKE '%{$search}%' OR p.username LIKE '%{$search}%')";
}
if (!empty($filter_daya)) {
    $where[] = "t.daya = {$filter_daya}";
}
$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Hitung total data
$total_query = "SELECT COUNT(*) as total FROM pelanggan p 
                JOIN tarif t ON p.id_tarif = t.id_tarif {$where_clause}";
$total_result = $conn->query($total_query);
$total_data = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_data / $per_page);

// Build ORDER BY
$order_by = "ORDER BY ";
if ($sort == 'daya') {
    $order_by .= "t.daya {$order}";
} elseif ($sort == 'nama_pelanggan') {
    $order_by .= "p.nama_pelanggan {$order}";
} elseif ($sort == 'nomor_kwh') {
    $order_by .= "p.nomor_kwh {$order}";
} else {
    $order_by .= "p.{$sort} {$order}";
}

// Ambil data pelanggan
$query = "SELECT p.*, t.daya, t.tarifperkwh 
          FROM pelanggan p 
          JOIN tarif t ON p.id_tarif = t.id_tarif 
          {$where_clause}
          {$order_by}
          LIMIT {$start}, {$per_page}";
$result = $conn->query($query);

// Get unique daya for filter
$daya_list = $conn->query("SELECT DISTINCT daya FROM tarif ORDER BY daya ASC");

require_once '../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-users me-2"></i>Data Pelanggan
    </h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Data Pelanggan</li>
        </ol>
    </nav>
</div>

<!-- Action & Filter Section -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-filter me-2"></i>Filter & Pencarian
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="w-50">
                <label class="form-label">Cari</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Nama / No. KWH / Username..." value="<?php echo $search; ?>">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Filter Daya</label>
                <select name="daya" class="form-select">
                    <option value="">Semua Daya</option>
                    <?php while ($d = $daya_list->fetch_assoc()): ?>
                        <option value="<?php echo $d['daya']; ?>" <?php echo $filter_daya == $d['daya'] ? 'selected' : ''; ?>>
                            <?php echo number_format($d['daya']); ?> Watt
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
                <!-- <div class="col-md-2">
                    <label class="form-label">Urutkan</label>
                    <select name="sort" class="form-select">
                        <option value="id_pelanggan" <?php echo $sort == 'id_pelanggan' ? 'selected' : ''; ?>>ID</option>
                        <option value="nama_pelanggan" <?php echo $sort == 'nama_pelanggan' ? 'selected' : ''; ?>>Nama (A-Z)</option>
                        <option value="nomor_kwh" <?php echo $sort == 'nomor_kwh' ? 'selected' : ''; ?>>No. KWH</option>
                        <option value="daya" <?php echo $sort == 'daya' ? 'selected' : ''; ?>>Daya</option>
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
        <h5 class="mb-0">Daftar Pelanggan</h5>
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
                               Nama Pelanggan <?php echo sortIcon('nama_pelanggan', $sort, $order); ?>
                            </a>
                        </th>
                        <th>Username</th>
                        <th>
                            <a href="<?php echo sortUrl('nomor_kwh', $sort, $order); ?>" class="text-white text-decoration-none">
                                No. KWH <?php echo sortIcon('nomor_kwh', $sort, $order); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo sortUrl('alamat', $sort, $order); ?>" class="text-white text-decoration-none">
                                Alamat <?php echo sortIcon('alamat', $sort, $order); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo sortUrl('daya', $sort, $order); ?>" class="text-white text-decoration-none">
                                Daya <?php echo sortIcon('daya', $sort, $order); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo sortUrl('tarifperkwh', $sort, $order); ?>" class="text-white text-decoration-none">
                                Tarif/KWH <?php echo sortIcon('tarifperkwh', $sort, $order); ?>
                            </a>
                        </th>
                        <th width="12%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $no = $start + 1; while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><strong><?php echo $row['nama_pelanggan']; ?></strong></td>
                                <td><?php echo $row['username']; ?></td>
                                <td><span class="badge bg-primary"><?php echo $row['nomor_kwh']; ?></span></td>
                                <td><?php echo substr($row['alamat'], 0, 40) . (strlen($row['alamat']) > 40 ? '...' : ''); ?></td>
                                <td><?php echo number_format($row['daya']); ?> Watt</td>
                                <td><?php echo formatRupiah($row['tarifperkwh']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info btn-icon" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEdit<?php echo $row['id_pelanggan']; ?>"
                                            title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?hapus=<?php echo $row['id_pelanggan']; ?>" 
                                       class="btn btn-sm btn-danger btn-icon"
                                       onclick="return confirmDelete('Apakah Anda yakin ingin menghapus pelanggan ini?')"
                                       title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            
                            <!-- Modal Edit -->
                            <div class="modal fade" id="modalEdit<?php echo $row['id_pelanggan']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-info text-white">
                                            <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Pelanggan</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="id_pelanggan" value="<?php echo $row['id_pelanggan']; ?>">
                                                
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Username</label>
                                                        <input type="text" name="username" class="form-control" 
                                                               value="<?php echo $row['username']; ?>" required>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Password <small class="text-muted">(Kosongkan jika tidak diubah)</small></label>
                                                        <input type="password" name="password" class="form-control">
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Nomor KWH</label>
                                                        <input type="text" name="nomor_kwh" class="form-control" 
                                                               value="<?php echo $row['nomor_kwh']; ?>" required>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Nama Pelanggan</label>
                                                        <input type="text" name="nama_pelanggan" class="form-control" 
                                                               value="<?php echo $row['nama_pelanggan']; ?>" required>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Alamat</label>
                                                    <textarea name="alamat" class="form-control" rows="3" required><?php echo $row['alamat']; ?></textarea>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Daya Listrik</label>
                                                    <select name="id_tarif" class="form-select" required>
                                                        <?php foreach ($tarif as $t): ?>
                                                            <option value="<?php echo $t['id_tarif']; ?>" 
                                                                    <?php echo $t['id_tarif'] == $row['id_tarif'] ? 'selected' : ''; ?>>
                                                                <?php echo number_format($t['daya']); ?> Watt - <?php echo formatRupiah($t['tarifperkwh']); ?>/kWh
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    <i class="fas fa-times me-2"></i>Batal
                                                </button>
                                                <button type="submit" name="edit" class="btn btn-info text-white">
                                                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p class="mb-0">Tidak ada data pelanggan</p>
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
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page-1])); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                            </li>
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
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"><?php echo $total_pages; ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page+1])); ?>">
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

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Tambah Pelanggan Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor KWH</label>
                            <input type="text" name="nomor_kwh" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Pelanggan</label>
                            <input type="text" name="nama_pelanggan" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Daya Listrik</label>
                        <select name="id_tarif" class="form-select" required>
                            <option value="">Pilih Daya</option>
                            <?php foreach ($tarif as $t): ?>
                                <option value="<?php echo $t['id_tarif']; ?>">
                                    <?php echo number_format($t['daya']); ?> Watt - <?php echo formatRupiah($t['tarifperkwh']); ?>/kWh
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Batal
                    </button>
                    <button type="submit" name="tambah" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
