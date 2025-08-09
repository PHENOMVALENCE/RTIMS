<?php
require_once '../auth/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAuth('officer');

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'record_incident') {
    $licence_no = $_POST['licence_no'];
    $offence_id = isset($_POST['selected_offence_id']) ? $_POST['selected_offence_id'] : '';
    $location = $_POST['location'];
    $custom_description = $_POST['custom_description'];
    
    if (empty($offence_id)) {
        $error = 'Please select an offence from the suggestions.';
    } else {
        try {
            $user_query = "SELECT id, name FROM users WHERE licence_no = ?";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->execute([$licence_no]);
            $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $error = 'Driver with licence number ' . $licence_no . ' not found.';
            } else {
                $image_path = null;
                if (isset($_FILES['incident_image']) && $_FILES['incident_image']['error'] == 0) {
                    $image_path = uploadImage($_FILES['incident_image']);
                    if (!$image_path) {
                        $error = 'Failed to upload image. Please check file format and size.';
                    }
                }
                
                if (!$error) {
                    $insert_query = "INSERT INTO incidents (user_id, offence_id, officer_id, location, image_path, control_number, status) 
                                    VALUES (?, ?, ?, ?, ?, '', 'Pending')";
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->execute([$user['id'], $offence_id, $_SESSION['user_id'], $location, $image_path]);
                    
                    $incident_id = $conn->lastInsertId();
                    $control_number = generateControlNumber($incident_id);
                    $update_query = "UPDATE incidents SET control_number = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->execute([$control_number, $incident_id]);
                    
                    $success = 'Incident recorded successfully for ' . htmlspecialchars($user['name']) . '. Control Number: ' . $control_number;
                    if ($image_path) {
                        $success .= ' (Evidence photo uploaded)';
                    }
                    
                    if (defined('EMAIL_NOTIFICATIONS_ENABLED') && EMAIL_NOTIFICATIONS_ENABLED) {
                        try {
                            $offence_query = "SELECT description FROM offences WHERE id = ?";
                            $offence_stmt = $conn->prepare($offence_query);
                            $offence_stmt->execute([$offence_id]);
                            $offence = $offence_stmt->fetch(PDO::FETCH_ASSOC);
                            
                            $officer_query = "SELECT name, badge_number FROM officers WHERE id = ?";
                            $officer_stmt = $conn->prepare($officer_query);
                            $officer_stmt->execute([$_SESSION['user_id']]);
                            $officer = $officer_stmt->fetch(PDO::FETCH_ASSOC);
                            
                            $incident_data = [
                                'control_number' => $control_number,
                                'driver_name' => $user['name'],
                                'licence_no' => $licence_no,
                                'officer_name' => $officer['name'] ?? $_SESSION['user_name'],
                                'badge_number' => $officer['badge_number'] ?? $_SESSION['badge_number'] ?? 'N/A',
                                'offence_description' => $offence['description'] ?? $custom_description,
                                'location' => $location,
                                'incident_date' => date('Y-m-d H:i:s'),
                                'has_evidence' => !empty($image_path)
                            ];
                            
                            $email_sent = sendIncidentNotification($incident_data);
                            if ($email_sent) {
                                $success .= ' (Email notification sent)';
                            }
                        } catch (Exception $e) {
                            error_log("Email notification error for incident " . $control_number . ": " . $e->getMessage());
                        }
                    }
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
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        .camera-modal {
            max-width: 90%;
            margin: 5% auto;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        .camera-container {
            position: relative;
            width: 100%;
            max-height: 70vh;
            overflow: hidden;
        }
        #cameraVideo {
            width: 100%;
            height: auto;
            background: black;
        }
        #cameraCanvas {
            display: none;
        }
        .camera-status {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 8px;
            border-radius: 4px;
        }
        .image-preview {
            max-width: 100%;
            max-height: 200px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .upload-status {
            margin-top: 10px;
        }
        .success { color: #28a745; }
        .error { color: #dc2626; }
        .offence-suggestions {
            position: absolute;
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .offence-suggestion-item {
            padding: 10px;
            cursor: pointer;
        }
        .offence-suggestion-item:hover {
            background: #f0f0f0;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>🚦 RTIMS</h2>
                <p>Traffic Officer</p>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span><span class="nav-text">Dashboard</span></a>
                <a href="record_incident.php" class="nav-item active"><span class="nav-icon">🚨</span><span class="nav-text">Record Incident</span></a>
                <a href="my_incidents.php" class="nav-item"><span class="nav-icon">📋</span><span class="nav-text">My Incidents</span></a>
                <a href="../test_image_upload.php" target="_blank" class="nav-item"><span class="nav-icon">🧪</span><span class="nav-text">Test Upload</span></a>
                <a href="../auth/logout.php" class="nav-item logout"><span class="nav-icon">🚪</span><span class="nav-text">Logout</span></a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
                    <small>Badge: <?php echo htmlspecialchars($_SESSION['badge_number']); ?></small>
                </div>
            </div>
        </div>

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
                                            <input type="file" name="incident_image" id="incident_image" accept="image/*" onchange="previewImage(this)">
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

                <div id="cameraModal" class="modal">
                    <div class="modal-content camera-modal">
                        <div class="modal-header">
                            <h3>📷 Capture Evidence Photo</h3>
                            <button type="button" onclick="closeCamera()" class="modal-close">×</button>
                        </div>
                        <div class="modal-body">
                            <div class="camera-container">
                                <video id="cameraVideo" autoplay playsinline muted></video>
                                <canvas id="cameraCanvas" style="display: none;"></canvas>
                                <div class="camera-status" id="cameraStatus">📹 Camera loading...</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" onclick="capturePhoto()" class="btn btn-primary" id="captureBtn">
                                📸 Take Photo
                            </button>
                            <button type="button" onclick="closeCamera()" class="btn btn-secondary">Cancel</button>
                        </div>
                    </div>
                </div>

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
     let cameraStream = null;

        function openCamera() {
            const modal = document.getElementById("cameraModal");
            const video = document.getElementById("cameraVideo");
            const canvas = document.getElementById("cameraCanvas");
            const status = document.getElementById("cameraStatus");
            
            if (!modal || !video || !canvas || !status) {
                alert("Camera interface not found. Please refresh the page.");
                return;
            }

            modal.style.display = "block";
            status.style.display = "block";
            status.textContent = "📹 Requesting camera access...";

            const constraints = {
                video: {
                    facingMode: "environment",
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            };

            navigator.mediaDevices.getUserMedia(constraints)
                .then(stream => {
                    cameraStream = stream;
                    video.srcObject = stream;
                    
                    video.onloadedmetadata = () => {
                        canvas.width = video.videoWidth;
                        canvas.height = video.videoHeight;
                        status.style.display = "none";
                    };
                })
                .catch(err => {
                    status.style.display = "block";
                    let message = "Failed to access camera. ";
                    if (err.name === "NotAllowedError") {
                        message += "Please allow camera permissions.";
                    } else if (err.name === "NotFoundError") {
                        message += "No camera found on this device.";
                    } else {
                        message += "Error: " + err.message;
                    }
                    status.textContent = message;
                    setTimeout(closeCamera, 3000);
                });
        }

        function closeCamera() {
            const modal = document.getElementById("cameraModal");
            const video = document.getElementById("cameraVideo");
            const status = document.getElementById("cameraStatus");
            
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
                cameraStream = null;
            }
            
            if (video) video.srcObject = null;
            if (modal) modal.style.display = "none";
            if (status) status.style.display = "none";
        }

        function capturePhoto() {
            const video = document.getElementById("cameraVideo");
            const canvas = document.getElementById("cameraCanvas");
            const preview = document.getElementById("image_preview");
            const container = document.getElementById("image_preview_container");
            const fileInput = document.getElementById("incident_image");
            const status = document.getElementById("upload_status");

            if (!video || !canvas || !preview || !container || !fileInput || !status) {
                alert("Required elements not found.");
                return;
            }

            if (!video.videoWidth || !video.videoHeight) {
                alert("Camera not ready. Please wait and try again.");
                return;
            }

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const context = canvas.getContext("2d");

            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            canvas.toBlob(blob => {
                if (!blob) {
                    status.innerHTML = '<span class="error">❌ Failed to capture image.</span>';
                    return;
                }

                const file = new File([blob], `evidence_${Date.now()}.jpg`, { type: "image/jpeg" });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;

                preview.src = URL.createObjectURL(blob);
                container.style.display = "block";
                status.innerHTML = `<span class="success">✅ Photo captured (${(blob.size / 1024).toFixed(1)} KB)</span>`;
                
                closeCamera();
            }, "image/jpeg", 0.9);
        }

        function previewImage(input) {
            const preview = document.getElementById("image_preview");
            const container = document.getElementById("image_preview_container");
            const status = document.getElementById("upload_status");
            const file = input.files[0];

            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                status.innerHTML = '<span class="error">❌ File too large (max 5MB).</span>';
                input.value = '';
                container.style.display = 'none';
                return;
            }

            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                status.innerHTML = '<span class="error">❌ Invalid file type (JPEG/PNG/GIF only).</span>';
                input.value = '';
                container.style.display = 'none';
                return;
            }

            const reader = new FileReader();
            reader.onload = e => {
                preview.src = e.target.result;
                container.style.display = 'block';
                status.innerHTML = `<span class="success">✅ Image ready (${(file.size / 1024).toFixed(1)} KB)</span>`;
            };
            reader.readAsDataURL(file);
        }

        function removeImage() {
            const input = document.getElementById('incident_image');
            const container = document.getElementById('image_preview_container');
            const status = document.getElementById('upload_status');
            
            input.value = '';
            container.style.display = 'none';
            status.innerHTML = '';
        }

        function validateForm() {
            const offenceId = document.getElementById("selected_offence_id").value;
            if (!offenceId) {
                alert("Please select an offence from the suggestions.");
                return false;
            }
            return true;
        }

        function resetForm() {
            document.getElementById('selected_offence_id').value = '';
            document.getElementById('selected-offence-info').style.display = 'none';
            removeImage();
        }

        document.getElementById("cameraModal").addEventListener("click", e => {
            if (e.target === e.currentTarget) closeCamera();
        });

        document.addEventListener("keydown", e => {
            if (e.key === "Escape" && document.getElementById("cameraModal").style.display === "block") {
                closeCamera();
            }
        });
    </script>
</body>
</html>