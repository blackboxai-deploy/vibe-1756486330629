<?php
$page_title = "Students Management";
require_once '../../includes/header.php';
require_once '../../config/database.php';

// Check permissions
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'faculty') {
    $_SESSION['error_message'] = 'Access denied. You do not have permission to access this page.';
    header('Location: ../../dashboard.php');
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Handle search and filtering
    $search = $_GET['search'] ?? '';
    $department_filter = $_GET['department'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    
    // Build query with filters
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $where_conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ? OR s.email LIKE ?)";
        $search_param = "%{$search}%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        $types .= 'ssss';
    }
    
    if (!empty($department_filter)) {
        $where_conditions[] = "s.department = ?";
        $params[] = $department_filter;
        $types .= 's';
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "s.status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get students with optional filters
    $query = "SELECT s.*, u.username, u.email as user_email, u.status as user_status 
              FROM students s 
              LEFT JOIN users u ON s.user_id = u.id 
              {$where_clause} 
              ORDER BY s.created_at DESC";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $students_result = $stmt->get_result();
    } else {
        $students_result = $conn->query($query);
    }
    
    // Get departments for filter dropdown
    $departments_result = $conn->query("SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department != '' ORDER BY department");
    $departments = [];
    while ($row = $departments_result->fetch_assoc()) {
        $departments[] = $row['department'];
    }
    
} catch (Exception $e) {
    $error_message = "Database error occurred.";
    error_log("Students index error: " . $e->getMessage());
}
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Students Management</h2>
        <p style="margin: 5px 0 0 0; color: #6c757d;">Manage student records and information</p>
    </div>
    <?php if ($_SESSION['role'] == 'admin'): ?>
        <a href="add.php" class="btn btn-success">
            ➕ Add New Student
        </a>
    <?php endif; ?>
</div>

<!-- Search and Filter Section -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h3>Search & Filter Students</h3>
    </div>
    <form method="GET" action="index.php" style="padding: 20px;">
        <div class="form-row">
            <div class="form-group">
                <label for="search">Search Students</label>
                <input type="text" id="search" name="search" class="form-control" 
                       placeholder="Search by name, student ID, or email" 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="form-group">
                <label for="department">Department</label>
                <select id="department" name="department" class="form-control">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>" 
                                <?php echo $department_filter === $dept ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="graduated" <?php echo $status_filter === 'graduated' ? 'selected' : ''; ?>>Graduated</option>
                </select>
            </div>
        </div>
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">🔍 Search</button>
            <a href="index.php" class="btn btn-secondary">🔄 Reset</a>
        </div>
    </form>
</div>

<!-- Students Table -->
<div class="table-container">
    <div class="table-header">
        <h3>Students List</h3>
        <div style="font-size: 14px; color: #6c757d;">
            Total: <?php echo $students_result ? $students_result->num_rows : 0; ?> students found
        </div>
    </div>
    
    <?php if ($students_result && $students_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th data-sort="student_id">Student ID</th>
                        <th data-sort="name">Name</th>
                        <th data-sort="email">Email</th>
                        <th data-sort="department">Department</th>
                        <th data-sort="semester">Semester</th>
                        <th data-sort="status">Status</th>
                        <th data-sort="admission_date">Admission Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($student = $students_result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($student['student_id']); ?></strong>
                            </td>
                            <td>
                                <div style="font-weight: 500;">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                </div>
                                <?php if ($student['phone']): ?>
                                    <div style="font-size: 12px; color: #6c757d;">
                                        📞 <?php echo htmlspecialchars($student['phone']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td>
                                <?php if ($student['department']): ?>
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars($student['department']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #6c757d;">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($student['semester']): ?>
                                    <span class="badge badge-success">Semester <?php echo $student['semester']; ?></span>
                                <?php else: ?>
                                    <span style="color: #6c757d;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $student['status'] === 'active' ? 'success' : 
                                         ($student['status'] === 'graduated' ? 'info' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($student['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo $student['admission_date'] ? date('M j, Y', strtotime($student['admission_date'])) : 'N/A'; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="view.php?id=<?php echo $student['id']; ?>" 
                                       class="btn btn-sm btn-info" data-tooltip="View Details">
                                        👁️
                                    </a>
                                    <?php if ($_SESSION['role'] == 'admin'): ?>
                                        <a href="edit.php?id=<?php echo $student['id']; ?>" 
                                           class="btn btn-sm btn-warning" data-tooltip="Edit Student">
                                            ✏️
                                        </a>
                                        <a href="delete.php?id=<?php echo $student['id']; ?>" 
                                           class="btn btn-sm btn-danger" data-tooltip="Delete Student"
                                           data-action="delete">
                                            🗑️
                                        </a>
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
            <div style="font-size: 64px; margin-bottom: 20px;">👥</div>
            <h3 style="margin-bottom: 10px;">No Students Found</h3>
            <p style="margin-bottom: 20px;">
                <?php if (!empty($search) || !empty($department_filter) || !empty($status_filter)): ?>
                    No students match your search criteria. Try adjusting your filters.
                <?php else: ?>
                    No students have been added to the system yet.
                <?php endif; ?>
            </p>
            <?php if ($_SESSION['role'] == 'admin'): ?>
                <a href="add.php" class="btn btn-success">Add First Student</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
if (isset($db)) {
    $db->close();
}

// Add page-specific scripts
$inline_scripts = '
    // Auto-submit search form on Enter
    document.getElementById("search").addEventListener("keypress", function(e) {
        if (e.key === "Enter") {
            this.form.submit();
        }
    });
    
    // Highlight search terms in results
    const searchTerm = "' . addslashes($search) . '";
    if (searchTerm) {
        const rows = document.querySelectorAll("tbody tr");
        rows.forEach(row => {
            const cells = row.querySelectorAll("td");
            cells.forEach(cell => {
                if (cell.textContent.toLowerCase().includes(searchTerm.toLowerCase())) {
                    cell.style.backgroundColor = "#fff3cd";
                }
            });
        });
    }
';

require_once '../../includes/footer.php';
?>