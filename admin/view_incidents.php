<?php
require_once '../auth/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAuth('admin');

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $incident_id = $_POST['incident_id'];
    $new_status = $_POST['status'];
    
    try {
        $query = "UPDATE incidents SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        if ($stmt->execute([$new_status, $incident_id])) {
            $success = 'Incident status updated successfully!';
        }
    } catch(PDOException $e) {
        $error = 'Error updating status: ' . $e->getMessage();
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query with filters
$where_conditions = [];
$params = [];

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

if ($search) {
    $where_conditions[] = "(u.name LIKE ? OR u.licence_no LIKE ? OR i.control_number LIKE ? OR o.description LIKE ?)";
    $search_term = '%' . $search . '%';
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get incidents with filters
$incidents_query = "SELECT i.*, o.description as offence_description, o.amount_tzs,
                           u.name as driver_name, u.licence_no, u.plate_no,
                           off.name as officer_name, off.badge_number
                    FROM incidents i
                    JOIN offences o ON i.offence_id = o.id
                    JOIN users u ON i.user_id = u.id
                    JOIN officers off ON i.officer_id = off.id
                    $where_clause
                    ORDER BY i.created_at DESC";

$incidents_stmt = $conn->prepare($incidents_query);
$incidents_stmt->execute($params);
$incidents = $incidents_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary statistics
$summary_query = "SELECT 
                     COUNT(*) as total_incidents,
                     COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_count,
                     COUNT(CASE WHEN status = 'Paid' THEN 1 END) as paid_count,
                     COUNT(CASE WHEN status = 'Cancelled' THEN 1 END) as cancelled_count,
                     SUM(o.amount_tzs) as total_amount,
                     SUM(CASE WHEN i.status = 'Pending' THEN o.amount_tzs ELSE 0 END) as pending_amount
                  FROM incidents i
                  JOIN offences o ON i.offence_id = o.id
                  $where_clause";

$summary_stmt = $conn->prepare($summary_query);
$summary_stmt->execute($params);
$summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Incidents - RTIMS Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <div class="dashboard-header">
                <div>
                    <h2>📊 All Traffic Incidents</h2>
                    <p>View and manage all recorded traffic incidents</p>
                </div>
                <div class="dashboard-nav">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="manage_users.php">Manage Users</a>
                    <a href="manage_officers.php">Manage Officers</a>
                    <a href="manage_offences.php">Manage Offences</a>
                    <a href="view_incidents.php" class="active">All Incidents</a>
                    <a href="../auth/logout.php">Logout</a>
                </div>
            </div>

            <div class="dashboard-content">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <!-- Summary Statistics -->
                <div class="stats-grid" style="margin-bottom: 30px;">
                    <div class="stat-card">
                        <h3><?php echo $summary['total_incidents']; ?></h3>
                        <p>Total Incidents</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $summary['pending_count']; ?></h3>
                        <p>Pending</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $summary['paid_count']; ?></h3>
                        <p>Paid</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $summary['cancelled_count']; ?></h3>
                        <p>Cancelled</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo formatCurrency($summary['total_amount']); ?></h3>
                        <p>Total Amount</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo formatCurrency($summary['pending_amount']); ?></h3>
                        <p>Pending Amount</p>
                    </div>
                </div>

                <!-- Filters -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                    <h3>🔍 Filter Incidents</h3>
                    <form method="GET" class="form-grid">
                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select name="status" id="status">
                                <option value="">All Statuses</option>
                                <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Paid" <?php echo $status_filter == 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="Cancelled" <?php echo $status_filter == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="date_from">Date From:</label>
                            <input type="date" name="date_from" id="date_from" value="<?php echo $date_from; ?>">
                        </div>

                        <div class="form-group">
                            <label for="date_to">Date To:</label>
                            <input type="date" name="date_to" id="date_to" value="<?php echo $date_to; ?>">
                        </div>

                        <div class="form-group">
                            <label for="search">Search:</label>
                            <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Driver name, licence, control number...">
                        </div>

                        <div style="grid-column: 1 / -1; display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="view_incidents.php" class="btn btn-secondary">Clear Filters</a>
                            <button type="button" onclick="exportToCSV('incidents_table', 'incidents_report.csv')" class="btn btn-success">Export CSV</button>
                        </div>
                    </form>
                </div>

                <!-- Incidents Table -->
                <h3>Incidents (<?php echo count($incidents); ?>)</h3>
                <table class="data-table" id="incidents_table">
                    <thead>
                        <tr>
                            <th>Control Number</th>
                            <th>Driver</th>
                            <th>Offence</th>
                            <th>Officer</th>
                            <th>Location</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($incidents as $incident): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($incident['control_number']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($incident['driver_name']); ?><br>
                                    <small>
                                        Licence: <?php echo htmlspecialchars($incident['licence_no']); ?><br>
                                        Plate: <?php echo htmlspecialchars($incident['plate_no']); ?>
                                    </small>
                                </td>
                                <td><?php echo htmlspecialchars($incident['offence_description']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($incident['officer_name']); ?><br>
                                    <small>Badge: <?php echo htmlspecialchars($incident['badge_number']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($incident['location']); ?></td>
                                <td><?php echo formatCurrency($incident['amount_tzs']); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="incident_id" value="<?php echo $incident['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" class="status-badge status-<?php echo strtolower($incident['status']); ?>">
                                            <option value="Pending" <?php echo $incident['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Paid" <?php echo $incident['status'] == 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                            <option value="Cancelled" <?php echo $incident['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($incident['created_at'])); ?></td>
                                <td>
                                    <?php if ($incident['image_path']): ?>
                                        <a href="../uploads/<?php echo $incident['image_path']; ?>" target="_blank" 
                                           class="btn btn-secondary" style="font-size: 0.8rem; padding: 5px 10px;">View Image</a>
                                    <?php else: ?>
                                        <span style="color: #999; font-size: 0.8rem;">No Image</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
