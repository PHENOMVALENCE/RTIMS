<?php
// Include email configuration
require_once __DIR__ . '/email_config.php';

class Database {
    private $host = 'localhost';
    private $db_name = 'rtims5';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Helper functions
function generateControlNumber($incident_id) {
    return 'GOV-TZ-RTIMS-' . date('Y') . '-' . str_pad($incident_id, 5, '0', STR_PAD_LEFT);
}

function formatCurrency($amount) {
    return 'TZS ' . number_format($amount, 2);
}

// Email notification functions
function sendIncidentNotification($incident_data) {
    // Check if email notifications are enabled
    if (!EMAIL_NOTIFICATIONS_ENABLED) {
        return false;
    }
    
    $notification_email = NOTIFICATION_EMAIL;
    
    $subject = 'New Traffic Incident Recorded - Control #' . $incident_data['control_number'];
    
    // Create HTML email content
    $html_message = createIncidentEmailHTML($incident_data);
    
    // Email headers for HTML content
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . SYSTEM_EMAIL_FROM,
        'Reply-To: ' . SYSTEM_EMAIL_REPLY_TO,
        'X-Mailer: PHP/' . phpversion(),
        'X-Priority: 2', // High priority for incident notifications
        'Importance: High'
    ];
    
    // Send email
    $success = mail($notification_email, $subject, $html_message, implode("\r\n", $headers));
    
    if (!$success) {
        error_log("Failed to send incident notification email for control number: " . $incident_data['control_number']);
    } else {
        error_log("Incident notification email sent successfully for control number: " . $incident_data['control_number'] . " to " . $notification_email);
    }
    
    return $success;
}

