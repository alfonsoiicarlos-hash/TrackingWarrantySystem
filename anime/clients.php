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
        $clientName = trim($_POST['client_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $company = trim($_POST['company']);
        
        // Validate email if provided
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            // Check if we're updating or inserting
            if (isset($_POST['client_id']) && !empty($_POST['client_id'])) {
                // Update existing client
                $clientId = $_POST['client_id'];
                
                // Check if client already exists (excluding current client)
                $checkStmt = $pdo->prepare("SELECT id FROM clients WHERE (client_name = ? OR email = ?) AND id != ?");
                $checkStmt->execute([$clientName, $email, $clientId]);
                
                if ($checkStmt->rowCount() > 0) {
                    $error = "Client with this name or email already exists.";
                } else {
                    // Update client
                    $stmt = $pdo->prepare("UPDATE clients SET client_name = ?, email = ?, phone = ?, address = ?, company = ? WHERE id = ?");
                    
                    if ($stmt->execute([$clientName, $email, $phone, $address, $company, $clientId])) {
                        $message = "Client updated successfully!";
                    } else {
                        $error = "Error updating client. Please try again.";
                    }
                }
            } else {
                // Insert new client
                $checkStmt = $pdo->prepare("SELECT id FROM clients WHERE client_name = ? OR email = ?");
                $checkStmt->execute([$clientName, $email]);
                
                if ($checkStmt->rowCount() > 0) {
                    $error = "Client with this name or email already exists.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO clients (client_name, email, phone, address, company) VALUES (?, ?, ?, ?, ?)");
                    
                    if ($stmt->execute([$clientName, $email, $phone, $address, $company])) {
                        $message = "Client added successfully!";
                    } else {
                        $error = "Error adding client. Please try again.";
                    }
                }
            }
        }
    }
}

// Handle client deletion
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    
    // Check if client has products
    $checkProducts = $pdo->prepare("SELECT COUNT(*) FROM products WHERE client_id = ?");
    $checkProducts->execute([$deleteId]);
    $productCount = $checkProducts->fetchColumn();
    
    if ($productCount > 0) {
        $error = "Cannot delete client. There are products associated with this client.";
    } else {
        $deleteStmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
        if ($deleteStmt->execute([$deleteId])) {
            $message = "Client deleted successfully!";
        } else {
            $error = "Error deleting client. Please try again.";
        }
    }
}

// Get client for editing
$editClient = null;
if (isset($_GET['edit_id'])) {
    $editStmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $editStmt->execute([$_GET['edit_id']]);
    $editClient = $editStmt->fetch();
}

