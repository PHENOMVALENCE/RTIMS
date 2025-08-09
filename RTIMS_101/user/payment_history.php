<?php
require_once '../auth/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAuth('user');

$database = new Database();
$conn = $database->getConnection();

// Get user's payment history (paid incidents)
$payments_query = "SELECT i.*, o.description as offence_description, o.amount_tzs,
                          off.name as officer_name, off.badge_number
                   FROM incidents i
                   JOIN offences o ON i.offence_id = o.id
                   JOIN officers off ON i.officer_id = off.id
                   WHERE i.user_id = ? AND i.status = 'Paid'
                   ORDER BY i.created_at DESC";

$payments_stmt = $conn->prepare($payments_query);
$payments_stmt->execute([$_SESSION['user_id']]);
$payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_paid = 0;
foreach ($payments as $payment) {
    $total_paid += $payment['amount_tzs'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - RTIMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <div class="dashboard-header">
                <div>
                    <h2>💰 Payment History</h2>
                    <p>Your paid traffic fines</p>
                </div>
                <div class="dashboard-nav">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="profile.php">Profile</a>
                    <a href="payment_history.php" class="active">Payment History</a>
                    <a href="../auth/logout.php">Logout</a>
                </div>
            </div>

            <div class="dashboard-content">
                <!-- Payment Summary -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                    <h3>📊 Payment Summary</h3>
                    <div class="stats-grid" style="margin-top: 20px;">
                        <div class="stat-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                            <h3><?php echo count($payments); ?></h3>
                            <p>Total Payments Made</p>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                            <h3><?php echo formatCurrency($total_paid); ?></h3>
                            <p>Total Amount Paid</p>
                        </div>
                    </div>
                </div>

                <!-- Payment History Table -->
                <h3>Payment Records</h3>
                <?php if (empty($payments)): ?>
                    <div class="alert alert-info">
                        <p>No payments made yet. All your paid fines will appear here.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Control Number</th>
                                <th>Offence</th>
                                <th>Officer</th>
                                <th>Amount Paid</th>
                                <th>Payment Date</th>
                                <th>Evidence</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($payment['control_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($payment['offence_description']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($payment['officer_name']); ?><br>
                                        <small>Badge: <?php echo htmlspecialchars($payment['badge_number']); ?></small>
                                    </td>
                                    <td class="currency" style="color: #28a745; font-weight: bold;">
                                        <?php echo formatCurrency($payment['amount_tzs']); ?>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($payment['created_at'])); ?></td>
                                    <td>
                                        <?php if ($payment['image_path']): ?>
                                            <a href="view_evidence.php?id=<?php echo $payment['id']; ?>" class="btn btn-secondary" style="font-size: 0.8rem; padding: 5px 10px;">📷 View</a>
                                        <?php else: ?>
                                            <span style="color: #999; font-size: 0.8rem;">No Image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="view_incident.php?id=<?php echo $payment['id']; ?>" class="btn btn-secondary" style="font-size: 0.8rem; padding: 5px 10px;">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Export Options -->
                    <div style="text-align: center; margin-top: 20px;">
                        <button onclick="exportToCSV('payment_history_table', 'my_payment_history.csv')" class="btn btn-success">📊 Export to CSV</button>
                        <button onclick="window.print()" class="btn btn-secondary">🖨️ Print History</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <style>
        @media print {
            .dashboard-header, .dashboard-nav, button {
                display: none !important;
            }
            
            .container {
                max-width: none;
                margin: 0;
                padding: 10px;
            }
            
            .dashboard {
                box-shadow: none;
            }
        }
    </style>
</body>
</html>
