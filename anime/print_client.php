<?php
include 'config.php';
checkAuth();

/* VALIDATE ID */
if (!isset($_GET['id']) || empty($_GET['id'])) {
    exit('Invalid client ID');
}

$clientId = (int) $_GET['id'];

/* FETCH CLIENT */
$clientStmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$clientStmt->execute([$clientId]);
$client = $clientStmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    exit('Client not found');
}

/* FETCH PRODUCTS */
$productStmt = $pdo->prepare("
    SELECT product_name, serial_number
    FROM products
    WHERE client_id = ?
");
$productStmt->execute([$clientId]);
$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Client</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body { font-size: 14px; }

        .print-header {
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .logo {
            max-width: 100px;
        }

        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>

<body onload="window.print()">

<div class="container">

    <!-- HEADER -->
    <div class="row align-items-center print-header">
        <div class="col-4">
            <img src="logonila.png" class="img-fluid logo" alt="Logo">
        </div>
        <div class="col-8 text-end">
            <h5 class="fw-bold mb-0">Anime Computer Services</h5>
            <small class="text-muted">Client Purchase Record</small><br>
            <small class="text-muted"><?= date('F d, Y') ?></small>
        </div>
    </div>

    <!-- CLIENT INFO -->
    <h6 class="fw-bold">Client Information</h6>

    <div class="table-responsive">
        <table class="table table-bordered">
            <tr><th width="30%">Name</th><td><?= htmlspecialchars($client['client_name']) ?></td></tr>
            <tr><th>Email</th><td><?= htmlspecialchars($client['email'] ?? 'N/A') ?></td></tr>
            <tr><th>Phone</th><td><?= htmlspecialchars($client['phone'] ?? 'N/A') ?></td></tr>
            <tr><th>Company</th><td><?= htmlspecialchars($client['company'] ?? 'N/A') ?></td></tr>
            <tr><th>Address</th><td><?= htmlspecialchars($client['address'] ?? 'N/A') ?></td></tr>
        </table>
    </div>

    <!-- PRODUCTS -->
    <h6 class="fw-bold mt-4">Purchased Products</h6>

    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Product Name</th>
                    <th>Serial Number</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['product_name']) ?></td>
                            <td><?= htmlspecialchars($p['serial_number']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="2" class="text-center">No products found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="text-end no-print mt-3">
        <button class="btn btn-secondary btn-sm" onclick="window.close()">Close</button>
    </div>

</div>

</body>
</html>
