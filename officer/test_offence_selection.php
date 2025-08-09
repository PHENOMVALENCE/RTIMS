<?php
require_once '../auth/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAuth('officer');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<h2>Form Submission Test</h2>";
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h3>Validation Results:</h3>";
    $selected_offence_id = isset($_POST['selected_offence_id']) ? $_POST['selected_offence_id'] : '';
    echo "Selected Offence ID: '" . $selected_offence_id . "'<br>";
    echo "Is Empty: " . (empty($selected_offence_id) ? 'YES' : 'NO') . "<br>";
    echo "Length: " . strlen($selected_offence_id) . "<br>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Offence Selection - RTIMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <div class="dashboard-header">
                <div>
                    <h2>🧪 Test Offence Selection</h2>
                    <p>Debug form for testing offence selection</p>
                </div>
                <div class="dashboard-nav">
                    <a href="dashboard.php">← Back to Dashboard</a>
                </div>
            </div>

            <div class="dashboard-content">
                <form method="POST" style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <input type="hidden" name="selected_offence_id" id="selected_offence_id">
                    
                    <div class="form-group" style="position: relative;">
                        <label for="offence_description">Offence Description:</label>
                        <input type="text" name="custom_description" id="offence_description" 
                               placeholder="Start typing: overspeeding, parking, seatbelt, phone..." required>
                        <div id="offence_suggestions" class="offence-suggestions" style="display: none;"></div>
                    </div>

                    <!-- Debug Section -->
                    <div id="debug-info" style="background: #e9ecef; padding: 15px; margin: 15px 0; border-radius: 5px; font-family: monospace;">
                        <strong>Debug Info:</strong><br>
                        Selected Offence ID: <span id="debug-offence-id">None</span><br>
                        Selected Description: <span id="debug-description">None</span><br>
                        Hidden Field Value: <span id="debug-hidden-value">None</span>
                    </div>

                    <button type="submit" class="btn btn-primary">Test Submit</button>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        // Additional debugging for test page
        setInterval(() => {
            const hiddenField = document.getElementById("selected_offence_id")
            const debugHiddenValue = document.getElementById("debug-hidden-value")
            if (hiddenField && debugHiddenValue) {
                debugHiddenValue.textContent = hiddenField.value || 'Empty'
            }
        }, 1000)
    </script>
</body>
</html>
