<?php
include 'config.php';
checkAuth();

// Get statistics for reports
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$activeWarranty = $pdo->query("SELECT COUNT(*) FROM products WHERE warranty_end_date >= CURDATE() AND status = 'active'")->fetchColumn();
$expiredWarranty = $pdo->query("SELECT COUNT(*) FROM products WHERE warranty_end_date < CURDATE() OR status = 'expired'")->fetchColumn();
$expiringSoon = $pdo->query("SELECT COUNT(*) FROM products WHERE warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status = 'active'")->fetchColumn();
$totalClients = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();

// Get warranty status data
$warrantyStatusData = $pdo->query("
    SELECT 
        SUM(CASE WHEN warranty_end_date >= CURDATE() AND status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN warranty_end_date < CURDATE() OR status = 'expired' THEN 1 ELSE 0 END) as expired,
        SUM(CASE WHEN warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status = 'active' THEN 1 ELSE 0 END) as expiring_soon,
        SUM(CASE WHEN status = 'void' THEN 1 ELSE 0 END) as void
    FROM products
")->fetch();

// Get category distribution
$categoryData = $pdo->query("
    SELECT category, COUNT(*) as count 
    FROM products 
    WHERE category IS NOT NULL
    GROUP BY category 
    ORDER BY count DESC
")->fetchAll();

// Get monthly warranty expirations for the next 6 months
$monthlyExpirations = $pdo->query("
    SELECT 
        DATE_FORMAT(warranty_end_date, '%Y-%m') as month,
        COUNT(*) as count
    FROM products 
    WHERE warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 MONTH)
    AND status = 'active'
    GROUP BY DATE_FORMAT(warranty_end_date, '%Y-%m')
    ORDER BY month
")->fetchAll();

// Get products by client
$clientProducts = $pdo->query("
    SELECT c.client_name, COUNT(p.id) as product_count
    FROM clients c
    LEFT JOIN products p ON c.id = p.client_id
    GROUP BY c.id, c.client_name
    HAVING product_count > 0
    ORDER BY product_count DESC
    LIMIT 10
")->fetchAll();

// Get expiring products for the table
$expiringProducts = $pdo->query("
    SELECT p.product_name, p.serial_number, p.warranty_end_date, c.client_name
    FROM products p
    LEFT JOIN clients c ON p.client_id = c.id
    WHERE p.warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND p.status = 'active'
    ORDER BY p.warranty_end_date ASC
    LIMIT 5
")->fetchAll();

// Get recent products for the table
$recentProducts = $pdo->query("
    SELECT p.product_name, p.category, p.created_at, p.warranty_end_date, p.status
    FROM products p
    ORDER BY p.created_at DESC
    LIMIT 5
")->fetchAll();

// Prepare data for charts
$months = [];
$expirationCounts = [];
foreach ($monthlyExpirations as $data) {
    $months[] = date('M Y', strtotime($data['month'] . '-01'));
    $expirationCounts[] = $data['count'];
}

$clientNames = array_column($clientProducts, 'client_name');
$clientCounts = array_column($clientProducts, 'product_count');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Anime PC Warranty System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            background: var(--light);
            font-weight: 400;
            color: var(--dark);
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
            background: var(--light);
            min-height: 100vh;
        }
        
        .navbar-custom {
            background: #fff;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            border-radius: 15px;
            padding: 15px 25px;
            margin-bottom: 30px;
        }
        
        /* Stat Cards */
        .stat-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            padding: 20px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .stat-card i {
            font-size: 2rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-card h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .stat-card p {
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
            margin: 0;
        }
        
        .stat-card .trend {
            display: flex;
            align-items: center;
            font-size: 0.8rem;
            margin-top: 8px;
        }
        
        .stat-card .trend.up {
            color: var(--success);
        }
        
        .stat-card .trend.down {
            color: var(--danger);
        }
        
        /* Minimal Chart Containers */
        .chart-container {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            padding: 20px;
            margin-bottom: 25px;
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .chart-container:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .chart-header {
            border-bottom: 1px solid var(--gray-light);
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        
        .chart-title {
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            font-size: 1rem;
        }
        
        .chart-title i {
            margin-right: 8px;
            color: var(--primary);
            font-size: 1.1rem;
        }
        
        /* Buttons */
        .btn-custom {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        /* Responsive */
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
        
        /* Section Styling */
        .report-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--gray-light);
            display: flex;
            align-items: center;
            font-size: 1.3rem;
        }
        
        .section-title i {
            margin-right: 10px;
            color: var(--primary);
        }
        
        /* Table Styling */
        .table-container {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            padding: 20px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }
        
        .table-container:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .table-title {
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            font-size: 1rem;
        }
        
        .table-title i {
            margin-right: 8px;
            color: var(--primary);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(99, 102, 241, 0.05);
        }
        
        /* Badge Styling */
        .badge {
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
        }
        
        /* Minimal Chart Sizes */
        .mini-chart {
            height: 200px !important;
        }
        
        .small-chart {
            height: 180px !important;
        }
        
        /* Compact layout */
        .compact-grid {
            margin-bottom: 10px;
        }
        
        /* Print Styles */
       @media print {

    @page {
        size: A4 portrait;
        margin: 8mm;
    }

    body {
        zoom: 0.70; /* critical: para magkasya charts + tables */
    }

    /* alisin UI */
    .sidebar,
    .navbar-custom,
    .btn,
    .btn-custom,
    .btn-outline-secondary {
        display: none !important;
    }

    .main-content {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    /* bawasan vertical spacing */
    .report-section {
        margin-bottom: 8px !important;
    }

    .section-title {
        margin-bottom: 8px !important;
        padding-bottom: 4px !important;
        font-size: 1.1rem !important;
    }

    /* charts */
    .chart-container {
        padding: 8px !important;
        margin-bottom: 8px !important;
    }

    .mini-chart {
        height: 120px !important;
    }

    /* tables */
    .table-container {
        padding: 8px !important;
        margin-bottom: 8px !important;
    }

    table {
        font-size: 11px !important;
    }

    /* IMPORTANT: bawal maputol sa page */
    .report-section,
    .chart-container,
    .table-container {
        page-break-inside: avoid !important;
    }
}

@media print {

    /* itago ang overview section */
    .print-hide {
        display: none !important;
    }

    /* existing print rules */
    .sidebar,
    .navbar-custom,
    .btn,
    .btn-custom {
        display: none !important;
    }

    body {
        zoom: 0.8;
    }

    .main-content {
        margin: 0 !important;
        padding: 0 !important;
    }
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
            <li><a href="reports.php" class="active"><i class="bi bi-graph-up"></i> <span>Reports</span></a></li>

            <li><a href="backup.php"><i class="bi bi-arrow-clockwise"></i> <span>Backup/Restore</span></a></li>
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
                            <a class="nav-link active" href="#"><i class="bi bi-graph-up me-2"></i> Reports</a>
                        </li>
                    </ul>
                    <span class="navbar-text">
                        <i class="bi bi-person-circle me-2"></i> Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                    </span>
                </div>
            </div>
        </nav>

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1"><i class="bi bi-graph-up me-2"></i>Analytics & Reports</h1>
                <p class="text-muted mb-0">Key metrics and warranty insights</p>
            </div>
            <div>
                <button class="btn btn-outline-secondary me-2" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i>Print
                </button>
                <a href="generate_pdf.php" class="btn btn-custom">
                    <i class="bi bi-file-pdf me-2"></i>Export PDF
                </a>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="report-section print-hide">

            <h2 class="section-title"><i class="bi bi-bar-chart me-2"></i>Overview</h2>
            <div class="row g-3 compact-grid">
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="stat-card">
                        <i class="bi bi-pc-display"></i>
                        <h3><?php echo $totalProducts; ?></h3>
                        <p>Total Products</p>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="stat-card">
                        <i class="bi bi-shield-check"></i>
                        <h3><?php echo $activeWarranty; ?></h3>
                        <p>Active</p>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="stat-card">
                        <i class="bi bi-clock-history"></i>
                        <h3><?php echo $expiringSoon; ?></h3>
                        <p>Expiring Soon</p>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="stat-card">
                        <i class="bi bi-shield-x"></i>
                        <h3><?php echo $expiredWarranty; ?></h3>
                        <p>Expired</p>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="stat-card">
                        <i class="bi bi-people"></i>
                        <h3><?php echo $totalClients; ?></h3>
                        <p>Total Clients</p>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="stat-card">
                        <i class="bi bi-percent"></i>
                        <h3><?php echo $totalProducts > 0 ? round(($activeWarranty / $totalProducts) * 100, 1) : 0; ?>%</h3>
                        <p>Active Rate</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Minimal Charts Section -->
        <div class="report-section">
            <h2 class="section-title"><i class="bi bi-pie-chart me-2"></i>Visual Summary</h2>
            <div class="row g-3">
                <!-- Warranty Status Chart -->
                <div class="col-lg-4">
                    <div class="chart-container">
                        <div class="chart-header">
                            <h5 class="chart-title"><i class="bi bi-pie-chart-fill me-2"></i>Warranty Status</h5>
                        </div>
                        <canvas id="warrantyChart" class="mini-chart"></canvas>
                    </div>
                </div>
                
                <!-- Category Distribution -->
                <div class="col-lg-4">
                    <div class="chart-container">
                        <div class="chart-header">
                            <h5 class="chart-title"><i class="bi bi-grid-3x3-gap me-2"></i>Top Categories</h5>
                        </div>
                        <canvas id="categoryChart" class="mini-chart"></canvas>
                    </div>
                </div>
                
                <!-- Monthly Expirations -->
                <div class="col-lg-4">
                    <div class="chart-container">
                        <div class="chart-header">
                            <h5 class="chart-title"><i class="bi bi-calendar-range me-2"></i>Expirations</h5>
                        </div>
                        <canvas id="expirationChart" class="mini-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Reports -->
        <div class="report-section">
            <h2 class="section-title"><i class="bi bi-table me-2"></i>Detailed Reports</h2>
            <div class="row g-3">
                <!-- Expiring Products -->
                <div class="col-lg-6">
                    <div class="table-container">
                        <div class="table-header">
                            <h5 class="table-title"><i class="bi bi-clock me-2"></i>Products Expiring Soon</h5>
                            <a href="products.php?status=expiring_soon" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Serial</th>
                                        <th>Expiry Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expiringProducts as $product): 
                                        $daysLeft = floor((strtotime($product['warranty_end_date']) - time()) / (60 * 60 * 24));
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                            <td><code><?php echo htmlspecialchars($product['serial_number']); ?></code></td>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($product['warranty_end_date'])); ?>
                                                <small class="text-warning d-block">(<?php echo $daysLeft; ?> days)</small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Products -->
                <div class="col-lg-6">
                    <div class="table-container">
                        <div class="table-header">
                            <h5 class="table-title"><i class="bi bi-plus-circle me-2"></i>Recent Products</h5>
                            <a href="products.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Added Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentProducts as $product): 
                                        $warrantyStatus = getWarrantyStatus($product['warranty_end_date'], $product['status']);
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $warrantyStatus['class']; ?>">
                                                    <?php echo $warrantyStatus['text']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
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
        // Warranty Status Chart - Minimal version
        const warrantyCtx = document.getElementById('warrantyChart').getContext('2d');
        const warrantyChart = new Chart(warrantyCtx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Expiring', 'Expired', 'Void'],
                datasets: [{
                    data: [
                        <?php echo $warrantyStatusData['active']; ?>,
                        <?php echo $warrantyStatusData['expiring_soon']; ?>,
                        <?php echo $warrantyStatusData['expired']; ?>,
                        <?php echo $warrantyStatusData['void']; ?>
                    ],
                    backgroundColor: [
                        '#10b981',
                        '#f59e0b',
                        '#ef4444',
                        '#6b7280'
                    ],
                    borderWidth: 2,
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
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                family: 'Poppins',
                                size: 10
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });

        // Category Chart - Minimal version
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        
        // For minimal version, let's show only top 5 categories
        const topCategories = <?php echo json_encode(array_slice($categoryData, 0, 5)); ?>;
        const categoryLabels = topCategories.map(item => item.category);
        const categoryCounts = topCategories.map(item => item.count);
        
        const categoryChart = new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: categoryLabels,
                datasets: [{
                    label: 'Products',
                    data: categoryCounts,
                    backgroundColor: '#6366f1',
                    borderWidth: 0,
                    borderRadius: 6
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
                                family: 'Poppins',
                                size: 10
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Poppins',
                                size: 9
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Expiration Chart - Minimal version
        const expirationCtx = document.getElementById('expirationChart').getContext('2d');
        const expirationChart = new Chart(expirationCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Expirations',
                    data: <?php echo json_encode($expirationCounts); ?>,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#f59e0b',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1,
                    pointRadius: 3
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
                                family: 'Poppins',
                                size: 10
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Poppins',
                                size: 9
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>