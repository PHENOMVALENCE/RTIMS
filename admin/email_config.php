<?php
require_once '../auth/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAuth('admin');

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

// Handle configuration updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_email_config') {
    $notifications_enabled = isset($_POST['notifications_enabled']) ? 1 : 0;
    $notification_email = trim($_POST['notification_email']);
    $system_email_from = trim($_POST['system_email_from']);
    $system_email_reply_to = trim($_POST['system_email_reply_to']);
    
    // Validate email addresses
    if (!filter_var($notification_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid notification email address.';
    } elseif (!filter_var($system_email_reply_to, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid reply-to email address.';
    } else {
        // Update configuration file (this is a simple implementation)
        // In a production system, you might want to store these in a database
        $config_content = "<?php\n";
        $config_content .= "// Email configuration settings\n";
        $config_content .= "define('EMAIL_NOTIFICATIONS_ENABLED', " . ($notifications_enabled ? 'true' : 'false') . ");\n";
        $config_content .= "define('NOTIFICATION_EMAIL', '" . addslashes($notification_email) . "');\n";
        $config_content .= "define('SYSTEM_EMAIL_FROM', '" . addslashes($system_email_from) . "');\n";
        $config_content .= "define('SYSTEM_EMAIL_REPLY_TO', '" . addslashes($system_email_reply_to) . "');\n";
        $config_content .= "?>";
        
        $config_file = '../config/email_config.php';
        
        if (file_put_contents($config_file, $config_content)) {
            $success = 'Email configuration updated successfully.';
        } else {
            $error = 'Failed to update configuration file.';
        }
    }
}

// Read current configuration
$current_config = [
    'notifications_enabled' => defined('EMAIL_NOTIFICATIONS_ENABLED') ? EMAIL_NOTIFICATIONS_ENABLED : true,
    'notification_email' => defined('NOTIFICATION_EMAIL') ? NOTIFICATION_EMAIL : 'mwiganivalence@gmail.com',
    'system_email_from' => defined('SYSTEM_EMAIL_FROM') ? SYSTEM_EMAIL_FROM : 'RTIMS Notifications <noreply@rtims.gov.tz>',
    'system_email_reply_to' => defined('SYSTEM_EMAIL_REPLY_TO') ? SYSTEM_EMAIL_REPLY_TO : 'admin@rtims.gov.tz'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Configuration - RTIMS Admin</title>
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
                <a href="dashboard.php" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="view_incidents.php" class="nav-item">
                    <span class="nav-icon">🚨</span>
                    <span class="nav-text">View Incidents</span>
                </a>
                <a href="manage_users.php" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Manage Users</span>
                </a>
                <a href="manage_officers.php" class="nav-item">
                    <span class="nav-icon">👮</span>
                    <span class="nav-text">Manage Officers</span>
                </a>
                <a href="manage_offences.php" class="nav-item">
                    <span class="nav-icon">📋</span>
                    <span class="nav-text">Manage Offences</span>
                </a>
                <a href="email_config.php" class="nav-item active">
                    <span class="nav-icon">📧</span>
                    <span class="nav-text">Email Settings</span>
                </a>
                <a href="reports.php" class="nav-item">
                    <span class="nav-icon">📈</span>
                    <span class="nav-text">Reports</span>
                </a>
                <a href="../auth/logout.php" class="nav-item logout">
                    <span class="nav-icon">🚪</span>
                    <span class="nav-text">Logout</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
                    <small>Administrator</small>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h1>📧 Email Configuration</h1>
                <p>Manage email notification settings for incident reports</p>
            </div>

            <div class="content-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <!-- Email Configuration Form -->
                <div class="card">
                    <div class="card-header">
                        <h3>📧 Email Notification Settings</h3>
                        <p>Configure email notifications for new incident reports</p>
                    </div>
                    
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_email_config">
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="notifications_enabled" value="1" 
                                           <?php echo $current_config['notifications_enabled'] ? 'checked' : ''; ?>>
                                    Enable Email Notifications
                                </label>
                                <small class="form-help">When enabled, email notifications will be sent for new incidents</small>
                            </div>

                            <div class="form-group">
                                <label for="notification_email">Notification Email Address <span class="required">*</span></label>
                                <input type="email" name="notification_email" id="notification_email" 
                                       value="<?php echo htmlspecialchars($current_config['notification_email']); ?>" 
                                       required>
                                <small class="form-help">Email address to receive incident notifications</small>
                            </div>

                            <div class="form-group">
                                <label for="system_email_from">From Email Address <span class="required">*</span></label>
                                <input type="text" name="system_email_from" id="system_email_from" 
                                       value="<?php echo htmlspecialchars($current_config['system_email_from']); ?>" 
                                       required>
                                <small class="form-help">Format: "Display Name &lt;email@domain.com&gt;" or just "email@domain.com"</small>
                            </div>

                            <div class="form-group">
                                <label for="system_email_reply_to">Reply-To Email Address <span class="required">*</span></label>
                                <input type="email" name="system_email_reply_to" id="system_email_reply_to" 
                                       value="<?php echo htmlspecialchars($current_config['system_email_reply_to']); ?>" 
                                       required>
                                <small class="form-help">Email address for replies to system notifications</small>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    💾 Save Configuration
                                </button>
                                <a href="../test_email_notification.php" target="_blank" class="btn btn-secondary">
                                    🧪 Test Email
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Current Configuration Display -->
                <div class="card">
                    <div class="card-header">
                        <h3>📋 Current Configuration</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <strong>Notifications Status:</strong>
                                <span class="<?php echo $current_config['notifications_enabled'] ? 'text-success' : 'text-error'; ?>">
                                    <?php echo $current_config['notifications_enabled'] ? '✅ Enabled' : '❌ Disabled'; ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <strong>Notification Email:</strong>
                                <span><?php echo htmlspecialchars($current_config['notification_email']); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>From Address:</strong>
                                <span><?php echo htmlspecialchars($current_config['system_email_from']); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>Reply-To Address:</strong>
                                <span><?php echo htmlspecialchars($current_config['system_email_reply_to']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Requirements -->
                <div class="card">
                    <div class="card-header">
                        <h3>⚠️ Server Requirements</h3>
                    </div>
                    <div class="card-body">
                        <div class="requirements-list">
                            <div class="requirement-item">
                                <span class="req-icon">📧</span>
                                <div>
                                    <strong>Mail Function:</strong>
                                    <span class="<?php echo function_exists('mail') ? 'text-success' : 'text-error'; ?>">
                                        <?php echo function_exists('mail') ? '✅ Available' : '❌ Not Available'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="requirement-item">
                                <span class="req-icon">⚙️</span>
                                <div>
                                    <strong>SMTP Configuration:</strong>
                                    <p>Ensure your server has SMTP configured for outbound emails</p>
                                </div>
                            </div>
                            <div class="requirement-item">
                                <span class="req-icon">🔒</span>
                                <div>
                                    <strong>Security:</strong>
                                    <p>Use valid email addresses to avoid emails being marked as spam</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
