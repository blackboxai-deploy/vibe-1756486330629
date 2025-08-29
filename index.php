<?php
session_start();

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit();
}

require_once 'config/database.php';

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            // Prepare statement to prevent SQL injection
            $stmt = $conn->prepare("SELECT id, username, password, email, role, status FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if ($user['status'] === 'inactive') {
                    $error_message = 'Your account has been deactivated. Please contact the administrator.';
                } elseif (password_verify($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['login_time'] = time();
                    
                    // Update last login time
                    $update_stmt = $conn->prepare("UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $update_stmt->bind_param("i", $user['id']);
                    $update_stmt->execute();
                    
                    // Redirect to dashboard
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $error_message = 'Invalid username or password.';
                }
            } else {
                $error_message = 'Invalid username or password.';
            }
            
            $stmt->close();
            $db->close();
        } catch (Exception $e) {
            $error_message = 'Database connection failed. Please try again later.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// Check for logout message
if (isset($_SESSION['logout_message'])) {
    $success_message = $_SESSION['logout_message'];
    unset($_SESSION['logout_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - College Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="Login to College Management System">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/x-icon" href="https://placehold.co/32x32?text=CMS">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="https://placehold.co/80x80?text=College+Management+System+Logo" alt="College Management System Logo" style="margin-bottom: 20px; border-radius: 50%;">
                <h1>College Management System</h1>
                <p>Please sign in to your account</p>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="index.php" data-validate="true">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        value="<?php echo htmlspecialchars($username ?? ''); ?>"
                        required 
                        autocomplete="username"
                        placeholder="Enter your username or email"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        required 
                        autocomplete="current-password"
                        placeholder="Enter your password"
                    >
                </div>
                
                <button type="submit" class="btn btn-primary">
                    Sign In
                </button>
            </form>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center;">
                <h4 style="color: #666; font-size: 16px; margin-bottom: 15px;">Demo Login Credentials</h4>
                <div style="text-align: left; background: #f8f9fa; padding: 15px; border-radius: 8px; font-size: 14px;">
                    <div style="margin-bottom: 10px;">
                        <strong>Administrator:</strong><br>
                        Username: <code>admin</code><br>
                        Password: <code>admin123</code>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <strong>Faculty:</strong><br>
                        Username: <code>faculty</code><br>
                        Password: <code>faculty123</code>
                    </div>
                    <div>
                        <strong>Student:</strong><br>
                        Username: <code>student</code><br>
                        Password: <code>student123</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/script.js"></script>
    <script>
        // Auto-focus username field
        document.addEventListener('DOMContentLoaded', function() {
            const usernameField = document.getElementById('username');
            if (usernameField && !usernameField.value) {
                usernameField.focus();
            }
            
            // Add loading state to form submission
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="spinner"></span> Signing In...';
                    }
                });
            }
        });
    </script>
</body>
</html>