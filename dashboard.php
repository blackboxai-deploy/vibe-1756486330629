<?php
$page_title = "Dashboard";
require_once 'includes/header.php';
require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get dashboard statistics
    $stats = [];
    
    // Students count
    $result = $conn->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
    $stats['students'] = $result->fetch_assoc()['count'];
    
    // Faculty count
    $result = $conn->query("SELECT COUNT(*) as count FROM faculty WHERE status = 'active'");
    $stats['faculty'] = $result->fetch_assoc()['count'];
    
    // Courses count
    $result = $conn->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
    $stats['courses'] = $result->fetch_assoc()['count'];
    
    // Pending fees count
    $result = $conn->query("SELECT COUNT(*) as count FROM fees WHERE status IN ('pending', 'overdue')");
    $stats['pending_fees'] = $result->fetch_assoc()['count'];
    
    // Total fees collected this month
    $result = $conn->query("SELECT SUM(paid_amount) as total FROM fees WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())");
    $fees_row = $result->fetch_assoc();
    $stats['fees_collected'] = $fees_row['total'] ?? 0;
    
    // Recent activities based on user role
    $recent_activities = [];
    
    if ($_SESSION['role'] == 'admin') {
        // Recent student admissions
        $result = $conn->query("SELECT first_name, last_name, department, created_at FROM students ORDER BY created_at DESC LIMIT 5");
        while ($row = $result->fetch_assoc()) {
            $recent_activities[] = [
                'type' => 'student_admission',
                'message' => $row['first_name'] . ' ' . $row['last_name'] . ' admitted to ' . $row['department'],
                'time' => $row['created_at']
            ];
        }
        
        // Recent fee payments
        $result = $conn->query("SELECT s.first_name, s.last_name, f.amount, f.payment_date 
                              FROM fees f 
                              JOIN students s ON f.student_id = s.id 
                              WHERE f.status = 'paid' 
                              ORDER BY f.payment_date DESC LIMIT 5");
        while ($row = $result->fetch_assoc()) {
            $recent_activities[] = [
                'type' => 'fee_payment',
                'message' => $row['first_name'] . ' ' . $row['last_name'] . ' paid $' . number_format($row['amount'], 2),
                'time' => $row['payment_date']
            ];
        }
    } elseif ($_SESSION['role'] == 'faculty') {
        // Recent exam schedules for faculty courses
        $result = $conn->query("SELECT e.exam_name, c.course_name, e.exam_date 
                              FROM exams e 
                              JOIN courses c ON e.course_id = c.id 
                              JOIN faculty f ON c.faculty_id = f.id 
                              JOIN users u ON f.user_id = u.id 
                              WHERE u.id = {$_SESSION['user_id']} 
                              ORDER BY e.created_at DESC LIMIT 5");
        while ($row = $result->fetch_assoc()) {
            $recent_activities[] = [
                'type' => 'exam_schedule',
                'message' => $row['exam_name'] . ' scheduled for ' . $row['course_name'],
                'time' => $row['exam_date']
            ];
        }
    } else { // student
        // Recent exam results
        $result = $conn->query("SELECT e.exam_name, c.course_name, r.marks_obtained, r.total_marks, r.grade 
                              FROM results r 
                              JOIN exams e ON r.exam_id = e.id 
                              JOIN courses c ON e.course_id = c.id 
                              JOIN students s ON r.student_id = s.id 
                              JOIN users u ON s.user_id = u.id 
                              WHERE u.id = {$_SESSION['user_id']} 
                              ORDER BY r.created_at DESC LIMIT 5");
        while ($row = $result->fetch_assoc()) {
            $recent_activities[] = [
                'type' => 'exam_result',
                'message' => $row['exam_name'] . ' - Grade: ' . $row['grade'] . ' (' . $row['marks_obtained'] . '/' . $row['total_marks'] . ')',
                'time' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    // Sort activities by time
    usort($recent_activities, function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });
    $recent_activities = array_slice($recent_activities, 0, 8);
    
} catch (Exception $e) {
    $error_message = "Database error occurred.";
    error_log("Dashboard error: " . $e->getMessage());
}
?>

<!-- Dashboard Cards -->
<div class="dashboard-cards">
    <div class="card">
        <div class="card-header">
            <div class="card-icon students">👥</div>
            <div>
                <div class="card-title">Total Students</div>
            </div>
        </div>
        <div class="card-value"><?php echo number_format($stats['students'] ?? 0); ?></div>
        <div class="card-description">Active enrolled students</div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="card-icon faculty">👨‍🏫</div>
            <div>
                <div class="card-title">Faculty Members</div>
            </div>
        </div>
        <div class="card-value"><?php echo number_format($stats['faculty'] ?? 0); ?></div>
        <div class="card-description">Active faculty members</div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="card-icon courses">📚</div>
            <div>
                <div class="card-title">Total Courses</div>
            </div>
        </div>
        <div class="card-value"><?php echo number_format($stats['courses'] ?? 0); ?></div>
        <div class="card-description">Available courses</div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="card-icon fees">💰</div>
            <div>
                <div class="card-title">Fees Collected</div>
            </div>
        </div>
        <div class="card-value">$<?php echo number_format($stats['fees_collected'] ?? 0, 2); ?></div>
        <div class="card-description">This month</div>
    </div>
</div>

<!-- Quick Actions and Recent Activities -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px;">
    
    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #2c3e50; font-size: 20px;">Quick Actions</h3>
        </div>
        <div style="display: grid; gap: 15px;">
            
            <?php if ($_SESSION['role'] == 'admin'): ?>
                <a href="modules/students/add.php" class="btn btn-success" style="text-decoration: none; display: block; text-align: center;">
                    ➕ Add New Student
                </a>
                <a href="modules/faculty/add.php" class="btn btn-info" style="text-decoration: none; display: block; text-align: center;">
                    ➕ Add New Faculty
                </a>
                <a href="modules/courses/add.php" class="btn btn-warning" style="text-decoration: none; display: block; text-align: center;">
                    ➕ Add New Course
                </a>
                <a href="modules/reports/index.php" class="btn btn-primary" style="text-decoration: none; display: block; text-align: center;">
                    📈 Generate Reports
                </a>
            <?php elseif ($_SESSION['role'] == 'faculty'): ?>
                <a href="modules/students/index.php" class="btn btn-success" style="text-decoration: none; display: block; text-align: center;">
                    👥 View Students
                </a>
                <a href="modules/exams/add.php" class="btn btn-info" style="text-decoration: none; display: block; text-align: center;">
                    📝 Schedule Exam
                </a>
                <a href="modules/results/add.php" class="btn btn-warning" style="text-decoration: none; display: block; text-align: center;">
                    📋 Enter Results
                </a>
                <a href="modules/courses/index.php" class="btn btn-primary" style="text-decoration: none; display: block; text-align: center;">
                    📚 My Courses
                </a>
            <?php else: // student ?>
                <a href="modules/courses/index.php" class="btn btn-success" style="text-decoration: none; display: block; text-align: center;">
                    📚 My Courses
                </a>
                <a href="modules/results/index.php" class="btn btn-info" style="text-decoration: none; display: block; text-align: center;">
                    📋 View Results
                </a>
                <a href="modules/fees/index.php" class="btn btn-warning" style="text-decoration: none; display: block; text-align: center;">
                    💰 Fee Details
                </a>
                <a href="modules/exams/index.php" class="btn btn-primary" style="text-decoration: none; display: block; text-align: center;">
                    📝 Exam Schedule
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Activities -->
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #2c3e50; font-size: 20px;">Recent Activities</h3>
        </div>
        <div style="max-height: 400px; overflow-y: auto;">
            <?php if (!empty($recent_activities)): ?>
                <?php foreach ($recent_activities as $activity): ?>
                    <div style="padding: 12px 0; border-bottom: 1px solid #f1f3f4; display: flex; justify-content: space-between; align-items: flex-start;">
                        <div style="flex: 1;">
                            <div style="font-weight: 500; color: #2c3e50; margin-bottom: 4px;">
                                <?php 
                                $icon = '';
                                switch ($activity['type']) {
                                    case 'student_admission': $icon = '👥'; break;
                                    case 'fee_payment': $icon = '💰'; break;
                                    case 'exam_schedule': $icon = '📝'; break;
                                    case 'exam_result': $icon = '📋'; break;
                                    default: $icon = '📌';
                                }
                                echo $icon . ' ' . htmlspecialchars($activity['message']);
                                ?>
                            </div>
                            <div style="font-size: 12px; color: #6c757d;">
                                <?php echo date('M j, Y g:i A', strtotime($activity['time'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; color: #6c757d; padding: 40px 20px;">
                    <div style="font-size: 48px; margin-bottom: 15px;">📭</div>
                    <p>No recent activities to display.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- System Overview (Admin Only) -->
<?php if ($_SESSION['role'] == 'admin'): ?>
<div class="card" style="margin-top: 30px;">
    <div class="card-header" style="border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 20px;">
        <h3 style="margin: 0; color: #2c3e50; font-size: 20px;">System Overview</h3>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
        <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <div style="font-size: 24px; font-weight: 600; color: #e74c3c; margin-bottom: 5px;">
                <?php echo $stats['pending_fees'] ?? 0; ?>
            </div>
            <div style="color: #6c757d; font-size: 14px;">Pending Fees</div>
        </div>
        
        <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <div style="font-size: 24px; font-weight: 600; color: #28a745; margin-bottom: 5px;">
                <?php 
                $result = $conn->query("SELECT COUNT(*) as count FROM exams WHERE status = 'scheduled'");
                echo $result->fetch_assoc()['count'] ?? 0;
                ?>
            </div>
            <div style="color: #6c757d; font-size: 14px;">Scheduled Exams</div>
        </div>
        
        <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <div style="font-size: 24px; font-weight: 600; color: #007bff; margin-bottom: 5px;">
                <?php 
                $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
                echo $result->fetch_assoc()['count'] ?? 0;
                ?>
            </div>
            <div style="color: #6c757d; font-size: 14px;">Active Users</div>
        </div>
        
        <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <div style="font-size: 24px; font-weight: 600; color: #ffc107; margin-bottom: 5px;">
                <?php echo date('Y'); ?>
            </div>
            <div style="color: #6c757d; font-size: 14px;">Academic Year</div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
if (isset($db)) {
    $db->close();
}
require_once 'includes/footer.php';
?>