<?php
// db_setup.php
require_once __DIR__ . '/config/db.php';

header('Content-Type: text/plain');

echo "Starting database setup...\n";
echo "Database Type Detected: " . strtoupper(DB_TYPE) . "\n\n";

try {
    if (DB_TYPE === 'mysql') {
        // MySQL Schema
        $queries = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('admin', 'instructor', 'student') NOT NULL,
                status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;",

            "CREATE TABLE IF NOT EXISTS departments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                code VARCHAR(10) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;",

            "CREATE TABLE IF NOT EXISTS courses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                department_id INT NOT NULL,
                name VARCHAR(100) NOT NULL,
                code VARCHAR(10) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;",

            "CREATE TABLE IF NOT EXISTS exams (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_id INT NOT NULL,
                instructor_id INT NOT NULL,
                title VARCHAR(150) NOT NULL,
                duration_minutes INT NOT NULL,
                start_time DATETIME NOT NULL,
                end_time DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
                FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;",

            "CREATE TABLE IF NOT EXISTS questions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                exam_id INT NOT NULL,
                question_text TEXT NOT NULL,
                option_a VARCHAR(255) NOT NULL,
                option_b VARCHAR(255) NOT NULL,
                option_c VARCHAR(255) NOT NULL,
                option_d VARCHAR(255) NOT NULL,
                correct_option CHAR(1) NOT NULL,
                points INT NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;",

            "CREATE TABLE IF NOT EXISTS student_exams (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                exam_id INT NOT NULL,
                score INT DEFAULT NULL,
                status ENUM('started', 'completed') NOT NULL DEFAULT 'started',
                started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                completed_at TIMESTAMP NULL DEFAULT NULL,
                FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;",

            "CREATE TABLE IF NOT EXISTS student_answers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_exam_id INT NOT NULL,
                question_id INT NOT NULL,
                selected_option CHAR(1) NOT NULL,
                is_correct TINYINT(1) NOT NULL,
                FOREIGN KEY (student_exam_id) REFERENCES student_exams(id) ON DELETE CASCADE,
                FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;"
        ];
    } else {
        // SQLite Schema
        $queries = [
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                role TEXT NOT NULL CHECK(role IN ('admin', 'instructor', 'student')),
                status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'inactive')),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );",

            "CREATE TABLE IF NOT EXISTS departments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                code TEXT NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );",

            "CREATE TABLE IF NOT EXISTS courses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                department_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                code TEXT NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
            );",

            "CREATE TABLE IF NOT EXISTS exams (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                course_id INTEGER NOT NULL,
                instructor_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                duration_minutes INTEGER NOT NULL,
                start_time DATETIME NOT NULL,
                end_time DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
                FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE
            );",

            "CREATE TABLE IF NOT EXISTS questions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                exam_id INTEGER NOT NULL,
                question_text TEXT NOT NULL,
                option_a TEXT NOT NULL,
                option_b TEXT NOT NULL,
                option_c TEXT NOT NULL,
                option_d TEXT NOT NULL,
                correct_option TEXT NOT NULL CHECK(correct_option IN ('A', 'B', 'C', 'D')),
                points INTEGER NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
            );",

            "CREATE TABLE IF NOT EXISTS student_exams (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                exam_id INTEGER NOT NULL,
                score INTEGER DEFAULT NULL,
                status TEXT NOT NULL DEFAULT 'started' CHECK(status IN ('started', 'completed')),
                started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                completed_at DATETIME DEFAULT NULL,
                FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
            );",

            "CREATE TABLE IF NOT EXISTS student_answers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_exam_id INTEGER NOT NULL,
                question_id INTEGER NOT NULL,
                selected_option TEXT NOT NULL CHECK(selected_option IN ('A', 'B', 'C', 'D')),
                is_correct INTEGER NOT NULL CHECK(is_correct IN (0, 1)),
                FOREIGN KEY (student_exam_id) REFERENCES student_exams(id) ON DELETE CASCADE,
                FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
            );"
        ];
    }

    // Execute queries
    foreach ($queries as $index => $sql) {
        $pdo->exec($sql);
        echo "Executed table schema " . ($index + 1) . " successfully.\n";
    }

    echo "\nSeeding default accounts...\n";

    // Seed Admin, Instructor, Student if not exists
    $users = [
        [
            'name' => 'System Administrator',
            'email' => 'admin@exam.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'status' => 'active'
        ],
        [
            'name' => 'Professor Alok',
            'email' => 'instructor@exam.com',
            'password' => password_hash('instructor123', PASSWORD_DEFAULT),
            'role' => 'instructor',
            'status' => 'active'
        ],
        [
            'name' => 'Ashish Keer',
            'email' => 'student@exam.com',
            'password' => password_hash('student123', PASSWORD_DEFAULT),
            'role' => 'student',
            'status' => 'active'
        ]
    ];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $insertUser = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");

    foreach ($users as $user) {
        $stmt->execute([$user['email']]);
        if ($stmt->fetchColumn() == 0) {
            $insertUser->execute([$user['name'], $user['email'], $user['password'], $user['role'], $user['status']]);
            echo "Seeded user: {$user['name']} ({$user['role']})\n";
        } else {
            echo "User already exists: {$user['name']}\n";
        }
    }

    echo "\nSeeding departments and courses...\n";
    
    // Check if CS Department exists
    $deptStmt = $pdo->prepare("SELECT id FROM departments WHERE code = ?");
    $deptStmt->execute(['CS']);
    $csDeptId = $deptStmt->fetchColumn();
    
    if (!$csDeptId) {
        $insertDept = $pdo->prepare("INSERT INTO departments (name, code) VALUES (?, ?)");
        $insertDept->execute(['Computer Science', 'CS']);
        if (DB_TYPE === 'mysql') {
            $csDeptId = $pdo->lastInsertId();
        } else {
            $csDeptId = $pdo->lastInsertId();
        }
        echo "Created CS Department.\n";
    } else {
        echo "CS Department already exists.\n";
    }

    // Check if BCA Course exists
    $courseStmt = $pdo->prepare("SELECT id FROM courses WHERE code = ?");
    $courseStmt->execute(['BCA']);
    $bcaCourseId = $courseStmt->fetchColumn();

    if (!$bcaCourseId) {
        $insertCourse = $pdo->prepare("INSERT INTO courses (department_id, name, code) VALUES (?, ?, ?)");
        $insertCourse->execute([$csDeptId, 'Bachelor of Computer Applications', 'BCA']);
        $bcaCourseId = $pdo->lastInsertId();
        echo "Created BCA Course.\n";
    } else {
        echo "BCA Course already exists.\n";
    }

    // Get Instructor Id
    $instStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $instStmt->execute(['instructor@exam.com']);
    $instructorId = $instStmt->fetchColumn();

    echo "\nSeeding a dummy exam and questions...\n";
    
    // Check if exam exists
    $examStmt = $pdo->prepare("SELECT id FROM exams WHERE title = ?");
    $examStmt->execute(['Computer Networks Midterm']);
    $examId = $examStmt->fetchColumn();

    if (!$examId) {
        $insertExam = $pdo->prepare("INSERT INTO exams (course_id, instructor_id, title, duration_minutes, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)");
        $startTime = date('Y-m-d H:i:s');
        $endTime = date('Y-m-d H:i:s', strtotime('+30 days'));
        $insertExam->execute([$bcaCourseId, $instructorId, 'Computer Networks Midterm', 15, $startTime, $endTime]);
        $examId = $pdo->lastInsertId();
        echo "Created exam: Computer Networks Midterm.\n";

        // Seed 5 questions
        $questions = [
            [
                'text' => 'Which layer of the OSI model is responsible for routing packets?',
                'a' => 'Physical Layer',
                'b' => 'Data Link Layer',
                'c' => 'Network Layer',
                'd' => 'Transport Layer',
                'correct' => 'C',
                'points' => 2
            ],
            [
                'text' => 'HTTP operates at which layer of the OSI model?',
                'a' => 'Application Layer',
                'b' => 'Presentation Layer',
                'c' => 'Session Layer',
                'd' => 'Transport Layer',
                'correct' => 'A',
                'points' => 2
            ],
            [
                'text' => 'What does DNS stand for?',
                'a' => 'Domain Network Service',
                'b' => 'Domain Name System',
                'c' => 'Digital Network System',
                'd' => 'Distributed Name Service',
                'correct' => 'B',
                'points' => 2
            ],
            [
                'text' => 'Is IPv6 address 128-bit long?',
                'a' => 'True',
                'b' => 'False',
                'c' => 'Depends on network class',
                'd' => 'None of the above',
                'correct' => 'A',
                'points' => 2
            ],
            [
                'text' => 'Which protocol is connection-oriented?',
                'a' => 'UDP',
                'b' => 'IP',
                'c' => 'TCP',
                'd' => 'ICMP',
                'correct' => 'C',
                'points' => 2
            ]
        ];

        $insertQuest = $pdo->prepare("INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option, points) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($questions as $q) {
            $insertQuest->execute([$examId, $q['text'], $q['a'], $q['b'], $q['c'], $q['d'], $q['correct'], $q['points']]);
        }
        echo "Seeded 5 sample questions for the exam.\n";
    } else {
        echo "Exam and questions already seeded.\n";
    }

    echo "\nDatabase setup and seeding completed successfully!\n";

} catch (PDOException $e) {
    echo "\nError running database setup: " . $e->getMessage() . "\n";
}
?>