// Get all clients
$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT c.*, COUNT(p.id) as product_count 
          FROM clients c 
          LEFT JOIN products p ON c.id = p.client_id 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (c.client_name LIKE ? OR c.email LIKE ? OR c.company LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$query .= " GROUP BY c.id ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$clients = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients - Anime PC Warranty System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
   <link rel="stylesheet" href="client.css">
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
            <li><a href="clients.php" class="active"><i class="bi bi-people"></i> <span>Clients</span></a></li>
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
                            <a class="nav-link active" href="#"><i class="bi bi-people me-2"></i> Clients</a>
                        </li>
                    </ul>
                    <span class="navbar-text">
                        <i class="bi bi-person-circle me-2"></i> Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                    </span>
                </div>
            </div>
        </nav>

        <h1 class="h3 fw-bold text-dark mb-4">
            <i class="bi <?php echo $editClient ? 'bi-pencil' : 'bi-people'; ?> me-2"></i>
            <?php echo $editClient ? 'Edit Client' : 'Client Management'; ?>
        </h1>

        <!-- Client Statistics -->
        <?php if (!$editClient): ?>
        <div class="client-stats">
            <?php
            $totalClients = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
            $clientsWithProducts = $pdo->query("SELECT COUNT(DISTINCT client_id) FROM products WHERE client_id IS NOT NULL")->fetchColumn();
            $recentClients = $pdo->query("SELECT COUNT(*) FROM clients WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
            ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalClients; ?></div>
                <div class="stat-label">Total Clients</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $clientsWithProducts; ?></div>
                <div class="stat-label">Clients with Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $recentClients; ?></div>
                <div class="stat-label">New Clients (30 days)</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Add/Edit Client Form -->
        <div class="form-card">
            <div class="form-header">
                <h4 class="mb-2">
                    <i class="bi <?php echo $editClient ? 'bi-pencil' : 'bi-person-plus'; ?> me-2"></i>
                    <?php echo $editClient ? 'Edit Client' : 'Add New Client'; ?>
                </h4>
                <p class="mb-0 opacity-75">
                    <?php echo $editClient ? 'Update client information and contact details' : 'Add a new client to the warranty system'; ?>
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
                
                <form id="clientForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <?php if ($editClient): ?>
                        <input type="hidden" name="client_id" value="<?php echo $editClient['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-section">
                        <h5 class="section-title"><i class="bi bi-person-badge"></i> Client Information</h5>
                        <div class="form-grid">
                            <div>
                                <label for="client_name" class="form-label">Client Name *</label>
                                <input type="text" class="form-control" id="client_name" name="client_name" 
                                       value="<?php echo $editClient ? htmlspecialchars($editClient['client_name']) : ''; ?>" required>
                                <div class="invalid-feedback" id="clientNameFeedback"></div>
                            </div>
                            
                            <div>
                                <label for="company" class="form-label">Company</label>
                                <input type="text" class="form-control" id="company" name="company" 
                                       value="<?php echo $editClient ? htmlspecialchars($editClient['company']) : ''; ?>" 
                                       placeholder="Optional company name">
                            </div>
                        </div>
                        
                    </div>
                    
                    <div class="form-section">
                        <h5 class="section-title"><i class="bi bi-telephone"></i> Contact Information</h5>
                        <div class="form-grid">
                            <div>
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo $editClient ? htmlspecialchars($editClient['email']) : ''; ?>" 
                                       placeholder="client@gmail.com">
                                <div class="invalid-feedback" id="emailFeedback"></div>
                            </div>
                            
                            <div>
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo $editClient ? htmlspecialchars($editClient['phone']) : ''; ?>" 
                                       placeholder="+63">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h5 class="section-title"><i class="bi bi-geo-alt"></i> Address Information</h5>
                        <div>
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control form-textarea" id="address" name="address" rows="4" 
                                      placeholder="Enter full address including street, city, state, and zip code..."><?php echo $editClient ? htmlspecialchars($editClient['address']) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between pt-3">
                        <?php if ($editClient): ?>
                            <a href="clients.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Cancel Edit
                            </a>
                        <?php else: ?>
                            <div></div>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-custom">
                            <i class="bi <?php echo $editClient ? 'bi-check-circle' : 'bi-person-plus'; ?> me-2"></i>
                            <?php echo $editClient ? 'Update Client' : 'Add Client'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="search-box">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="bi bi-search me-2"></i>Search Clients</h5>
                <div>
                    <span class="badge bg-primary"><?php echo count($clients); ?> clients found</span>
                </div>
            </div>
            <form method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-10">
                        <label class="form-label">Search by Name, Email, or Company</label>
                        <input type="text" class="form-control" name="search" placeholder="Enter client name, email, or company..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-2"></i>Search
                        </button>
                    </div>
                </div>
                <?php if (!empty($search)): ?>
                <div class="mt-3">
                    <a href="clients.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-clockwise me-2"></i>Clear Search
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Client List -->
        <div class="client-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Contact Info</th>
                            <th>Company</th>
                            <th>Products</th>
                            <th>Registered</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($clients) > 0): ?>
                            <?php foreach ($clients as $client): 
                                $initial = strtoupper(substr($client['client_name'], 0, 1));
                            ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="client-avatar">
                                                <?php echo $initial; ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($client['client_name']); ?></strong>
                                                <?php if ($client['address']): ?>
                                                    <br><small class="text-muted"><?php echo substr(htmlspecialchars($client['address']), 0, 30); ?>...</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($client['email']): ?>
                                            <div><i class="bi bi-envelope me-2 text-muted"></i><?php echo htmlspecialchars($client['email']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($client['phone']): ?>
                                            <div><i class="bi bi-telephone me-2 text-muted"></i><?php echo htmlspecialchars($client['phone']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($client['company'] ?: 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $client['product_count'] > 0 ? 'primary' : 'secondary'; ?>">
                                            <?php echo $client['product_count']; ?> products
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($client['created_at'])); ?></td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo $client['updated_at'] ? date('M j, Y', strtotime($client['updated_at'])) : 'Never'; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="clients.php?edit_id=<?php echo $client['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="print_client.php?id=<?php echo $client['id']; ?>" 
   class="btn btn-sm btn-outline-success" 
   target="_blank">
   <i class="bi bi-printer"></i>
</a>

                                            <button class="btn btn-sm btn-outline-danger delete-client" 
                                                    data-id="<?php echo $client['id']; ?>" 
                                                    data-name="<?php echo htmlspecialchars($client['client_name']); ?>"
                                                    data-products="<?php echo $client['product_count']; ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="bi bi-people display-1 text-muted mb-3"></i>
                                    <h4 class="text-muted">No clients found</h4>
                                    <p class="text-muted">
                                        <?php echo !empty($search) ? 'Try a different search term' : 'Add your first client using the form above'; ?>
                                    </p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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

<script>
    const phoneInput = document.getElementById('phone');

    phoneInput.addEventListener('input', function () {
        // prevent letters, allow numbers only
        this.value = this.value.replace(/[^0-9]/g, '');

        // limit to 11 digits
        if (this.value.length > 11) {
            this.value = this.value.slice(0, 11);
        }
    });
</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
        document.getElementById('clientForm').addEventListener('submit', function(event) {
            let clientName = document.getElementById('client_name');
            let email = document.getElementById('email');
            let valid = true;

            clientName.classList.remove('is-invalid');
            email.classList.remove('is-invalid');

            if (clientName.value.trim() === '') {
                clientName.classList.add('is-invalid');
                document.getElementById('clientNameFeedback').textContent = 'Client name is required.';
                valid = false;
            }

            if (email.value !== '' && !isValidEmail(email.value)) {
                email.classList.add('is-invalid');
                document.getElementById('emailFeedback').textContent = 'Please enter a valid email address.';
                valid = false;
            }

            if (!valid) {
                event.preventDefault();
            }
        });

        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        // Delete client with SweetAlert confirmation
        document.querySelectorAll('.delete-client').forEach(button => {
            button.addEventListener('click', function() {
                const clientId = this.getAttribute('data-id');
                const clientName = this.getAttribute('data-name');
                const productCount = parseInt(this.getAttribute('data-products'));
                
                let alertText = `You are about to delete "${clientName}".`;
                let icon = 'question';
                
                if (productCount > 0) {
                    alertText += ` This client has ${productCount} product(s) associated. Deleting will remove these associations.`;
                    icon = 'warning';
                }
                
                alertText += " This action cannot be undone.";
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: alertText,
                    icon: icon,
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `clients.php?delete_id=${clientId}`;
                    }
                });
            });
        });
    </script>
</body>
</html>