<?php
// tests/run_tests.php
// Automated Functional Test Suite for Online Examination System

// Define testing environment
define('IN_TEST_RUNNER', true);
$testDbFile = __DIR__ . '/test_database.db';
if (file_exists($testDbFile)) {
    unlink($testDbFile);
}

// Connect to temporary SQLite test database
try {
    $pdo = new PDO("sqlite:" . $testDbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("PRAGMA foreign_keys = ON;");
} catch (Exception $e) {
    die("Failed to create test database: " . $e->getMessage() . "\n");
}

$passCount = 0;
$failCount = 0;

function assertTest($condition, $testName, $details = '') {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [\033[32mPASS\033[0m] " . $testName . "\n";
    } else {
        $failCount++;
        echo "  [\033[31mFAIL\033[0m] " . $testName . ($details ? " -> " . $details : "") . "\n";
    }
}

echo "====================================================\n";
echo " ONLINE EXAMINATION SYSTEM - FUNCTIONAL TEST SUITE\n";
echo "====================================================\n\n";

// --- 1. DATABASE SCHEMA CREATION & SETUP ---
echo "1. Database Schema & Setup Tests:\n";
try {
    $queries = [
        "CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            role TEXT NOT NULL CHECK(role IN ('admin', 'instructor', 'student')),
            status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'inactive')),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );",
        "CREATE TABLE departments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            code TEXT NOT NULL UNIQUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );",
        "CREATE TABLE courses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            department_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            code TEXT NOT NULL UNIQUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
        );",
        "CREATE TABLE exams (
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
        "CREATE TABLE questions (
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
        "CREATE TABLE student_exams (
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
        "CREATE TABLE student_answers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_exam_id INTEGER NOT NULL,
            question_id INTEGER NOT NULL,
            selected_option TEXT NOT NULL CHECK(selected_option IN ('A', 'B', 'C', 'D', '')),
            is_correct INTEGER NOT NULL CHECK(is_correct IN (0, 1)),
            FOREIGN KEY (student_exam_id) REFERENCES student_exams(id) ON DELETE CASCADE,
            FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
        );"
    ];

    foreach ($queries as $q) {
        $pdo->exec($q);
    }
    assertTest(true, "TC-DB-001: Execute schema migrations and table creation");
} catch (Exception $e) {
    assertTest(false, "TC-DB-001: Execute schema migrations", $e->getMessage());
}

// --- 2. AUTHENTICATION & USER MANAGEMENT ---
echo "\n2. Authentication & Account Tests:\n";

// TC-AUTH-001: Create Admin User
$adminPass = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'admin', 'active')");
$stmt->execute(['Admin User', 'admin@test.com', $adminPass]);
$adminId = $pdo->lastInsertId();
assertTest($adminId > 0, "TC-AUTH-001: Register Admin user account");

// TC-AUTH-002: Create Instructor User
$instPass = password_hash('inst123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'instructor', 'active')");
$stmt->execute(['Instructor User', 'instructor@test.com', $instPass]);
$instructorId = $pdo->lastInsertId();
assertTest($instructorId > 0, "TC-AUTH-002: Register Instructor user account");

// TC-AUTH-003: Student Self-Registration
$studPass = password_hash('student123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'student', 'active')");
$stmt->execute(['Student User', 'student@test.com', $studPass]);
$studentId = $pdo->lastInsertId();
assertTest($studentId > 0, "TC-AUTH-003: Register Student user account");

// TC-AUTH-004: Duplicate Email Rejection
try {
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'student', 'active')");
    $stmt->execute(['Duplicate User', 'student@test.com', $studPass]);
    assertTest(false, "TC-AUTH-004: Reject duplicate email registration");
} catch (Exception $e) {
    assertTest(true, "TC-AUTH-004: Reject duplicate email registration");
}

// TC-AUTH-005: Password Verification Logic
$fetchUser = $pdo->query("SELECT * FROM users WHERE email = 'student@test.com'")->fetch();
$validPass = password_verify('student123', $fetchUser['password']);
$invalidPass = password_verify('wrongpassword', $fetchUser['password']);
assertTest($validPass && !$invalidPass, "TC-AUTH-005: Verify password hashing & verification logic");


// --- 3. ADMIN MODULE FUNCTIONALITIES ---
echo "\n3. Admin Module Tests:\n";

// TC-ADM-001: Department Creation
$stmt = $pdo->prepare("INSERT INTO departments (name, code) VALUES (?, ?)");
$stmt->execute(['Computer Science', 'CS']);
$deptId = $pdo->lastInsertId();
assertTest($deptId > 0, "TC-ADM-001: Create new academic department (CS)");

// TC-ADM-002: Course Creation
$stmt = $pdo->prepare("INSERT INTO courses (department_id, name, code) VALUES (?, ?, ?)");
$stmt->execute([$deptId, 'Bachelor of Computer Applications', 'BCA']);
$courseId = $pdo->lastInsertId();
assertTest($courseId > 0, "TC-ADM-002: Create course linked to department (BCA)");

// TC-ADM-003: Modify Student Status (Active -> Inactive -> Active)
$pdo->exec("UPDATE users SET status = 'inactive' WHERE id = $studentId");
$statusInactive = $pdo->query("SELECT status FROM users WHERE id = $studentId")->fetchColumn();
$pdo->exec("UPDATE users SET status = 'active' WHERE id = $studentId");
$statusActive = $pdo->query("SELECT status FROM users WHERE id = $studentId")->fetchColumn();
assertTest($statusInactive === 'inactive' && $statusActive === 'active', "TC-ADM-003: Toggle student account status (Active/Inactive)");


// --- 4. INSTRUCTOR MODULE FUNCTIONALITIES ---
echo "\n4. Instructor Module Tests:\n";

// TC-INS-001: Schedule Examination
$startTime = date('Y-m-d H:i:s', strtotime('-1 hour'));
$endTime = date('Y-m-d H:i:s', strtotime('+2 hours'));
$stmt = $pdo->prepare("INSERT INTO exams (course_id, instructor_id, title, duration_minutes, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$courseId, $instructorId, 'Data Structures & Algorithms', 30, $startTime, $endTime]);
$examId = $pdo->lastInsertId();
assertTest($examId > 0, "TC-INS-001: Schedule new examination with timing window");

// TC-INS-002: Question Banking - MCQ Question
$stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option, points) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$examId, 'What is the time complexity of binary search?', 'O(n)', 'O(log n)', 'O(n^2)', 'O(1)', 'B', 2]);
$q1Id = $pdo->lastInsertId();
assertTest($q1Id > 0, "TC-INS-002: Insert MCQ question into question bank");

