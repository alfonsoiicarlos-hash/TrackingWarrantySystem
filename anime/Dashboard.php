<?php
include 'config.php';
checkAuth();

// Get statistics
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$activeWarranty = $pdo->query("SELECT COUNT(*) FROM products WHERE warranty_end_date >= CURDATE() AND status = 'active'")->fetchColumn();
$expiredWarranty = $pdo->query("SELECT COUNT(*) FROM products WHERE warranty_end_date < CURDATE() OR status = 'expired'")->fetchColumn();
$expiringSoon = $pdo->query("SELECT COUNT(*) FROM products WHERE warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status = 'active'")->fetchColumn();
$totalClients = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();

// Get expiring products for alert
$expiringProducts = $pdo->query("SELECT product_name, warranty_end_date FROM products WHERE warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status = 'active' LIMIT 5")->fetchAll();

// Get recent products
$recentProducts = $pdo->query("
    SELECT p.*, c.client_name 
    FROM products p 
    LEFT JOIN clients c ON p.client_id = c.id 
    ORDER BY p.created_at DESC LIMIT 5
")->fetchAll();

// Get warranty status data for chart
$warrantyStatusData = $pdo->query("
    SELECT 
        SUM(CASE WHEN warranty_end_date >= CURDATE() AND status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN warranty_end_date < CURDATE() OR status = 'expired' THEN 1 ELSE 0 END) as expired,
        SUM(CASE WHEN warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status = 'active' THEN 1 ELSE 0 END) as expiring_soon
    FROM products
")->fetch();

// Get category distribution for chart
$categoryData = $pdo->query("
    SELECT category, COUNT(*) as count 
    FROM products 
    WHERE category IS NOT NULL AND category != ''
    GROUP BY category
")->fetchAll();

// Prepare data for charts
$categoryLabels = [];
$categoryCounts = [];
foreach($categoryData as $category) {
    $categoryLabels[] = $category['category'] ?: 'Uncategorized';
    $categoryCounts[] = $category['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Anime PC Warranty System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="dash.css">
</head>
<body class="no-navigation">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h4 class="mb-0"><i class="bi bi-pc-display-horizontal"></i> <span>Anime PC</span></h4>
            <small class="opacity-75">Warranty System</small>
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php" class="active"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a></li>
            <li><a href="products.php"><i class="bi bi-plus-circle"></i> <span>Add Record</span></a></li>
            <li><a href="clients.php"><i class="bi bi-people"></i> <span>Clients</span></a></li>
            <li><a href="warranty.php"><i class="bi bi-search"></i> <span>Check Warranty</span></a></li>
                            <li><a href="history.php"><i class="bi bi-trash me-2  "></i> <span>History</span></a></li>
            <li><a href="reports.php"><i class="bi bi-graph-up"></i> <span>Reports</span></a></li>

            <li><a href="backup.php"><i class="bi bi-arrow-clockwise"></i> <span>Backup/Restore</span></a></li>
            <li><a class="logout-link" onclick="confirmLogout()"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></a></li>
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
                            <a class="nav-link active" href="#"><i class="bi bi-house-door me-2"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php"><i class="bi bi-plus-circle me-2"></i> Products</a>
                        </li>
                    </ul>
                    <span class="navbar-text">
                        <i class="bi bi-person-circle me-2"></i> Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                    </span>
                </div>
            </div>
        </nav>

        <!-- Expiring Warranty Alert -->
        <?php if (count($expiringProducts) > 0): ?>
        <div class="alert-expiring">
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <div class="d-flex">
                    <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
                    <div>
                        <h5 class="alert-heading mb-2">Warranty Expiring Soon!</h5>
                        <ul class="mb-2 ps-3">
                            <?php foreach ($expiringProducts as $product): 
                                $daysLeft = floor((strtotime($product['warranty_end_date']) - time()) / (60 * 60 * 24));
                            ?>
                                <li>
                                    <strong><?php echo htmlspecialchars($product['product_name']); ?></strong> 
                                    (Expires: <?php echo date('M j', strtotime($product['warranty_end_date'])); ?> - <?php echo $daysLeft; ?> days)
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="fw-bold mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>! 👋</h2>
                    <p class="mb-0 opacity-75">Here's what's happening with your warranties today.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="fs-1"><?php echo date('M j, Y'); ?></div>
                    <div class="opacity-75"><?php echo date('l'); ?></div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card primary">
                    <i class="bi bi-pc-display"></i>
                    <h3><?php echo $totalProducts; ?></h3>
                    <p>Total Products</p>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card success">
                    <i class="bi bi-shield-check"></i>
                    <h3><?php echo $activeWarranty; ?></h3>
                    <p>Active Warranty</p>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card warning">
                    <i class="bi bi-clock-history"></i>
                    <h3><?php echo $expiringSoon; ?></h3>
                    <p>Expiring Soon</p>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card danger">
                    <i class="bi bi-shield-x"></i>
                    <h3><?php echo $expiredWarranty; ?></h3>
                    <p>Expired Warranty</p>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card info">
                    <i class="bi bi-people"></i>
                    <h3><?php echo $totalClients; ?></h3>
                    <p>Total Clients</p>
                </div>
            </div>
        </div>

        <!-- Charts and Recent Products -->
        <div class="row">
            <div class="col-lg-8">
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-pie-chart me-2"></i>Warranty Status</h5>
                            <div class="chart-wrapper">
                                <?php if ($warrantyStatusData['active'] + $warrantyStatusData['expired'] + $warrantyStatusData['expiring_soon'] > 0): ?>
                                    <canvas id="warrantyChart"></canvas>
                                <?php else: ?>
                                    <div class="no-data-chart">
                                        <i class="bi bi-pie-chart"></i>
                                        <p>No warranty data available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-grid-3x3-gap me-2"></i>Products by Category</h5>
                            <div class="chart-wrapper">
                                <?php if (count($categoryData) > 0): ?>
                                    <canvas id="categoryChart"></canvas>
                                <?php else: ?>
                                    <div class="no-data-chart">
                                        <i class="bi bi-bar-chart"></i>
                                        <p>No category data available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="chart-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2"></i>Recent Products</h5>
                        <a href="products.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <?php if (count($recentProducts) > 0): ?>
                        <div>
                            <?php foreach ($recentProducts as $product): 
                                $warrantyStatus = getWarrantyStatus($product['warranty_end_date'], $product['status']);
                            ?>
                                <div class="recent-product">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($product['product_name']); ?></h6>
                                            <p class="mb-1 text-muted small">SN: <?php echo htmlspecialchars($product['serial_number']); ?></p>
                                            <?php if ($product['client_name']): ?>
                                                <p class="mb-1 text-muted small">Client: <?php echo htmlspecialchars($product['client_name']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted"><?php echo date('M j', strtotime($product['created_at'])); ?></small>
                                            <div class="mt-1">
                                                <span class="badge bg-<?php echo $warrantyStatus['class']; ?>">
                                                    <?php echo $warrantyStatus['text']; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox display-6 text-muted mb-3"></i>
                            <p class="text-muted">No products added yet.</p>
                        </div>
                    <?php endif; ?>
                    
                </div>
                
            </div>
            
        </div>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Comprehensive browser navigation disabling
        function disableBrowserNavigation() {
            // Disable back button
            history.pushState(null, null, document.URL);
            window.addEventListener('popstate', function() {
                history.pushState(null, null, document.URL);
            });

            // Disable forward button
            history.pushState(null, null, document.URL);
            window.addEventListener('popstate', function() {
                history.pushState(null, null, document.URL);
            });

            // Prevent navigation through keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Disable Alt + Left Arrow (Back)
                if (e.altKey && e.keyCode === 37) {
                    e.preventDefault();
                    return false;
                }
                // Disable Alt + Right Arrow (Forward)
                if (e.altKey && e.keyCode === 39) {
                    e.preventDefault();
                    return false;
                }
                // Disable Backspace key navigation (except in input fields)
                if (e.keyCode === 8 && !['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
                    e.preventDefault();
                    return false;
                }
            });

            // Disable right-click context menu
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                return false;
            });

            // Prevent drag and drop
            document.addEventListener('dragstart', function(e) {
                e.preventDefault();
                return false;
            });

            // Disable text selection
            document.addEventListener('selectstart', function(e) {
                e.preventDefault();
                return false;
            });
        }

        // SweetAlert Logout Confirmation with enhanced navigation blocking
        function confirmLogout() {
            Swal.fire({
                title: 'Are you sure?',
                text: "You will be logged out of the system!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, logout!',
                cancelButtonText: 'Cancel',
                backdrop: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Enhanced navigation blocking during logout
                    disableBrowserNavigation();
                    
                

                    // Redirect after a brief delay to ensure navigation is blocked
                    setTimeout(() => {
                        window.location.href = 'logout.php';
                    }, 1500);
                }
            });
        }

        // Initialize navigation blocking when page loads
        document.addEventListener('DOMContentLoaded', function() {
            disableBrowserNavigation();
            
            // Additional protection: re-apply navigation blocking every 2 seconds
            setInterval(disableBrowserNavigation, 2000);

            // Block any attempt to navigate away
           
        });

        // Warranty Status Chart
        <?php if ($warrantyStatusData['active'] + $warrantyStatusData['expired'] + $warrantyStatusData['expiring_soon'] > 0): ?>
        const warrantyCtx = document.getElementById('warrantyChart').getContext('2d');
        const warrantyChart = new Chart(warrantyCtx, {
            type: 'doughnut',
            data: {
                labels: ['Active Warranty', 'Expiring Soon', 'Expired Warranty'],
                datasets: [{
                    data: [
                        <?php echo $warrantyStatusData['active']; ?>,
                        <?php echo $warrantyStatusData['expiring_soon']; ?>,
                        <?php echo $warrantyStatusData['expired']; ?>
                    ],
                    backgroundColor: [
                        '#10b981',
                        '#f59e0b',
                        '#ef4444'
                    ],
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                family: 'Poppins',
                                size: 12
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
        <?php endif; ?>

        // Category Chart
        <?php if (count($categoryData) > 0): ?>
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($categoryLabels); ?>,
                datasets: [{
                    label: 'Products',
                    data: <?php echo json_encode($categoryCounts); ?>,
                    backgroundColor: [
                        '#667eea', '#764ba2', '#f093fb', '#4facfe', '#00f2fe',
                        '#43e97b', '#38f9d7', '#ff5858', '#f5576c', '#ffecd2'
                    ],
                    borderWidth: 0,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                family: 'Poppins'
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Poppins'
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Auto-hide expiring alert after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.querySelector('.alert-expiring');
            if (alert) {
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 3000);
            }
        });
    </script>
    

</body>
</html>