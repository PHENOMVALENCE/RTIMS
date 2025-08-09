<?php
require_once '../auth/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAuth('admin');

$database = new Database();
$conn = $database->getConnection();

// Get date range for reports
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // First day of current month
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d'); // Today

// Overall Statistics
$overall_stats_query = "SELECT 
                           COUNT(DISTINCT u.id) as total_users,
                           COUNT(DISTINCT o.id) as total_officers,
                           COUNT(DISTINCT off.id) as total_offences,
                           COUNT(i.id) as total_incidents,
                           SUM(CASE WHEN i.status = 'Pending' THEN off.amount_tzs ELSE 0 END) as pending_revenue,
                           SUM(CASE WHEN i.status = 'Paid' THEN off.amount_tzs ELSE 0 END) as collected_revenue
                        FROM users u
                        CROSS JOIN officers o
                        CROSS JOIN offences off
                        LEFT JOIN incidents i ON 1=1
                        WHERE DATE(i.created_at) BETWEEN ? AND ?";

$overall_stmt = $conn->prepare($overall_stats_query);
$overall_stmt->execute([$date_from, $date_to]);
$overall_stats = $overall_stmt->fetch(PDO::FETCH_ASSOC);

// Daily Incidents Report
$daily_query = "SELECT 
                   DATE(created_at) as incident_date,
                   COUNT(*) as daily_count,
                   SUM(o.amount_tzs) as daily_amount
                FROM incidents i
                JOIN offences o ON i.offence_id = o.id
                WHERE DATE(i.created_at) BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY incident_date DESC";

$daily_stmt = $conn->prepare($daily_query);
$daily_stmt->execute([$date_from, $date_to]);
$daily_reports = $daily_stmt->fetchAll(PDO::FETCH_ASSOC);

// Top Offences Report
$top_offences_query = "SELECT 
                          o.keyword,
                          o.description,
                          o.amount_tzs,
                          COUNT(i.id) as incident_count,
                          SUM(o.amount_tzs) as total_revenue
                       FROM offences o
                       LEFT JOIN incidents i ON o.id = i.offence_id 
                       WHERE DATE(i.created_at) BETWEEN ? AND ?
                       GROUP BY o.id
                       ORDER BY incident_count DESC
                       LIMIT 10";

$top_offences_stmt = $conn->prepare($top_offences_query);
$top_offences_stmt->execute([$date_from, $date_to]);
$top_offences = $top_offences_stmt->fetchAll(PDO::FETCH_ASSOC);

// Officer Performance Report
$officer_performance_query = "SELECT 
                                 off.name,
                                 off.badge_number,
                                 COUNT(i.id) as incidents_recorded,
                                 SUM(o.amount_tzs) as total_fines_issued
                              FROM officers off
                              LEFT JOIN incidents i ON off.id = i.officer_id
                              LEFT JOIN offences o ON i.offence_id = o.id
                              WHERE DATE(i.created_at) BETWEEN ? AND ?
                              GROUP BY off.id
                              ORDER BY incidents_recorded DESC";

$officer_stmt = $conn->prepare($officer_performance_query);
$officer_stmt->execute([$date_from, $date_to]);
$officer_performance = $officer_stmt->fetchAll(PDO::FETCH_ASSOC);

// Status Distribution
$status_query = "SELECT 
                    status,
                    COUNT(*) as count,
                    SUM(o.amount_tzs) as amount
                 FROM incidents i
                 JOIN offences o ON i.offence_id = o.id
                 WHERE DATE(i.created_at) BETWEEN ? AND ?
                 GROUP BY status";

