<?php
// config.php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'anime_pc_warranty');

// File upload configuration
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Create connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Check if user is logged in
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// File upload function
function uploadFile($file) {
    $fileName = time() . '_' . basename($file['name']);
    $targetPath = UPLOAD_DIR . $fileName;
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File is too large.'];
    }
    
    // Check file type
    $fileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
    if (!in_array($fileType, ALLOWED_TYPES)) {
        return ['success' => false, 'error' => 'Only JPG, JPEG, PNG & GIF files are allowed.'];
    }
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'file_path' => $targetPath];
    } else {
        return ['success' => false, 'error' => 'Failed to upload file.'];
    }
}

// Get warranty status
function getWarrantyStatus($warranty_end_date, $status) {
    $endDate = new DateTime($warranty_end_date);
    $today = new DateTime();
    
    if ($status == 'void') {
        return ['status' => 'void', 'class' => 'secondary', 'text' => 'Void'];
    } elseif ($endDate < $today || $status == 'expired') {
        return ['status' => 'expired', 'class' => 'danger', 'text' => 'Expired'];
    } elseif ($endDate <= (new DateTime())->modify('+30 days')) {
        return ['status' => 'expiring_soon', 'class' => 'warning', 'text' => 'Expiring Soon'];
    } else {
        return ['status' => 'active', 'class' => 'success', 'text' => 'Active'];
    }
}
?>