<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../config/database.php');

// continue with authentication logic...

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function login($username, $password, $user_type) {
        try {
            switch($user_type) {
                case 'admin':
                    $query = "SELECT id, name, username, password FROM admins WHERE username = ?";
                    break;
                case 'officer':
                    $query = "SELECT id, name, username, password, badge_number FROM officers WHERE username = ?";
                    break;
                case 'user':
                    $query = "SELECT id, name, licence_no, plate_no, password FROM users WHERE licence_no = ?";
                    break;
                default:
                    return false;
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_type'] = $user_type;
                
                if ($user_type == 'officer') {
                    $_SESSION['badge_number'] = $user['badge_number'];
                } elseif ($user_type == 'user') {
                    $_SESSION['licence_no'] = $user['licence_no'];
                    $_SESSION['plate_no'] = $user['plate_no'];
                }
                
                return true;
            }
            return false;
        } catch(PDOException $exception) {
            return false;
        }
    }
    
    public function register($name, $licence_no, $plate_no, $password) {
        try {
            // Check if licence number already exists
            $check_query = "SELECT id FROM users WHERE licence_no = ?";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->execute([$licence_no]);
            
            if ($check_stmt->rowCount() > 0) {
                return false; // User already exists
            }
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (name, licence_no, plate_no, password) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            
            return $stmt->execute([$name, $licence_no, $plate_no, $hashed_password]);
        } catch(PDOException $exception) {
            return false;
        }
    }
    
    public function logout() {
        session_destroy();
        header("Location: ../index.php");
        exit();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function requireAuth($user_type = null) {
        if (!$this->isLoggedIn()) {
            header("Location: ../index.php");
            exit();
        }
        
        if ($user_type && $_SESSION['user_type'] !== $user_type) {
            header("Location: ../index.php");
            exit();
        }
    }
}
?>
