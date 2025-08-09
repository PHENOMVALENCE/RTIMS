<?php
require_once 'config/database.php';

echo "<h2>🧪 Testing Image Upload System</h2>";

// Check upload directory
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
$web_url = '/uploads/';

echo "<h3>📁 Directory Check:</h3>";
echo "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "<strong>Upload Directory:</strong> " . $upload_dir . "<br>";
echo "<strong>Web URL:</strong> " . $web_url . "<br>";
echo "<strong>Directory exists:</strong> " . (file_exists($upload_dir) ? '✅ Yes' : '❌ No') . "<br>";
echo "<strong>Directory writable:</strong> " . (is_writable($upload_dir) ? '✅ Yes' : '❌ No') . "<br>";

// Try to create directory if it doesn't exist
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        echo "✅ Directory created successfully<br>";
    } else {
        echo "❌ Failed to create directory<br>";
    }
}

// Check permissions
if (file_exists($upload_dir)) {
    $perms = fileperms($upload_dir);
    echo "<strong>Directory permissions:</strong> " . substr(sprintf('%o', $perms), -4) . "<br>";
}

// Test file upload form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['test_image'])) {
    echo "<h3>📤 Upload Test Result:</h3>";
    
    echo "<strong>File Info:</strong><br>";
    echo "Name: " . $_FILES['test_image']['name'] . "<br>";
    echo "Size: " . number_format($_FILES['test_image']['size']) . " bytes<br>";
    echo "Type: " . $_FILES['test_image']['type'] . "<br>";
    echo "Error: " . $_FILES['test_image']['error'] . "<br>";
    
    $result = uploadImage($_FILES['test_image']);
    
    if ($result) {
        echo "<br>✅ <strong>Upload successful!</strong><br>";
        echo "Filename: " . $result . "<br>";
        echo "Full path: " . $upload_dir . $result . "<br>";
        echo "File exists: " . (file_exists($upload_dir . $result) ? '✅ Yes' : '❌ No') . "<br>";
        
        $web_path = $web_url . $result;
        echo "Web URL: <a href='" . $web_path . "' target='_blank'>" . $web_path . "</a><br>";
        
        if (file_exists($upload_dir . $result)) {
            echo "<br><strong>📷 Image Preview:</strong><br>";
            echo "<img src='" . $web_path . "' style='max-width: 300px; border: 1px solid #ccc; border-radius: 8px;' onerror=\"this.style.display='none'; this.nextElementSibling.style.display='block';\"><br>";
            echo "<div style='display:none; color: red; padding: 10px; background: #ffe6e6; border-radius: 5px; margin: 10px 0;'>❌ Image failed to load. Check server permissions.</div>";
        }
    } else {
        echo "❌ <strong>Upload failed</strong><br>";
        echo "Check the error log for details.<br>";
    }
}

// List existing files
echo "<h3>📂 Existing Files in Upload Directory:</h3>";
if (file_exists($upload_dir)) {
    $files = glob($upload_dir . '*');
    if (empty($files)) {
        echo "No files found<br>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Filename</th><th>Size</th><th>Modified</th><th>Preview</th></tr>";
        foreach ($files as $file) {
            if (is_file($file)) {
                $filename = basename($file);
                $size = filesize($file);
                $modified = date('Y-m-d H:i:s', filemtime($file));
                $web_path = $web_url . $filename;
                
                echo "<tr>";
                echo "<td>" . $filename . "</td>";
                echo "<td>" . number_format($size) . " bytes</td>";
                echo "<td>" . $modified . "</td>";
                echo "<td>";
                
                if (in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
                    echo "<a href='" . $web_path . "' target='_blank'>";
                    echo "<img src='" . $web_path . "' style='width: 50px; height: 50px; object-fit: cover; border-radius: 4px;' onerror=\"this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA1MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjUwIiBoZWlnaHQ9IjUwIiBmaWxsPSIjZjBmMGYwIi8+Cjx0ZXh0IHg9IjI1IiB5PSIyNSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxMiIgZmlsbD0iIzk5OSI+4p2MPC90ZXh0Pgo8L3N2Zz4K'\">";
                    echo "</a>";
                } else {
                    echo "📄 " . strtoupper(pathinfo($filename, PATHINFO_EXTENSION));
                }
                
                echo "</td>";
                echo "</tr>";
            }
        }
        echo "</table>";
    }
} else {
    echo "❌ Upload directory does not exist<br>";
}

// Test direct access
echo "<h3>🔗 Direct Access Test:</h3>";
if (file_exists($upload_dir)) {
    $test_files = glob($upload_dir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
    if (!empty($test_files)) {
        $test_file = basename($test_files[0]);
        $test_url = $web_url . $test_file;
        echo "Testing direct access to: <a href='" . $test_url . "' target='_blank'>" . $test_url . "</a><br>";
        echo "<img src='" . $test_url . "' style='max-width: 200px; margin: 10px 0;' onerror=\"this.style.display='none'; this.nextElementSibling.style.display='block';\">";
        echo "<div style='display:none; color: red; padding: 10px; background: #ffe6e6; border-radius: 5px;'>❌ Direct access failed. Check .htaccess and server configuration.</div>";
    } else {
        echo "No image files found to test direct access.<br>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Image Upload Test - RTIMS</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        .test-form { 
            background: #e3f2fd; 
            padding: 20px; 
            border-radius: 8px; 
            margin: 20px 0; 
            border-left: 4px solid #2196f3;
        }
        table {
            width: 100%;
            margin: 10px 0;
        }
        th {
            background: #f0f0f0;
            padding: 8px;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .success { color: #4caf50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-form">
            <h3>📤 Test Image Upload</h3>
            <form method="POST" enctype="multipart/form-data">
                <p>
                    <label for="test_image">Select an image file (max 5MB):</label><br>
                    <input type="file" name="test_image" id="test_image" accept="image/*" required>
                </p>
                <p>
                    <button type="submit" style="background: #2196f3; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">Upload Test Image</button>
                </p>
            </form>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" style="background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">← Back to RTIMS</a>
        </div>
    </div>
</body>
</html>
