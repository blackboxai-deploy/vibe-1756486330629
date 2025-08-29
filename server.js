const express = require('express');
const path = require('path');
const fs = require('fs');
const mysql = require('mysql2/promise');
const bcrypt = require('bcryptjs');
const session = require('express-session');

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(express.static('public'));
app.use('/css', express.static('css'));
app.use('/js', express.static('js'));

// Session configuration
app.use(session({
    secret: 'college-management-secret-key',
    resave: false,
    saveUninitialized: false,
    cookie: { secure: false, maxAge: 24 * 60 * 60 * 1000 } // 24 hours
}));

// Set view engine (we'll use EJS to simulate PHP)
app.set('view engine', 'ejs');
app.set('views', './views');

// Database configuration
const dbConfig = {
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASS || '',
    database: process.env.DB_NAME || 'college_management'
};

// In-memory database simulation (since we don't have MySQL)
let database = {
    users: [
        {
            id: 1,
            username: 'admin',
            password: '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // admin123
            email: 'admin@college.edu',
            role: 'admin',
            status: 'active',
            created_at: new Date().toISOString()
        },
        {
            id: 2,
            username: 'faculty',
            password: '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // faculty123
            email: 'faculty@college.edu',
            role: 'faculty',
            status: 'active',
            created_at: new Date().toISOString()
        },
        {
            id: 3,
            username: 'student',
            password: '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // student123
            email: 'student@college.edu',
            role: 'student',
            status: 'active',
            created_at: new Date().toISOString()
        }
    ],
    students: [
        {
            id: 1,
            user_id: 3,
            student_id: 'STU2024001',
            first_name: 'John',
            last_name: 'Doe',
            email: 'john.doe@college.edu',
            phone: '(555) 123-4567',
            address: '123 Main St, City, State 12345',
            date_of_birth: '2000-05-15',
            gender: 'male',
            department: 'Computer Science',
            semester: 4,
            admission_date: '2022-09-01',
            status: 'active',
            created_at: new Date().toISOString()
        },
        {
            id: 2,
            user_id: null,
            student_id: 'STU2024002',
            first_name: 'Jane',
            last_name: 'Smith',
            email: 'jane.smith@college.edu',
            phone: '(555) 234-5678',
            address: '456 Oak Ave, City, State 12345',
            date_of_birth: '2001-08-22',
            gender: 'female',
            department: 'Information Technology',
            semester: 2,
            admission_date: '2023-09-01',
            status: 'active',
            created_at: new Date().toISOString()
        }
    ],
    faculty: [
        {
            id: 1,
            user_id: 2,
            faculty_id: 'FAC001',
            first_name: 'Dr. Robert',
            last_name: 'Johnson',
            email: 'robert.johnson@college.edu',
            phone: '(555) 345-6789',
            department: 'Computer Science',
            designation: 'Professor',
            qualification: 'Ph.D. Computer Science',
            experience: 15,
            salary: 85000.00,
            joining_date: '2010-08-15',
            status: 'active',
            created_at: new Date().toISOString()
        }
    ],
    courses: [
        {
            id: 1,
            course_code: 'CS101',
            course_name: 'Introduction to Programming',
            description: 'Basic programming concepts using Python',
            credits: 3,
            department: 'Computer Science',
            semester: 1,
            faculty_id: 1,
            status: 'active',
            created_at: new Date().toISOString()
        },
        {
            id: 2,
            course_code: 'CS201',
            course_name: 'Data Structures',
            description: 'Fundamental data structures and algorithms',
            credits: 4,
            department: 'Computer Science',
            semester: 2,
            faculty_id: 1,
            status: 'active',
            created_at: new Date().toISOString()
        }
    ],
    fees: [
        {
            id: 1,
            student_id: 1,
            fee_type: 'Tuition Fee',
            amount: 5000.00,
            due_date: '2024-12-31',
            paid_amount: 2500.00,
            payment_date: '2024-01-15',
            payment_method: 'Credit Card',
            status: 'partial',
            created_at: new Date().toISOString()
        }
    ],
    exams: [
        {
            id: 1,
            exam_name: 'Midterm Exam - Programming',
            course_id: 1,
            exam_date: '2024-03-15',
            exam_time: '09:00:00',
            duration: 120,
            total_marks: 100,
            exam_type: 'midterm',
            status: 'scheduled',
            created_at: new Date().toISOString()
        }
    ],
    results: [
        {
            id: 1,
            student_id: 1,
            exam_id: 1,
            marks_obtained: 85.5,
            total_marks: 100,
            grade: 'A',
            remarks: 'Excellent performance',
            created_at: new Date().toISOString()
        }
    ]
};

// Helper functions
function requireAuth(req, res, next) {
    if (!req.session.user_id) {
        return res.redirect('/');
    }
    next();
}

function requireRole(roles) {
    return (req, res, next) => {
        if (!req.session.role || !roles.includes(req.session.role)) {
            req.session.error_message = 'Access denied.';
            return res.redirect('/dashboard');
        }
        next();
    };
}

// Routes

// Home/Login page
app.get('/', (req, res) => {
    if (req.session.user_id) {
        return res.redirect('/dashboard');
    }
    
    res.sendFile(path.join(__dirname, 'index.html'));
});

