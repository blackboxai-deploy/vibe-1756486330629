<?php
$page_title = "Courses Management";
require_once '../../includes/header.php';
require_once '../../config/database.php';

// Check permissions - all roles can view courses but only admin can add/edit/delete
$can_modify = ($_SESSION['role'] == 'admin');

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Handle search and filtering
    $search = $_GET['search'] ?? '';
    $department_filter = $_GET['department'] ?? '';
    $semester_filter = $_GET['semester'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    
    // Build query with filters based on user role
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $where_conditions[] = "(c.course_code LIKE ? OR c.course_name LIKE ? OR c.description LIKE ?)";
        $search_param = "%{$search}%";
        $params = array_merge($params, [$search_param, $search_param, $search_param]);
        $types .= 'sss';
    }
    
    if (!empty($department_filter)) {
        $where_conditions[] = "c.department = ?";
        $params[] = $department_filter;
        $types .= 's';
    }
    
    if (!empty($semester_filter)) {
        $where_conditions[] = "c.semester = ?";
        $params[] = $semester_filter;
        $types .= 'i';
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "c.status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }
    
    // For faculty, show only their courses
    if ($_SESSION['role'] == 'faculty') {
        $where_conditions[] = "f.user_id = ?";
        $params[] = $_SESSION['user_id'];
        $types .= 'i';
    }
    
    // For students, show only their enrolled courses (you may want to create an enrollment table)
    // For now, we'll show all active courses for students
    if ($_SESSION['role'] == 'student') {
        $where_conditions[] = "c.status = 'active'";
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get courses with faculty information
    $query = "SELECT c.*, 
                     f.first_name as faculty_first_name, 
                     f.last_name as faculty_last_name,
                     f.faculty_id
              FROM courses c 
              LEFT JOIN faculty f ON c.faculty_id = f.id 
              {$where_clause} 
              ORDER BY c.course_code ASC";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $courses_result = $stmt->get_result();
    } else {
        $courses_result = $conn->query($query);
    }
    
    // Get departments and semesters for filter dropdowns
    $departments_result = $conn->query("SELECT DISTINCT department FROM courses WHERE department IS NOT NULL AND department != '' ORDER BY department");
    $departments = [];
    while ($row = $departments_result->fetch_assoc()) {
        $departments[] = $row['department'];
    }
    
} catch (Exception $e) {
    $error_message = "Database error occurred.";
    error_log("Courses index error: " . $e->getMessage());
}
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Courses Management</h2>
        <p style="margin: 5px 0 0 0; color: #6c757d;">
            <?php if ($_SESSION['role'] == 'admin'): ?>
                Manage course catalog and assignments
            <?php elseif ($_SESSION['role'] == 'faculty'): ?>
                View your assigned courses
            <?php else: ?>
                Browse available courses
            <?php endif; ?>
        </p>
    </div>
    <?php if ($can_modify): ?>
        <a href="add.php" class="btn btn-success">
            ➕ Add New Course
        </a>
    <?php endif; ?>
</div>

<!-- Search and Filter Section -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h3>Search & Filter Courses</h3>
    </div>
    <form method="GET" action="index.php" style="padding: 20px;">
        <div class="form-row">
            <div class="form-group">
                <label for="search">Search Courses</label>
                <input type="text" id="search" name="search" class="form-control" 
                       placeholder="Search by course code, name, or description" 
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
                <label for="semester">Semester</label>
                <select id="semester" name="semester" class="form-control">
                    <option value="">All Semesters</option>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?php echo $i; ?>" 
                                <?php echo $semester_filter == $i ? 'selected' : ''; ?>>
                            Semester <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <?php if ($can_modify): ?>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <?php endif; ?>
        </div>
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">🔍 Search</button>
            <a href="index.php" class="btn btn-secondary">🔄 Reset</a>
        </div>
    </form>
</div>

<!-- Courses Table -->
<div class="table-container">
    <div class="table-header">
        <h3>Courses List</h3>
        <div style="font-size: 14px; color: #6c757d;">
            Total: <?php echo $courses_result ? $courses_result->num_rows : 0; ?> courses found
        </div>
    </div>
    
    <?php if ($courses_result && $courses_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th data-sort="course_code">Course Code</th>
                        <th data-sort="course_name">Course Name</th>
                        <th data-sort="credits">Credits</th>
                        <th data-sort="department">Department</th>
                        <th data-sort="semester">Semester</th>
                        <th data-sort="faculty">Faculty</th>
                        <?php if ($can_modify): ?>
                        <th data-sort="status">Status</th>
                        <?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($course = $courses_result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong style="color: #007bff;">
                                    <?php echo htmlspecialchars($course['course_code']); ?>
                                </strong>
                            </td>
                            <td>
                                <div style="font-weight: 500; margin-bottom: 4px;">
                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                </div>
                                <?php if ($course['description']): ?>
                                    <div style="font-size: 12px; color: #6c757d; line-height: 1.3;">
                                        <?php echo htmlspecialchars(substr($course['description'], 0, 80)) . (strlen($course['description']) > 80 ? '...' : ''); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-info">
                                    <?php echo $course['credits']; ?> Credits
                                </span>
                            </td>
                            <td>
                                <?php if ($course['department']): ?>
                                    <span class="badge badge-warning">
                                        <?php echo htmlspecialchars($course['department']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #6c757d;">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($course['semester']): ?>
                                    <span class="badge badge-success">
                                        Semester <?php echo $course['semester']; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #6c757d;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($course['faculty_first_name']): ?>
                                    <div style="font-weight: 500;">
                                        <?php echo htmlspecialchars($course['faculty_first_name'] . ' ' . $course['faculty_last_name']); ?>
                                    </div>
                                    <div style="font-size: 12px; color: #6c757d;">
                                        ID: <?php echo htmlspecialchars($course['faculty_id']); ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: #e74c3c;">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($can_modify): ?>
                            <td>
                                <span class="badge badge-<?php echo $course['status'] === 'active' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($course['status']); ?>
                                </span>
                            </td>
                            <?php endif; ?>
                            <td>
                                <div class="btn-group">
                                    <a href="view.php?id=<?php echo $course['id']; ?>" 
                                       class="btn btn-sm btn-info" data-tooltip="View Details">
                                        👁️
                                    </a>
                                    <?php if ($can_modify): ?>
                                        <a href="edit.php?id=<?php echo $course['id']; ?>" 
                                           class="btn btn-sm btn-warning" data-tooltip="Edit Course">
                                            ✏️
                                        </a>
                                        <a href="delete.php?id=<?php echo $course['id']; ?>" 
                                           class="btn btn-sm btn-danger" data-tooltip="Delete Course"
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
            <div style="font-size: 64px; margin-bottom: 20px;">📚</div>
            <h3 style="margin-bottom: 10px;">No Courses Found</h3>
            <p style="margin-bottom: 20px;">
                <?php if (!empty($search) || !empty($department_filter) || !empty($semester_filter) || !empty($status_filter)): ?>
                    No courses match your search criteria. Try adjusting your filters.
                <?php elseif ($_SESSION['role'] == 'faculty'): ?>
                    No courses have been assigned to you yet. Please contact the administrator.
                <?php else: ?>
                    No courses have been added to the system yet.
                <?php endif; ?>
            </p>
            <?php if ($can_modify): ?>
                <a href="add.php" class="btn btn-success">Add First Course</a>
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