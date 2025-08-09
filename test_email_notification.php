<?php
require_once 'config/database.php';

// Test email notification functionality
echo "<h2>🚦 RTIMS Email Notification Test</h2>";

// Sample incident data for testing
$test_incident_data = [
    'control_number' => 'GOV-TZ-RTIMS-2025-00001',
    'driver_name' => 'John Doe',
    'licence_no' => 'MC123456789',
    'officer_name' => 'Officer Jane Smith',
    'badge_number' => 'TZ001',
    'offence_description' => 'Overspeeding (exceeding speed limit by 20 km/h)',
    'location' => 'Uhuru Street, near Kisutu Court, Dar es Salaam',
    'incident_date' => date('Y-m-d H:i:s'),
    'has_evidence' => true
];

echo "<h3>Test Incident Data:</h3>";
echo "<pre>";
print_r($test_incident_data);
echo "</pre>";

echo "<h3>Sending Test Email...</h3>";

try {
    $email_sent = sendIncidentNotification($test_incident_data);
    
    if ($email_sent) {
        echo "<div style='color: green; padding: 15px; background: #f0fff0; border: 1px solid #4caf50; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ <strong>Email sent successfully!</strong><br>";
        echo "📧 Notification sent to: mwiganivalence@gmail.com<br>";
        echo "📝 Subject: New Traffic Incident Recorded - Control #" . $test_incident_data['control_number'];
        echo "</div>";
    } else {
        echo "<div style='color: red; padding: 15px; background: #fff0f0; border: 1px solid #f44336; border-radius: 5px; margin: 10px 0;'>";
        echo "❌ <strong>Failed to send email</strong><br>";
        echo "Please check your server's mail configuration.";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div style='color: red; padding: 15px; background: #fff0f0; border: 1px solid #f44336; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<h3>Email Preview:</h3>";
echo "<div style='border: 1px solid #ddd; padding: 20px; background: #f9f9f9;'>";
echo "<strong>HTML Email Content:</strong><br><br>";

// Generate and display the email HTML for preview
$email_html = createIncidentEmailHTML($test_incident_data);
echo "<iframe style='width: 100%; height: 600px; border: 1px solid #ccc;' srcdoc='" . htmlspecialchars($email_html) . "'></iframe>";
echo "</div>";

echo "<hr>";
echo "<p><a href='officer/record_incident.php'>← Back to Record Incident</a></p>";
?>
