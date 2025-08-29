<?php
$page_title = "User Management";
require_once '../../includes/header.php';
require_once '../../config/database.php';

// Check permissions - only admin can access user management
if ($_SESSION['role'] != 'admin') {
    $_SESSION['error_message'] = 'Access denied. Only administrators can access user management.';
    header('Location: ../../dashboard.php');
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Handle search and filtering
    $search = $_GET['search'] ?? '';
    $role_filter = $_GET['role'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    
    // Build query with filters
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $where_conditions[] = "(u.username LIKE ? OR u.email LIKE ?)";
        $search_param = "%{$search}%";
        $params = array_merge($params, [$search_param, $search_param]);
        $types .= 'ss';
    }
    
    if (!empty($role_filter)) {
        $where_conditions[] = "u.role = ?";
        $params[] = $role_filter;
        $types .= 's';
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "u.status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get users with optional filters
    $query = "SELECT u.*,
                     CASE 
                         WHEN u.role = 'student' THEN s.first_name
                         WHEN u.role = 'faculty' THEN f.first_name
                         ELSE 'System'
                     END as first_name,
                     CASE 
                         WHEN u.role = 'student' THEN s.last_name
                         WHEN u.role = 'faculty' THEN f.last_name
                         ELSE 'User'
                     END as last_name,
                     CASE 
                         WHEN u.role = 'student' THEN s.student_id
                         WHEN u.role = 'faculty' THEN f.faculty_id
                         ELSE NULL
                     END as profile_id
              FROM users u 
              LEFT JOIN students s ON u.id = s.user_id AND u.role = 'student'
              LEFT JOIN faculty f ON u.id = f.user_id AND u.role = 'faculty'
              {$where_clause} 
              ORDER BY u.created_at DESC";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $users_result = $stmt->get_result();
    } else {
        $users_result = $conn->query($query);
    }
    
} catch (Exception $e) {
    $error_message = "Database error occurred.";
    error_log("Users error: " . $e->getMessage());
}
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">User Management</h2>
        <p style="margin: 5px 0 0 0; color: #6c757d;">Manage system users and their permissions</p>
    </div>
    <a href="add.php" class="btn btn-success">
        ➕ Add New User
    </a>
</div>

<!-- Search and Filter Section -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h3>Search & Filter Users</h3>
    </div>
    <form method="GET" action="index.php" style="padding: 20px;">
        <div class="form-row">
            <div class="form-group">
                <label for="search">Search Users</label>
                <input type="text" id="search" name="search" class="form-control" 
                       placeholder="Search by username or email" 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" class="form-control">
                    <option value="">All Roles</option>
                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                    <option value="faculty" <?php echo $role_filter === 'faculty' ? 'selected' : ''; ?>>Faculty</option>
                    <option value="student" <?php echo $role_filter === 'student' ? 'selected' : ''; ?>>Student</option>
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

<!-- Users Table -->
<div class="table-container">
    <div class="table-header">
        <h3>System Users</h3>
        <div style="font-size: 14px; color: #6c757d;">
            Total: <?php echo $users_result ? $users_result->num_rows : 0; ?> users found
        </div>
    </div>
    
    <?php if ($users_result && $users_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th data-sort="username">Username</th>
                        <th data-sort="name">Full Name</th>
                        <th data-sort="email">Email</th>
                        <th data-sort="role">Role</th>
                        <th data-sort="profile_id">Profile ID</th>
                        <th data-sort="status">Status</th>
                        <th data-sort="created_at">Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                            </td>
                            <td>
                                <div style="font-weight: 500;">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $user['role'] === 'admin' ? 'danger' : 
                                         ($user['role'] === 'faculty' ? 'info' : 'success'); 
                                ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['profile_id']): ?>
                                    <span style="font-family: monospace; background: #f8f9fa; padding: 2px 6px; border-radius: 4px;">
                                        <?php echo htmlspecialchars($user['profile_id']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #6c757d;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="view.php?id=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-info" data-tooltip="View Details">
                                        👁️
                                    </a>
                                    <a href="edit.php?id=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-warning" data-tooltip="Edit User">
                                        ✏️
                                    </a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="reset-password.php?id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-info" data-tooltip="Reset Password">
                                            🔑
                                        </a>
                                        <a href="delete.php?id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-danger" data-tooltip="Delete User"
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
            <div style="font-size: 64px; margin-bottom: 20px;">👤</div>
            <h3 style="margin-bottom: 10px;">No Users Found</h3>
            <p style="margin-bottom: 20px;">
                <?php if (!empty($search) || !empty($role_filter) || !empty($status_filter)): ?>
                    No users match your search criteria. Try adjusting your filters.
                <?php else: ?>
                    No users have been added to the system yet.
                <?php endif; ?>
            </p>
            <a href="add.php" class="btn btn-success">Add First User</a>
        </div>
    <?php endif; ?>
</div>

<?php
if (isset($db)) $db->close();

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