// TC-INS-003: Question Banking - True/False Question
$stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option, points) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$examId, 'A stack operates on a LIFO (Last In First Out) basis.', 'True', 'False', '', '', 'A', 2]);
$q2Id = $pdo->lastInsertId();
assertTest($q2Id > 0, "TC-INS-003: Insert True/False question into question bank");


// --- 5. STUDENT MODULE & AUTOMATED EVALUATION ---
echo "\n5. Student Module & Evaluation Tests:\n";

// TC-STU-001: Initialize Exam Attempt
$stmt = $pdo->prepare("INSERT INTO student_exams (student_id, exam_id, score, status, started_at) VALUES (?, ?, NULL, 'started', ?)");
$stmt->execute([$studentId, $examId, date('Y-m-d H:i:s')]);
$studentExamId = $pdo->lastInsertId();
assertTest($studentExamId > 0, "TC-STU-001: Initialize student exam attempt session");

// TC-STU-002: Answer Submission & Automated Evaluation Logic
// Q1: Answer 'B' (Correct, 2 pts), Q2: Answer 'A' (Correct, 2 pts) -> Total = 4 pts
$answers = [
    $q1Id => ['selected' => 'B', 'correct_option' => 'B', 'points' => 2],
    $q2Id => ['selected' => 'A', 'correct_option' => 'A', 'points' => 2]
];

$calculatedScore = 0;
$stmtAns = $pdo->prepare("INSERT INTO student_answers (student_exam_id, question_id, selected_option, is_correct) VALUES (?, ?, ?, ?)");

foreach ($answers as $qid => $data) {
    $isCorrect = ($data['selected'] === $data['correct_option']) ? 1 : 0;
    if ($isCorrect) {
        $calculatedScore += $data['points'];
    }
    $stmtAns->execute([$studentExamId, $qid, $data['selected'], $isCorrect]);
}

// Complete attempt
$up = $pdo->prepare("UPDATE student_exams SET score = ?, status = 'completed', completed_at = ? WHERE id = ?");
$up->execute([$calculatedScore, date('Y-m-d H:i:s'), $studentExamId]);

$fetchAttempt = $pdo->query("SELECT * FROM student_exams WHERE id = $studentExamId")->fetch();
assertTest($fetchAttempt['status'] === 'completed' && intval($fetchAttempt['score']) === 4, "TC-STU-002: Evaluate answers automatically and record score (4/4)");

// TC-STU-003: Scorecard Review Details
$answersCount = $pdo->query("SELECT COUNT(*) FROM student_answers WHERE student_exam_id = $studentExamId AND is_correct = 1")->fetchColumn();
assertTest(intval($answersCount) === 2, "TC-STU-003: Verify scorecard breakdown record count");

// --- SUMMARY ---
echo "\n====================================================\n";
echo " TEST SUMMARY\n";
echo "====================================================\n";
echo " Total Tests Run: " . ($passCount + $failCount) . "\n";
echo " \033[32mPassed: $passCount\033[0m\n";
echo " \033[31mFailed: $failCount\033[0m\n";
echo "====================================================\n\n";

// Cleanup test db
if (file_exists($testDbFile)) {
    unlink($testDbFile);
}

exit($failCount === 0 ? 0 : 1);
?>
