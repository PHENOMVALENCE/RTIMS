<?php
require_once '../auth/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAuth('officer');

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

// Handle new incident submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'record_incident') {
    $licence_no = $_POST['licence_no'];
    $offence_id = isset($_POST['selected_offence_id']) ? $_POST['selected_offence_id'] : '';
    $location = $_POST['location'];
    $custom_description = $_POST['custom_description'];
    
    // Validate that an offence was selected
    if (empty($offence_id) || $offence_id === '') {
        $error = 'Please select an offence from the suggestions.';
    } else {
        try {
            // Find user by licence number
            $user_query = "SELECT id, name FROM users WHERE licence_no = ?";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->execute([$licence_no]);
            $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $error = 'Driver with licence number ' . $licence_no . ' not found.';
            } else {
                // Handle image upload
                $image_path = null;
                if (isset($_FILES['incident_image']) && $_FILES['incident_image']['error'] == 0) {
                    $image_path = uploadImage($_FILES['incident_image']);
                    if (!$image_path) {
                        $error = 'Failed to upload image. Please check file format and size.';
                    }
                }
                
                if (!$error) {
                    // Insert incident
                    $insert_query = "INSERT INTO incidents (user_id, offence_id, officer_id, location, image_path, control_number, status) 
                                    VALUES (?, ?, ?, ?, ?, '', 'Pending')";
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->execute([$user['id'], $offence_id, $_SESSION['user_id'], $location, $image_path]);
                    
                    $incident_id = $conn->lastInsertId();
                    
                    // Generate and update control number
                    $control_number = generateControlNumber($incident_id);
                    $update_query = "UPDATE incidents SET control_number = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->execute([$control_number, $incident_id]);
                    
                    $success = 'Incident recorded successfully for ' . htmlspecialchars($user['name']) . '. Control Number: ' . $control_number;
                    if ($image_path) {
                        $success .= ' (Evidence photo uploaded)';
                    }
                    
                    // Clear form data
                    $_POST = array();
                }
            }
        } catch(PDOException $exception) {
            $error = 'Database error: ' . $exception->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Incident - RTIMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>🚦 RTIMS</h2>
                <p>Traffic Officer</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="record_incident.php" class="nav-item active">
                    <span class="nav-icon">🚨</span>
                    <span class="nav-text">Record Incident</span>
                </a>
                <a href="my_incidents.php" class="nav-item">
                    <span class="nav-icon">📋</span>
                    <span class="nav-text">My Incidents</span>
                </a>
                <a href="../test_image_upload.php" target="_blank" class="nav-item">
                    <span class="nav-icon">🧪</span>
                    <span class="nav-text">Test Upload</span>
                </a>
                <a href="../auth/logout.php" class="nav-item logout">
                    <span class="nav-icon">🚪</span>
                    <span class="nav-text">Logout</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
                    <small>Badge: <?php echo htmlspecialchars($_SESSION['badge_number']); ?></small>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h1>🚨 Record Traffic Incident</h1>
                <p>Document traffic violations and upload evidence</p>
            </div>

            <div class="content-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <!-- Record Incident Form -->
                <div class="card">
                    <div class="card-header">
                        <h3>📝 Incident Details</h3>
                        <p>Fill in all required information to record a traffic incident</p>
                    </div>
                    
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
                            <input type="hidden" name="action" value="record_incident">
                            <input type="hidden" name="selected_offence_id" id="selected_offence_id" value="">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="licence_no">Driver's Licence Number <span class="required">*</span></label>
                                    <input type="text" name="licence_no" id="licence_no" 
                                           placeholder="Enter driver's licence number" 
                                           value="<?php echo isset($_POST['licence_no']) ? htmlspecialchars($_POST['licence_no']) : ''; ?>" 
                                           required>
                                    <small class="form-help">Enter the exact licence number as shown on the driver's licence</small>
                                </div>

                                <div class="form-group" style="position: relative;">
                                    <label for="offence_description">Offence Type <span class="required">*</span></label>
                                    <input type="text" name="custom_description" id="offence_description" 
                                           placeholder="Start typing: overspeeding, parking, seatbelt, phone..." 
                                           value="<?php echo isset($_POST['custom_description']) ? htmlspecialchars($_POST['custom_description']) : ''; ?>" 
                                           required>
                                    <div id="offence_suggestions" class="offence-suggestions" style="display: none;"></div>
                                    <small class="form-help">💡 Type keywords to search for matching offences</small>
                                    <div id="selected-offence-info" style="color: #28a745; display: none; font-weight: bold; margin-top: 5px;">
                                        ✅ Offence selected
                                    </div>
                                </div>

                                <div class="form-group full-width">
                                    <label for="location">Incident Location <span class="required">*</span></label>
                                    <textarea name="location" id="location" rows="4" 
                                              placeholder="Provide detailed location information (street, landmarks, GPS coordinates if available)"
                                              required><?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?></textarea>
                                    <small class="form-help">Be as specific as possible to help identify the exact location</small>
                                </div>

                                <div class="form-group full-width">
                                    <label for="incident_image">Evidence Photo</label>
                                    <div class="image-upload-area">
                                        <div class="upload-controls">
                                            <button type="button" onclick="openCamera()" class="btn btn-secondary">
                                                📷 Capture Photo
                                            </button>
                                            <span class="upload-or">or</span>
                                            <input type="file" name="incident_image" id="incident_image" 
                                                   accept="image/*" onchange="previewImage(this)" class="file-input">
                                            <label for="incident_image" class="file-label">
                                                📁 Choose File
                                            </label>
                                        </div>
                                        
                                        <div id="image_preview_container" style="display: none;">
                                            <img id="image_preview" class="image-preview">
                                            <button type="button" onclick="removeImage()" class="btn btn-danger btn-sm">
                                                ❌ Remove
                                            </button>
                                        </div>
                                        
                                        <div id="upload_status" class="upload-status"></div>
                                        <small class="form-help">Optional: Upload clear evidence photo (max 5MB, JPEG/PNG/GIF)</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-large">
                                    🚨 Record Incident
                                </button>
                                <button type="reset" class="btn btn-secondary" onclick="resetForm()">
                                    🔄 Clear Form
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Camera Modal -->
                <div id="cameraModal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>📷 Capture Evidence Photo</h3>
                            <button type="button" onclick="closeCamera()" class="modal-close">×</button>
                        </div>
                        <div class="modal-body">
                            <video id="cameraVideo" autoplay></video>
                            <canvas id="cameraCanvas" style="display: none;"></canvas>
                        </div>
                        <div class="modal-footer">
                            <button type="button" onclick="capturePhoto()" class="btn btn-primary">📸 Take Photo</button>
                            <button type="button" onclick="closeCamera()" class="btn btn-secondary">Cancel</button>
                        </div>
                    </div>
                </div>

                <!-- Quick Tips -->
                <div class="card">
                    <div class="card-header">
                        <h3>💡 Quick Tips</h3>
                    </div>
                    <div class="card-body">
                        <div class="tips-grid">
                            <div class="tip-item">
                                <span class="tip-icon">🔍</span>
                                <div>
                                    <strong>Accurate Information</strong>
                                    <p>Double-check licence numbers and location details</p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <span class="tip-icon">📷</span>
                                <div>
                                    <strong>Clear Evidence</strong>
                                    <p>Take clear photos showing the violation and licence plate</p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <span class="tip-icon">📍</span>
                                <div>
                                    <strong>Precise Location</strong>
                                    <p>Include street names, landmarks, and direction of travel</p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <span class="tip-icon">⚡</span>
                                <div>
                                    <strong>Quick Processing</strong>
                                    <p>Submit incidents promptly for faster processing</p>
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
        function validateForm() {
            const selectedOffenceId = document.getElementById("selected_offence_id").value;
            
            if (!selectedOffenceId || selectedOffenceId.trim() === '') {
                alert("Please select an offence from the dropdown suggestions before submitting.");
                document.getElementById("offence_description").focus();
                return false;
            }
            
            return true;
        }
        
        function previewImage(input) {
            const preview = document.getElementById("image_preview");
            const container = document.getElementById("image_preview_container");
            const status = document.getElementById("upload_status");
            const file = input.files[0];

            if (file) {
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    status.innerHTML = '<span class="error">❌ File too large. Maximum size is 5MB.</span>';
                    input.value = '';
                    container.style.display = 'none';
                    return;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    status.innerHTML = '<span class="error">❌ Invalid file type. Please select a JPEG, PNG, or GIF image.</span>';
                    input.value = '';
                    container.style.display = 'none';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = (e) => {
                    preview.src = e.target.result;
                    container.style.display = 'block';
                    status.innerHTML = '<span class="success">✅ Image ready for upload (' + (file.size / 1024).toFixed(1) + ' KB)</span>';
                };
                reader.readAsDataURL(file);
            } else {
                container.style.display = 'none';
                status.innerHTML = '';
            }
        }
        
        function removeImage() {
            document.getElementById('incident_image').value = '';
            document.getElementById('image_preview_container').style.display = 'none';
            document.getElementById('upload_status').innerHTML = '';
        }
        
        function resetForm() {
            document.getElementById('selected_offence_id').value = '';
            document.getElementById('selected-offence-info').style.display = 'none';
            removeImage();
        }
    </script>
</body>
</html>
