<?php
include 'config.php';
checkAuth();

$message = '';
$error = '';

// Handle backup request
if (isset($_POST['backup'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid form submission";
    } else {
        $backupFile = 'backup/anime_pc_backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        if (!file_exists('backup')) {
            mkdir('backup', 0777, true);
        }
        
        // Get all table data including structure and data
        $tables = ['users', 'clients', 'products', 'settings'];
        $backupContent = "-- Anime PC Warranty System Database Backup\n";
        $backupContent .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $backupContent .= "-- Database: " . DB_NAME . "\n\n";
        
        // Set SQL mode to avoid issues
        $backupContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            // Get table structure
            $result = $pdo->query("SHOW CREATE TABLE `$table`");
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $backupContent .= "--\n-- Table structure for table `$table`\n--\n\n";
            $backupContent .= "DROP TABLE IF EXISTS `$table`;\n";
            $backupContent .= $row['Create Table'] . ";\n\n";
            
            // Get table data
            $backupContent .= "--\n-- Dumping data for table `$table`\n--\n\n";
            
            $result = $pdo->query("SELECT * FROM `$table`");
            $rows = $result->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($rows) > 0) {
                $columns = array_keys($rows[0]);
                $columnList = '`' . implode('`, `', $columns) . '`';
                
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = $pdo->quote($value);
                        }
                    }
                    $backupContent .= "INSERT INTO `$table` ($columnList) VALUES (" . implode(', ', $values) . ");\n";
                }
                $backupContent .= "\n";
            } else {
                $backupContent .= "-- No data found for table `$table`\n\n";
            }
        }
        
        // Re-enable foreign key checks
        $backupContent .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        if (file_put_contents($backupFile, $backupContent)) {
            $message = "Backup created successfully: " . basename($backupFile);
            
            // Log backup activity
            $logMessage = "Backup created: " . basename($backupFile) . " by " . $_SESSION['username'];
            error_log($logMessage);
        } else {
            $error = "Failed to create backup file. Please check directory permissions.";
        }
    }
}

// Handle restore request
if (isset($_POST['restore']) && isset($_FILES['backup_file'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid form submission";
    } else {
        $file = $_FILES['backup_file'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $backupContent = file_get_contents($file['tmp_name']);
            
            // Validate it's a SQL file
            if (strpos($backupContent, '-- Anime PC Warranty System Database Backup') === false) {
                $error = "Invalid backup file. Please upload a valid Anime PC backup file.";
            } else {
                // Split SQL statements
                $statements = array_filter(array_map('trim', explode(';', $backupContent)));
                
                try {
                    $pdo->beginTransaction();
                    
                    // Disable foreign key checks
                    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
                    
                    $successCount = 0;
                    $errorCount = 0;
                    
                    foreach ($statements as $statement) {
                        if (!empty($statement) && 
                            !str_starts_with($statement, '--') && 
                            !str_starts_with($statement, '/*')) {
                            try {
                                $pdo->exec($statement);
                                $successCount++;
                            } catch (Exception $e) {
                                $errorCount++;
                                error_log("SQL Error in backup restore: " . $e->getMessage());
                            }
                        }
                    }
                    
                    // Re-enable foreign key checks
                    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
                    
                    $pdo->commit();
                    
                    if ($errorCount > 0) {
                        $message = "Database restored with $errorCount errors. $successCount statements executed successfully.";
                    } else {
                        $message = "Database restored successfully! $successCount statements executed.";
                    }
                    
                    // Log restore activity
                    $logMessage = "Database restored from: " . $file['name'] . " by " . $_SESSION['username'];
                    error_log($logMessage);
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Restore failed: " . $e->getMessage();
                }
            }
        } else {
            $error = "Error uploading backup file: " . $file['error'];
        }
    }
}

// Handle backup deletion
if (isset($_GET['delete_backup'])) {
    $backupFile = 'backup/' . basename($_GET['delete_backup']);
    if (file_exists($backupFile) && unlink($backupFile)) {
        $message = "Backup file deleted successfully!";
        
        // Log deletion activity
        $logMessage = "Backup deleted: " . basename($backupFile) . " by " . $_SESSION['username'];
        error_log($logMessage);
    } else {
        $error = "Failed to delete backup file.";
    }
}

// Handle backup download
if (isset($_GET['download_backup'])) {
    $backupFile = 'backup/' . basename($_GET['download_backup']);
    if (file_exists($backupFile)) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . basename($backupFile) . '"');
        header('Content-Length: ' . filesize($backupFile));
        readfile($backupFile);
        exit;
    }
}

