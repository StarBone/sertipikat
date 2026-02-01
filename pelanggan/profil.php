<?php

require_once '../includes/functions.php';

// Cek apakah user adalah pelanggan
if (!isPelanggan()) {
    redirect('../admin/dashboard.php');
}

$page_title = 'Profil Saya';
$id_pelanggan = $_SESSION['id_pelanggan'];

// Ambil data pelanggan
$query = "SELECT p.*, t.daya, t.tarifperkwh 
          FROM pelanggan p 
          JOIN tarif t ON p.id_tarif = t.id_tarif 
          WHERE p.id_pelanggan = {$id_pelanggan}";
$result = $conn->query($query);
$pelanggan = $result->fetch_assoc();

// Proses Update Profil
if (isset($_POST['update_profil'])) {
    $nama_pelanggan = clean($_POST['nama_pelanggan']);
    $alamat = clean($_POST['alamat']);
    
    $query = "UPDATE pelanggan 
              SET nama_pelanggan = '{$nama_pelanggan}', 
                  alamat = '{$alamat}'
              WHERE id_pelanggan = {$id_pelanggan}";
    
    if ($conn->query($query)) {
        $_SESSION['nama'] = $nama_pelanggan;
        setFlashMessage('success', 'Profil berhasil diupdate!');
    } else {
        setFlashMessage('error', 'Gagal mengupdate profil: ' . $conn->error);
    }
    redirect('profil.php');
}

// Proses Update Password
if (isset($_POST['update_password'])) {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    // Verifikasi password lama
    if (!password_verify($password_lama, $pelanggan['password'])) {
        setFlashMessage('error', 'Password lama tidak sesuai!');
        redirect('profil.php');
    }
    
    // Cek konfirmasi password
    if ($password_baru !== $konfirmasi_password) {
        setFlashMessage('error', 'Konfirmasi password tidak sesuai!');
        redirect('profil.php');
    }
    
    // Update password
    $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
    $query = "UPDATE pelanggan SET password = '{$password_hash}' WHERE id_pelanggan = {$id_pelanggan}";
    
    if ($conn->query($query)) {
        setFlashMessage('success', 'Password berhasil diupdate!');
    } else {
        setFlashMessage('error', 'Gagal mengupdate password: ' . $conn->error);
    }
    redirect('profil.php');
}

require_once '../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-user-cog me-2"></i>Profil Saya
    </h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Profil Saya</li>
        </ol>
    </nav>
</div>

<div class="row">
    <!-- Informasi Profil -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>Informasi Profil
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?php echo $pelanggan['username']; ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nomor KWH</label>
                        <input type="text" class="form-control" value="<?php echo $pelanggan['nomor_kwh']; ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama_pelanggan" class="form-control" 
                               value="<?php echo $pelanggan['nama_pelanggan']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="3" required><?php echo $pelanggan['alamat']; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Daya Listrik</label>
                        <input type="text" class="form-control" 
                               value="<?php echo number_format($pelanggan['daya']); ?> Watt" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tarif per kWh</label>
                        <input type="text" class="form-control" 
                               value="<?php echo formatRupiah($pelanggan['tarifperkwh']); ?>" readonly>
                    </div>
                    
                    <button type="submit" name="update_profil" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Ubah Password -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-lock me-2"></i>Ubah Password
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Password Lama</label>
                        <input type="password" name="password_lama" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password_baru" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" name="konfirmasi_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" name="update_password" class="btn btn-warning">
                        <i class="fas fa-key me-2"></i>Ubah Password
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Info Card -->
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Informasi
                </h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        Username dan Nomor KWH tidak dapat diubah
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        Daya dan tarif ditentukan oleh pihak PLN
                    </li>
                    <li>
                        <i class="fas fa-check-circle text-success me-2"></i>
                        Pastikan password baru mudah diingat
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
