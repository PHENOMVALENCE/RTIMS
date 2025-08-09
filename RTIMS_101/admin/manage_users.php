<?php
require_once '../auth/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAuth('admin');

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_user':
                $name = $_POST['name'];
                $licence_no = $_POST['licence_no'];
                $plate_no = $_POST['plate_no'];
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                try {
                    $query = "INSERT INTO users (name, licence_no, plate_no, password) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($query);
                    if ($stmt->execute([$name, $licence_no, $plate_no, $password])) {
                        $success = 'User added successfully!';
                    }
                } catch(PDOException $e) {
                    $error = 'Error adding user: ' . $e->getMessage();
                }
                break;
                
            case 'delete_user':
                $user_id = $_POST['user_id'];
                try {
                    $query = "DELETE FROM users WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    if ($stmt->execute([$user_id])) {
                        $success = 'User deleted successfully!';
                    }
                } catch(PDOException $e) {
                    $error = 'Error deleting user: ' . $e->getMessage();
                }
                break;
                
            case 'update_user':
                $user_id = $_POST['user_id'];
                $name = $_POST['name'];
                $licence_no = $_POST['licence_no'];
                $plate_no = $_POST['plate_no'];
                
                try {
                    $query = "UPDATE users SET name = ?, licence_no = ?, plate_no = ? WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    if ($stmt->execute([$name, $licence_no, $plate_no, $user_id])) {
                        $success = 'User updated successfully!';
                    }
                } catch(PDOException $e) {
                    $error = 'Error updating user: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get all users with their incident counts
$users_query = "SELECT u.*, 
                       COUNT(i.id) as incident_count,
                       SUM(CASE WHEN i.status = 'Pending' THEN o.amount_tzs ELSE 0 END) as pending_amount
                FROM users u
                LEFT JOIN incidents i ON u.id = i.user_id
                LEFT JOIN offences o ON i.offence_id = o.id
                GROUP BY u.id
                ORDER BY u.created_at DESC";

$users_stmt = $conn->prepare($users_query);
$users_stmt->execute();
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - RTIMS Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <div class="dashboard-header">
                <div>
                    <h2>👥 Manage Drivers</h2>
                    <p>Add, edit, and manage registered drivers</p>
                </div>
                <div class="dashboard-nav">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="manage_users.php" class="active">Manage Users</a>
                    <a href="manage_officers.php">Manage Officers</a>
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

                <!-- Add New User Form -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                    <h3>➕ Add New Driver</h3>
                    <form method="POST" class="form-grid">
                        <input type="hidden" name="action" value="add_user">
                        
                        <div class="form-group">
                            <label for="name">Full Name:</label>
                            <input type="text" name="name" id="name" required>
                        </div>

                        <div class="form-group">
                            <label for="licence_no">Driving Licence Number:</label>
                            <input type="text" name="licence_no" id="licence_no" required>
                        </div>

                        <div class="form-group">
                            <label for="plate_no">Car Plate Number:</label>
                            <input type="text" name="plate_no" id="plate_no" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" name="password" id="password" required>
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <button type="submit" class="btn btn-primary">Add Driver</button>
                        </div>
                    </form>
                </div>

                <!-- Users Table -->
                <h3>Registered Drivers (<?php echo count($users); ?>)</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Licence Number</th>
                            <th>Plate Number</th>
                            <th>Incidents</th>
                            <th>Pending Amount</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['licence_no']); ?></td>
                                <td><?php echo htmlspecialchars($user['plate_no']); ?></td>
                                <td><?php echo $user['incident_count']; ?></td>
                                <td><?php echo formatCurrency($user['pending_amount'] ?? 0); ?></td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                            class="btn btn-secondary" style="font-size: 0.8rem; padding: 5px 10px; margin-right: 5px;">Edit</button>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirmDelete('Are you sure you want to delete this driver?')">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
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

    <!-- Edit User Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; width: 90%; max-width: 500px;">
            <h3>Edit Driver</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group">
                    <label for="edit_name">Full Name:</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>

                <div class="form-group">
                    <label for="edit_licence_no">Driving Licence Number:</label>
                    <input type="text" name="licence_no" id="edit_licence_no" required>
                </div>

                <div class="form-group">
                    <label for="edit_plate_no">Car Plate Number:</label>
                    <input type="text" name="plate_no" id="edit_plate_no" required>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Update Driver</button>
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_name').value = user.name;
            document.getElementById('edit_licence_no').value = user.licence_no;
            document.getElementById('edit_plate_no').value = user.plate_no;
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
