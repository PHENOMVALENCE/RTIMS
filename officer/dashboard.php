<?php
require_once '../auth/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAuth('officer');

$database = new Database();
$conn = $database->getConnection();

// Get officer's recent incidents
$incidents_query = "SELECT i.*, o.description as offence_description, o.amount_tzs,
                           u.name as driver_name, u.licence_no, u.plate_no
                    FROM incidents i
                    JOIN offences o ON i.offence_id = o.id
                    JOIN users u ON i.user_id = u.id
                    WHERE i.officer_id = ?
                    ORDER BY i.created_at DESC
                    LIMIT 10";

$incidents_stmt = $conn->prepare($incidents_query);
$incidents_stmt->execute([$_SESSION['user_id']]);
$recent_incidents = $incidents_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "SELECT 
                    COUNT(*) as total_recorded,
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_recorded,
                    COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_incidents,
                    COUNT(CASE WHEN status = 'Paid' THEN 1 END) as paid_incidents
                FROM incidents 
                WHERE officer_id = ?";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute([$_SESSION['user_id']]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Dashboard - RTIMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>🚦 RTIMS</h2>
                <p>Traffic Officer</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="record_incident.php" class="nav-item">
                    <span class="nav-icon">🚨</span>
                    <span class="nav-text">Record Incident</span>
                </a>
                <a href="my_incidents.php" class="nav-item">
                    <span class="nav-icon">📋</span>
                    <span class="nav-text">My Incidents</span>
                </a>
                <a href="../test_image_upload.php" target="_blank" class="nav-item">
                    <span class="nav-icon">🧪</span>
                    <span class="nav-text">Test Upload</span>
                </a>
                <a href="../auth/logout.php" class="nav-item logout">
                    <span class="nav-icon">🚪</span>
                    <span class="nav-text">Logout</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
                    <small>Badge: <?php echo htmlspecialchars($_SESSION['badge_number']); ?></small>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h1>📊 Officer Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>. Here's your activity overview.</p>
            </div>

            <div class="content-body">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-icon">📊</div>
                        <div class="stat-content">
                            <h3><?php echo $stats['total_recorded']; ?></h3>
                            <p>Total Incidents Recorded</p>
                        </div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon">📅</div>
                        <div class="stat-content">
                            <h3><?php echo $stats['today_recorded']; ?></h3>
                            <p>Recorded Today</p>
                        </div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-content">
                            <h3><?php echo $stats['pending_incidents']; ?></h3>
                            <p>Pending Cases</p>
                        </div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <h3><?php echo $stats['paid_incidents']; ?></h3>
                            <p>Paid Cases</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3>⚡ Quick Actions</h3>
                        <p>Common tasks and shortcuts</p>
                    </div>
                    <div class="card-body">
                        <div class="button-group">
                            <a href="record_incident.php" class="btn btn-primary btn-large">
                                🚨 Record New Incident
                            </a>
                            <a href="my_incidents.php" class="btn btn-secondary">
                                📋 View My Incidents
                            </a>
                            <a href="../test_image_upload.php" target="_blank" class="btn btn-info">
                                🧪 Test Upload System
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Recent Incidents -->
                <div class="card">
                    <div class="card-header">
                        <h3>📋 Recent Incidents</h3>
                        <div class="card-actions">
                            <a href="my_incidents.php" class="btn btn-secondary btn-sm">View All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_incidents)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">📋</div>
                                <h3>No Incidents Recorded Yet</h3>
                                <p>Start by recording your first traffic incident.</p>
                                <a href="record_incident.php" class="btn btn-primary">🚨 Record Incident</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Control Number</th>
                                            <th>Driver</th>
                                            <th>Offence</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Evidence</th>
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
                                                <td class="amount-cell"><?php echo formatCurrency($incident['amount_tzs']); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo strtolower($incident['status']); ?>">
                                                        <?php echo $incident['status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($incident['image_path']): ?>
                                                        <a href="/uploads/<?php echo urlencode($incident['image_path']); ?>" 
                                                           target="_blank" class="btn btn-secondary btn-sm">
                                                            📷 View
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="no-evidence">No Image</span>
                                                    <?php endif; ?>
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

                <!-- Performance Tips -->
                <div class="card">
                    <div class="card-header">
                        <h3>💡 Performance Tips</h3>
                    </div>
                    <div class="card-body">
                        <div class="tips-grid">
                            <div class="tip-item">
                                <span class="tip-icon">📷</span>
                                <div>
                                    <strong>Clear Evidence Photos</strong>
                                    <p>Always capture clear photos showing the violation and licence plate for better case documentation.</p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <span class="tip-icon">📍</span>
                                <div>
                                    <strong>Accurate Location Details</strong>
                                    <p>Include specific landmarks, street names, and GPS coordinates when possible.</p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <span class="tip-icon">⚡</span>
                                <div>
                                    <strong>Quick Processing</strong>
                                    <p>Record incidents promptly while details are fresh in your memory.</p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <span class="tip-icon">🔍</span>
                                <div>
                                    <strong>Double-Check Information</strong>
                                    <p>Verify licence numbers and offence types before submitting to avoid errors.</p>
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

Now let me update the user dashboard with the new sidebar design:
