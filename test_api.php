<?php
// Simple test page to check if the API is working
require_once 'config/database.php';

echo "<h2>Testing RTIMS API</h2>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h3>Database Connection: ✅ Success</h3>";
    
    // Test offences query
    $query = "SELECT id, keyword, description, amount_tzs FROM offences LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $offences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Available Offences:</h3>";
    echo "<ul>";
    foreach ($offences as $offence) {
        echo "<li><strong>" . htmlspecialchars($offence['keyword']) . "</strong>: " . 
             htmlspecialchars($offence['description']) . " - TZS " . 
             number_format($offence['amount_tzs'], 2) . "</li>";
    }
    echo "</ul>";
    
    // Test search functionality
    echo "<h3>Testing Search for 'speed':</h3>";
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
    
    $search_term = '%speed%';
    $exact_term = 'speed%';
    
    $search_stmt = $conn->prepare($search_query);
    $search_stmt->execute([$search_term, $search_term, $exact_term, $exact_term]);
    $search_results = $search_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach ($search_results as $result) {
        echo "<li><strong>" . htmlspecialchars($result['keyword']) . "</strong>: " . 
             htmlspecialchars($result['description']) . " - TZS " . 
             number_format($result['amount_tzs'], 2) . "</li>";
    }
    echo "</ul>";
    
    echo "<h3>API Test: ✅ All systems working!</h3>";
    echo "<p><a href='index.php'>← Back to RTIMS</a></p>";
    
} catch(Exception $e) {
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
}
?>
