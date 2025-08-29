<?php
$page_title = "Add New Student";
require_once '../../includes/header.php';
require_once '../../config/database.php';

// Check permissions
if ($_SESSION['role'] != 'admin') {
    $_SESSION['error_message'] = 'Access denied. Only administrators can add students.';
    header('Location: index.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Validate required fields
        $required_fields = ['student_id', 'first_name', 'last_name', 'email', 'gender', 'department'];
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (empty(trim($_POST[$field] ?? ''))) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }
        
        // Validate email format
        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        // Check for duplicate student ID and email
        $student_id = trim($_POST['student_id']);
        $email = trim($_POST['email']);
        
        $check_stmt = $conn->prepare("SELECT id FROM students WHERE student_id = ? OR email = ?");
        $check_stmt->bind_param("ss", $student_id, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $existing = $check_result->fetch_assoc();
            $errors[] = 'Student ID or email already exists in the system.';
        }
        
        if (empty($errors)) {
            // Prepare student data
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
            $gender = $_POST['gender'];
            $department = trim($_POST['department']);
            $semester = !empty($_POST['semester']) ? (int)$_POST['semester'] : null;
            $admission_date = !empty($_POST['admission_date']) ? $_POST['admission_date'] : date('Y-m-d');
            
            // Create user account (optional)
            $user_id = null;
            $create_account = isset($_POST['create_account']) && $_POST['create_account'] == '1';
            
            if ($create_account) {
                $username = strtolower($student_id);
                $password = password_hash($student_id, PASSWORD_DEFAULT); // Default password is student ID
                
                // Check if username exists
                $user_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $user_check->bind_param("s", $username);
                $user_check->execute();
                
                if ($user_check->get_result()->num_rows == 0) {
                    $user_stmt = $conn->prepare("INSERT INTO users (username, password, email, role, status) VALUES (?, ?, ?, 'student', 'active')");
                    $user_stmt->bind_param("sss", $username, $password, $email);
                    
                    if ($user_stmt->execute()) {
                        $user_id = $conn->insert_id;
                    }
                }
            }
            
            // Insert student record
            $stmt = $conn->prepare("INSERT INTO students (user_id, student_id, first_name, last_name, email, phone, address, date_of_birth, gender, department, semester, admission_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            
            $stmt->bind_param("isssssssssss", $user_id, $student_id, $first_name, $last_name, $email, $phone, $address, $date_of_birth, $gender, $department, $semester, $admission_date);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Student added successfully!' . ($create_account ? ' Login credentials have been created.' : '');
                header('Location: index.php');
                exit();
            } else {
                $error_message = 'Failed to add student. Please try again.';
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
        
    } catch (Exception $e) {
        $error_message = 'Database error occurred. Please try again.';
        error_log("Add student error: " . $e->getMessage());
    }
}

// Get departments for dropdown (from existing students)
try {
    if (!isset($db)) {
        $db = new Database();
        $conn = $db->getConnection();
    }
    
    $departments_result = $conn->query("SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department != '' ORDER BY department");
    $departments = [];
    while ($row = $departments_result->fetch_assoc()) {
        $departments[] = $row['department'];
    }
    
    // Add common departments if none exist
    if (empty($departments)) {
        $departments = [
            'Computer Science',
            'Information Technology',
            'Electronics Engineering',
            'Mechanical Engineering',
            'Civil Engineering',
            'Business Administration',
            'Commerce',
            'Arts',
            'Science'
        ];
    }
    
} catch (Exception $e) {
    $departments = ['Computer Science', 'Information Technology', 'Business Administration'];
}
?>

<div class="page-header" style="margin-bottom: 30px;">
    <nav style="margin-bottom: 20px;">
        <a href="index.php" style="color: #007bff; text-decoration: none;">← Back to Students</a>
    </nav>
    <h2 style="margin: 0; color: #2c3e50;">Add New Student</h2>
    <p style="margin: 5px 0 0 0; color: #6c757d;">Enter student information to add them to the system</p>
</div>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger">
        <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<div class="form-container">
    <form method="POST" action="add.php" data-validate="true">
        
        <!-- Basic Information -->
        <div class="card" style="margin-bottom: 30px;">
            <div class="card-header">
                <h3>👤 Basic Information</h3>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="student_id">Student ID *</label>
                    <input type="text" id="student_id" name="student_id" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>" 
                           required placeholder="e.g., STU2024001">
                    <small style="color: #6c757d;">Unique identifier for the student</small>
                </div>
                
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" 
                           required placeholder="Enter first name">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" 
                           required placeholder="Enter last name">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           required placeholder="student@example.com">
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                           placeholder="+1234567890">
                </div>
                
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="gender">Gender *</label>
                    <select id="gender" name="gender" class="form-control" required>
                        <option value="">Select Gender</option>
                        <option value="male" <?php echo ($_POST['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo ($_POST['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                        <option value="other" <?php echo ($_POST['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" class="form-control" rows="3" 
                              placeholder="Enter full address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Academic Information -->
        <div class="card" style="margin-bottom: 30px;">
            <div class="card-header">
                <h3>🎓 Academic Information</h3>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="department">Department *</label>
                    <select id="department" name="department" class="form-control" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>" 
                                    <?php echo ($_POST['department'] ?? '') === $dept ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="semester">Current Semester</label>
                    <select id="semester" name="semester" class="form-control">
                        <option value="">Select Semester</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?php echo $i; ?>" 
                                    <?php echo ($_POST['semester'] ?? '') == $i ? 'selected' : ''; ?>>
                                Semester <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="admission_date">Admission Date</label>
                    <input type="date" id="admission_date" name="admission_date" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['admission_date'] ?? date('Y-m-d')); ?>">
                </div>
            </div>
        </div>
        
        <!-- Account Creation -->
        <div class="card" style="margin-bottom: 30px;">
            <div class="card-header">
                <h3>🔐 User Account</h3>
            </div>
            
            <div class="form-group">
                <div style="display: flex; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <input type="checkbox" id="create_account" name="create_account" value="1" 
                           <?php echo isset($_POST['create_account']) ? 'checked' : ''; ?> 
                           style="margin-right: 10px;">
                    <label for="create_account" style="margin: 0; cursor: pointer;">
                        Create login account for this student
                    </label>
                </div>
                <small style="color: #6c757d; margin-top: 5px; display: block;">
                    If checked, a user account will be created with username as Student ID and password as Student ID (student should change this after first login).
                </small>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div class="btn-group">
            <button type="submit" class="btn btn-success">
                💾 Add Student
            </button>
            <a href="index.php" class="btn btn-secondary">
                ❌ Cancel
            </a>
        </div>
    </form>
</div>

<?php
if (isset($db)) {
    $db->close();
}

// Add page-specific scripts
$inline_scripts = '
    // Auto-generate student ID
    document.getElementById("first_name").addEventListener("input", generateStudentId);
    document.getElementById("last_name").addEventListener("input", generateStudentId);
    document.getElementById("department").addEventListener("change", generateStudentId);
    
    function generateStudentId() {
        const firstName = document.getElementById("first_name").value.trim();
        const lastName = document.getElementById("last_name").value.trim();
        const department = document.getElementById("department").value;
        const studentIdField = document.getElementById("student_id");
        
        if (firstName && lastName && !studentIdField.value) {
            const year = new Date().getFullYear();
            const initials = (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
            const random = Math.floor(Math.random() * 1000).toString().padStart(3, "0");
            const deptCode = department ? department.substring(0, 3).toUpperCase() : "STU";
            
            studentIdField.value = deptCode + year + initials + random;
        }
    }
    
    // Phone number formatting
    document.getElementById("phone").addEventListener("input", function(e) {
        let value = e.target.value.replace(/\D/g, "");
        if (value.length >= 10) {
            value = value.replace(/(\d{3})(\d{3})(\d{4})/, "($1) $2-$3");
        }
        e.target.value = value;
    });
';

require_once '../../includes/footer.php';
?>