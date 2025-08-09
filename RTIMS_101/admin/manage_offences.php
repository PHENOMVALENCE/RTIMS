<?php
require_once '../auth/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAuth('admin');

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

// Handle offence actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_offence':
                $keyword = $_POST['keyword'];
                $description = $_POST['description'];
                $amount_tzs = $_POST['amount_tzs'];
                
                try {
                    $query = "INSERT INTO offences (keyword, description, amount_tzs) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($query);
                    if ($stmt->execute([$keyword, $description, $amount_tzs])) {
                        $success = 'Offence added successfully!';
                    }
                } catch(PDOException $e) {
                    $error = 'Error adding offence: ' . $e->getMessage();
                }
                break;
                
            case 'delete_offence':
                $offence_id = $_POST['offence_id'];
                try {
                    $query = "DELETE FROM offences WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    if ($stmt->execute([$offence_id])) {
                        $success = 'Offence deleted successfully!';
                    }
                } catch(PDOException $e) {
                    $error = 'Error deleting offence: ' . $e->getMessage();
                }
                break;
                
            case 'update_offence':
                $offence_id = $_POST['offence_id'];
                $keyword = $_POST['keyword'];
                $description = $_POST['description'];
                $amount_tzs = $_POST['amount_tzs'];
                
                try {
                    $query = "UPDATE offences SET keyword = ?, description = ?, amount_tzs = ? WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    if ($stmt->execute([$keyword, $description, $amount_tzs, $offence_id])) {
                        $success = 'Offence updated successfully!';
                    }
                } catch(PDOException $e) {
                    $error = 'Error updating offence: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get all offences with usage statistics
$offences_query = "SELECT o.*, 
                          COUNT(i.id) as usage_count,
                          SUM(CASE WHEN i.status = 'Pending' THEN o.amount_tzs ELSE 0 END) as pending_revenue
                   FROM offences o
                   LEFT JOIN incidents i ON o.id = i.offence_id
                   GROUP BY o.id
                   ORDER BY o.created_at DESC";

$offences_stmt = $conn->prepare($offences_query);
$offences_stmt->execute();
$offences = $offences_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Offences - RTIMS Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <div class="dashboard-header">
                <div>
                    <h2>📋 Manage Traffic Offences</h2>
                    <p>Add, edit, and manage traffic offences and fines</p>
                </div>
                <div class="dashboard-nav">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="manage_users.php">Manage Users</a>
                    <a href="manage_officers.php">Manage Officers</a>
                    <a href="manage_offences.php" class="active">Manage Offences</a>
                    <a href="view_incidents.php">All Incidents</a>
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

                <!-- Add New Offence Form -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                    <h3>➕ Add New Traffic Offence</h3>
                    <form method="POST" class="form-grid">
                        <input type="hidden" name="action" value="add_offence">
                        
                        <div class="form-group">
                            <label for="keyword">Keyword:</label>
                            <input type="text" name="keyword" id="keyword" 
                                   placeholder="e.g., overspeeding, parking" required>
                            <small>Used for search matching</small>
                        </div>

                        <div class="form-group">
                            <label for="description">Full Description:</label>
                            <textarea name="description" id="description" rows="3" 
                                      placeholder="e.g., Driving above the speed limit" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="amount_tzs">Fine Amount (TZS):</label>
                            <input type="number" name="amount_tzs" id="amount_tzs" 
                                   step="0.01" min="0" placeholder="50000.00" required>
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <button type="submit" class="btn btn-primary">Add Offence</button>
                        </div>
                    </form>
                </div>

                <!-- Offences Table -->
                <h3>Traffic Offences (<?php echo count($offences); ?>)</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Keyword</th>
                            <th>Description</th>
                            <th>Fine Amount</th>
                            <th>Usage Count</th>
                            <th>Pending Revenue</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($offences as $offence): ?>
                            <tr>
                                <td><?php echo $offence['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($offence['keyword']); ?></strong></td>
                                <td><?php echo htmlspecialchars($offence['description']); ?></td>
                                <td><?php echo formatCurrency($offence['amount_tzs']); ?></td>
                                <td><?php echo $offence['usage_count']; ?></td>
                                <td><?php echo formatCurrency($offence['pending_revenue'] ?? 0); ?></td>
                                <td><?php echo date('M j, Y', strtotime($offence['created_at'])); ?></td>
                                <td>
                                    <button onclick="editOffence(<?php echo htmlspecialchars(json_encode($offence)); ?>)" 
                                            class="btn btn-secondary" style="font-size: 0.8rem; padding: 5px 10px; margin-right: 5px;">Edit</button>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirmDelete('Are you sure you want to delete this offence?')">
                                        <input type="hidden" name="action" value="delete_offence">
                                        <input type="hidden" name="offence_id" value="<?php echo $offence['id']; ?>">
                                        <button type="submit" class="btn btn-danger" style="font-size: 0.8rem; padding: 5px 10px;">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Offence Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; width: 90%; max-width: 500px;">
            <h3>Edit Offence</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update_offence">
                <input type="hidden" name="offence_id" id="edit_offence_id">
                
                <div class="form-group">
                    <label for="edit_keyword">Keyword:</label>
                    <input type="text" name="keyword" id="edit_keyword" required>
                </div>

                <div class="form-group">
                    <label for="edit_description">Full Description:</label>
                    <textarea name="description" id="edit_description" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_amount_tzs">Fine Amount (TZS):</label>
                    <input type="number" name="amount_tzs" id="edit_amount_tzs" step="0.01" min="0" required>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Update Offence</button>
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function editOffence(offence) {
            document.getElementById('edit_offence_id').value = offence.id;
            document.getElementById('edit_keyword').value = offence.keyword;
            document.getElementById('edit_description').value = offence.description;
            document.getElementById('edit_amount_tzs').value = offence.amount_tzs;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>
