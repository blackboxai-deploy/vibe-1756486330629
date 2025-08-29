<?php
$page_title = "Exam Management";
require_once '../../includes/header.php';
require_once '../../config/database.php';

$can_modify = ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'faculty');

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Build query based on user role
    $where_clause = '';
    if ($_SESSION['role'] == 'faculty') {
        $where_clause = "WHERE f.user_id = {$_SESSION['user_id']}";
    } elseif ($_SESSION['role'] == 'student') {
        // For students, show all active exams (you might want to filter by enrolled courses)
        $where_clause = "WHERE e.status IN ('scheduled', 'ongoing')";
    }
    
    $query = "SELECT e.*, c.course_name, c.course_code,
                     f.first_name as faculty_first_name, f.last_name as faculty_last_name
              FROM exams e 
              JOIN courses c ON e.course_id = c.id 
              LEFT JOIN faculty f ON c.faculty_id = f.id 
              {$where_clause}
              ORDER BY e.exam_date ASC";
    
    $exams_result = $conn->query($query);
    
} catch (Exception $e) {
    $error_message = "Database error occurred.";
    error_log("Exams error: " . $e->getMessage());
}
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Exam Management</h2>
        <p style="margin: 5px 0 0 0; color: #6c757d;">
            <?php if ($_SESSION['role'] == 'admin'): ?>
                Manage exam schedules and coordination
            <?php elseif ($_SESSION['role'] == 'faculty'): ?>
                Manage your course exams
            <?php else: ?>
                View upcoming exams
            <?php endif; ?>
        </p>
    </div>
    <?php if ($can_modify): ?>
        <a href="add.php" class="btn btn-success">➕ Schedule Exam</a>
    <?php endif; ?>
</div>

<div class="table-container">
    <div class="table-header">
        <h3>Exam Schedule</h3>
        <div style="font-size: 14px; color: #6c757d;">
            Total: <?php echo $exams_result ? $exams_result->num_rows : 0; ?> exams
        </div>
    </div>
    
    <?php if ($exams_result && $exams_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>Exam Name</th>
                        <th>Course</th>
                        <th>Date & Time</th>
                        <th>Duration</th>
                        <th>Total Marks</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($exam = $exams_result->fetch_assoc()): ?>
                        <tr>
                            <td style="font-weight: 500;"><?php echo htmlspecialchars($exam['exam_name']); ?></td>
                            <td>
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($exam['course_name']); ?></div>
                                <div style="font-size: 12px; color: #6c757d;"><?php echo htmlspecialchars($exam['course_code']); ?></div>
                            </td>
                            <td>
                                <div><?php echo $exam['exam_date'] ? date('M j, Y', strtotime($exam['exam_date'])) : 'N/A'; ?></div>
                                <div style="font-size: 12px; color: #6c757d;">
                                    <?php echo $exam['exam_time'] ? date('g:i A', strtotime($exam['exam_time'])) : 'Time TBA'; ?>
                                </div>
                            </td>
                            <td><?php echo $exam['duration'] ? $exam['duration'] . ' mins' : 'N/A'; ?></td>
                            <td><?php echo $exam['total_marks'] ? $exam['total_marks'] . ' marks' : 'N/A'; ?></td>
                            <td>
                                <span class="badge badge-info"><?php echo ucfirst($exam['exam_type']); ?></span>
                            </td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $exam['status'] === 'completed' ? 'success' : 
                                         ($exam['status'] === 'ongoing' ? 'warning' : 'info'); 
                                ?>">
                                    <?php echo ucfirst($exam['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="view.php?id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-info">👁️</a>
                                    <?php if ($can_modify): ?>
                                        <a href="edit.php?id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-warning">✏️</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 60px 20px; color: #6c757d;">
            <div style="font-size: 64px; margin-bottom: 20px;">📝</div>
            <h3>No Exams Scheduled</h3>
        </div>
    <?php endif; ?>
</div>

<?php
if (isset($db)) $db->close();
require_once '../../includes/footer.php';
?>