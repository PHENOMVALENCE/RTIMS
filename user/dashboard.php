<?php
require_once '../auth/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAuth('user');

$database = new Database();
$conn = $database->getConnection();

// Get user's incidents
$query = "SELECT i.*, o.description as offence_description, o.amount_tzs, 
                 off.name as officer_name, off.badge_number
          FROM incidents i
          JOIN offences o ON i.offence_id = o.id
          JOIN officers off ON i.officer_id = off.id
          WHERE i.user_id = ?
          ORDER BY i.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "SELECT 
                    COUNT(*) as total_incidents,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_incidents,
                    SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) as paid_incidents,
                    SUM(CASE WHEN status = 'Pending' THEN o.amount_tzs ELSE 0 END) as total_pending_amount,
                    SUM(CASE WHEN status = 'Paid' THEN o.amount_tzs ELSE 0 END) as total_paid_amount
                FROM incidents i
                JOIN offences o ON i.offence_id = o.id
                WHERE i.user_id = ?";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute([$_SESSION['user_id']]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - RTIMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>🚦 RTIMS</h2>
                <p>Driver Portal</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="profile.php" class="nav-item">
                    <span class="nav-icon">👤</span>
                    <span class="nav-text">My Profile</span>
                </a>
                <a href="payment_history.php" class="nav-item">
                    <span class="nav-icon">💰</span>
                    <span class="nav-text">Payment History</span>
                </a>
                <a href="../auth/logout.php" class="nav-item logout">
                    <span class="nav-icon">🚪</span>
                    <span class="nav-text">Logout</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
                    <small>Licence: <?php echo htmlspecialchars($_SESSION['licence_no']); ?></small>
                    <small>Plate: <?php echo htmlspecialchars($_SESSION['plate_no']); ?></small>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h1>🚗 Driver Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>. Here's your traffic record overview.</p>
            </div>

            <div class="content-body">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-icon">📊</div>
                        <div class="stat-content">
                            <h3><?php echo $stats['total_incidents']; ?></h3>
                            <p>Total Offences</p>
                        </div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-content">
                            <h3><?php echo $stats['pending_incidents']; ?></h3>
                            <p>Pending Offences</p>
                        </div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <h3><?php echo $stats['paid_incidents']; ?></h3>
                            <p>Paid Offences</p>
                        </div>
                    </div>
                    <div class="stat-card danger">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <h3><?php echo formatCurrency($stats['total_pending_amount'] ?? 0); ?></h3>
                            <p>Total Pending Amount</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <?php if ($stats['pending_incidents'] > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <h3>⚠️ Action Required</h3>
                        <p>You have pending traffic fines that need attention</p>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <strong>Outstanding Fines:</strong> You have <?php echo $stats['pending_incidents']; ?> pending traffic fine(s) 
                            totaling <?php echo formatCurrency($stats['total_pending_amount']); ?>. Please pay them promptly to avoid additional penalties.
                        </div>
                        <div class="button-group">
                            <a href="#incidents-table" class="btn btn-warning">📋 View Pending Fines</a>
                            <a href="payment_history.php" class="btn btn-secondary">💰 Payment History</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Traffic Incidents -->
                <div class="card" id="incidents-table">
                    <div class="card-header">
                        <h3>🚨 Your Traffic Offences</h3>
                        <div class="card-actions">
                            <button onclick="exportToCSV('incidents_table', 'my_traffic_record.csv')" class="btn btn-success btn-sm">
                                📊 Export Record
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($incidents)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">🚗</div>
                                <h3>Clean Driving Record!</h3>
                                <p>No traffic offences recorded. Keep up the safe driving!</p>
                                <a href="profile.php" class="btn btn-primary">👤 View Profile</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table" id="incidents_table">
                                    <thead>
                                        <tr>
                                            <th>Control Number</th>
                                            <th>Offence</th>
                                            <th>Location</th>
                                            <th>Officer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($incidents as $incident): ?>
                                            <tr>
                                                <td>
                                                    <strong class="control-number"><?php echo htmlspecialchars($incident['control_number']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($incident['offence_description']); ?></td>
                                                <td class="location-cell"><?php echo htmlspecialchars($incident['location']); ?></td>
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
                                                <td>
                                                    <div class="button-group">
                                                        <a href="view_incident.php?id=<?php echo $incident['id']; ?>" class="btn btn-secondary btn-sm">
                                                            📋 Details
                                                        </a>
                                                        <?php if ($incident['status'] == 'Pending'): ?>
                                                            <a href="pay_fine.php?id=<?php echo $incident['id']; ?>" class="btn btn-success btn-sm">
                                                                💳 Pay
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if ($incident['image_path']): ?>
                                                            <a href="view_evidence.php?id=<?php echo $incident['id']; ?>" class="btn btn-info btn-sm">
                                                                📷 Evidence
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Safety Tips -->
                <div class="card">
                    <div class="card-header">
                        <h3>🛡️ Road Safety Tips</h3>
                    </div>
                    <div class="card-body">
                        <div class="tips-grid">
                            <div class="tip-item">
                                <span class="tip-icon">🚗</span>
                                <div>
                                    <strong>Follow Speed Limits</strong>
                                    <p>Always observe posted speed limits and adjust for road conditions.</p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <span class="tip-icon">🔒</span>
                                <div>
                                    <strong>Wear Seatbelts</strong>
                                    <p>Ensure all passengers wear seatbelts at all times while driving.</p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <span class="tip-icon">📱</span>
                                <div>
                                    <strong>Avoid Phone Use</strong>
                                    <p>Don't use mobile phones while driving. Pull over safely if you must take a call.</p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <span class="tip-icon">🚦</span>
                                <div>
                                    <strong>Obey Traffic Signals</strong>
                                    <p>Always stop at red lights and follow traffic signs and signals.</p>
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
