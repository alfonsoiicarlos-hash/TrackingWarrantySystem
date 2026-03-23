<?php
include 'config.php';
checkAuth();

$message = '';
$error = '';

// Get current user details
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch();

// Get current settings
$settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid form submission";
    } else {
        // Account Settings
        if (isset($_POST['update_account'])) {
            $fullName = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Please enter a valid email address.";
            } else {
                // Check if password change is requested
                if (!empty($currentPassword)) {
                    if (!password_verify($currentPassword, $user['password'])) {
                        $error = "Current password is incorrect.";
                    } elseif ($newPassword !== $confirmPassword) {
                        $error = "New passwords do not match.";
                    } elseif (strlen($newPassword) < 6) {
                        $error = "New password must be at least 6 characters long.";
                    } else {
                        // Update with new password
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ? WHERE id = ?");
                        if ($stmt->execute([$fullName, $email, $hashedPassword, $_SESSION['user_id']])) {
                            $message = "Account settings updated successfully!";
                            // Refresh user data
                            $userStmt->execute([$_SESSION['user_id']]);
                            $user = $userStmt->fetch();
                        } else {
                            $error = "Failed to update account settings.";
                        }
                    }
                } else {
                    // Update without password change
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                    if ($stmt->execute([$fullName, $email, $_SESSION['user_id']])) {
                        $message = "Account settings updated successfully!";
                        // Refresh user data
                        $userStmt->execute([$_SESSION['user_id']]);
                        $user = $userStmt->fetch();
                    } else {
                        $error = "Failed to update account settings.";
                    }
                }
            }
        }
        
        // System Settings
        if (isset($_POST['update_system'])) {
            $companyName = trim($_POST['company_name']);
            $warrantyAlertDays = intval($_POST['warranty_alert_days']);
            $itemsPerPage = intval($_POST['items_per_page']);
            
            // Update settings in database
            $settingsToUpdate = [
                'company_name' => $companyName,
                'warranty_alert_days' => $warrantyAlertDays,
                'items_per_page' => $itemsPerPage
            ];
            
            $success = true;
            foreach ($settingsToUpdate as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                if (!$stmt->execute([$key, $value, $value])) {
                    $success = false;
                }
            }
            
            if ($success) {
                $message = "System settings updated successfully!";
                // Refresh settings
                $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
                $settings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
            } else {
                $error = "Failed to update system settings.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Anime PC Warranty System</title>
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

        [data-theme="dark"] {
            --bg-color: #0f172a;
            --text-color: #f1f5f9;
            --card-bg: #1e293b;
            --border-color: #334155;
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: var(--bg-color);
            color: var(--text-color);
            font-weight: 400;
            transition: all 0.3s ease;
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
            background: var(--bg-color);
            min-height: 100vh;
        }
        
        .navbar-custom {
            background: var(--card-bg);
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            border-radius: 15px;
            padding: 15px 25px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }
        
        .settings-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .settings-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.12);
        }
        
        .settings-header {
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        
        .form-control, .form-select {
            border-radius: 12px;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            background: var(--card-bg);
            color: var(--text-color);
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
            background: var(--card-bg);
            color: var(--text-color);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 8px;
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
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .btn-outline-custom {
            border: 2px solid var(--primary);
            color: var(--primary);
            background: transparent;
            padding: 10px 25px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline-custom:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        
        .theme-toggle {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }
        
        .theme-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .theme-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
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
        
        .section-title {
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background: #ef4444; width: 25%; }
        .strength-fair { background: #f59e0b; width: 50%; }
        .strength-good { background: #10b981; width: 75%; }
        .strength-strong { background: #10b981; width: 100%; }
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
            <li><a href="settings.php" class="active"><i class="bi bi-gear"></i> <span>Settings</span></a></li>
            
            <li><a href="backup.php"><i class="bi bi-arrow-clockwise"></i> <span>Backup/Restore</span></a></li>
            <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></a></li>
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
                            <a class="nav-link active" href="#"><i class="bi bi-gear me-2"></i> Settings</a>
                        </li>
                    </ul>
                    <span class="navbar-text">
                        <i class="bi bi-person-circle me-2"></i> Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                    </span>
                </div>
            </div>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 fw-bold text-dark"><i class="bi bi-gear me-2"></i>System Settings</h1>
        </div>

        <!-- Account Settings -->
        <div class="settings-card">
            <div class="settings-header">
                <h4 class="fw-bold mb-0"><i class="bi bi-person-circle me-2"></i>Account Settings</h4>
                <p class="text-muted mb-0">Update your personal information and password</p>
            </div>
            
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
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                            <div class="form-text">Username cannot be changed</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Account Created</label>
                            <input type="text" class="form-control" value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" readonly>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <h5 class="fw-bold mb-3">Change Password</h5>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                            <div class="password-strength" id="passwordStrength"></div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end">
                    <button type="submit" name="update_account" class="btn btn-custom">
                        <i class="bi bi-check-circle me-2"></i>Update Account
                    </button>
                </div>
            </form>
        </div>

        <!-- System Settings -->
        <div class="settings-card">
            <div class="settings-header">
                <h4 class="fw-bold mb-0"><i class="bi bi-sliders me-2"></i>System Settings</h4>
                <p class="text-muted mb-0">Configure system-wide preferences</p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="company_name" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" 
                                   value="<?php echo htmlspecialchars($settings['company_name'] ?? 'Anime PC'); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="warranty_alert_days" class="form-label">Warranty Alert Days</label>
                            <input type="number" class="form-control" id="warranty_alert_days" name="warranty_alert_days" 
                                   value="<?php echo htmlspecialchars($settings['warranty_alert_days'] ?? '30'); ?>" min="1" max="365">
                            <div class="form-text">Number of days before expiry to show alerts</div>
                        </div>
                    </div>
                </div>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="items_per_page" class="form-label">Items Per Page</label>
                            <select class="form-select" id="items_per_page" name="items_per_page">
                                <option value="10" <?php echo ($settings['items_per_page'] ?? '10') == '10' ? 'selected' : ''; ?>>10 items</option>
                                <option value="25" <?php echo ($settings['items_per_page'] ?? '10') == '25' ? 'selected' : ''; ?>>25 items</option>
                                <option value="50" <?php echo ($settings['items_per_page'] ?? '10') == '50' ? 'selected' : ''; ?>>50 items</option>
                                <option value="100" <?php echo ($settings['items_per_page'] ?? '10') == '100' ? 'selected' : ''; ?>>100 items</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end">
                    <button type="submit" name="update_system" class="btn btn-custom">
                        <i class="bi bi-check-circle me-2"></i>Save System Settings
                    </button>
                </div>
            </form>
        </div>

        <!-- Danger Zone -->
        <div class="settings-card border-danger">
            <div class="settings-header border-danger">
                <h4 class="fw-bold mb-0 text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Danger Zone</h4>
                <p class="text-muted mb-0">Irreversible and destructive actions</p>
            </div>
            
            <div class="row g-4 align-items-center">
                <div class="col-md-8">
                    <h6 class="fw-bold mb-1">Delete All Data</h6>
                    <p class="text-muted mb-0">Permanently delete all products, clients, and settings. This action cannot be undone.</p>
                </div>
                
                <div class="col-md-4 text-end">
                    <button class="btn btn-outline-danger" id="deleteAllData">
                        <i class="bi bi-trash me-2"></i>Delete All Data
                    </button>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row g-4 align-items-center">
                <div class="col-md-8">
                    <h6 class="fw-bold mb-1">Export All Data</h6>
                    <p class="text-muted mb-0">Download a complete backup of all data in CSV format.</p>
                </div>
                
                <div class="col-md-4 text-end">
                    <a href="export_data.php" class="btn btn-outline-success">
                        <i class="bi bi-download me-2"></i>Export Data
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Theme Toggle Button -->
    <div class="theme-toggle">
        <button class="theme-btn" id="themeToggle">
            <i class="bi bi-moon"></i>
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const currentTheme = localStorage.getItem('theme') || 'light';
        
        // Apply saved theme
        document.documentElement.setAttribute('data-theme', currentTheme);
        updateThemeIcon();
        
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon();
        });
        
        function updateThemeIcon() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const icon = themeToggle.querySelector('i');
            
            if (currentTheme === 'dark') {
                icon.className = 'bi bi-sun';
            } else {
                icon.className = 'bi bi-moon';
            }
        }
        
        // Password Strength Indicator
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength';
            if (password.length === 0) {
                strengthBar.style.width = '0%';
            } else if (strength <= 2) {
                strengthBar.className += ' strength-weak';
            } else if (strength === 3) {
                strengthBar.className += ' strength-fair';
            } else if (strength === 4) {
                strengthBar.className += ' strength-good';
            } else {
                strengthBar.className += ' strength-strong';
            }
        });
        
        // Delete All Data Confirmation
        document.getElementById('deleteAllData').addEventListener('click', function() {
            Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete ALL data including products, clients, and settings. This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete everything!',
                cancelButtonText: 'Cancel',
                backdrop: true,
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'All data has been permanently deleted.',
                        icon: 'success',
                        confirmButtonColor: '#3085d6'
                    });
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
                confirmButtonColor: '#764ba2',
                timer: 3000
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