function createIncidentEmailHTML($incident_data) {
    $logoPath = '../public/placeholder-logo.png'; // Adjust path as needed
    
    $html = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>New Traffic Incident Notification</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;
                margin: 0;
                padding: 0;
                background-color: #f9f9f9;
                color: #333;
            }
            .email-container {
                max-width: 600px;
                margin: 20px auto;
                background-color: white;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
                overflow: hidden;
            }
            .header {
                background: linear-gradient(135deg, #1e40af, #3b82f6);
                color: white;
                padding: 30px;
                text-align: center;
            }
            .logo {
                max-height: 60px;
                margin-bottom: 20px;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
                font-weight: 700;
            }
            .header p {
                margin: 10px 0 0 0;
                opacity: 0.9;
                font-size: 16px;
            }
            .content {
                padding: 30px;
            }
            .alert-box {
                background-color: #fee2e2;
                border: 1px solid #fecaca;
                border-radius: 8px;
                padding: 15px;
                margin-bottom: 25px;
                text-align: center;
            }
            .alert-icon {
                font-size: 30px;
                margin-bottom: 10px;
                display: block;
            }
            .alert-title {
                font-weight: 700;
                color: #dc2626;
                margin-bottom: 5px;
            }
            .control-number {
                background-color: #f3f4f6;
                padding: 15px;
                border-radius: 8px;
                text-align: center;
                margin: 20px 0;
                border-left: 4px solid #3b82f6;
            }
            .control-number strong {
                font-size: 18px;
                color: #1e40af;
            }
            .details-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin: 25px 0;
            }
            .detail-item {
                background-color: #f8fafc;
                padding: 15px;
                border-radius: 8px;
                border-left: 3px solid #e5e7eb;
            }
            .detail-label {
                font-weight: 600;
                color: #6b7280;
                font-size: 12px;
                text-transform: uppercase;
                margin-bottom: 5px;
            }
            .detail-value {
                color: #111827;
                font-size: 14px;
                word-wrap: break-word;
            }
            .full-width {
                grid-column: 1 / -1;
            }
            .location-box {
                background-color: #fef3c7;
                border: 1px solid #fbbf24;
                border-radius: 8px;
                padding: 15px;
                margin: 20px 0;
            }
            .evidence-box {
                background-color: #f0fdf4;
                border: 1px solid #86efac;
                border-radius: 8px;
                padding: 15px;
                margin: 20px 0;
                text-align: center;
            }
            .footer {
                background-color: #f3f4f6;
                padding: 25px;
                text-align: center;
                border-top: 1px solid #e5e7eb;
            }
            .footer p {
                margin: 5px 0;
                font-size: 13px;
                color: #6b7280;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                background-color: #3b82f6;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                margin: 10px 0;
            }
            @media (max-width: 600px) {
                .details-grid {
                    grid-template-columns: 1fr;
                }
                .email-container {
                    margin: 10px;
                }
                .content {
                    padding: 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="header">
                <h1>🚦 RTIMS Notification</h1>
                <p>Road Traffic Incidents Management System</p>
            </div>
            
            <div class="content">
                <div class="alert-box">
                    <span class="alert-icon">🚨</span>
                    <div class="alert-title">New Traffic Incident Recorded</div>
                    <p>A new traffic incident has been recorded in the system and requires attention.</p>
                </div>
                
                <div class="control-number">
                    <div class="detail-label">Control Number</div>
                    <strong>' . htmlspecialchars($incident_data['control_number']) . '</strong>
                </div>
                
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Driver Name</div>
                        <div class="detail-value">' . htmlspecialchars($incident_data['driver_name']) . '</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Licence Number</div>
                        <div class="detail-value">' . htmlspecialchars($incident_data['licence_no']) . '</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Recording Officer</div>
                        <div class="detail-value">' . htmlspecialchars($incident_data['officer_name']) . '</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Badge Number</div>
                        <div class="detail-value">' . htmlspecialchars($incident_data['badge_number']) . '</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Offence Type</div>
                        <div class="detail-value">' . htmlspecialchars($incident_data['offence_description']) . '</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Date & Time</div>
                        <div class="detail-value">' . date('F j, Y \a\t g:i A', strtotime($incident_data['incident_date'])) . '</div>
                    </div>
                </div>
                
                <div class="location-box">
                    <div class="detail-label">📍 Incident Location</div>
                    <div class="detail-value" style="color: #92400e; font-weight: 500;">' . htmlspecialchars($incident_data['location']) . '</div>
                </div>';
                
    if ($incident_data['has_evidence']) {
        $html .= '
                <div class="evidence-box">
                    <div class="detail-label">📷 Evidence Photo</div>
                    <div class="detail-value" style="color: #166534; font-weight: 500;">✅ Evidence photo uploaded and available for review</div>
                </div>';
    }
    
    $html .= '
                <div style="text-align: center; margin: 30px 0;">
                    <p style="color: #6b7280; margin-bottom: 15px;">Access the full incident details in the RTIMS system:</p>
                    <a href="' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/RTIMS_101/admin/view_incidents.php" class="btn">
                        View Incident Details
                    </a>
                </div>
            </div>
            
            <div class="footer">
                <p><strong>Road Traffic Incidents Management System (RTIMS)</strong></p>
                <p>This is an automated notification from the RTIMS system.</p>
                <p>© ' . date('Y') . ' Government of Tanzania - Traffic Management Division</p>
                <p style="font-size: 11px; margin-top: 15px;">
                    This email was sent to mwiganivalence@gmail.com as part of the incident notification system.
                </p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

function uploadImage($file) {
    // Define upload directory relative to document root
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            error_log("Failed to create upload directory: " . $upload_dir);
            return false;
        }
    }
    
    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log("Upload error code: " . $file['error']);
        return false;
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $file_type = finfo_file($file_info, $file['tmp_name']);
    finfo_close($file_info);
    
    if (!in_array($file_type, $allowed_types)) {
        error_log("Invalid file type: " . $file_type);
        return false;
    }
    
    // Check file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        error_log("File too large: " . $file['size'] . " bytes");
        return false;
    }
    
    // Generate unique filename
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = 'evidence_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $file_extension;
    $full_path = $upload_dir . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $full_path)) {
        // Set proper permissions
        chmod($full_path, 0644);
        error_log("File uploaded successfully: " . $full_path);
        return $new_filename;
    } else {
        error_log("Failed to move uploaded file to: " . $full_path);
        return false;
    }
}

function getImageUrl($filename) {
    if (empty($filename)) {
        return null;
    }
    
    // Return direct URL to uploads folder
    $base_url = '/uploads/' . $filename;
    return $base_url;
}

function checkImageExists($filename) {
    if (empty($filename)) {
        return false;
    }
    
    $full_path = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $filename;
    return file_exists($full_path);
}
?>
