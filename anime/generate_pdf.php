<?php
include 'config.php';
checkAuth();

// Get all products
$products = $pdo->query("
    SELECT p.*, c.client_name 
    FROM products p 
    LEFT JOIN clients c ON p.client_id = c.id 
    ORDER BY p.created_at DESC
")->fetchAll();

// Count status
$active_count = 0;
$expired_count = 0;
foreach ($products as $product) {
    $endDate = new DateTime($product['warranty_end_date']);
    $today = new DateTime();
    if ($endDate < $today || $product['status'] == 'expired') {
        $expired_count++;
    } else {
        $active_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anime PC - Products Warranty Report</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #007144ff 0%, #202020ff 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }

        .header h1 {
            color: #333;
            font-size: 2.5em;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #005221ff, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header .subtitle {
            color: #666;
            font-size: 1.1em;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.total { border-top: 4px solid #667eea; }
        .stat-card.active { border-top: 4px solid #28a745; }
        .stat-card.expired { border-top: 4px solid #dc3545; }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .total .stat-number { color: #1f2025ff; }
        .active .stat-number { color: #28a745; }
        .expired .stat-number { color: #dc3545; }

        .stat-label {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .controls {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .report-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background: linear-gradient(135deg, #000000ff, #353535ff);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border: none;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-expired {
            background: #f8d7da;
            color: #721c24;
        }

        .footer {
            text-align: center;
            color: white;
            padding: 20px;
            font-size: 0.9em;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2em;
            }
            
            .controls {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            th, td {
                padding: 8px 10px;
                font-size: 0.9em;
            }
        }

        /* Print styles */
        @media print {
            body {
                background: white !important;
                padding: 0 !important;
            }
            
            .header, .stats-container, .controls, .footer {
                display: none !important;
            }
            
            .report-container {
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🖥️ Anime PC - Warranty Management</h1>
            <p class="subtitle">Comprehensive Products Warranty Report</p>
        </div>

        <div class="stats-container">
            <div class="stat-card total">
                <div class="stat-number"><?php echo count($products); ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card active">
                <div class="stat-number"><?php echo $active_count; ?></div>
                <div class="stat-label">Active Warranties</div>
            </div>
            <div class="stat-card expired">
                <div class="stat-number"><?php echo $expired_count; ?></div>
                <div class="stat-label">Expired Warranties</div>
            </div>
        </div>

        <div class="controls">
            <button class="btn btn-primary" onclick="downloadPDF()">
                📄 Download PDF Report
            </button>
            <button class="btn btn-success" onclick="window.print()">
                🖨️ Print Report
            </button>
            <a href="dashboard.php" class="btn btn-danger">
                ⬅️ Back to Dashboard
            </a>
        </div>

        <div class="report-container" id="pdf-content">
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="color: #333; margin-bottom: 10px;">Products Warranty Report</h2>
                <p style="color: #666;">Generated on: <?php echo date('F j, Y'); ?></p>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Serial Number</th>
                            <th>Category</th>
                            <th>Purchase Date</th>
                            <th>Warranty End</th>
                            <th>Status</th>
                            <th>Client</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): 
                            $endDate = new DateTime($product['warranty_end_date']);
                            $today = new DateTime();
                            $status = ($endDate < $today || $product['status'] == 'expired') ? 'Expired' : 'Active';
                            $status_class = ($status == 'Expired') ? 'status-expired' : 'status-active';
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($product['serial_number']); ?></code></td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($product['purchase_date'])); ?></td>
                            <td><?php echo date('M j, Y', strtotime($product['warranty_end_date'])); ?></td>
                            <td>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo $status; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($product['client_name'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <strong>Total Products:</strong> <?php echo count($products); ?>
                    </div>
                    <div>
                        <strong>Active Warranties:</strong> <?php echo $active_count; ?>
                    </div>
                    <div>
                        <strong>Expired Warranties:</strong> <?php echo $expired_count; ?>
                    </div>
                    <div>
                        <strong>Report Date:</strong> <?php echo date('F j, Y, g:i A'); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Anime PC Warranty System. All rights reserved.</p>
        </div>
    </div>

    <script>
        function downloadPDF() {
            const element = document.getElementById('pdf-content');
            const options = {
                margin: [10, 10, 10, 10],
                filename: 'anime_pc_warranty_report_<?php echo date('Y-m-d'); ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
            };

            // Show loading state
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '⏳ Generating PDF...';
            button.disabled = true;

            html2pdf().set(options).from(element).save().finally(() => {
                // Restore button state
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }

        // Add animation to stats cards on load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card');
            cards.forEach((card, index) => {
                card.style.animation = `fadeInUp 0.6s ease ${index * 0.1}s both`;
            });
        });

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>