<?php
$page_title = "Faculty Management";
require_once '../../includes/header.php';
require_once '../../config/database.php';

// Check permissions
if ($_SESSION['role'] != 'admin') {
    $_SESSION['error_message'] = 'Access denied. Only administrators can access faculty management.';
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
        $where_conditions[] = "(f.first_name LIKE ? OR f.last_name LIKE ? OR f.faculty_id LIKE ? OR f.email LIKE ? OR f.designation LIKE ?)";
        $search_param = "%{$search}%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
        $types .= 'sssss';
    }
    
    if (!empty($department_filter)) {
        $where_conditions[] = "f.department = ?";
        $params[] = $department_filter;
        $types .= 's';
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "f.status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get faculty with optional filters
    $query = "SELECT f.*, u.username, u.email as user_email, u.status as user_status 
              FROM faculty f 
              LEFT JOIN users u ON f.user_id = u.id 
              {$where_clause} 
              ORDER BY f.created_at DESC";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $faculty_result = $stmt->get_result();
    } else {
        $faculty_result = $conn->query($query);
    }
    
    // Get departments for filter dropdown
    $departments_result = $conn->query("SELECT DISTINCT department FROM faculty WHERE department IS NOT NULL AND department != '' ORDER BY department");
    $departments = [];
    while ($row = $departments_result->fetch_assoc()) {
        $departments[] = $row['department'];
    }
    
} catch (Exception $e) {
    $error_message = "Database error occurred.";
    error_log("Faculty index error: " . $e->getMessage());
}
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Faculty Management</h2>
        <p style="margin: 5px 0 0 0; color: #6c757d;">Manage faculty members and their information</p>
    </div>
    <a href="add.php" class="btn btn-success">
        ➕ Add New Faculty
    </a>
</div>

<!-- Search and Filter Section -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h3>Search & Filter Faculty</h3>
    </div>
    <form method="GET" action="index.php" style="padding: 20px;">
        <div class="form-row">
            <div class="form-group">
                <label for="search">Search Faculty</label>
                <input type="text" id="search" name="search" class="form-control" 
                       placeholder="Search by name, faculty ID, email, or designation" 
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
                </select>
            </div>
        </div>
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">🔍 Search</button>
            <a href="index.php" class="btn btn-secondary">🔄 Reset</a>
        </div>
    </form>
</div>

<!-- Faculty Table -->
<div class="table-container">
    <div class="table-header">
        <h3>Faculty List</h3>
        <div style="font-size: 14px; color: #6c757d;">
            Total: <?php echo $faculty_result ? $faculty_result->num_rows : 0; ?> faculty members found
        </div>
    </div>
    
    <?php if ($faculty_result && $faculty_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th data-sort="faculty_id">Faculty ID</th>
                        <th data-sort="name">Name</th>
                        <th data-sort="email">Email</th>
                        <th data-sort="department">Department</th>
                        <th data-sort="designation">Designation</th>
                        <th data-sort="experience">Experience</th>
                        <th data-sort="status">Status</th>
                        <th data-sort="joining_date">Joining Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($faculty = $faculty_result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($faculty['faculty_id']); ?></strong>
                            </td>
                            <td>
                                <div style="font-weight: 500;">
                                    <?php echo htmlspecialchars($faculty['first_name'] . ' ' . $faculty['last_name']); ?>
                                </div>
                                <?php if ($faculty['phone']): ?>
                                    <div style="font-size: 12px; color: #6c757d;">
                                        📞 <?php echo htmlspecialchars($faculty['phone']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($faculty['email']); ?></td>
                            <td>
                                <?php if ($faculty['department']): ?>
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars($faculty['department']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #6c757d;">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($faculty['designation']): ?>
                                    <span class="badge badge-warning">
                                        <?php echo htmlspecialchars($faculty['designation']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #6c757d;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($faculty['experience']): ?>
                                    <span><?php echo $faculty['experience']; ?> years</span>
                                <?php else: ?>
                                    <span style="color: #6c757d;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $faculty['status'] === 'active' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($faculty['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo $faculty['joining_date'] ? date('M j, Y', strtotime($faculty['joining_date'])) : 'N/A'; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="view.php?id=<?php echo $faculty['id']; ?>" 
                                       class="btn btn-sm btn-info" data-tooltip="View Details">
                                        👁️
                                    </a>
                                    <a href="edit.php?id=<?php echo $faculty['id']; ?>" 
                                       class="btn btn-sm btn-warning" data-tooltip="Edit Faculty">
                                        ✏️
                                    </a>
                                    <a href="delete.php?id=<?php echo $faculty['id']; ?>" 
                                       class="btn btn-sm btn-danger" data-tooltip="Delete Faculty"
                                       data-action="delete">
                                        🗑️
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 60px 20px; color: #6c757d;">
            <div style="font-size: 64px; margin-bottom: 20px;">👨‍🏫</div>
            <h3 style="margin-bottom: 10px;">No Faculty Found</h3>
            <p style="margin-bottom: 20px;">
                <?php if (!empty($search) || !empty($department_filter) || !empty($status_filter)): ?>
                    No faculty members match your search criteria. Try adjusting your filters.
                <?php else: ?>
                    No faculty members have been added to the system yet.
                <?php endif; ?>
            </p>
            <a href="add.php" class="btn btn-success">Add First Faculty Member</a>
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