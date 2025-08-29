<?php
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Log logout activity (optional)
    if (isset($_SESSION['username'])) {
        error_log("User logout: " . $_SESSION['username'] . " at " . date('Y-m-d H:i:s'));
    }
    
    // Set logout message
    $logout_message = 'You have been successfully logged out.';
    
    // Clear all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Start new session for the message
    session_start();
    $_SESSION['logout_message'] = $logout_message;
}

// Redirect to login page
header('Location: index.php');
exit();
?>