$status_stmt = $conn->prepare($status_query);
$status_stmt->execute([$date_from, $date_to]);
$status_distribution = $status_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - RTIMS Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <div class="dashboard-header">
                <div>
                    <h2>📊 Reports & Analytics</h2>
                    <p>System performance and statistics</p>
                </div>
                <div class="dashboard-nav">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="manage_users.php">Manage Users</a>
                    <a href="manage_officers.php">Manage Officers</a>
                    <a href="manage_offences.php">Manage Offences</a>
                    <a href="view_incidents.php">All Incidents</a>
                    <a href="reports.php" class="active">Reports</a>
                    <a href="../auth/logout.php">Logout</a>
                </div>
            </div>

            <div class="dashboard-content">
                <!-- Date Range Filter -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                    <h3>📅 Report Period</h3>
                    <form method="GET" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap; margin-top: 15px;">
                        <div class="form-group" style="margin: 0;">
                            <label for="date_from">From Date:</label>
                            <input type="date" name="date_from" id="date_from" value="<?php echo $date_from; ?>">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label for="date_to">To Date:</label>
                            <input type="date" name="date_to" id="date_to" value="<?php echo $date_to; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                        <button type="button" onclick="window.print()" class="btn btn-secondary">🖨️ Print Report</button>
                    </form>
                </div>

                <!-- Overall Statistics -->
                <div class="stats-grid" style="margin-bottom: 30px;">
                    <div class="stat-card">
                        <h3><?php echo $overall_stats['total_incidents']; ?></h3>
                        <p>Total Incidents</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo formatCurrency($overall_stats['collected_revenue']); ?></h3>
                        <p>Revenue Collected</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo formatCurrency($overall_stats['pending_revenue']); ?></h3>
                        <p>Pending Revenue</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $overall_stats['total_users']; ?></h3>
                        <p>Registered Drivers</p>
                    </div>
                </div>

                <div class="form-grid">
                    <!-- Daily Incidents Chart -->
                    <div>
                        <h3>📈 Daily Incidents Report</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Incidents</th>
                                    <th>Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($daily_reports as $daily): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($daily['incident_date'])); ?></td>
                                        <td><?php echo $daily['daily_count']; ?></td>
                                        <td><?php echo formatCurrency($daily['daily_amount']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Status Distribution -->
                    <div>
                        <h3>📊 Status Distribution</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($status_distribution as $status): ?>
                                    <tr>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($status['status']); ?>">
                                                <?php echo $status['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $status['count']; ?></td>
                                        <td><?php echo formatCurrency($status['amount']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Top Offences -->
                <h3>🏆 Most Common Offences</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Offence Type</th>
                            <th>Description</th>
                            <th>Fine Amount</th>
                            <th>Incidents</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; foreach ($top_offences as $offence): ?>
                            <tr>
                                <td><strong><?php echo $rank++; ?></strong></td>
                                <td><?php echo htmlspecialchars($offence['keyword']); ?></td>
                                <td><?php echo htmlspecialchars($offence['description']); ?></td>
                                <td><?php echo formatCurrency($offence['amount_tzs']); ?></td>
                                <td><?php echo $offence['incident_count']; ?></td>
                                <td><?php echo formatCurrency($offence['total_revenue']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Officer Performance -->
                <h3>👮‍♂️ Officer Performance</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Officer Name</th>
                            <th>Badge Number</th>
                            <th>Incidents Recorded</th>
                            <th>Total Fines Issued</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($officer_performance as $officer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($officer['name']); ?></td>
                                <td><?php echo htmlspecialchars($officer['badge_number']); ?></td>
                                <td><?php echo $officer['incidents_recorded']; ?></td>
                                <td><?php echo formatCurrency($officer['total_fines_issued']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Export Options -->
                <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <h3>📤 Export Options</h3>
                    <div style="display: flex; gap: 15px; justify-content: center; margin-top: 15px; flex-wrap: wrap;">
                        <button onclick="exportToCSV('daily_reports_table', 'daily_incidents_report.csv')" class="btn btn-success">Export Daily Report</button>
                        <button onclick="exportToCSV('top_offences_table', 'top_offences_report.csv')" class="btn btn-success">Export Offences Report</button>
                        <button onclick="exportToCSV('officer_performance_table', 'officer_performance_report.csv')" class="btn btn-success">Export Officer Report</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <style>
        @media print {
            .dashboard-header, .dashboard-nav, button, form {
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
            
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
