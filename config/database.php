<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'college_management');

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $conn;
    private $error;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
        
        if ($this->conn->connect_error) {
            $this->error = "Connection failed: " . $this->conn->connect_error;
            die($this->error);
        }
        
        $this->conn->set_charset("utf8");
        $this->createTables();
    }

    public function getConnection() {
        return $this->conn;
    }

    private function createTables() {
        // Users table
        $users_table = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            role ENUM('admin', 'faculty', 'student') NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        // Students table
        $students_table = "CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            student_id VARCHAR(20) UNIQUE NOT NULL,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            phone VARCHAR(15),
            address TEXT,
            date_of_birth DATE,
            gender ENUM('male', 'female', 'other') NOT NULL,
            department VARCHAR(100),
            semester INT,
            admission_date DATE,
            status ENUM('active', 'inactive', 'graduated') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )";

        // Faculty table
        $faculty_table = "CREATE TABLE IF NOT EXISTS faculty (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            faculty_id VARCHAR(20) UNIQUE NOT NULL,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            phone VARCHAR(15),
            department VARCHAR(100),
            designation VARCHAR(100),
            qualification VARCHAR(255),
            experience INT,
            salary DECIMAL(10,2),
            joining_date DATE,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )";

        // Courses table
        $courses_table = "CREATE TABLE IF NOT EXISTS courses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_code VARCHAR(20) UNIQUE NOT NULL,
            course_name VARCHAR(100) NOT NULL,
            description TEXT,
            credits INT NOT NULL,
            department VARCHAR(100),
            semester INT,
            faculty_id INT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE SET NULL
        )";

        // Fees table
        $fees_table = "CREATE TABLE IF NOT EXISTS fees (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT,
            fee_type VARCHAR(50) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            due_date DATE,
            paid_amount DECIMAL(10,2) DEFAULT 0,
            payment_date DATE NULL,
            payment_method VARCHAR(50),
            status ENUM('pending', 'partial', 'paid', 'overdue') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        )";

        // Exams table
        $exams_table = "CREATE TABLE IF NOT EXISTS exams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            exam_name VARCHAR(100) NOT NULL,
            course_id INT,
            exam_date DATE,
            exam_time TIME,
            duration INT,
            total_marks INT,
            exam_type ENUM('midterm', 'final', 'quiz', 'assignment') NOT NULL,
            status ENUM('scheduled', 'ongoing', 'completed', 'cancelled') DEFAULT 'scheduled',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        )";

        // Results table
        $results_table = "CREATE TABLE IF NOT EXISTS results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT,
            exam_id INT,
            marks_obtained DECIMAL(5,2),
            total_marks DECIMAL(5,2),
            grade VARCHAR(10),
            remarks TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
            UNIQUE KEY unique_student_exam (student_id, exam_id)
        )";

        // Execute table creation
        $tables = [
            $users_table, $students_table, $faculty_table, 
            $courses_table, $fees_table, $exams_table, $results_table
        ];

        foreach ($tables as $table) {
            if (!$this->conn->query($table)) {
                die("Error creating table: " . $this->conn->error);
            }
        }

        // Insert default admin user if not exists
        $this->createDefaultAdmin();
    }

    private function createDefaultAdmin() {
        $check_admin = "SELECT id FROM users WHERE username = 'admin' LIMIT 1";
        $result = $this->conn->query($check_admin);
        
        if ($result->num_rows == 0) {
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            $insert_admin = "INSERT INTO users (username, password, email, role) 
                           VALUES ('admin', '$password', 'admin@college.edu', 'admin')";
            $this->conn->query($insert_admin);
        }
    }

    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>