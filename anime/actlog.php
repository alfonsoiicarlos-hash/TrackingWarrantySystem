<?php
include 'config.php';
checkAuth();

// Kunin lahat ng activity logs
$stmt = $pdo->prepare("
    SELECT id, username, action, description, ip_address, created_at
    FROM activity_logs
    ORDER BY created_at DESC
");
$stmt->execute();
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Logs - Anime PC Warranty System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #f4f6f9;
        }
        .log-card {
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-radius: 10px;
        }
        .badge-action {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>

<div class="container-fluid mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold">
            <i class="bi bi-clock-history me-2"></i>Activity Logs
        </h3>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="card log-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Exact Activity</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>

                <?php if ($logs): ?>
                    <?php foreach ($logs as $index => $log): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>

                            <td>
                                <?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?>
                            </td>

                            <td>
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo htmlspecialchars($log['username']); ?>
                            </td>

                            <td>
                                <?php
                                    $color = 'secondary';
                                    if (str_contains($log['action'], 'ADD')) $color = 'success';
                                    if (str_contains($log['action'], 'UPDATE')) $color = 'primary';
                                    if (str_contains($log['action'], 'DELETE')) $color = 'danger';
                                    if (str_contains($log['action'], 'UPLOAD')) $color = 'warning';
                                ?>
                                <span class="badge bg-<?php echo $color; ?> badge-action">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($log['description']); ?>
                            </td>

                            <td>
                                <code><?php echo htmlspecialchars($log['ip_address']); ?></code>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-inbox display-6 d-block mb-2"></i>
                            No activity logs found.
                        </td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
