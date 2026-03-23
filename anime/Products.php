<?php
include 'config.php';
checkAuth();

$message = '';
$error = '';

// Handle form submission for both add and edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid form submission";
    } else {
        $productName = trim($_POST['product_name']);
        $serialNumber = trim($_POST['serial_number']);
       $category = trim($_POST['category']);

$category = trim($_POST['category']);

if ($category === 'Other' && !empty($_POST['custom_category'])) {
    $category = trim($_POST['custom_category']);
}

        $purchaseDate = $_POST['purchase_date'];
        $warrantyPeriod = intval($_POST['warranty_period']);
        $clientId = !empty($_POST['client_id']) ? $_POST['client_id'] : null;
        $notes = trim($_POST['notes']);
        
        // Calculate warranty end date
        $warrantyEndDate = date('Y-m-d', strtotime($purchaseDate . " + $warrantyPeriod months"));
        
        // Handle file upload
        $imagePath = null;
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['product_image']);
            if ($uploadResult['success']) {
                $imagePath = $uploadResult['file_path'];
            } else {
                $error = $uploadResult['error'];
            }
        }
        
        if (!$error) {
            // Check if we're updating or inserting
            if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
                // Update existing product
                $productId = $_POST['product_id'];
                
                // Check if serial number already exists (excluding current product)
                $checkStmt = $pdo->prepare("SELECT id FROM products WHERE serial_number = ? AND id != ?");
                $checkStmt->execute([$serialNumber, $productId]);
                
                if ($checkStmt->rowCount() > 0) {
                    $error = "Serial number already exists in the system.";
                } else {
                    // Update product
                    if ($imagePath) {
                        // If new image uploaded, update with image
                        $stmt = $pdo->prepare("UPDATE products SET product_name = ?, serial_number = ?, category = ?, purchase_date = ?, warranty_period = ?, warranty_end_date = ?, client_id = ?, image_path = ?, notes = ? WHERE id = ?");
                        $result = $stmt->execute([$productName, $serialNumber, $category, $purchaseDate, $warrantyPeriod, $warrantyEndDate, $clientId, $imagePath, $notes, $productId]);
                    } else {
                        // Keep existing image
                        $stmt = $pdo->prepare("UPDATE products SET product_name = ?, serial_number = ?, category = ?, purchase_date = ?, warranty_period = ?, warranty_end_date = ?, client_id = ?, notes = ? WHERE id = ?");
                        $result = $stmt->execute([$productName, $serialNumber, $category, $purchaseDate, $warrantyPeriod, $warrantyEndDate, $clientId, $notes, $productId]);
                    }
                    
                    if ($result) {
                        $message = "Product updated successfully!";
                        // Redirect to clear form
                        header("Location: products.php?message=" . urlencode($message));
                        exit();
                    } else {
                        $error = "Error updating product. Please try again.";
                    }
                }
            } else {
                // Insert new product
                $checkStmt = $pdo->prepare("SELECT id FROM products WHERE serial_number = ?");
                $checkStmt->execute([$serialNumber]);
                
                if ($checkStmt->rowCount() > 0) {
                    $error = "Serial number already exists in the system.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO products (product_name, serial_number, category, purchase_date, warranty_period, warranty_end_date, client_id, image_path, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    if ($stmt->execute([$productName, $serialNumber, $category, $purchaseDate, $warrantyPeriod, $warrantyEndDate, $clientId, $imagePath, $notes])) {
                        $message = "Product added successfully!";
                        // Redirect to clear form
                        header("Location: products.php?message=" . urlencode($message));
                        exit();
                    } else {
                        $error = "Error adding product. Please try again.";
                    }
                }
            }
        }
    }
}

