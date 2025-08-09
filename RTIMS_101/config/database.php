<?php
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