// Get existing backups
$backups = [];
if (file_exists('backup')) {
    $files = glob('backup/*.sql');
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    $backups = $files;
}

// Get database statistics
$dbStats = [
    'products' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'clients' => $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn(),
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'settings' => $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn(),
    'total_size' => 0
];

// Calculate database size
$result = $pdo->query("
    SELECT SUM(data_length + index_length) as size
    FROM information_schema.TABLES 
    WHERE table_schema = '" . DB_NAME . "'
")->fetch();
$dbStats['total_size'] = $result['size'] ? round($result['size'] / 1024 / 1024, 2) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore - Anime PC Warranty System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
             --primary: #008028ff;
            --secondary: #232323ff;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #1f2937;
            --light: #f8fafc;
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: #f8fafc;
            font-weight: 400;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
            color: #fff;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            padding-top: 20px;
            transition: all 0.3s;
            box-shadow: 3px 0 20px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .sidebar-brand {
            padding: 0 20px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav li {
            margin: 5px 15px;
        }
        
        .sidebar-nav a {
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .sidebar-nav a:hover, .sidebar-nav a.active {
            background: rgba(255,255,255,0.15);
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .sidebar-nav i {
            font-size: 1.3rem;
            margin-right: 12px;
            width: 24px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 280px;
            padding: 30px;
            transition: all 0.3s;
            background: #f8fafc;
            min-height: 100vh;
        }
        
        .navbar-custom {
            background: #fff;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            border-radius: 15px;
            padding: 15px 25px;
            margin-bottom: 30px;
        }
        
        .backup-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 0;
            margin-bottom: 30px;
            border: none;
            overflow: hidden;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 25px 30px;
            border: none;
        }
        
        .card-body-custom {
            padding: 30px;
        }
        
        .btn-custom {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-success-custom {
            background: linear-gradient(135deg, var(--success), #34d399);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-success-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
            color: white;
        }
        
        .btn-warning-custom {
            background: linear-gradient(135deg, var(--warning), #fbbf24);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-warning-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
            color: white;
        }
        
        .form-control, .form-select {
            border-radius: 12px;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }
        
        .backup-item {
            background: #fff;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .backup-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: var(--primary);
        }
        
        .backup-actions {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 500;
        }
        
        .feature-section {
            background: #f8fafc;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-label {
            display: block;
            padding: 15px;
            border: 2px dashed #cbd5e0;
            border-radius: 12px;
            text-align: center;
            background: #f8fafc;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .file-input-label:hover {
            border-color: var(--primary);
            background: #f1f5f9;
        }
        
        .file-input-label.dragover {
            border-color: var(--success);
            background: #f0fdf4;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            .sidebar span {
                display: none;
            }
            .sidebar-brand h4 span {
                display: none;
            }
            .main-content {
                margin-left: 70px;
                padding: 20px;
            }
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .progress-bar-custom {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h4 class="mb-0"><i class="bi bi-pc-display-horizontal"></i> <span>Anime PC</span></h4>
            <small class="opacity-75">Warranty System</small>
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a></li>
            <li><a href="products.php"><i class="bi bi-plus-circle"></i> <span>Add Record</span></a></li>
            <li><a href="clients.php"><i class="bi bi-people"></i> <span>Clients</span></a></li>
            <li><a href="warranty.php"><i class="bi bi-search"></i> <span>Check Warranty</span></a></li>
                            <li><a href="history.php"><i class="bi bi-trash me-2  "></i> <span>History</span></a></li>
            <li><a href="reports.php"><i class="bi bi-graph-up"></i> <span>Reports</span></a></li>
            <li><a href="backup.php" class="active"><i class="bi bi-arrow-clockwise"></i> <span>Backup/Restore</span></a></li>
                <li> <a href="Login.php" class="logout-btn" id="logoutBtn">
    <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light navbar-custom">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php"><i class="bi bi-house-door me-2"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="#"><i class="bi bi-arrow-clockwise me-2"></i> Backup/Restore</a>
                        </li>
                    </ul>
                    <span class="navbar-text">
                        <i class="bi bi-person-circle me-2"></i> Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                    </span>
                </div>
            </div>
        </nav>

        <h1 class="h3 fw-bold text-dark mb-4"><i class="bi bi-arrow-clockwise me-2"></i>Backup & Restore</h1>

        <!-- Database Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $dbStats['products']; ?></div>
                <div class="stat-label">Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $dbStats['clients']; ?></div>
                <div class="stat-label">Clients</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $dbStats['users']; ?></div>
                <div class="stat-label">Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $dbStats['total_size']; ?> MB</div>
                <div class="stat-label">Database Size</div>
            </div>
        </div>

        <!-- Backup Section -->
        <div class="backup-card">
            <div class="card-header-custom">
                <h4 class="mb-2"><i class="bi bi-cloud-arrow-down me-2"></i>Create Backup</h4>
                <p class="mb-0 opacity-75">Generate a complete backup of your database including all data and settings</p>
            </div>
            <div class="card-body-custom">
                <?php if ($message): ?>
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="feature-section">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="feature-icon">
                                <i class="bi bi-database"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Complete Database Backup</h5>
                            <p class="text-muted mb-0">
                                Creates a comprehensive backup including all products, clients, users, and system settings. 
                                The backup file contains SQL statements that can restore your entire database.
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <button type="submit" name="backup" class="btn btn-success-custom">
                                    <i class="bi bi-cloud-arrow-down me-2"></i>Create Backup Now
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="row text-muted">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <span>Includes all tables and data</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <span>Preserves relationships</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <span>Automatic timestamping</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Restore Section -->
        <div class="backup-card">
            <div class="card-header-custom">
                <h4 class="mb-2"><i class="bi bi-cloud-arrow-up me-2"></i>Restore Backup</h4>
                <p class="mb-0 opacity-75">Restore your database from a previous backup file</p>
            </div>
            <div class="card-body-custom">
                <div class="feature-section">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="feature-icon">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Restore Database</h5>
                            <p class="text-muted mb-3">
                                Upload a backup file to restore your database. This will replace all current data with the backup data.
                                <strong class="text-danger">This action cannot be undone!</strong>
                            </p>
                            
                            <form method="POST" enctype="multipart/form-data" id="restoreForm">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <div class="file-input-wrapper mb-3">
                                    <input type="file" id="backup_file" name="backup_file" accept=".sql" required>
                                    <label for="backup_file" class="file-input-label" id="fileInputLabel">
                                        <i class="bi bi-cloud-arrow-up display-4 text-muted mb-3"></i>
                                        <h5 class="text-muted">Choose Backup File</h5>
                                        <p class="text-muted mb-0">Drag & drop your SQL backup file here or click to browse</p>
                                        <small class="text-muted">Maximum file size: 50MB</small>
                                    </label>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" name="restore" class="btn btn-warning-custom">
                                        <i class="bi bi-arrow-counterclockwise me-2"></i>Restore Database
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-warning">
                                <h6 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Warning</h6>
                                <p class="mb-0 small">
                                    Restoring a backup will completely replace your current database. 
                                    All existing data will be lost and replaced with the backup data.
                                    Make sure you have a current backup before proceeding.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Existing Backups -->
        <div class="backup-card">
            <div class="card-header-custom">
                <h4 class="mb-2"><i class="bi bi-clock-history me-2"></i>Existing Backups</h4>
                <p class="mb-0 opacity-75">Manage your existing backup files</p>
            </div>
            <div class="card-body-custom">
                <?php if (count($backups) > 0): ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Backup Files (<?php echo count($backups); ?>)</h5>
                            <small class="text-muted">Sorted by most recent</small>
                        </div>
                    </div>
                    
                    <div class="backup-list">
                        <?php foreach ($backups as $backup): 
                            $filename = basename($backup);
                            $filesize = round(filesize($backup) / 1024 / 1024, 2); // MB
                            $filetime = date('F j, Y H:i:s', filemtime($backup));
                            $timeAgo = time_elapsed_string($filetime);
                            
                            // Extract date from filename for display
                            preg_match('/anime_pc_backup_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})/', $filename, $matches);
                            $displayName = $matches[1] ?? $filename;
                        ?>
                            <div class="backup-item">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h6 class="fw-bold mb-1">
                                            <i class="bi bi-file-earmark-zip text-primary me-2"></i>
                                            <?php echo $displayName; ?>
                                        </h6>
                                        <div class="text-muted small">
                                            <span class="me-3"><i class="bi bi-calendar me-1"></i><?php echo $filetime; ?></span>
                                            <span class="me-3"><i class="bi bi-hdd me-1"></i><?php echo $filesize; ?> MB</span>
                                            <span><i class="bi bi-clock me-1"></i><?php echo $timeAgo; ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="backup-actions">
                                            <a href="backup.php?download_backup=<?php echo urlencode($filename); ?>" class="btn btn-sm btn-outline-primary me-2">
                                                <i class="bi bi-download me-1"></i>Download
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger delete-backup" 
                                                    data-filename="<?php echo $filename; ?>">
                                                <i class="bi bi-trash me-1"></i>Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox display-1 text-muted mb-3"></i>
                        <h4 class="text-muted">No backups found</h4>
                        <p class="text-muted">Create your first backup using the form above.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Information Section -->
        <div class="backup-card">
            <div class="card-header-custom">
                <h4 class="mb-2"><i class="bi bi-info-circle me-2"></i>Backup Information</h4>
                <p class="mb-0 opacity-75">Important notes about backup and restore</p>
            </div>
            <div class="card-body-custom">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3"><i class="bi bi-lightbulb me-2"></i>Best Practices</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Create regular backups before major changes</li>
                            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Store backups in multiple locations</li>
                            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Test backup restoration periodically</li>
                            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Keep at least 3 recent backups</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3"><i class="bi bi-shield-exclamation me-2"></i>Security Notes</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Backup files contain sensitive data</li>
                            <li class="mb-2"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Store backups securely</li>
                            <li class="mb-2"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Don't share backup files publicly</li>
                            <li class="mb-2"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Delete old backups when no longer needed</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script>
    // Logout confirmation
    document.getElementById('logoutBtn').addEventListener('click', function (e) {
        e.preventDefault(); // pigilan ang default link behavior

        Swal.fire({
            title: 'Are you sure?',
            text: 'You will be logged out of the system.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, logout',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php';
            }
        });
    });
</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File input handling
        const fileInput = document.getElementById('backup_file');
        const fileInputLabel = document.getElementById('fileInputLabel');
        
        fileInput.addEventListener('change', function(e) {
            if (this.files.length > 0) {
                const fileName = this.files[0].name;
                fileInputLabel.innerHTML = `
                    <i class="bi bi-file-earmark-check display-4 text-success mb-2"></i>
                    <h5 class="text-success">${fileName}</h5>
                    <p class="text-muted mb-0">File selected successfully</p>
                    <small class="text-muted">Click to choose a different file</small>
                `;
                fileInputLabel.classList.add('dragover');
            }
        });

        // Drag and drop functionality
        fileInputLabel.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        fileInputLabel.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        fileInputLabel.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
            
            if (fileInput.files.length > 0) {
                const fileName = fileInput.files[0].name;
                fileInputLabel.innerHTML = `
                    <i class="bi bi-file-earmark-check display-4 text-success mb-2"></i>
                    <h5 class="text-success">${fileName}</h5>
                    <p class="text-muted mb-0">File dropped successfully</p>
                    <small class="text-muted">Click to choose a different file</small>
                `;
                fileInputLabel.classList.add('dragover');
            }
        });

        // Delete backup confirmation
        document.querySelectorAll('.delete-backup').forEach(button => {
            button.addEventListener('click', function() {
                const filename = this.getAttribute('data-filename');
                
                Swal.fire({
                    title: 'Delete Backup?',
                    text: `Are you sure you want to delete "${filename}"? This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `backup.php?delete_backup=${filename}`;
                    }
                });
            });
        });

        // Restore confirmation
        document.getElementById('restoreForm').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('backup_file');
            
            if (fileInput.files.length === 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'No File Selected',
                    text: 'Please select a backup file to restore.',
                    confirmButtonColor: '#764ba2'
                });
                return;
            }

            e.preventDefault();
            
            Swal.fire({
                title: 'Restore Database?',
                text: 'This will replace ALL current data with the backup data. This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, restore it!',
                cancelButtonText: 'Cancel',
                backdrop: true,
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Restoring...',
                        text: 'Please wait while we restore your database.',
                        icon: 'info',
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit the form
                    e.target.submit();
                }
            });
        });

        // SweetAlert for messages
        <?php if ($message): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?php echo $message; ?>',
                confirmButtonColor: '#764ba2'
            });
        });
        <?php endif; ?>
        
        <?php if ($error): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '<?php echo $error; ?>',
                confirmButtonColor: '#764ba2'
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>

<?php
// Helper function to display time ago
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>