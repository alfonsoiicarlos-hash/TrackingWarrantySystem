<?php

include 'config.php';
checkAuth();

$history = $pdo->query("SELECT * FROM delete_history ORDER BY deleted_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="his.css">
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
            <li><a href="warranty.php"><i class="bi bi-search"></i> <span>Check Warranty</span></a></li>
            <li><a href="products.php" class="active"><i class="bi bi-trash me-2"></i> <span>History</span></a></li>
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
                        <a class="nav-link active" href="#"><i class="bi bi-trash me-2"></i> Deleted History</a>
                    </li>
                </ul>
                <span class="navbar-text">
                    <i class="bi bi-person-circle me-2"></i> Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                </span>
            </div>
        </div>
    </nav>

    <div class="product-table p-4">

        <!-- ✅ SEARCH BAR DITO -->
        <div class="d-flex justify-content-end mb-3">
            <input type="text" id="searchInput" class="form-control w-25" placeholder="Search...">
        </div>

        <?php if (empty($history)): ?>
            <div class="empty-state">
                <img src="assets/img/empty.png" width="180" class="mb-3">
                <h5>No deleted product logs yet</h5>
                <p class="text-muted">All deleted products will appear here.</p>
            </div>
        <?php else: ?>

        <div class="table-responsive mt-4">
            <table class="table table-striped">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Serial</th>
                        <th>Category</th>
                        <th>Client</th>
                        <th>Deleted By</th>
                        <th>Deleted At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?= $row['product_id'] ?></td>
                            <td><?= htmlspecialchars($row['product_name']) ?></td>
                            <td><?= htmlspecialchars($row['serial_number']) ?></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td><?= htmlspecialchars($row['client_name']) ?></td>
                            <td><span class="badge bg-danger"><?= htmlspecialchars($row['deleted_by']) ?></span></td>
                            <td><?= date("M d, Y h:i A", strtotime($row['deleted_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php endif; ?>

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
<!-- ✅ JAVASCRIPT SEARCH FUNCTION -->
<script>
document.getElementById("searchInput").addEventListener("keyup", function () {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll("table tbody tr");

    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
});
</script>

</body>
</html>