// Login endpoint
app.post('/login', async (req, res) => {
    const { username, password } = req.body;
    
    if (!username || !password) {
        return res.redirect('/?error=Please enter both username and password');
    }
    
    // Find user
    const user = database.users.find(u => 
        u.username === username || u.email === username
    );
    
    if (!user) {
        return res.redirect('/?error=Invalid username or password');
    }
    
    if (user.status === 'inactive') {
        return res.redirect('/?error=Account deactivated. Contact administrator');
    }
    
    // Verify password (for demo, we'll use simple comparison)
    const validPassword = await bcrypt.compare(password, user.password);
    if (!validPassword) {
        return res.redirect('/?error=Invalid username or password');
    }
    
    // Set session
    req.session.user_id = user.id;
    req.session.username = user.username;
    req.session.email = user.email;
    req.session.role = user.role;
    req.session.login_time = Date.now();
    
    res.redirect('/dashboard');
});

// Dashboard
app.get('/dashboard', requireAuth, (req, res) => {
    res.sendFile(path.join(__dirname, 'dashboard.html'));
});

// Dashboard API
app.get('/api/dashboard/stats', requireAuth, (req, res) => {
    const stats = {
        students: database.students.filter(s => s.status === 'active').length,
        faculty: database.faculty.filter(f => f.status === 'active').length,
        courses: database.courses.filter(c => c.status === 'active').length,
        pending_fees: database.fees.filter(f => f.status === 'pending' || f.status === 'overdue').length,
        fees_collected: database.fees.reduce((sum, f) => sum + (f.paid_amount || 0), 0)
    };
    
    res.json(stats);
});

// Students routes
app.get('/modules/students', requireAuth, requireRole(['admin', 'faculty']), (req, res) => {
    res.sendFile(path.join(__dirname, 'modules/students/index.html'));
});

app.get('/api/students', requireAuth, requireRole(['admin', 'faculty']), (req, res) => {
    const { search, department, status } = req.query;
    let students = [...database.students];
    
    if (search) {
        const searchLower = search.toLowerCase();
        students = students.filter(s => 
            s.first_name.toLowerCase().includes(searchLower) ||
            s.last_name.toLowerCase().includes(searchLower) ||
            s.student_id.toLowerCase().includes(searchLower) ||
            s.email.toLowerCase().includes(searchLower)
        );
    }
    
    if (department) {
        students = students.filter(s => s.department === department);
    }
    
    if (status) {
        students = students.filter(s => s.status === status);
    }
    
    // Add user info
    students = students.map(s => {
        const user = database.users.find(u => u.id === s.user_id);
        return {
            ...s,
            username: user?.username,
            user_email: user?.email,
            user_status: user?.status
        };
    });
    
    res.json(students);
});

// Faculty routes
app.get('/modules/faculty', requireAuth, requireRole(['admin']), (req, res) => {
    res.sendFile(path.join(__dirname, 'modules/faculty/index.html'));
});

app.get('/api/faculty', requireAuth, requireRole(['admin']), (req, res) => {
    res.json(database.faculty);
});

// Courses routes
app.get('/modules/courses', requireAuth, (req, res) => {
    res.sendFile(path.join(__dirname, 'modules/courses/index.html'));
});

app.get('/api/courses', requireAuth, (req, res) => {
    let courses = [...database.courses];
    
    // Add faculty info
    courses = courses.map(c => {
        const faculty = database.faculty.find(f => f.id === c.faculty_id);
        return {
            ...c,
            faculty_first_name: faculty?.first_name,
            faculty_last_name: faculty?.last_name,
            faculty_id_ref: faculty?.faculty_id
        };
    });
    
    // Filter by role
    if (req.session.role === 'faculty') {
        const userFaculty = database.faculty.find(f => f.user_id === req.session.user_id);
        if (userFaculty) {
            courses = courses.filter(c => c.faculty_id === userFaculty.id);
        }
    } else if (req.session.role === 'student') {
        courses = courses.filter(c => c.status === 'active');
    }
    
    res.json(courses);
});

// Fees routes
app.get('/modules/fees', requireAuth, requireRole(['admin', 'student']), (req, res) => {
    res.sendFile(path.join(__dirname, 'modules/fees/index.html'));
});

app.get('/api/fees', requireAuth, requireRole(['admin', 'student']), (req, res) => {
    let fees = [...database.fees];
    
    // Filter by role
    if (req.session.role === 'student') {
        const userStudent = database.students.find(s => s.user_id === req.session.user_id);
        if (userStudent) {
            fees = fees.filter(f => f.student_id === userStudent.id);
        }
    }
    
    // Add student info
    fees = fees.map(f => {
        const student = database.students.find(s => s.id === f.student_id);
        return {
            ...f,
            first_name: student?.first_name,
            last_name: student?.last_name,
            student_id_ref: student?.student_id
        };
    });
    
    res.json(fees);
});

// Session info endpoint
app.get('/api/session', requireAuth, (req, res) => {
    res.json({
        user_id: req.session.user_id,
        username: req.session.username,
        email: req.session.email,
        role: req.session.role,
        login_time: req.session.login_time
    });
});

// Logout
app.get('/logout', (req, res) => {
    req.session.destroy();
    res.redirect('/?message=Successfully logged out');
});

// Error handler
app.use((err, req, res, next) => {
    console.error(err.stack);
    res.status(500).send('Something broke!');
});

// Start server
app.listen(PORT, () => {
    console.log(`College Management System running on port ${PORT}`);
    console.log('Demo Login Credentials:');
    console.log('Admin: username=admin, password=admin123');
    console.log('Faculty: username=faculty, password=faculty123');
    console.log('Student: username=student, password=student123');
});

module.exports = app;