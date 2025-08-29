<?php
$page_title = "Results Management";
require_once '../../includes/header.php';
require_once '../../config/database.php';

$can_modify = ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'faculty');

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Build query based on user role
    $where_clause = '';
    if ($_SESSION['role'] == 'student') {
        $where_clause = "WHERE s.user_id = {$_SESSION['user_id']}";
    } elseif ($_SESSION['role'] == 'faculty') {
        $where_clause = "WHERE f.user_id = {$_SESSION['user_id']}";
    }
    
    $query = "SELECT r.*, 
                     s.first_name, s.last_name, s.student_id,
                     e.exam_name, c.course_name, c.course_code
              FROM results r 
              JOIN students s ON r.student_id = s.id 
              JOIN exams e ON r.exam_id = e.id 
              JOIN courses c ON e.course_id = c.id
              LEFT JOIN faculty f ON c.faculty_id = f.id
              {$where_clause}
              ORDER BY r.created_at DESC";
    
    $results_result = $conn->query($query);
    
} catch (Exception $e) {
    $error_message = "Database error occurred.";
    error_log("Results error: " . $e->getMessage());
}
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Results Management</h2>
        <p style="margin: 5px 0 0 0; color: #6c757d;">
            <?php if ($_SESSION['role'] == 'admin'): ?>
                Manage exam results and grades
            <?php elseif ($_SESSION['role'] == 'faculty'): ?>
                Enter and manage results for your courses
            <?php else: ?>
                View your exam results
            <?php endif; ?>
        </p>
    </div>
    <?php if ($can_modify): ?>
        <a href="add.php" class="btn btn-success">➕ Add Result</a>
    <?php endif; ?>
</div>

<div class="table-container">
    <div class="table-header">
        <h3>Exam Results</h3>
        <div style="font-size: 14px; color: #6c757d;">
            Total: <?php echo $results_result ? $results_result->num_rows : 0; ?> results
        </div>
    </div>
    
    <?php if ($results_result && $results_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <?php if ($_SESSION['role'] != 'student'): ?>
                        <th>Student</th>
                        <?php endif; ?>
                        <th>Exam</th>
                        <th>Course</th>
                        <th>Marks</th>
                        <th>Grade</th>
                        <th>Percentage</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($result = $results_result->fetch_assoc()): ?>
                        <tr>
                            <?php if ($_SESSION['role'] != 'student'): ?>
                            <td>
                                <div style="font-weight: 500;">
                                    <?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?>
                                </div>
                                <div style="font-size: 12px; color: #6c757d;">
                                    ID: <?php echo htmlspecialchars($result['student_id']); ?>
                                </div>
                            </td>
                            <?php endif; ?>
                            <td style="font-weight: 500;"><?php echo htmlspecialchars($result['exam_name']); ?></td>
                            <td>
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($result['course_name']); ?></div>
                                <div style="font-size: 12px; color: #6c757d;"><?php echo htmlspecialchars($result['course_code']); ?></div>
                            </td>
                            <td>
                                <strong><?php echo $result['marks_obtained']; ?></strong> / <?php echo $result['total_marks']; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php 
                                    $grade = $result['grade'];
                                    echo ($grade == 'A' || $grade == 'A+') ? 'success' : 
                                         (($grade == 'B' || $grade == 'B+') ? 'info' : 
                                          (($grade == 'C' || $grade == 'C+') ? 'warning' : 'danger')); 
                                ?>">
                                    <?php echo htmlspecialchars($result['grade']); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $percentage = ($result['marks_obtained'] / $result['total_marks']) * 100;
                                echo number_format($percentage, 1) . '%';
                                ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="view.php?id=<?php echo $result['id']; ?>" class="btn btn-sm btn-info">👁️</a>
                                    <?php if ($can_modify): ?>
                                        <a href="edit.php?id=<?php echo $result['id']; ?>" class="btn btn-sm btn-warning">✏️</a>
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
            <div style="font-size: 64px; margin-bottom: 20px;">📋</div>
            <h3>No Results Found</h3>
        </div>
    <?php endif; ?>
</div>

<?php
if (isset($db)) $db->close();
require_once '../../includes/footer.php';
?>