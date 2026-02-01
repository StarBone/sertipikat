<?php

require_once 'includes/functions.php';

// Jika sudah login, redirect ke dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('pelanggan/dashboard.php');
    }
}

$error = '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        // Cek di tabel user (admin)
        $query = "SELECT u.*, l.nama_level FROM user u 
                  JOIN level l ON u.id_level = l.id_level 
                  WHERE u.username = '{$username}'";
        $result = $conn->query($query);
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama'] = $user['nama_admin'];
                $_SESSION['level'] = $user['nama_level'];
                $_SESSION['id_level'] = $user['id_level'];
                
                setFlashMessage('success', 'Selamat datang, ' . $user['nama_admin'] . '!');
                redirect('admin/dashboard.php');
            } else {
                $error = 'Password salah!';
            }
        } else {
            // Cek di tabel pelanggan
            $query = "SELECT p.*, l.nama_level, t.daya, t.tarifperkwh 
                      FROM pelanggan p 
                      JOIN level l ON l.id_level = 2
                      JOIN tarif t ON p.id_tarif = t.id_tarif
                      WHERE p.username = '{$username}'";
            $result = $conn->query($query);
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id_pelanggan'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['nama'] = $user['nama_pelanggan'];
                    $_SESSION['level'] = 'Pelanggan';
                    $_SESSION['id_pelanggan'] = $user['id_pelanggan'];
                    $_SESSION['nomor_kwh'] = $user['nomor_kwh'];
                    $_SESSION['daya'] = $user['daya'];
                    
                    setFlashMessage('success', 'Selamat datang, ' . $user['nama_pelanggan'] . '!');
                    redirect('pelanggan/dashboard.php');
                } else {
                    $error = 'Password salah!';
                }
            } else {
                $error = 'Username tidak ditemukan!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #1a5276;
            --primary-dark: #154360;
            --primary-light: #2980b9;
        }
        
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .login-header i {
            font-size: 4rem;
            margin-bottom: 15px;
            color: #f1c40f;
        }
        
        .login-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }
        
        .login-header p {
            margin: 10px 0 0;
            opacity: 0.9;
        }
        
        .login-body {
            padding: 35px 30px;
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-floating .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            height: 55px;
        }
        
        .form-floating .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(26, 82, 118, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 82, 118, 0.4);
            color: white;
        }
        
        .login-footer {
            text-align: center;
            padding: 20px 30px;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
        }
        
        .login-footer p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .demo-info {
            background: #e8f4f8;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .demo-info h6 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .demo-info p {
            margin: 5px 0;
            font-size: 0.85rem;
            color: #555;
        }
        
        .demo-info code {
            background: #fff;
            padding: 2px 6px;
            border-radius: 4px;
            color: #e74c3c;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-bolt"></i>
            <h1><?php echo APP_NAME; ?></h1>
            <p>Sistem Pembayaran Listrik Pascabayar</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Username" required autofocus>
                    <label for="username"><i class="fas fa-user me-2 text-muted"></i>Username</label>
                </div>
                
                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Password" required>
                    <label for="password"><i class="fas fa-lock me-2 text-muted"></i>Password</label>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
            </form>
            
            <div class="demo-info">
                <h6><i class="fas fa-info-circle me-2"></i>Demo Login</h6>
                <p class="mb-1"><strong>Admin:</strong> <code>admin</code> / <code>password</code></p>
                <p class="mb-0"><strong>Pelanggan:</strong> <code>budi</code> / <code>password</code></p>
            </div>
        </div>
        
        <div class="login-footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - v<?php echo APP_VERSION; ?></p>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
