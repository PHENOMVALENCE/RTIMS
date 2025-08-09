<?php
require_once '../auth/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAuth('user');

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    $name = $_POST['name'];
    $plate_no = $_POST['plate_no'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    try {
        // Get current user data
        $user_query = "SELECT * FROM users WHERE id = ?";
        $user_stmt = $conn->prepare($user_query);
        $user_stmt->execute([$_SESSION['user_id']]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify current password if new password is provided
        if (!empty($new_password)) {
            if (!password_verify($current_password, $user['password'])) {
                $error = 'Current password is incorrect.';
            } elseif ($new_password !== $confirm_password) {
                $error = 'New passwords do not match.';
            } elseif (strlen($new_password) < 6) {
                $error = 'New password must be at least 6 characters long.';
            } else {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE users SET name = ?, plate_no = ?, password = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                if ($update_stmt->execute([$name, $plate_no, $hashed_password, $_SESSION['user_id']])) {
                    $_SESSION['user_name'] = $name;
                    $_SESSION['plate_no'] = $plate_no;
                    $success = 'Profile updated successfully with new password!';
                }
            }
        } else {
            // Update without password change
            $update_query = "UPDATE users SET name = ?, plate_no = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            if ($update_stmt->execute([$name, $plate_no, $_SESSION['user_id']])) {
                $_SESSION['user_name'] = $name;
                $_SESSION['plate_no'] = $plate_no;
                $success = 'Profile updated successfully!';
            }
        }
    } catch(PDOException $e) {
        $error = 'Error updating profile: ' . $e->getMessage();
    }
}

// Get user data
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Get user statistics
$stats_query = "SELECT 
                    COUNT(*) as total_incidents,
                    COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_incidents,
                    COUNT(CASE WHEN status = 'Paid' THEN 1 END) as paid_incidents,
                    SUM(CASE WHEN status = 'Pending' THEN o.amount_tzs ELSE 0 END) as total_pending,
                    SUM(CASE WHEN status = 'Paid' THEN o.amount_tzs ELSE 0 END) as total_paid
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
    <title>My Profile - RTIMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <div class="dashboard-header">
                <div>
                    <h2>👤 My Profile</h2>
                    <p>Manage your account information</p>
                </div>
                <div class="dashboard-nav">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="profile.php" class="active">Profile</a>
                    <a href="payment_history.php">Payment History</a>
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

                <div class="form-grid">
                    <!-- Profile Information -->
                    <div>
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                            <h3>📋 Profile Information</h3>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="form-group">
                                    <label for="name">Full Name:</label>
                                    <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="licence_no">Driving Licence Number:</label>
                                    <input type="text" name="licence_no" id="licence_no" 
                                           value="<?php echo htmlspecialchars($user['licence_no']); ?>" readonly>
                                    <small>Licence number cannot be changed</small>
                                </div>

                                <div class="form-group">
                                    <label for="plate_no">Car Plate Number:</label>
                                    <input type="text" name="plate_no" id="plate_no" 
                                           value="<?php echo htmlspecialchars($user['plate_no']); ?>" required>
                                </div>

                                <h4 style="margin-top: 30px; margin-bottom: 15px;">Change Password (Optional)</h4>
                                
                                <div class="form-group">
                                    <label for="current_password">Current Password:</label>
                                    <input type="password" name="current_password" id="current_password">
                                </div>

                                <div class="form-group">
                                    <label for="new_password">New Password:</label>
                                    <input type="password" name="new_password" id="new_password" minlength="6">
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password:</label>
                                    <input type="password" name="confirm_password" id="confirm_password" minlength="6">
                                </div>

                                <button type="submit" class="btn btn-primary">Update Profile</button>
                            </form>
                        </div>
                    </div>

                    <!-- Account Statistics -->
                    <div>
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                            <h3>📊 Account Statistics</h3>
                            
                            <div style="display: grid; gap: 15px; margin-top: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: white; border-radius: 5px;">
                                    <span>Total Incidents:</span>
                                    <strong><?php echo $stats['total_incidents']; ?></strong>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: white; border-radius: 5px;">
                                    <span>Pending Cases:</span>
                                    <strong style="color: #856404;"><?php echo $stats['pending_incidents']; ?></strong>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: white; border-radius: 5px;">
                                    <span>Paid Cases:</span>
                                    <strong style="color: #155724;"><?php echo $stats['paid_incidents']; ?></strong>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: white; border-radius: 5px;">
                                    <span>Total Pending Amount:</span>
                                    <strong style="color: #856404;"><?php echo formatCurrency($stats['total_pending'] ?? 0); ?></strong>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: white; border-radius: 5px;">
                                    <span>Total Paid Amount:</span>
                                    <strong style="color: #155724;"><?php echo formatCurrency($stats['total_paid'] ?? 0); ?></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Account Details -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                            <h3>ℹ️ Account Details</h3>
                            
                            <div style="display: grid; gap: 10px; margin-top: 20px;">
                                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e1e5e9;">
                                    <span>Account ID:</span>
                                    <strong><?php echo $user['id']; ?></strong>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e1e5e9;">
                                    <span>Member Since:</span>
                                    <strong><?php echo date('M j, Y', strtotime($user['created_at'])); ?></strong>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                                    <span>Account Status:</span>
                                    <strong style="color: #155724;">Active</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword && confirmPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Require current password if new password is entered
        document.getElementById('new_password').addEventListener('input', function() {
            const currentPasswordField = document.getElementById('current_password');
            if (this.value) {
                currentPasswordField.required = true;
            } else {
                currentPasswordField.required = false;
            }
        });
    </script>
</body>
</html>
