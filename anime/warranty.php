<?php
include 'config.php';
checkAuth();

$searchResult = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $searchType = $_POST['search_type'];
    $searchValue = trim($_POST['search_value']);
    
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid form submission";
    } else {
        if ($searchType == 'serial') {
            $stmt = $pdo->prepare("
                SELECT p.*, c.client_name, c.email, c.phone 
                FROM products p 
                LEFT JOIN clients c ON p.client_id = c.id 
                WHERE p.serial_number = ?
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT p.*, c.client_name, c.email, c.phone 
                FROM products p 
                LEFT JOIN clients c ON p.client_id = c.id 
                WHERE p.product_name LIKE ?
            ");
            $searchValue = "%$searchValue%";
        }
        
        $stmt->execute([$searchValue]);
        $searchResult = $stmt->fetchAll();
        
        if (empty($searchResult)) {
            $error = "No products found matching your search.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Warranty - Anime PC Warranty System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
   <link rel="stylesheet" href="war.css">

   <!-- PRINT STYLE -->
   <style>
      @media print {
    body * {
        visibility: hidden;
    }

    #printArea, #printArea * {
        visibility: visible;
    }

    #printArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        font-size: 11px;
        line-height: 1.3;
    }

    .print-header {
        border-bottom: 2px solid #000;
        padding-bottom: 8px;
    }

    .print-logo {
        width: 60px;
        height: auto;
    }

    table {
        width: 100%;
        table-layout: fixed;
    }

    th, td {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding: 4px;
        font-size: 11px;
    }

    .sidebar,
    .navbar,
    .btn,
    .search-card,
    .empty-state {
        display: none !important;
    }
}


    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">
            <h4 class="mb-0"><i class="bi bi-pc-display-horizontal"></i> <span>Anime PC</span></h4>
            <small class="opacity-75">Warranty System</small>
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a></li>
            <li><a href="products.php"><i class="bi bi-plus-circle"></i> <span>Add Record</span></a></li>
            <li><a href="clients.php"><i class="bi bi-people"></i> <span>Clients</span></a></li>
            <li><a href="warranty.php" class="active"><i class="bi bi-search"></i> <span>Check Warranty</span></a></li>
                    <li><a href="history.php"><i class="bi bi-trash me-2  "></i> <span>History</span></a></li>
            <li><a href="reports.php"><i class="bi bi-graph-up"></i> <span>Reports</span></a></li>
        
            <li><a href="backup.php"><i class="bi bi-arrow-clockwise"></i> <span>Backup/Restore</span></a></li>
          
              <li> <a href="Login.php" class="logout-btn" id="logoutBtn">
    <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
</a></li>
        </ul>
    </div>

  
    <div class="main-content">
       
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
                            <a class="nav-link active" href="#"><i class="bi bi-search me-2"></i> Check Warranty</a>
                        </li>
                    </ul>
                    <span class="navbar-text">
                        <i class="bi bi-person-circle me-2"></i> Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                    </span>
                </div>
            </div>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 fw-bold text-dark"><i class="bi bi-search me-2"></i>Check Warranty Status</h1>
        </div>

       
        <div class="card search-card mb-5">
            <div class="card-header card-header-custom">
                <h4 class="mb-0"><i class="bi bi-search-heart me-2"></i>Search Product Warranty</h4>
                <p class="mb-0 opacity-75">Enter product serial number or name to check warranty status</p>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form id="searchForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="row g-4">
                        <div class="col-md-3">
                            <label for="search_type" class="form-label info-label">Search By</label>
                            <select class="form-select" id="search_type" name="search_type" required>
                                <option value="serial">Serial Number</option>
                                <option value="name">Product Name</option>
                            </select>
                        </div>
                        
                        <div class="col-md-7">
                            <label for="search_value" class="form-label info-label" id="search_label">Serial Number</label>
                            <input type="text" class="form-control" id="search_value" name="search_value" placeholder="Enter serial number..." required>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-custom w-100">
                                <i class="bi bi-search me-2"></i>Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


        <?php if ($searchResult !== null): ?>

            <!-- PRINT BUTTON (DAGDAGAN) -->
            <button id="printBtn" class="btn btn-success mb-3">
                <i class="bi bi-printer"></i> Print
            </button>

            <!-- PRINT AREA (DAGDAGAN) -->
           <div id="printArea">

    <!-- PRINT HEADER -->
    <div class="print-header d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-2">
            <img src="logonila.png" class="print-logo" alt="Logo">
            <div>
                <div class="fw-bold">Anime Computer Services</div>
                <small>Customer Warranty Record</small>
            </div>
        </div>
        <div class="text-end">
            <small><?= date('F d, Y') ?></small>
        </div>
    </div>

    <hr>


                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="h5 fw-bold text-dark">Customer Warranty Record</h3>
                    <span class="badge bg-primary fs-6"><?php echo count($searchResult); ?> product(s) found</span>
                </div>
                
                <!-- TABLE PRINT FORMAT (DAGDAGAN) -->
                <table class="table table-bordered">
                    <thead>
                        <tr>
                             <th>Client</th>
                            <th>Product Name</th>
                            <th>Serial Number</th>
                         
                            <th>Purchase Date</th>
                            <th>Warranty End</th>
                            <th>Status</th>
                           
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($searchResult as $product): 
                            $warrantyStatus = getWarrantyStatus($product['warranty_end_date'], $product['status']);
                        ?>
                        <tr>
                              <td><?php echo htmlspecialchars($product['client_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['serial_number']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($product['purchase_date'])); ?></td>
                            <td><?php echo date('M j, Y', strtotime($product['warranty_end_date'])); ?></td>
                            <td><?php echo $warrantyStatus['text']; ?></td>
                          
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            </div> <!-- end printArea -->


            <!-- ORIGINAL CARD DISPLAY (WALA BINAGO) -->
            <?php foreach ($searchResult as $product): 
                $warrantyStatus = getWarrantyStatus($product['warranty_end_date'], $product['status']);
                $imageSrc = $product['image_path'] ? $product['image_path'] : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHZpZXdCb3g9IjAgMCA4MCA4MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iODAiIGhlaWdodD0iODAiIHJ4PSIxMiIgZmlsbD0iI2YxZjVmOSIvPjxwYXRoIGQ9Ik00Ni42NjY3IDMzLjMzMzNDNDYuNjY2NyAzNS41NTIzIDQ0Ljg3ODkgMzcuMzMzMyA0Mi42NjY3IDM3LjMzMzNDNDAuNDU0NSAzNy4zMzMzIDM4LjY2NjcgMzUuNTUyMyAzOC42NjY3IDMzLjMzMzNDMzguNjY2NyAzMS4xMTQ0IDQwLjQ1NDUgMjkuMzMzMyA0Mi42NjY3IDI5LjMzMzNDNDQuODc4OSAyOS4zMzMzIDQ2LjY2NjcgMzEuMTE0NCA0Ni42NjY3IDMzLjMzMzNaIiBmaWxsPSIjOTRBMUFFIi8+PHBhdGggZD0iTTUwLjY2NjcgNDUuMzMzM0gyOS4zMzMzQzI4LjU5NyA0NS4zMzMzIDI4IDQ1LjkyOTMgMjggNDYuNjY2N0MyOCA0Ny40MDQgMjguNTk3IDQ4IDI5LjMzMzMgNDhINTAuNjY2N0M1MS40MDMgNDggNTIgNDcuNDA0IDUyIDQ2LjY2NjdDNTIgNDUuOTI5MyA1MS40MDMgNDUuMzMzMyA1MC42NjY3IDQ1LjMzMzNaIiBmaWxsPSIjOTRBMUFFIi8+PHBhdGggZD0iTTUwLjY2NjcgNDAuNjY2N0gyOS4zMzMzQzI4LjU5NyA0MC42NjY3IDI4IDQxLjI2MjcgMjggNDJDMjggNDIuNzM3MyAyOC41OTcgNDMuMzMzMyAyOS4zMzMzIDQzLjMzMzNINTAuNjY2N0M1MS40MDMgNDMuMzMzMyA1MiA0Mi43MzczIDUyIDQyQzUyIDQxLjI2MjcgNTEuNDAzIDQwLjY2NjcgNTAuNjY2NyA0MC42NjY3WiIgZmlsbD0iIzk0QTBBRSIvPjwvc3ZnPg==';
            ?>
                <div class="card result-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <img src="<?php echo $imageSrc; ?>" class="product-image" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                            </div>
                            
                            <div class="col">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="info-label">Product Name</div>
                                        <div class="info-value fw-bold text-dark"><?php echo htmlspecialchars($product['product_name']); ?></div>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <div class="info-label">Serial Number</div>
                                        <div class="info-value font-monospace"><?php echo htmlspecialchars($product['serial_number']); ?></div>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <div class="info-label">Category</div>
                                        <div class="info-value"><?php echo htmlspecialchars($product['category']); ?></div>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <div class="info-label">Purchase Date</div>
                                        <div class="info-value"><?php echo date('M j, Y', strtotime($product['purchase_date'])); ?></div>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <div class="info-label">Warranty End</div>
                                        <div class="info-value fw-bold"><?php echo date('M j, Y', strtotime($product['warranty_end_date'])); ?></div>
                                    </div>
                                    
                                    <div class="col-md-1">
                                        <div class="info-label">Status</div>
                                        <span class="warranty-badge bg-<?php echo $warrantyStatus['class']; ?>">
                                            <?php echo $warrantyStatus['text']; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if ($product['client_name']): ?>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="info-label">Client Information</div>
                                        <div class="d-flex gap-4">
                                            <div class="info-value">
                                                <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($product['client_name']); ?>
                                            </div>
                                            <?php if ($product['email']): ?>
                                            <div class="info-value">
                                                <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($product['email']); ?>
                                            </div>
                                            <?php endif; ?>
                                            <?php if ($product['phone']): ?>
                                            <div class="info-value">
                                                <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($product['phone']); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($product['notes']): ?>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="info-label">Notes</div>
                                        <div class="info-value"><?php echo htmlspecialchars($product['notes']); ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($error)): ?>
            <div class="empty-state">
                <i class="bi bi-search"></i>
                <h4 class="text-muted mb-3">No Products Found</h4>
                <p class="text-muted">Try searching with different criteria</p>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-search-heart"></i>
                <h4 class="text-muted mb-3">Search for Products</h4>
                <p class="text-muted">Enter a serial number or product name above to check warranty status</p>
            </div>
        <?php endif; ?>
    </div>

<script>
    document.getElementById('logoutBtn').addEventListener('click', function (e) {
        e.preventDefault(); 

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

<!-- PRINT SCRIPT -->
<script>
    document.getElementById('printBtn').addEventListener('click', function(){
        window.print();
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('search_type').addEventListener('change', function() {
        const searchValue = document.getElementById('search_value');
        const searchLabel = document.getElementById('search_label');
        
        if (this.value === 'serial') {
            searchLabel.textContent = 'Serial Number';
            searchValue.placeholder = 'Enter serial number...';
        } else {
            searchLabel.textContent = 'Product Name';
            searchValue.placeholder = 'Enter product name...';
        }
    });

    document.getElementById('searchForm').addEventListener('submit', function(event) {
        const searchValue = document.getElementById('search_value');
        
        if (searchValue.value.trim() === '') {
            event.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Missing Information',
                text: 'Please enter a search value',
                confirmButtonColor: '#764ba2'
            });
        }
    });
</script>
</body>
</html>