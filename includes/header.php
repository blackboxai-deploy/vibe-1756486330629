<?php
if (!isset($_SESSION)) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>College Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="College Management System - Manage students, faculty, courses, fees, exams, and results efficiently">
    <meta name="keywords" content="college management, student management, faculty management, course management">
    <meta name="author" content="College Management System">
    <link rel="icon" type="image/x-icon" href="https://placehold.co/32x32?text=CMS">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>College CMS</h3>
                <p>Management System</p>
            </div>
            <ul class="sidebar-nav">
                <li>
                    <a href="dashboard.php" class="<?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                        <span class="icon">📊</span>
                        Dashboard
                    </a>
                </li>
                
                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'faculty'): ?>
                <li>
                    <a href="modules/students/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/students/') !== false ? 'active' : ''; ?>">
                        <span class="icon">👥</span>
                        Students
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($_SESSION['role'] == 'admin'): ?>
                <li>
                    <a href="modules/faculty/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/faculty/') !== false ? 'active' : ''; ?>">
                        <span class="icon">👨‍🏫</span>
                        Faculty
                    </a>
                </li>
                <?php endif; ?>
                
                <li>
                    <a href="modules/courses/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/courses/') !== false ? 'active' : ''; ?>">
                        <span class="icon">📚</span>
                        Courses
                    </a>
                </li>
                
                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'student'): ?>
                <li>
                    <a href="modules/fees/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/fees/') !== false ? 'active' : ''; ?>">
                        <span class="icon">💰</span>
                        Fees
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'faculty'): ?>
                <li>
                    <a href="modules/exams/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/exams/') !== false ? 'active' : ''; ?>">
                        <span class="icon">📝</span>
                        Exams
                    </a>
                </li>
                <?php endif; ?>
                
                <li>
                    <a href="modules/results/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/results/') !== false ? 'active' : ''; ?>">
                        <span class="icon">📋</span>
                        Results
                    </a>
                </li>
                
                <?php if ($_SESSION['role'] == 'admin'): ?>
                <li>
                    <a href="modules/reports/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/reports/') !== false ? 'active' : ''; ?>">
                        <span class="icon">📈</span>
                        Reports
                    </a>
                </li>
                
                <li>
                    <a href="modules/users/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/users/') !== false ? 'active' : ''; ?>">
                        <span class="icon">👤</span>
                        Users
                    </a>
                </li>
                <?php endif; ?>
                
                <li>
                    <a href="logout.php" onclick="return confirm('Are you sure you want to logout?')">
                        <span class="icon">🚪</span>
                        Logout
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-header">
                <div class="header-title">
                    <button class="sidebar-toggle d-md-none" type="button">
                        <span>☰</span>
                    </button>
                    <h1><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
                </div>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <span class="badge badge-<?php echo $_SESSION['role'] == 'admin' ? 'danger' : ($_SESSION['role'] == 'faculty' ? 'info' : 'success'); ?>">
                        <?php echo ucfirst($_SESSION['role']); ?>
                    </span>
                    <a href="logout.php" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to logout?')">
                        Logout
                    </a>
                </div>
            </div>
            
            <div class="content-area">
                <!-- Alert Container -->
                <div id="alert-container"></div>