// Handle product deletion
// Handle product deletion
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];

    // Fetch product data before deletion
    $fetchStmt = $pdo->prepare("
        SELECT p.*, c.client_name 
        FROM products p 
        LEFT JOIN clients c ON p.client_id = c.id
        WHERE p.id = ?
    ");
    $fetchStmt->execute([$deleteId]);
    $productData = $fetchStmt->fetch();

    if ($productData) {
        
        // Insert into delete_history
        $logStmt = $pdo->prepare("
            INSERT INTO delete_history 
            (product_id, product_name, serial_number, category, client_name, deleted_by) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $logStmt->execute([
            $productData['id'],
            $productData['product_name'],
            $productData['serial_number'],
            $productData['category'],
            $productData['client_name'] ?? 'N/A',
            $_SESSION['username']   // user who deleted
        ]);

        // Now delete product
        $deleteStmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        if ($deleteStmt->execute([$deleteId])) {
            $message = "Product deleted and logged in history!";
            header("Location: products.php?message=" . urlencode($message));
            exit();
        } else {
            $error = "Error deleting product.";
        }
    } else {
        $error = "Product not found.";
    }
}


// Get message from URL parameter
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Get product for editing
$editProduct = null;
if (isset($_GET['edit_id'])) {
    $editStmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $editStmt->execute([$_GET['edit_id']]);
    $editProduct = $editStmt->fetch();
}

// Get all products for display
$search = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';

$query = "SELECT p.*, c.client_name FROM products p LEFT JOIN clients c ON p.client_id = c.id WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (p.product_name LIKE ? OR p.serial_number LIKE ? OR c.client_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($statusFilter)) {
    if ($statusFilter == 'active') {
        $query .= " AND p.warranty_end_date >= CURDATE() AND p.status = 'active'";
    } elseif ($statusFilter == 'expired') {
        $query .= " AND (p.warranty_end_date < CURDATE() OR p.status = 'expired')";
    } elseif ($statusFilter == 'expiring_soon') {
        $query .= " AND p.warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND p.status = 'active'";
    }
}

if (!empty($categoryFilter)) {
    $query .= " AND p.category = ?";
    $params[] = $categoryFilter;
}

