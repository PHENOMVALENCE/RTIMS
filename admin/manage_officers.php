<?php
require_once '../auth/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAuth('admin');

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

// Handle officer actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_officer':
                $name = $_POST['name'];
                $username = $_POST['username'];
                $badge_number = $_POST['badge_number'];
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                try {
                    $query = "INSERT INTO officers (name, username, badge_number, password) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($query);
                    if ($stmt->execute([$name, $username, $badge_number, $password])) {
                        $success = 'Officer added successfully!';
                    }
                } catch(PDOException $e) {
                    $error = 'Error adding officer: ' . $e->getMessage();
                }
                break;
                
            case 'delete_officer':
                $officer_id = $_POST['officer_id'];
                try {
                    $query = "DELETE FROM officers WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    if ($stmt->execute([$officer_id])) {
                        $success = 'Officer deleted successfully!';
                    }
                } catch(PDOException $e) {
                    $error = 'Error deleting officer: ' . $e->getMessage();
                }
                break;
                
            case 'update_officer':
                $officer_id = $_POST['officer_id'];
                $name = $_POST['name'];
                $username = $_POST['username'];
                $badge_number = $_POST['badge_number'];
                
                try {
                    $query = "UPDATE officers SET name = ?, username = ?, badge_number = ? WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    if ($stmt->execute([$name, $username, $badge_number, $officer_id])) {
                        $success = 'Officer updated successfully!';
                    }
                } catch(PDOException $e) {
                    $error = 'Error updating officer: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get all officers with their incident counts
$officers_query = "SELECT o.*, 
                          COUNT(i.id) as incident_count,
                          COUNT(CASE WHEN DATE(i.created_at) = CURDATE() THEN 1 END) as today_count
                   FROM officers o
                   LEFT JOIN incidents i ON o.id = i.officer_id
                   GROUP BY o.id
                   ORDER BY o.created_at DESC";

$officers_stmt = $conn->prepare($officers_query);
$officers_stmt->execute();
$officers = $officers_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Officers - RTIMS Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <div class="dashboard-header">
                <div>
                    <h2>👮‍♂️ Manage Traffic Officers</h2>
                    <p>Add, edit, and manage traffic officers</p>
                </div>
                <div class="dashboard-nav">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="manage_users.php">Manage Users</a>
                    <a href="manage_officers.php" class="active">Manage Officers</a>
                    <a href="manage_offences.php">Manage Offences</a>
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

                <!-- Add New Officer Form -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                    <h3>➕ Add New Traffic Officer</h3>
                    <form method="POST" class="form-grid">
                        <input type="hidden" name="action" value="add_officer">
                        
                        <div class="form-group">
                            <label for="name">Full Name:</label>
                            <input type="text" name="name" id="name" required>
                        </div>

                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" name="username" id="username" required>
                        </div>

                        <div class="form-group">
                            <label for="badge_number">Badge Number:</label>
                            <input type="text" name="badge_number" id="badge_number" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" name="password" id="password" required>
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <button type="submit" class="btn btn-primary">Add Officer</button>
                        </div>
                    </form>
                </div>

                <!-- Officers Table -->
                <h3>Traffic Officers (<?php echo count($officers); ?>)</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Badge Number</th>
                            <th>Total Incidents</th>
                            <th>Today's Records</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($officers as $officer): ?>
                            <tr>
                                <td><?php echo $officer['id']; ?></td>
                                <td><?php echo htmlspecialchars($officer['name']); ?></td>
                                <td><?php echo htmlspecialchars($officer['username']); ?></td>
                                <td><?php echo htmlspecialchars($officer['badge_number']); ?></td>
                                <td><?php echo $officer['incident_count']; ?></td>
                                <td><?php echo $officer['today_count']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($officer['created_at'])); ?></td>
                                <td>
                                    <button onclick="editOfficer(<?php echo htmlspecialchars(json_encode($officer)); ?>)" 
                                            class="btn btn-secondary" style="font-size: 0.8rem; padding: 5px 10px; margin-right: 5px;">Edit</button>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirmDelete('Are you sure you want to delete this officer?')">
                                        <input type="hidden" name="action" value="delete_officer">
                                        <input type="hidden" name="officer_id" value="<?php echo $officer['id']; ?>">
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

    <!-- Edit Officer Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; width: 90%; max-width: 500px;">
            <h3>Edit Officer</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update_officer">
                <input type="hidden" name="officer_id" id="edit_officer_id">
                
                <div class="form-group">
                    <label for="edit_name">Full Name:</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>

                <div class="form-group">
                    <label for="edit_username">Username:</label>
                    <input type="text" name="username" id="edit_username" required>
                </div>

                <div class="form-group">
                    <label for="edit_badge_number">Badge Number:</label>
                    <input type="text" name="badge_number" id="edit_badge_number" required>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Update Officer</button>
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function editOfficer(officer) {
            document.getElementById('edit_officer_id').value = officer.id;
            document.getElementById('edit_name').value = officer.name;
            document.getElementById('edit_username').value = officer.username;
            document.getElementById('edit_badge_number').value = officer.badge_number;
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
