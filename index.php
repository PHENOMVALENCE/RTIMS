<?php
session_start();
require_once 'auth/auth.php';

$auth = new Auth();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    switch($_SESSION['user_type']) {
        case 'admin':
            header("Location: admin/dashboard.php");
            break;
        case 'officer':
            header("Location: officer/dashboard.php");
            break;
        case 'user':
            header("Location: user/dashboard.php");
            break;
    }
    exit();
}

$error = '';
$success = '';

// Handle login
if (isset($_POST['action']) && $_POST['action'] == 'login' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];
    
    if ($auth->login($username, $password, $user_type)) {
        switch($user_type) {
            case 'admin':
                header("Location: admin/dashboard.php");
                break;
            case 'officer':
                header("Location: officer/dashboard.php");
                break;
            case 'user':
                header("Location: user/dashboard.php");
                break;
        }
        exit();
    } else {
        $error = 'Invalid credentials';
    }
}

// Handle registration
if (isset($_POST['action']) && $_POST['action'] == 'register' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $licence_no = $_POST['licence_no'];
    $plate_no = $_POST['plate_no'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif ($auth->register($name, $licence_no, $plate_no, $password)) {
        $success = 'Registration successful! You can now login.';
    } else {
        $error = 'Registration failed. Licence number may already exist.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RTIMS - Road Traffic Incident Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>🚦 RTIMS</h1>
            <p>Road Traffic Incident Management System - Tanzania</p>
        </header>

        <div class="auth-container">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="tabs">
                <button class="tab-button active" onclick="showTab('login')">Login</button>
                <button class="tab-button" onclick="showTab('register')">Register (Drivers)</button>
            </div>

            <!-- Login Form -->
            <div id="login" class="tab-content active">
                <form method="POST" class="auth-form">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label for="user_type">Login As:</label>
                        <select name="user_type" id="user_type" required onchange="updateLoginFields()">
                            <option value="user">Driver</option>
                            <option value="officer">Traffic Officer</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="username" id="username-label">Driving Licence Number:</label>
                        <input type="text" name="username" id="username" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" name="password" id="password" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Login</button>
                </form>

                <div class="demo-credentials">
                    <h4>Demo Credentials:</h4>
                    <p><strong>Admin:</strong> admin / password</p>
                    <p><strong>Officer:</strong> officer1 / password</p>
                    <p><strong>Driver:</strong> DL123456789 / password</p>
                </div>
            </div>

            <!-- Registration Form -->
            <div id="register" class="tab-content">
                <form method="POST" class="auth-form">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="form-group">
                        <label for="reg_name">Full Name:</label>
                        <input type="text" name="name" id="reg_name" required>
                    </div>

                    <div class="form-group">
                        <label for="licence_no">Driving Licence Number:</label>
                        <input type="text" name="licence_no" id="licence_no" required>
                    </div>

                    <div class="form-group">
                        <label for="plate_no">Car Plate Number:</label>
                        <input type="text" name="plate_no" id="plate_no" required>
                    </div>

                    <div class="form-group">
                        <label for="reg_password">Password:</label>
                        <input type="password" name="password" id="reg_password" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" name="confirm_password" id="confirm_password" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Register</button>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