$query .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get clients for dropdown
$clients = $pdo->query("SELECT id, client_name FROM clients ORDER BY client_name")->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Anime PC Warranty System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        <link rel="stylesheet" href="prod.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
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
            <li><a href="products.php" class="active"><i class="bi bi-plus-circle"></i> <span>Add Record</span></a></li>
            <li><a href="clients.php"><i class="bi bi-people"></i> <span>Clients</span></a></li>
            <li><a href="warranty.php"><i class="bi bi-search"></i> <span>Check Warranty</span></a></li>
                       <li><a href="history.php"><i class="bi bi-trash me-2  "></i> <span>History</span></a></li>
            <li><a href="reports.php"><i class="bi bi-graph-up"></i> <span>Reports</span></a></li>

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
                            <a class="nav-link active" href="#"><i class="bi bi-plus-circle me-2"></i> Record</a>
                        </li>
                    </ul>
                    <span class="navbar-text">
                        <i class="bi bi-person-circle me-2"></i> Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                    </span>
                </div>
            </div>
        </nav>

        <h1 class="h3 fw-bold text-dark mb-4">
            <i class="bi <?php echo $editProduct ? 'bi-pencil' : 'bi-plus-circle'; ?> me-2"></i>
            <?php echo $editProduct ? 'Edit Product' : 'Add  Record'; ?>
        </h1>

        <!-- Add/Edit Product Form -->
        <div class="form-card">
            <div class="form-header">
                <h4 class="mb-2">
                    <i class="bi <?php echo $editProduct ? 'bi-pencil' : 'bi-plus-lg'; ?> me-2"></i>
                    <?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?>
                </h4>
                <p class="mb-0 opacity-75">
                    <?php echo $editProduct ? 'Update product information and warranty details' : 'Add a new product to the warranty system'; ?>
                </p>
            </div>
            <div class="form-body">
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
                
                <form id="productForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <?php if ($editProduct): ?>
                        <input type="hidden" name="product_id" value="<?php echo $editProduct['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-section">
                        <h5 class="section-title"><i class="bi bi-info-circle"></i> Basic Information</h5>
                        <div class="form-grid">
                            <div>
                                <label for="product_name" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" id="product_name" name="product_name" 
                                       value="<?php echo $editProduct ? htmlspecialchars($editProduct['product_name']) : ''; ?>" required>
                                <div class="invalid-feedback" id="productNameFeedback"></div>
                            </div>
                            
                            <div>
                                <label for="serial_number" class="form-label">Serial Number *</label>
                                <input type="text" class="form-control" id="serial_number" name="serial_number" 
                                       value="<?php echo $editProduct ? htmlspecialchars($editProduct['serial_number']) : ''; ?>" required>
                                <div class="invalid-feedback" id="serialNumberFeedback"></div>
                            </div>
                            
                            <div>
                                <label for="category" class="form-label">Category</label>
                             <select class="form-select" id="category" name="category" required>
    <option value="">Select Category</option>

    <?php foreach ($categories as $cat): ?>
        <option value="<?php echo htmlspecialchars($cat['category']); ?>"
            <?php echo $editProduct && $editProduct['category'] == $cat['category'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($cat['category']); ?>
        </option>
    <?php endforeach; ?>

 <option value="Other" style="background-color: green; color: white;">
    + Add New Category
  </option>

</select>  

<input type="text"
       name="custom_category"
       id="custom_category"
       class="form-control mt-2 d-none"
       placeholder="Enter new category">

                            </div>
                            
                           <div>
    <label for="client_id" class="form-label">Client</label>

    <div class="d-flex gap-2">
        <select class="form-select" id="client_id" name="client_id">
            <option value="">Select Client</option>
            <?php foreach ($clients as $client): ?>
                <option value="<?php echo $client['id']; ?>"
                    <?php echo $editProduct && $editProduct['client_id'] == $client['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($client['client_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- BUTTON -->
        <a href="clients.php" class="btn btn-outline-primary d-flex align-items-center justify-content-center">
    <i class="bi bi-person-plus"></i>
</a>

    </div>

    <small class="text-muted">
        Click + to add/view clients
    </small>
</div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h5 class="section-title"><i class="bi bi-calendar-check"></i> Warranty Information</h5>
                        <div class="form-grid">
                            <div>
                                <label for="purchase_date" class="form-label">Purchase Date *</label>
                               <input type="date" class="form-control" id="purchase_date" name="purchase_date"
       value="<?php echo date('Y-m-d'); ?>"
       max="<?php echo date('Y-m-d'); ?>" 
       min="<?php echo date('Y-m-d'); ?>"
       required>

                                <div class="invalid-feedback" id="purchaseDateFeedback"></div>
                            </div>
                            
                            <div>
                                <label for="warranty_period" class="form-label">Warranty Period (months) *</label>
                                <input type="number" class="form-control" id="warranty_period" name="warranty_period" 
                                       value="<?php echo $editProduct ? $editProduct['warranty_period'] : ''; ?>" min="1" required>
                                <div class="invalid-feedback" id="warrantyPeriodFeedback"></div>
                            </div>
                            
                            <?php if ($editProduct): ?>
                            <div>
                                <label class="form-label">Warranty End Date</label>
                                <input type="text" class="form-control" value="<?php echo date('M j, Y', strtotime($editProduct['warranty_end_date'])); ?>" readonly style="background-color: #f8f9fa;">
                                <small class="text-muted">Calculated automatically</small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h5 class="section-title"><i class="bi bi-image"></i> Product Image</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="product_image" class="form-label">Upload Product Image</label>
                                <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*">
                                <div class="form-text">Supported formats: JPG, PNG, GIF (Max: 5MB)</div>
                                <div class="invalid-feedback" id="productImageFeedback"></div>
                            </div>
                            <div class="col-md-6 text-center">
                                <?php if ($editProduct && $editProduct['image_path']): ?>
                                    <img src="<?php echo $editProduct['image_path']; ?>" class="image-preview" id="imagePreview" style="display: block;" alt="Current product image">
                                    <small class="text-muted">Current image</small>
                                <?php else: ?>
                                    <img id="imagePreview" class="image-preview" src="#" alt="Image preview">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h5 class="section-title"><i class="bi bi-chat-text"></i> Additional Information</h5>
                        <div>
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control form-textarea" id="notes" name="notes" rows="4" 
                                      placeholder="Add any additional notes about the product..."><?php echo $editProduct ? htmlspecialchars($editProduct['notes']) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between pt-3">
                        <?php if ($editProduct): ?>
                            <a href="products.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Cancel Edit
                            </a>
                        <?php else: ?>
                            <div></div>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-custom">
                            <i class="bi <?php echo $editProduct ? 'bi-check-circle' : 'bi-plus-circle'; ?> me-2"></i>
                            <?php echo $editProduct ? 'Update Product' : 'Add Product'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="search-box">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="bi bi-filter me-2"></i>Search & Filter Products</h5>
                <div>
                    <a href="generate_pdf.php" class="btn btn-success btn-sm">
                        <i class="bi bi-file-pdf me-2"></i>Export PDF
                    </a>
                </div>
            </div>
            <form method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Search Products</label>
                        <input type="text" class="form-control" name="search" placeholder="Search by name, serial, or client..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $categoryFilter == $cat['category'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $statusFilter == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="expiring_soon" <?php echo $statusFilter == 'expiring_soon' ? 'selected' : ''; ?>>Expiring Soon</option>
                            <option value="expired" <?php echo $statusFilter == 'expired' ? 'selected' : ''; ?>>Expired</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel me-2"></i>Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Product List -->
        <div class="product-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Serial Number</th>
                            <th>Category</th>
                            <th>Purchase Date</th>
                            <th>Warranty End</th>
                            <th>Status</th>
                            <th>Client</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($products) > 0): ?>
                            <?php foreach ($products as $product): 
                                $warrantyStatus = getWarrantyStatus($product['warranty_end_date'], $product['status']);
                                $imageSrc = $product['image_path'] ? $product['image_path'] : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHZpZXdCb3g9IjAgMCA4MCA4MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iODAiIGhlaWdodD0iODAiIHJ4PSIxMiIgZmlsbD0iI2YxZjVmOSIvPjxwYXRoIGQ9Ik00Ni42NjY3IDMzLjMzMzNDNDYuNjY2NyAzNS41NTIzIDQ0Ljg3ODkgMzcuMzMzMyA0Mi42NjY3IDM3LjMzMzNDNDAuNDU0NSAzNy4zMzMzIDM4LjY2NjcgMzUuNTUyMyAzOC42NjY3IDMzLjMzMzNDMzguNjY2NyAzMS4xMTQ0IDQwLjQ1NDUgMjkuMzMzMyA0Mi42NjY3IDI5LjMzMzNDNDQuODc4OSAyOS4zMzMzIDQ2LjY2NjcgMzEuMTE0NCA0Ni42NjY3IDMzLjMzMzNaIiBmaWxsPSIjOTRBMUFFIi8+PHBhdGggZD0iTTUwLjY2NjcgNDUuMzMzM0gyOS4zMzMzQzI4LjU5NyA0NS4zMzMzIDI4IDQ1LjkyOTMgMjggNDYuNjY2N0MyOCA0Ny40MDQgMjguNTk3IDQ4IDI5LjMzMzMgNDhINTAuNjY2N0M1MS40MDMgNDggNTIgNDcuNDA0IDUyIDQ2LjY2NjdDNTIgNDUuOTI5MyA1MS40MDMgNDUuMzMzMyA1MC42NjY3IDQ1LjMzMzNaIiBmaWxsPSIjOTRBMUFFIi8+PHBhdGggZD0iTTUwLjY2NjcgNDAuNjY2N0gyOS4zMzMzQzI4LjU5NyA0MC42NjY3IDI4IDQxLjI2MjcgMjggNDJDMjggNDIuNzM3MyAyOC41OTcgNDMuMzMzMyAyOS4zMzMzIDQzLjMzMzNINTAuNjY2N0M1MS40MDMgNDMuMzMzMyA1MiA0Mi43MzczIDUyIDQyQzUyIDQxLjI2MjcgNTEuNDAzIDQwLjY2NjcgNTAuNjY2NyA0MC42NjY3WiIgZmlsbD0iIzk0QTBBRSIvPjwvc3ZnPg==';
                            ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo $imageSrc; ?>" class="product-image" alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                             data-bs-toggle="modal" data-bs-target="#imageModal" 
                                             onclick="showImageModal('<?php echo $imageSrc; ?>', '<?php echo htmlspecialchars($product['product_name']); ?>')">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                        <?php if ($product['notes']): ?>
                                            <br><small class="text-muted"><?php echo substr(htmlspecialchars($product['notes']), 0, 50); ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><code class="bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($product['serial_number']); ?></code></td>
                                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($product['purchase_date'])); ?></td>
                                    <td>
                                        <strong><?php echo date('M j, Y', strtotime($product['warranty_end_date'])); ?></strong>
                                        <?php 
                                            $daysLeft = floor((strtotime($product['warranty_end_date']) - time()) / (60 * 60 * 24));
                                            if ($warrantyStatus['status'] == 'expiring_soon'): 
                                        ?>
                                            <br><small class="text-warning">(<?php echo $daysLeft; ?> days left)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="warranty-badge bg-<?php echo $warrantyStatus['class']; ?>">
                                            <?php echo $warrantyStatus['text']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['client_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="products.php?edit_id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger delete-product" 
                                                    data-id="<?php echo $product['id']; ?>" 
                                                    data-name="<?php echo htmlspecialchars($product['product_name']); ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <i class="bi bi-inbox display-1 text-muted mb-3"></i>
                                    <h4 class="text-muted">No products found</h4>
                                    <p class="text-muted">Add your first product using the form above.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalTitle">Product Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="image-modal-img" alt="Product Image">
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
        // Image preview
        document.getElementById('product_image').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        // Image modal
        function showImageModal(imageSrc, productName) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('imageModalTitle').textContent = productName;
        }

        // SweetAlert for messages
        <?php if ($message && !isset($_GET['edit_id'])): ?>
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

        // Form validation
        document.getElementById('productForm').addEventListener('submit', function(event) {
            let productName = document.getElementById('product_name');
            let serialNumber = document.getElementById('serial_number');
            let purchaseDate = document.getElementById('purchase_date');
            let warrantyPeriod = document.getElementById('warranty_period');
            let valid = true;

            [productName, serialNumber, purchaseDate, warrantyPeriod].forEach(field => {
                field.classList.remove('is-invalid');
            });

            if (productName.value.trim() === '') {
                productName.classList.add('is-invalid');
                document.getElementById('productNameFeedback').textContent = 'Product name is required.';
                valid = false;
            }

            if (serialNumber.value.trim() === '') {
                serialNumber.classList.add('is-invalid');
                document.getElementById('serialNumberFeedback').textContent = 'Serial number is required.';
                valid = false;
            }

            if (purchaseDate.value === '') {
                purchaseDate.classList.add('is-invalid');
                document.getElementById('purchaseDateFeedback').textContent = 'Purchase date is required.';
                valid = false;
            }

            if (warrantyPeriod.value === '' || warrantyPeriod.value < 1) {
                warrantyPeriod.classList.add('is-invalid');
                document.getElementById('warrantyPeriodFeedback').textContent = 'Valid warranty period is required.';
                valid = false;
            }

            if (!valid) {
                event.preventDefault();
            }
        });

        // Delete product with SweetAlert confirmation
        document.querySelectorAll('.delete-product').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                const productName = this.getAttribute('data-name');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: `You are about to delete "${productName}". This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `products.php?delete_id=${productId}`;
                    }
                });
            });
        });

        // Auto-calculate warranty end date when purchase date or warranty period changes
        document.getElementById('purchase_date').addEventListener('change', calculateWarrantyEnd);
        document.getElementById('warranty_period').addEventListener('input', calculateWarrantyEnd);

        function calculateWarrantyEnd() {
            const purchaseDate = document.getElementById('purchase_date').value;
            const warrantyPeriod = document.getElementById('warranty_period').value;
            
            if (purchaseDate && warrantyPeriod) {
                const purchase = new Date(purchaseDate);
                const endDate = new Date(purchase);
                endDate.setMonth(purchase.getMonth() + parseInt(warrantyPeriod));
                
                // Format date as YYYY-MM-DD for display
                const formattedDate = endDate.toISOString().split('T')[0];
                
                // You can display this somewhere if needed
                console.log('Warranty ends:', formattedDate);
            }
        }
    </script>
    <script>
const form = document.getElementById('productForm');
const storageKey = 'productFormData';

// SAVE data habang nag-iinput
form.addEventListener('input', () => {
    const formData = new FormData(form);
    const data = {};

    formData.forEach((value, key) => {
        // file upload hindi sinasama
        if (key !== 'product_image') {
            data[key] = value;
        }
    });

    localStorage.setItem(storageKey, JSON.stringify(data));
});

// RESTORE data pag balik sa page
document.addEventListener('DOMContentLoaded', () => {
    const savedData = localStorage.getItem(storageKey);
    if (!savedData) return;

    const data = JSON.parse(savedData);
    Object.keys(data).forEach(key => {
        const field = form.querySelector(`[name="${key}"]`);
        if (field) {
            field.value = data[key];
        }
    });
});

// CLEAR data kapag successful submit
form.addEventListener('submit', () => {
    localStorage.removeItem(storageKey);
});
</script>
<script>
const category = document.getElementById('category');
const custom = document.getElementById('custom_category');

category.addEventListener('change', () => {
    if (category.value === 'Other') {
        custom.classList.remove('d-none');
        custom.required = true;
    } else {
        custom.classList.add('d-none');
        custom.required = false;
        custom.value = '';
    }
});
</script>


</body>
</html>