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

// Get incident details with evidence
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

if (!$incident['image_path']) {
    header("Location: view_incident.php?id=" . $incident_id);
    exit();
}

// Generate direct image URL
$image_url = '/uploads/' . $incident['image_path'];
$full_image_path = $_SERVER['DOCUMENT_ROOT'] . $image_url;
$image_exists = file_exists($full_image_path);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evidence Photo - RTIMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .evidence-container {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        
        .evidence-image {
            max-width: 100%;
            max-height: 70vh;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            margin: 20px 0;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .evidence-image:hover {
            transform: scale(1.02);
        }
        
        .zoom-controls {
            margin: 20px 0;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .incident-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: left;
        }
        
        .image-error {
            background: #f8d7da;
            color: #721c24;
            padding: 30px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <div class="dashboard-header">
                <div>
                    <h2>📷 Evidence Photo</h2>
                    <p>Control Number: <?php echo htmlspecialchars($incident['control_number']); ?></p>
                </div>
                <div class="dashboard-nav">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="view_incident.php?id=<?php echo $incident_id; ?>">← Back to Incident</a>
                    <a href="../auth/logout.php">Logout</a>
                </div>
            </div>

            <div class="dashboard-content">
                <div class="evidence-container">
                    <!-- Incident Summary -->
                    <div class="incident-summary">
                        <h3>📋 Incident Summary</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                            <div>
                                <strong>Offence:</strong><br>
                                <?php echo htmlspecialchars($incident['offence_description']); ?>
                            </div>
                            <div>
                                <strong>Officer:</strong><br>
                                <?php echo htmlspecialchars($incident['officer_name']); ?> (<?php echo htmlspecialchars($incident['badge_number']); ?>)
                            </div>
                            <div>
                                <strong>Date:</strong><br>
                                <?php echo date('M j, Y g:i A', strtotime($incident['created_at'])); ?>
                            </div>
                            <div>
                                <strong>Fine Amount:</strong><br>
                                <span style="color: #dc3545; font-weight: bold;"><?php echo formatCurrency($incident['amount_tzs']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Evidence Photo -->
                    <div>
                        <h3>📸 Evidence Photo</h3>
                        
                        <?php if ($image_exists): ?>
                            <img src="<?php echo $image_url; ?>" 
                                 alt="Traffic Incident Evidence" 
                                 class="evidence-image" 
                                 id="evidenceImage"
                                 onclick="toggleFullscreen(this)"
                                 onerror="showImageError()">
                            
                            <!-- Zoom Controls -->
                            <div class="zoom-controls">
                                <button onclick="zoomImage(-0.2)" class="btn btn-secondary">🔍- Zoom Out</button>
                                <button onclick="resetZoom()" class="btn btn-secondary">↻ Reset</button>
                                <button onclick="zoomImage(0.2)" class="btn btn-secondary">🔍+ Zoom In</button>
                                <button onclick="downloadImage()" class="btn btn-primary">💾 Download</button>
                                <button onclick="openInNewTab()" class="btn btn-secondary">🔗 Open in New Tab</button>
                            </div>
                            
                            <p style="color: #666; font-size: 0.9rem; margin-top: 10px;">
                                💡 <strong>Tip:</strong> Click on the image to view in fullscreen mode<br>
                                🎮 <strong>Keyboard:</strong> +/- to zoom, 0 to reset, Escape to exit fullscreen
                            </p>
                        <?php else: ?>
                            <div class="image-error">
                                <h4>❌ Evidence Photo Not Available</h4>
                                <p>The evidence photo could not be found. This might be due to:</p>
                                <ul style="text-align: left; margin: 10px 0;">
                                    <li>The image file may have been moved or deleted</li>
                                    <li>There may be a server configuration issue</li>
                                    <li>The file path may be incorrect</li>
                                </ul>
                                <p><strong>File Path:</strong> <?php echo htmlspecialchars($image_url); ?></p>
                                <p><strong>Expected Location:</strong> <?php echo htmlspecialchars($full_image_path); ?></p>
                                <button onclick="location.reload()" class="btn btn-primary">🔄 Try Again</button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Action Buttons -->
                    <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                            <a href="view_incident.php?id=<?php echo $incident_id; ?>" class="btn btn-secondary">← Back to Details</a>
                            <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
                            <?php if ($incident['status'] == 'Pending'): ?>
                                <a href="pay_fine.php?id=<?php echo $incident_id; ?>" class="btn btn-success">💳 Pay Fine</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentZoom = 1;
        
        function showImageError() {
            const img = document.getElementById('evidenceImage');
            if (img) {
                img.style.display = 'none';
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'image-error';
                errorDiv.innerHTML = `
                    <h4>❌ Image Loading Failed</h4>
                    <p>The evidence photo could not be loaded from the server.</p>
                    <p><strong>Image URL:</strong> <?php echo htmlspecialchars($image_url); ?></p>
                    <button onclick="location.reload()" class="btn btn-primary">🔄 Try Again</button>
                    <a href="../test_image_upload.php" target="_blank" class="btn btn-secondary">🧪 Test Upload System</a>
                `;
                
                img.parentNode.insertBefore(errorDiv, img);
            }
        }
        
        function zoomImage(factor) {
            const img = document.getElementById('evidenceImage');
            if (img) {
                currentZoom += factor;
                if (currentZoom < 0.5) currentZoom = 0.5;
                if (currentZoom > 3) currentZoom = 3;
                img.style.transform = `scale(${currentZoom})`;
                img.style.transition = 'transform 0.3s ease';
            }
        }
        
        function resetZoom() {
            const img = document.getElementById('evidenceImage');
            if (img) {
                currentZoom = 1;
                img.style.transform = 'scale(1)';
                img.style.transition = 'transform 0.3s ease';
            }
        }
        
        function toggleFullscreen(img) {
            if (!document.fullscreenElement) {
                img.requestFullscreen().catch(err => {
                    console.log('Fullscreen not supported');
                    alert('Fullscreen mode not supported in this browser');
                });
            } else {
                document.exitFullscreen();
            }
        }
        
        function downloadImage() {
            const link = document.createElement('a');
            link.href = '<?php echo $image_url; ?>';
            link.download = 'evidence_<?php echo $incident['control_number']; ?>.jpg';
            link.click();
        }
        
        function openInNewTab() {
            window.open('<?php echo $image_url; ?>', '_blank');
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            switch(e.key) {
                case '+':
                case '=':
                    e.preventDefault();
                    zoomImage(0.2);
                    break;
                case '-':
                    e.preventDefault();
                    zoomImage(-0.2);
                    break;
                case '0':
                    e.preventDefault();
                    resetZoom();
                    break;
                case 'Escape':
                    if (document.fullscreenElement) {
                        document.exitFullscreen();
                    }
                    break;
            }
        });
    </script>
</body>
</html>
