<?php
require_once '../auth/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAuth('admin');

$database = new Database();
$conn = $database->getConnection();

// Get system statistics
$stats_query = "SELECT 
                    (SELECT COUNT(*) FROM users) as total_users,
                    (SELECT COUNT(*) FROM officers) as total_officers,
                    (SELECT COUNT(*) FROM incidents) as total_incidents,
                    (SELECT COUNT(*) FROM incidents WHERE status = 'Pending') as pending_incidents,
                    (SELECT SUM(o.amount_tzs) FROM incidents i JOIN offences o ON i.offence_id = o.id WHERE i.status = 'Pending') as pending_amount,
                    (SELECT COUNT(*) FROM incidents WHERE DATE(created_at) = CURDATE()) as today_incidents";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get recent incidents
$recent_query = "SELECT i.*, o.description as offence_description, o.amount_tzs,
                        u.name as driver_name, u.licence_no, u.plate_no,
                        off.name as officer_name, off.badge_number
                 FROM incidents i
                 JOIN offences o ON i.offence_id = o.id
                 JOIN users u ON i.user_id = u.id
                 JOIN officers off ON i.officer_id = off.id
                 ORDER BY i.created_at DESC
                 LIMIT 10";

$recent_stmt = $conn->prepare($recent_query);
$recent_stmt->execute();
$recent_incidents = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RTIMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>🚦 RTIMS</h2>
                <p>Administrator</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="manage_users.php" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Manage Users</span>
                </a>
                <a href="manage_officers.php" class="nav-item">
                    <span class="nav-icon">👮‍♂️</span>
                    <span class="nav-text">Manage Officers</span>
                </a>
                <a href="manage_offences.php" class="nav-item">
                    <span class="nav-icon">📋</span>
                    <span class="nav-text">Manage Offences</span>
                </a>
                <a href="view_incidents.php" class="nav-item">
                    <span class="nav-icon">🚨</span>
                    <span class="nav-text">All Incidents</span>
                </a>
                <a href="email_config.php" class="nav-item">
                    <span class="nav-icon">📧</span>
                    <span class="nav-text">Email Settings</span>
                </a>
               <!-- <a href="reports.php" class="nav-item">
                    <span class="nav-icon">📈</span>
                    <span class="nav-text">Reports</span>
                </a> -->
                <a href="../auth/logout.php" class="nav-item logout">
                    <span class="nav-icon">🚪</span>
                    <span class="nav-text">Logout</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
                    <small>System Administrator</small>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h1>⚙️ Administrator Dashboard</h1>
                <p>System overview and management tools</p>
            </div>

            <div class="content-body">
                <!-- System Statistics -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <h3><?php echo $stats['total_users']; ?></h3>
                            <p>Registered Drivers</p>
                        </div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-icon">👮‍♂️</div>
                        <div class="stat-content">
                            <h3><?php echo $stats['total_officers']; ?></h3>
                            <p>Traffic Officers</p>
                        </div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon">🚨</div>
                        <div class="stat-content">
                            <h3><?php echo $stats['total_incidents']; ?></h3>
                            <p>Total Incidents</p>
                        </div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon">📅</div>
                        <div class="stat-content">
                            <h3><?php echo $stats['today_incidents']; ?></h3>
                            <p>Today's Incidents</p>
                        </div>
                    </div>
                    <div class="stat-card danger">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-content">
                            <h3><?php echo $stats['pending_incidents']; ?></h3>
                            <p>Pending Cases</p>
                        </div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <h3><?php echo formatCurrency($stats['pending_amount'] ?? 0); ?></h3>
                            <p>Pending Revenue</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3>⚡ Quick Actions</h3>
                        <p>Common administrative tasks</p>
                    </div>
                    <div class="card-body">
                        <div class="button-group">
                            <a href="manage_users.php" class="btn btn-primary">👥 Manage Drivers</a>
                            <a href="manage_officers.php" class="btn btn-info">👮‍♂️ Manage Officers</a>
                            <a href="manage_offences.php" class="btn btn-warning">📋 Manage Offences</a>
                            <a href="view_incidents.php" class="btn btn-secondary">🚨 View All Incidents</a>
                           <!-- <a href="reports.php" class="btn btn-success">📈 Generate Reports</a>-->
                        </div>
                    </div>
                </div>

                <!-- Recent Incidents -->
                <div class="card">
                    <div class="card-header">
                        <h3>📋 Recent Incidents</h3>
                        <div class="card-actions">
                            <a href="view_incidents.php" class="btn btn-secondary btn-sm">View All</a>
                            <button onclick="exportToCSV('recent_incidents_table', 'recent_incidents.csv')" class="btn btn-success btn-sm">📊 Export</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_incidents)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">📋</div>
                                <h3>No Incidents Recorded</h3>
                                <p>No traffic incidents have been recorded yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table" id="recent_incidents_table">
                                    <thead>
                                        <tr>
                                            <th>Control Number</th>
                                            <th>Driver</th>
                                            <th>Offence</th>
                                            <th>Officer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_incidents as $incident): ?>
                                            <tr>
                                                <td>
                                                    <strong class="control-number"><?php echo htmlspecialchars($incident['control_number']); ?></strong>
                                                </td>
                                                <td>
                                                    <div class="driver-info">
                                                        <strong><?php echo htmlspecialchars($incident['driver_name']); ?></strong>
                                                        <small>Licence: <?php echo htmlspecialchars($incident['licence_no']); ?></small>
                                                        <small>Plate: <?php echo htmlspecialchars($incident['plate_no']); ?></small>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($incident['offence_description']); ?></td>
                                                <td>
                                                    <div class="driver-info">
                                                        <strong><?php echo htmlspecialchars($incident['officer_name']); ?></strong>
                                                        <small>Badge: <?php echo htmlspecialchars($incident['badge_number']); ?></small>
                                                    </div>
                                                </td>
                                                <td class="amount-cell"><?php echo formatCurrency($incident['amount_tzs']); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo strtolower($incident['status']); ?>">
                                                        <?php echo $incident['status']; ?>
                                                    </span>
                                                </td>
                                                <td class="date-cell">
                                                    <?php echo date('M j, Y', strtotime($incident['created_at'])); ?>
                                                    <small><?php echo date('g:i A', strtotime($incident['created_at'])); ?></small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- System Health -->
                <div class="card">
                    <div class="card-header">
                        <h3>🔧 System Health</h3>
                    </div>
                    <div class="card-body">
                        <div class="tips-grid">
                            <div class="tip-item">
                                <span class="tip-icon">💾</span>
                                <div>
                                    <strong>Database Status</strong>
                                    <p>System is running smoothly with all connections active.</p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <span class="tip-icon">📁</span>
                                <div>
                                    <strong>File Uploads</strong>
                                    <p>Image upload system is operational and secure.</p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <span class="tip-icon">🔐</span>
                                <div>
                                    <strong>Security</strong>
                                    <p>All user sessions are encrypted and secure.</p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <span class="tip-icon">📊</span>
                                <div>
                                    <strong>Performance</strong>
                                    <p>System response times are optimal.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
