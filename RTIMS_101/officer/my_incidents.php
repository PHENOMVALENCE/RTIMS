<?php
require_once '../auth/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAuth('officer');

$database = new Database();
$conn = $database->getConnection();

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query with filters
$where_conditions = ["i.officer_id = ?"];
$params = [$_SESSION['user_id']];

if ($status_filter) {
    $where_conditions[] = "i.status = ?";
    $params[] = $status_filter;
}

if ($date_from) {
    $where_conditions[] = "DATE(i.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(i.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get incidents
$incidents_query = "SELECT i.*, o.description as offence_description, o.amount_tzs,
                           u.name as driver_name, u.licence_no, u.plate_no
                    FROM incidents i
                    JOIN offences o ON i.offence_id = o.id
                    JOIN users u ON i.user_id = u.id
                    $where_clause
                    ORDER BY i.created_at DESC";

$incidents_stmt = $conn->prepare($incidents_query);
$incidents_stmt->execute($params);
$incidents = $incidents_stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>My Incidents - RTIMS</title>
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
                <a href="dashboard.php" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="record_incident.php" class="nav-item">
                    <span class="nav-icon">🚨</span>
                    <span class="nav-text">Record Incident</span>
                </a>
                <a href="my_incidents.php" class="nav-item active">
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
                <h1>📋 My Recorded Incidents</h1>
                <p>View and manage incidents you have recorded</p>
            </div>

            <div class="content-body">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-icon">📊</div>
                        <div class="stat-content">
                            <h3><?php echo $stats['total_recorded']; ?></h3>
                            <p>Total Recorded</p>
                        </div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon">📅</div>
                        <div class="stat-content">
                            <h3><?php echo $stats['today_recorded']; ?></h3>
                            <p>Today's Records</p>
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

                <!-- Filters -->
                <div class="card">
                    <div class="card-header">
                        <h3>🔍 Filter Incidents</h3>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="filter-form">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select name="status" id="status">
                                        <option value="">All Statuses</option>
                                        <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Paid" <?php echo $status_filter == 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                        <option value="Cancelled" <?php echo $status_filter == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="date_from">Date From</label>
                                    <input type="date" name="date_from" id="date_from" value="<?php echo $date_from; ?>">
                                </div>

                                <div class="form-group">
                                    <label for="date_to">Date To</label>
                                    <input type="date" name="date_to" id="date_to" value="<?php echo $date_to; ?>">
                                </div>

                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="button-group">
                                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                                        <a href="my_incidents.php" class="btn btn-secondary">Clear</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Incidents Table -->
                <div class="card">
                    <div class="card-header">
                        <h3>📋 Incidents (<?php echo count($incidents); ?>)</h3>
                        <div class="card-actions">
                            <button onclick="exportToCSV('incidents_table', 'my_incidents.csv')" class="btn btn-success btn-sm">
                                📊 Export CSV
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($incidents)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">📋</div>
                                <h3>No Incidents Found</h3>
                                <p>No incidents match your current filters.</p>
                                <a href="record_incident.php" class="btn btn-primary">🚨 Record New Incident</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table" id="incidents_table">
                                    <thead>
                                        <tr>
                                            <th>Control Number</th>
                                            <th>Driver</th>
                                            <th>Offence</th>
                                            <th>Location</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Evidence</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($incidents as $incident): ?>
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
                                                <td class="location-cell"><?php echo htmlspecialchars($incident['location']); ?></td>
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
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
