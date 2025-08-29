<?php
$page_title = "Fee Management";
require_once '../../includes/header.php';
require_once '../../config/database.php';

// Check permissions
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'student') {
    $_SESSION['error_message'] = 'Access denied.';
    header('Location: ../../dashboard.php');
    exit();
}

$can_modify = ($_SESSION['role'] == 'admin');

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // For students, show only their fees
    $where_clause = '';
    if ($_SESSION['role'] == 'student') {
        $where_clause = "WHERE s.user_id = {$_SESSION['user_id']}";
    }
    
    $query = "SELECT f.*, s.first_name, s.last_name, s.student_id 
              FROM fees f 
              JOIN students s ON f.student_id = s.id 
              {$where_clause}
              ORDER BY f.created_at DESC";
    
    $fees_result = $conn->query($query);
    
} catch (Exception $e) {
    $error_message = "Database error occurred.";
    error_log("Fees error: " . $e->getMessage());
}
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Fee Management</h2>
        <p style="margin: 5px 0 0 0; color: #6c757d;">
            <?php echo $_SESSION['role'] == 'admin' ? 'Manage student fees and payments' : 'View your fee details'; ?>
        </p>
    </div>
    <?php if ($can_modify): ?>
        <a href="add.php" class="btn btn-success">➕ Add Fee Record</a>
    <?php endif; ?>
</div>

<div class="table-container">
    <div class="table-header">
        <h3>Fee Records</h3>
        <div style="font-size: 14px; color: #6c757d;">
            Total: <?php echo $fees_result ? $fees_result->num_rows : 0; ?> records
        </div>
    </div>
    
    <?php if ($fees_result && $fees_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                        <th>Student</th>
                        <?php endif; ?>
                        <th>Fee Type</th>
                        <th>Amount</th>
                        <th>Paid Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($fee = $fees_result->fetch_assoc()): ?>
                        <tr>
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                            <td>
                                <div style="font-weight: 500;">
                                    <?php echo htmlspecialchars($fee['first_name'] . ' ' . $fee['last_name']); ?>
                                </div>
                                <div style="font-size: 12px; color: #6c757d;">
                                    ID: <?php echo htmlspecialchars($fee['student_id']); ?>
                                </div>
                            </td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($fee['fee_type']); ?></td>
                            <td>$<?php echo number_format($fee['amount'], 2); ?></td>
                            <td>$<?php echo number_format($fee['paid_amount'], 2); ?></td>
                            <td><?php echo $fee['due_date'] ? date('M j, Y', strtotime($fee['due_date'])) : 'N/A'; ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $fee['status'] === 'paid' ? 'success' : 
                                         ($fee['status'] === 'overdue' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($fee['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="view.php?id=<?php echo $fee['id']; ?>" class="btn btn-sm btn-info">👁️</a>
                                    <?php if ($can_modify): ?>
                                        <a href="edit.php?id=<?php echo $fee['id']; ?>" class="btn btn-sm btn-warning">✏️</a>
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
            <div style="font-size: 64px; margin-bottom: 20px;">💰</div>
            <h3>No Fee Records Found</h3>
        </div>
    <?php endif; ?>
</div>

<?php
if (isset($db)) $db->close();
require_once '../../includes/footer.php';
?>