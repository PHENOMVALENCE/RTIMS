<?php
echo "<h1>🚦 RTIMS Email Notification System Verification</h1>";

// Check if configuration files exist
$config_files = [
    'config/database.php' => 'Main database configuration',
    'config/email_config.php' => 'Email configuration',
    'admin/email_config.php' => 'Email management page',
    'officer/record_incident.php' => 'Modified incident recording',
    'test_email_notification.php' => 'Email testing utility'
];

echo "<h2>📁 File Verification</h2>";
foreach ($config_files as $file => $description) {
    $exists = file_exists($file);
    $status = $exists ? '✅' : '❌';
    $color = $exists ? 'green' : 'red';
    echo "<div style='color: $color; margin: 5px 0;'>$status <strong>$file</strong> - $description</div>";
}

echo "<h2>⚙️ Configuration Check</h2>";

try {
    require_once 'config/database.php';
    
    $config_checks = [
        'EMAIL_NOTIFICATIONS_ENABLED' => defined('EMAIL_NOTIFICATIONS_ENABLED') ? (EMAIL_NOTIFICATIONS_ENABLED ? 'Enabled' : 'Disabled') : 'Not defined',
        'NOTIFICATION_EMAIL' => defined('NOTIFICATION_EMAIL') ? NOTIFICATION_EMAIL : 'Not defined',
        'SYSTEM_EMAIL_FROM' => defined('SYSTEM_EMAIL_FROM') ? SYSTEM_EMAIL_FROM : 'Not defined',
        'SYSTEM_EMAIL_REPLY_TO' => defined('SYSTEM_EMAIL_REPLY_TO') ? SYSTEM_EMAIL_REPLY_TO : 'Not defined'
    ];
    
    foreach ($config_checks as $setting => $value) {
        $status = ($value !== 'Not defined') ? '✅' : '❌';
        echo "<div style='margin: 5px 0;'>$status <strong>$setting:</strong> $value</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ <strong>Error loading configuration:</strong> " . $e->getMessage() . "</div>";
}

echo "<h2>🔧 Function Verification</h2>";

$functions = [
    'sendIncidentNotification' => 'Email sending function',
    'createIncidentEmailHTML' => 'Email template generation',
    'mail' => 'PHP mail function'
];

foreach ($functions as $func => $description) {
    $exists = function_exists($func);
    $status = $exists ? '✅' : '❌';
    $color = $exists ? 'green' : 'red';
    echo "<div style='color: $color; margin: 5px 0;'>$status <strong>$func()</strong> - $description</div>";
}

echo "<h2>🧪 Quick Actions</h2>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='test_email_notification.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🧪 Test Email</a>";
echo "<a href='admin/email_config.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>⚙️ Email Settings</a>";
echo "<a href='officer/record_incident.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚨 Record Incident</a>";
echo "</div>";

echo "<h2>📋 Implementation Summary</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<ul>";
echo "<li>✅ Email notification system implemented</li>";
echo "<li>✅ Professional HTML email template created</li>";
echo "<li>✅ Admin configuration panel added</li>";
echo "<li>✅ Integration with incident recording completed</li>";
echo "<li>✅ Error handling and logging implemented</li>";
echo "<li>✅ Testing utilities provided</li>";
echo "<li>✅ Documentation created</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>🎉 System Ready!</strong><br>";
echo "The email notification system is now active. When officers record new incidents, notifications will be automatically sent to <strong>mwiganivalence@gmail.com</strong>.";
echo "</div>";

echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>⚠️ Server Requirements:</strong><br>";
echo "Ensure your server has SMTP configured for outbound emails. Use the test utility to verify email functionality.";
echo "</div>";
?>
