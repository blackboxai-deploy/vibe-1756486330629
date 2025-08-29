<?php
$page_title = "Reports";
require_once '../../includes/header.php';
require_once '../../config/database.php';

// Check permissions - only admin can access reports
if ($_SESSION['role'] != 'admin') {
    $_SESSION['error_message'] = 'Access denied. Only administrators can access reports.';
    header('Location: ../../dashboard.php');
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get summary statistics for reports
    $stats = [];
    
    // Students stats
    $result = $conn->query("SELECT COUNT(*) as total, 
                                   SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                                   SUM(CASE WHEN status = 'graduated' THEN 1 ELSE 0 END) as graduated
                           FROM students");
    $stats['students'] = $result->fetch_assoc();
    
    // Faculty stats
    $result = $conn->query("SELECT COUNT(*) as total,
                                   SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active
                           FROM faculty");
    $stats['faculty'] = $result->fetch_assoc();
    
    // Fee stats
    $result = $conn->query("SELECT SUM(amount) as total_fees,
                                   SUM(paid_amount) as collected_fees,
                                   SUM(amount - paid_amount) as pending_fees,
                                   COUNT(*) as total_records
                           FROM fees");
    $stats['fees'] = $result->fetch_assoc();
    
    // Exam stats
    $result = $conn->query("SELECT COUNT(*) as total_exams,
                                   SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                                   SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled
                           FROM exams");
    $stats['exams'] = $result->fetch_assoc();
    
} catch (Exception $e) {
    $error_message = "Database error occurred.";
    error_log("Reports error: " . $e->getMessage());
}
?>

<div class="page-header" style="margin-bottom: 30px;">
    <h2 style="margin: 0; color: #2c3e50;">System Reports</h2>
    <p style="margin: 5px 0 0 0; color: #6c757d;">Generate and view various system reports</p>
</div>

<!-- Quick Stats Overview -->
<div class="dashboard-cards" style="margin-bottom: 40px;">
    <div class="card">
        <div class="card-header">
            <div class="card-icon students">👥</div>
            <div class="card-title">Student Statistics</div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 14px;">
            <div>Total: <strong><?php echo $stats['students']['total'] ?? 0; ?></strong></div>
            <div>Active: <strong><?php echo $stats['students']['active'] ?? 0; ?></strong></div>
            <div>Graduated: <strong><?php echo $stats['students']['graduated'] ?? 0; ?></strong></div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="card-icon faculty">👨‍🏫</div>
            <div class="card-title">Faculty Statistics</div>
        </div>
        <div style="font-size: 14px;">
            <div>Total: <strong><?php echo $stats['faculty']['total'] ?? 0; ?></strong></div>
            <div>Active: <strong><?php echo $stats['faculty']['active'] ?? 0; ?></strong></div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="card-icon fees">💰</div>
            <div class="card-title">Fee Statistics</div>
        </div>
        <div style="font-size: 14px;">
            <div>Total Fees: <strong>$<?php echo number_format($stats['fees']['total_fees'] ?? 0, 2); ?></strong></div>
            <div>Collected: <strong>$<?php echo number_format($stats['fees']['collected_fees'] ?? 0, 2); ?></strong></div>
            <div>Pending: <strong>$<?php echo number_format($stats['fees']['pending_fees'] ?? 0, 2); ?></strong></div>
        </div>
    </div>
</div>

<!-- Available Reports -->
<div class="row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px;">
    
    <!-- Student Reports -->
    <div class="card">
        <div class="card-header">
            <h3>👥 Student Reports</h3>
        </div>
        <div style="padding: 20px;">
            <div style="margin-bottom: 15px;">
                <a href="student-list.php" class="btn btn-info w-100" style="text-decoration: none; margin-bottom: 10px;">
                    📄 Complete Student List
                </a>
            </div>
            <div style="margin-bottom: 15px;">
                <a href="department-wise-students.php" class="btn btn-info w-100" style="text-decoration: none; margin-bottom: 10px;">
                    🏢 Department Wise Students
                </a>
            </div>
            <div style="margin-bottom: 15px;">
                <a href="student-performance.php" class="btn btn-info w-100" style="text-decoration: none; margin-bottom: 10px;">
                    📊 Student Performance Report
                </a>
            </div>
            <div>
                <a href="graduated-students.php" class="btn btn-info w-100" style="text-decoration: none;">
                    🎓 Graduated Students
                </a>
            </div>
        </div>
    </div>
    
    <!-- Fee Reports -->
    <div class="card">
        <div class="card-header">
            <h3>💰 Fee Reports</h3>
        </div>
        <div style="padding: 20px;">
            <div style="margin-bottom: 15px;">
                <a href="fee-collection.php" class="btn btn-success w-100" style="text-decoration: none; margin-bottom: 10px;">
                    💵 Fee Collection Report
                </a>
            </div>
            <div style="margin-bottom: 15px;">
                <a href="pending-fees.php" class="btn btn-success w-100" style="text-decoration: none; margin-bottom: 10px;">
                    ⏰ Pending Fees Report
                </a>
            </div>
            <div style="margin-bottom: 15px;">
                <a href="overdue-fees.php" class="btn btn-success w-100" style="text-decoration: none; margin-bottom: 10px;">
                    🚨 Overdue Fees Report
                </a>
            </div>
            <div>
                <a href="monthly-collection.php" class="btn btn-success w-100" style="text-decoration: none;">
                    📅 Monthly Collection Report
                </a>
            </div>
        </div>
    </div>
    
    <!-- Academic Reports -->
    <div class="card">
        <div class="card-header">
            <h3>📚 Academic Reports</h3>
        </div>
        <div style="padding: 20px;">
            <div style="margin-bottom: 15px;">
                <a href="course-wise-results.php" class="btn btn-warning w-100" style="text-decoration: none; margin-bottom: 10px;">
                    📖 Course Wise Results
                </a>
            </div>
            <div style="margin-bottom: 15px;">
                <a href="exam-schedule.php" class="btn btn-warning w-100" style="text-decoration: none; margin-bottom: 10px;">
                    📝 Exam Schedule Report
                </a>
            </div>
            <div style="margin-bottom: 15px;">
                <a href="faculty-workload.php" class="btn btn-warning w-100" style="text-decoration: none; margin-bottom: 10px;">
                    👨‍🏫 Faculty Workload Report
                </a>
            </div>
            <div>
                <a href="grade-distribution.php" class="btn btn-warning w-100" style="text-decoration: none;">
                    📊 Grade Distribution Report
                </a>
            </div>
        </div>
    </div>
    
    <!-- Custom Reports -->
    <div class="card">
        <div class="card-header">
            <h3>⚙️ Custom Reports</h3>
        </div>
        <div style="padding: 20px;">
            <div style="margin-bottom: 15px;">
                <a href="attendance-report.php" class="btn btn-primary w-100" style="text-decoration: none; margin-bottom: 10px;">
                    ✅ Attendance Report
                </a>
            </div>
            <div style="margin-bottom: 15px;">
                <a href="system-usage.php" class="btn btn-primary w-100" style="text-decoration: none; margin-bottom: 10px;">
                    📈 System Usage Report
                </a>
            </div>
            <div style="margin-bottom: 15px;">
                <a href="user-activity.php" class="btn btn-primary w-100" style="text-decoration: none; margin-bottom: 10px;">
                    👤 User Activity Report
                </a>
            </div>
            <div>
                <a href="export-data.php" class="btn btn-primary w-100" style="text-decoration: none;">
                    💾 Export Data
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="card" style="margin-top: 40px;">
    <div class="card-header">
        <h3>📊 Report Generation Activity</h3>
    </div>
    <div style="padding: 20px; text-align: center; color: #6c757d;">
        <p>Report generation history and analytics will be displayed here.</p>
        <small>Track which reports are generated most frequently and by which users.</small>
    </div>
</div>

<?php
if (isset($db)) $db->close();
require_once '../../includes/footer.php';
?>