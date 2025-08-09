<?php
require_once 'config/database.php';
require_once 'auth/auth.php';

// Start session to check authentication
session_start();

// Get the requested image
$image = isset($_GET['img']) ? $_GET['img'] : '';
$incident_id = isset($_GET['incident']) ? (int)$_GET['incident'] : 0;

if (empty($image) || !$incident_id) {
    header('HTTP/1.0 404 Not Found');
    exit('Image not found');
}

// Verify user has access to this image
$database = new Database();
$conn = $database->getConnection();

// Check if user is logged in and has access to this incident
$access_granted = false;

if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    switch ($_SESSION['user_type']) {
        case 'user':
            // Users can only view their own incident images
            $check_query = "SELECT image_path FROM incidents WHERE id = ? AND user_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->execute([$incident_id, $_SESSION['user_id']]);
            break;
            
        case 'officer':
            // Officers can view images of incidents they recorded
            $check_query = "SELECT image_path FROM incidents WHERE id = ? AND officer_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->execute([$incident_id, $_SESSION['user_id']]);
            break;
            
        case 'admin':
            // Admins can view all images
            $check_query = "SELECT image_path FROM incidents WHERE id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->execute([$incident_id]);
            break;
            
        default:
            header('HTTP/1.0 403 Forbidden');
            exit('Access denied');
    }
    
    $incident = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($incident && $incident['image_path'] === $image) {
        $access_granted = true;
    }
}

if (!$access_granted) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

// Serve the image
$image_path = __DIR__ . '/uploads/' . $image;

if (!file_exists($image_path)) {
    header('HTTP/1.0 404 Not Found');
    exit('Image file not found');
}

// Get image info
$image_info = getimagesize($image_path);
if (!$image_info) {
    header('HTTP/1.0 404 Not Found');
    exit('Invalid image file');
}

// Set appropriate headers
header('Content-Type: ' . $image_info['mime']);
header('Content-Length: ' . filesize($image_path));
header('Cache-Control: private, max-age=3600');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

// Output the image
readfile($image_path);
exit();
?>
