<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$query = isset($input['query']) ? trim($input['query']) : '';

// If no query, return some common offences
if (empty($query)) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $common_query = "SELECT id, keyword, description, amount_tzs 
                        FROM offences 
                        ORDER BY keyword 
                        LIMIT 5";
        
        $stmt = $conn->prepare($common_query);
        $stmt->execute();
        $offences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($offences);
        exit();
    } catch(PDOException $exception) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
        exit();
    }
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $search_query = "SELECT id, keyword, description, amount_tzs 
                     FROM offences 
                     WHERE keyword LIKE ? OR description LIKE ? 
                     ORDER BY 
                         CASE 
                             WHEN keyword LIKE ? THEN 1
                             WHEN description LIKE ? THEN 2
                             ELSE 3
                         END
                     LIMIT 10";
    
    $search_term = '%' . $query . '%';
    $exact_term = $query . '%';
    
    $stmt = $conn->prepare($search_query);
    $stmt->execute([$search_term, $search_term, $exact_term, $exact_term]);
    
    $offences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($offences);
    
} catch(PDOException $exception) {
    error_log("Database error in search_offences.php: " . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => $exception->getMessage()]);
}
?>
