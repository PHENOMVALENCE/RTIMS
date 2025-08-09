<?php
require_once '../auth/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAuth('user');

$database = new Database();
$conn = $database->getConnection();

// Get incident ID from URL
$incident_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$incident_id) {
    header("Location: dashboard.php");
    exit();
}

// Get incident details
$incident_query = "SELECT i.*, o.description as offence_description, o.amount_tzs, o.keyword,
                          off.name as officer_name, off.badge_number
                   FROM incidents i
                   JOIN offences o ON i.offence_id = o.id
                   JOIN officers off ON i.officer_id = off.id
                   WHERE i.id = ? AND i.user_id = ?";

$incident_stmt = $conn->prepare($incident_query);
$incident_stmt->execute([$incident_id, $_SESSION['user_id']]);
$incident = $incident_stmt->fetch(PDO::FETCH_ASSOC);

if (!$incident) {
    header("Location: dashboard.php");
    exit();
}

// Generate secure image URL if image exists
$image_url = null;
if ($incident['image_path']) {
    $image_url = '../image_handler.php?img=' . urlencode($incident['image_path']) . '&incident=' . $incident_id;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Details - RTIMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <div class="dashboard-header">
                <div>
                    <h2>📋 Incident Details</h2>
                    <p>Control Number: <?php echo htmlspecialchars($incident['control_number']); ?></p>
                </div>
                <div class="dashboard-nav">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="profile.php">Profile</a>
                    <a href="../auth/logout.php">Logout</a>
                </div>
            </div>

            <div class="dashboard-content">
                <div class="form-grid">
                    <!-- Incident Information -->
                    <div>
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                            <h3>🚨 Offence Information</h3>
                            
                            <div style="display: grid; gap: 15px; margin-top: 20px;">
                                <div style="display: flex; justify-content: space-between; padding: 10px; background: white; border-radius: 5px;">
                                    <span><strong>Control Number:</strong></span>
                                    <span style="font-family: monospace; font-weight: bold; color: #667eea;"><?php echo htmlspecialchars($incident['control_number']); ?></span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; padding: 10px; background: white; border-radius: 5px;">
                                    <span><strong>Offence Type:</strong></span>
                                    <span><?php echo htmlspecialchars($incident['keyword']); ?></span>
                                </div>
                                
                                <div style="padding: 10px; background: white; border-radius: 5px;">
                                    <strong>Description:</strong><br>
                                    <span style="margin-top: 5px; display: block;"><?php echo htmlspecialchars($incident['offence_description']); ?></span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; padding: 10px; background: white; border-radius: 5px;">
                                    <span><strong>Fine Amount:</strong></span>
                                    <span style="font-weight: bold; color: #dc3545; font-size: 1.1em;"><?php echo formatCurrency($incident['amount_tzs']); ?></span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; padding: 10px; background: white; border-radius: 5px;">
                                    <span><strong>Status:</strong></span>
                                    <span class="status-badge status-<?php echo strtolower($incident['status']); ?>">
                                        <?php echo $incident['status']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Location Information -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                            <h3>📍 Incident Location</h3>
                            <div style="padding: 15px; background: white; border-radius: 5px; margin-top: 15px;">
                                <?php echo nl2br(htmlspecialchars($incident['location'])); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Officer and Evidence -->
                    <div>
                        <!-- Officer Information -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                            <h3>👮‍♂️ Recording Officer</h3>
                            
                            <div style="display: grid; gap: 10px; margin-top: 15px;">
                                <div style="display: flex; justify-content: space-between; padding: 10px; background: white; border-radius: 5px;">
                                    <span><strong>Officer Name:</strong></span>
                                    <span><?php echo htmlspecialchars($incident['officer_name']); ?></span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; padding: 10px; background: white; border-radius: 5px;">
                                    <span><strong>Badge Number:</strong></span>
                                    <span><?php echo htmlspecialchars($incident['badge_number']); ?></span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; padding: 10px; background: white; border-radius: 5px;">
                                    <span><strong>Date Recorded:</strong></span>
                                    <span><?php echo date('M j, Y g:i A', strtotime($incident['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Evidence Photo -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                            <h3>📷 Evidence Photo</h3>
                            <?php if ($incident['image_path']): ?>
                                <?php 
                                $image_url = '/uploads/' . $incident['image_path'];
                                $image_exists = file_exists($_SERVER['DOCUMENT_ROOT'] . $image_url);
                                ?>
                                
                                <div style="text-align: center; margin-top: 15px;">
                                    <?php if ($image_exists): ?>
                                        <img src="<?php echo $image_url; ?>" 
                                             alt="Evidence Photo" 
                                             style="max-width: 100%; max-height: 300px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); cursor: pointer;"
                                             onclick="window.open('<?php echo $image_url; ?>', '_blank')"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                        
                                        <div style="display: none; text-align: center; padding: 30px; background: #f8d7da; color: #721c24; border-radius: 5px; margin-top: 15px;">
                                            <p>❌ Evidence photo could not be loaded</p>
                                            <small>Image URL: <?php echo htmlspecialchars($image_url); ?></small>
                                        </div>
                                        
                                        <br>
                                        <div style="margin-top: 10px;">
                                            <a href="view_evidence.php?id=<?php echo $incident['id']; ?>" class="btn btn-primary">🔍 View Full Size</a>
                                            <a href="<?php echo $image_url; ?>" target="_blank" class="btn btn-secondary">🔗 Open in New Tab</a>
                                        </div>
                                    <?php else: ?>
                                        <div style="text-align: center; padding: 30px; background: #f8d7da; color: #721c24; border-radius: 5px; margin-top: 15px;">
                                            <p>❌ Evidence photo file not found</p>
                                            <small>Expected: <?php echo htmlspecialchars($image_url); ?></small><br>
                                            <a href="../test_image_upload.php" target="_blank" class="btn btn-secondary" style="margin-top: 10px;">🧪 Test Upload System</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div style="text-align: center; padding: 30px; background: white; border-radius: 5px; margin-top: 15px; color: #666;">
                                    <p>📷 No evidence photo available</p>
                                    <small>The officer did not capture evidence for this incident</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <h3>📄 Actions</h3>
                    <div style="display: flex; gap: 15px; justify-content: center; margin-top: 15px; flex-wrap: wrap;">
                        <button onclick="window.print()" class="btn btn-primary">🖨️ Print Details</button>
                        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
                        
                        <?php if ($image_url): ?>
                            <a href="view_evidence.php?id=<?php echo $incident['id']; ?>" class="btn btn-primary">📷 View Evidence</a>
                        <?php endif; ?>
                        
                        <?php if ($incident['status'] == 'Pending'): ?>
                            <a href="pay_fine.php?id=<?php echo $incident['id']; ?>" class="btn btn-success">💳 Pay Fine (<?php echo formatCurrency($incident['amount_tzs']); ?>)</a>
                        <?php elseif ($incident['status'] == 'Paid'): ?>
                            <span class="btn btn-success" style="opacity: 0.7; cursor: not-allowed;">✅ Fine Paid</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Important Notice -->
                <div class="alert alert-info" style="margin-top: 20px;">
                    <h4>📢 Important Notice</h4>
                    <p><strong>Payment Instructions:</strong> You can pay your fine at any authorized payment center or through mobile money services. Keep your control number <strong><?php echo htmlspecialchars($incident['control_number']); ?></strong> for reference.</p>
                    <p><strong>Dispute Process:</strong> If you wish to dispute this offence, you can visit the nearest traffic office within 30 days of the incident date.</p>
                    <?php if ($image_url): ?>
                        <p><strong>Evidence:</strong> The evidence photo shows the circumstances of your traffic violation. You can view, download, or print this evidence for your records.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <style>
        @media print {
            .dashboard-header, .dashboard-nav, button, .alert {
                display: none !important;
            }
            
            .container {
                max-width: none;
                margin: 0;
                padding: 20px;
            }
            
            .dashboard {
                box-shadow: none;
                border: 1px solid #000;
            }
        }
    </style>
</body>
</html>
