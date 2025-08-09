<?php
require_once '../auth/auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireAuth();

$database = new Database();
$conn = $database->getConnection();

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Incident ID required']);
    exit;
}

$incident_id = $_GET['id'];

try {
    $query = "SELECT i.*, u.name as user_name, u.license_number, o.keyword, o.description as offence_description, o.amount_tzs,
                     off.name as officer_name, off.badge_number
              FROM incidents i
              JOIN users u ON i.user_id = u.id
              JOIN offences o ON i.offence_id = o.id
              JOIN officers off ON i.officer_id = off.id
              WHERE i.id = ?";
    
    // Check user role and add appropriate restrictions
    if ($_SESSION['user_role'] === 'officer') {
        $query .= " AND i.officer_id = ?";
        $params = [$incident_id, $_SESSION['user_id']];
    } elseif ($_SESSION['user_role'] === 'user') {
        $query .= " AND i.user_id = ?";
        $params = [$incident_id, $_SESSION['user_id']];
    } else {
        // Admin can see all incidents
        $params = [$incident_id];
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $incident = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($incident) {
        echo json_encode(['success' => true, 'incident' => $incident]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Incident not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
