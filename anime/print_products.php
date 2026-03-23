<?php
include 'config.php';
checkAuth();

if (!isset($_GET['client_id'])) {
    die('Client ID not specified.');
}

$clientId = intval($_GET['client_id']);

// Fetch client info
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$clientId]);
$client = $stmt->fetch();

if (!$client) {
    die('Client not found.');
}

// Fetch products of this client
$stmt = $pdo->prepare("SELECT * FROM products WHERE client_id = ?");
$stmt->execute([$clientId]);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Products - <?php echo htmlspecialchars($client['client_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            body * { visibility: hidden; }
            #printable, #printable * { visibility: visible; }
            #printable { position: absolute; left: 0; top: 0; width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container mt-4" id="printable">
        <h3>Products of Client: <?php echo htmlspecialchars($client['client_name']); ?></h3>
        <p>Company: <?php echo htmlspecialchars($client['company']); ?></p>
        <p>Email: <?php echo htmlspecialchars($client['email']); ?> | Phone: <?php echo htmlspecialchars($client['phone']); ?></p>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Serial Number</th>
                    <th>Model</th>
                    <th>Date Added</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $index => $product): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['serial_number']); ?></td>
                            <td><?php echo htmlspecialchars($product['model']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No products found for this client.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="text-center mt-3">
        <button class="btn btn-primary" onclick="window.print()">Print</button>
        <a href="clients.php" class="btn btn-secondary">Back</a>
    </div>
</body>
</html>
