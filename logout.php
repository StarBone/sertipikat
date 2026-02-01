<?php

require_once 'includes/functions.php';

// Hapus semua session
session_unset();
session_destroy();

// Redirect ke halaman login
header("Location: index.php");
exit();
?>
