<?php

// Cek apakah user sudah login
if (!isLoggedIn()) {
    redirect('../index.php');
}

// Dapatkan nama halaman aktif
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Get sort parameters
$sort = isset($_GET['sort']) ? clean($_GET['sort']) : '';
$order = isset($_GET['order']) ? clean($_GET['order']) : 'asc';

// Function to generate sort URL
function sortUrl($field, $currentSort, $currentOrder) {
    $newOrder = ($currentSort == $field && $currentOrder == 'asc') ? 'desc' : 'asc';
    $query = $_GET;
    $query['sort'] = $field;
    $query['order'] = $newOrder;
    return '?' . http_build_query($query);
}

// Function to display sort icon
function sortIcon($field, $currentSort, $currentOrder) {
    if ($currentSort == $field) {
        return $currentOrder == 'asc' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
    }
    return '<i class="fas fa-sort text-muted"></i>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Sidebar Overlay (for mobile) -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">
                <i class="fas fa-bolt"></i>
                <span>LISTRIK</span>
            </a>
        </div>
        
        <div class="sidebar-user">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-name"><?php echo $_SESSION['nama']; ?></div>
            <div class="user-role">
                <span class="badge bg-primary"><?php echo $_SESSION['level']; ?></span>
            </div>
        </div>
        
        <nav class="sidebar-menu">
            <?php if (isAdmin()): ?>
                <!-- Menu Admin -->
                <div class="menu-header">Menu Utama</div>
                
                <a href="dashboard.php" class="menu-item <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="pelanggan.php" class="menu-item <?php echo $current_page == 'pelanggan' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Data Pelanggan</span>
                </a>
                
                <a href="penggunaan.php" class="menu-item <?php echo $current_page == 'penggunaan' ? 'active' : ''; ?>">
                    <i class="fas fa-bolt"></i>
                    <span>Penggunaan Listrik</span>
                </a>
                
                <a href="tagihan.php" class="menu-item <?php echo $current_page == 'tagihan' ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice"></i>
                    <span>Tagihan</span>
                </a>
                
                <a href="pembayaran.php" class="menu-item <?php echo $current_page == 'pembayaran' ? 'active' : ''; ?>">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Pembayaran</span>
                </a>
                
                <div class="menu-header">Master Data</div>
                
                <a href="tarif.php" class="menu-item <?php echo $current_page == 'tarif' ? 'active' : ''; ?>">
                    <i class="fas fa-plug"></i>
                    <span>Data Tarif</span>
                </a>
            
            <?php else: ?>
                <!-- Menu Pelanggan -->
                <div class="menu-header">Menu Pelanggan</div>
                
                <a href="dashboard.php" class="menu-item <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="tagihan.php" class="menu-item <?php echo $current_page == 'tagihan' ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice"></i>
                    <span>Tagihan Saya</span>
                </a>
                
                <a href="riwayat.php" class="menu-item <?php echo $current_page == 'riwayat' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i>
                    <span>Riwayat Pembayaran</span>
                </a>
                
                <a href="profil.php" class="menu-item <?php echo $current_page == 'profil' ? 'active' : ''; ?>">
                    <i class="fas fa-user-cog"></i>
                    <span>Profil Saya</span>
                </a>
            <?php endif; ?>
            
            <div class="menu-header">Sistem</div>
            
            <a href="../logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="main-content">
        <!-- Top Navbar -->
        <header class="top-navbar">
            <button class="toggle-sidebar" id="toggle-sidebar">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="navbar-right d-flex justify-content-end align-items-center w-100">
                <div class="nav-item">
                    <span class="text-muted">
                        <i class="far fa-calendar-alt me-2"></i>
                        <?php echo date('d F Y'); ?>
                    </span>
                </div>
                
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2"></i>
                        <?php echo $_SESSION['nama']; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if (!isAdmin()): ?>
                        <li>
                            <a class="dropdown-item" href="profil.php">
                                <i class="fas fa-user me-2"></i>Profil
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li>
                            <a class="dropdown-item text-danger" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>
        
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <?php echo showFlashMessage(); ?>
