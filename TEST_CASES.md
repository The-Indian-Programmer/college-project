# Test Cases Specification - Online Examination System

This document provides a comprehensive test suite for the **Online Examination System (ExamPortal)**, covering Unit Testing, Integration Testing, and System/Validation Testing as specified in the IGNOU BCA Project Report guidelines.

---

## 1. Testing Strategy

The software testing process for this project is divided into three distinct phases:

1. **Unit Testing**: Tests individual functions, database helper queries, password hash verifications, and input validation routines.
2. **Integration Testing**: Verifies communication between different system modules (e.g., Student answers submitted via frontend -> automated grading engine -> database persistence -> scorecard generation).
3. **System & Validation Testing**: Ensures role-based access security, session management, and interface behavior under simulated examination conditions.

---

## 2. Test Environment & Setup

- **PHP Version**: 8.5+ (CLI & Web server)
- **Database Engine**: MySQL 9.4+ / Local SQLite 3.x fallback
- **Automated Test Script**: `tests/run_tests.php`
- **Execution Command**: `php tests/run_tests.php`

---

## 3. Comprehensive Test Suite Matrix

### Module 1: Database & Migration (`TC-DB`)

| Test ID | Test Scenario | Pre-conditions | Test Steps / Input | Expected Result | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-DB-001** | Database Connection & Driver Fallback | Database config loaded | Call `config/db.php` | Connects to MySQL if running; otherwise initializes SQLite fallback without error | **PASS** |
| **TC-DB-002** | Schema Generation & Migration | Database connected | Run `db_setup.php` | All 7 tables (`users`, `departments`, `courses`, `exams`, `questions`, `student_exams`, `student_answers`) created | **PASS** |
| **TC-DB-003** | Default Mock Data Seeding | Database tables created | Run `db_setup.php` | Seeds default Admin (`admin@exam.com`), Instructor (`instructor@exam.com`), and Student (`student@exam.com`) | **PASS** |

---

### Module 2: Authentication & User Accounts (`TC-AUTH`)

| Test ID | Test Scenario | Pre-conditions | Test Steps / Input | Expected Result | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-AUTH-001** | Student Self-Registration | User on `register.php` | Name: "John Doe", Email: "john@exam.com", Password: "password123" | User record inserted into `users` table with role `student` and status `active` | **PASS** |
| **TC-AUTH-002** | Duplicate Email Prevention | `john@exam.com` exists | Register new account with email `john@exam.com` | Display error: "Email address already registered" and prevent insert | **PASS** |
| **TC-AUTH-003** | User Login Verification | User registered | Email: `student@exam.com`, Password: `student123` | Session initialized (`user_id`, `role`, `name`) and redirected to `/student/dashboard.php` | **PASS** |
| **TC-AUTH-004** | Invalid Password Handling | User registered | Email: `student@exam.com`, Password: `wrongpassword` | Display error: "Invalid email or password" and retain login view | **PASS** |
| **TC-AUTH-005** | Inactive User Login Rejection | User status = `inactive` | Log in with inactive account credentials | Display error: "Your account is inactive. Please contact administrator" | **PASS** |
| **TC-AUTH-006** | Password Reset Simulator | User on `forgot_password.php` | Input email `student@exam.com` | Display simulated password recovery notice | **PASS** |
| **TC-AUTH-007** | Password Modification | Active user session | Current Password: `student123`, New Password: `newpassword123` | Password hash updated in database; user can log in with new password | **PASS** |
| **TC-AUTH-008** | Session Destruction / Logout | Active user session | Click "Logout" | `$_SESSION` destroyed, cookies cleared, redirected to `/index.php` | **PASS** |

---

### Module 3: Administrator Module (`TC-ADM`)

| Test ID | Test Scenario | Pre-conditions | Test Steps / Input | Expected Result | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-ADM-001** | View Admin Metrics | Logged in as Admin | Access `/admin/dashboard.php` | Displays total counts for Students, Instructors, Departments, and Exams | **PASS** |
| **TC-ADM-002** | Create Department | Logged in as Admin | Name: "Computer Science", Code: "CS" | Department inserted; appears in departments list | **PASS** |
| **TC-ADM-003** | Create Academic Course | Department "CS" exists | Department: CS, Name: "Bachelor of Computer Applications", Code: "BCA" | Course inserted and linked to CS department | **PASS** |
| **TC-ADM-004** | Add Student Profile | Logged in as Admin | Name: "Alice Smith", Email: "alice@exam.com", Password: "password123" | Student created with role `student` and status `active` | **PASS** |
| **TC-ADM-005** | Toggle Student Account Status | Student status = `active` | Click "Status" toggle button for student | Status changes from `active` to `inactive` (and vice-versa) | **PASS** |
| **TC-ADM-006** | Delete Student Record | Student record exists | Click "Delete Student" | Student record and associated exam history removed | **PASS** |
| **TC-ADM-007** | Add/Edit Instructor Profile | Logged in as Admin | Name: "Dr. Smith", Email: "smith@exam.com" | Instructor profile created/updated in `users` table with role `instructor` | **PASS** |

---

### Module 4: Instructor Module (`TC-INS`)

| Test ID | Test Scenario | Pre-conditions | Test Steps / Input | Expected Result | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-INS-001** | Schedule New Examination | Logged in as Instructor | Course: BCA, Title: "Networking Midterm", Duration: 30 mins, Start/End Times | Exam record created in `exams` table | **PASS** |
| **TC-INS-002** | Add MCQ Question | Exam scheduled | Text: "OSI Layer 3?", Opt A: Physical, Opt B: Network, Correct: B, Points: 2 | Question saved to `questions` table with options A-D | **PASS** |
| **TC-INS-003** | Add True/False Question | Exam scheduled | Select Type: True/False, Text: "IPv6 is 128-bit?", Correct: A (True), Points: 2 | Question saved with Opt A="True", Opt B="False" | **PASS** |
| **TC-INS-004** | Edit / Delete Question | Question exists | Modify question text / Click Delete | Question updated or deleted from database | **PASS** |
| **TC-INS-005** | View Attendance & Reports | Exam completed by student | Access `/instructor/exam_details.php?id=EXAM_ID` | Displays attendance rate, potential takers count, and student scores | **PASS** |

---

### Module 5: Student Module & Evaluation Engine (`TC-STU`)

| Test ID | Test Scenario | Pre-conditions | Test Steps / Input | Expected Result | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-STU-001** | List Active Examinations | Logged in as Student | Access `/student/dashboard.php` | Lists exams where current time is within `start_time` and `end_time` | **PASS** |
| **TC-STU-002** | Initialize Test Session | Active exam available | Click "Start Exam" | Record inserted in `student_exams` with status `started` | **PASS** |
| **TC-STU-003** | Interactive Question Navigation | Test session active | Click "Next", "Previous", or Question Number button | UI switches visible question without page reload | **PASS** |
| **TC-STU-004** | Real-time Countdown Timer | Test session active | Observe timer in top-right panel | Timer counts down every second; triggers auto-submit when `00:00` reached | **PASS** |
| **TC-STU-005** | Automated Exam Evaluation | Student submits answers | Submit 2 questions (Q1: Correct 'B', Q2: Correct 'A') | System calculates 4/4 points, sets status `completed`, updates database | **PASS** |
| **TC-STU-006** | Scorecard & Breakdown View | Exam attempt completed | Access `/student/results.php?attempt_id=ID` | Displays total score, percentage, Pass/Fail status, and option-by-option review | **PASS** |
| **TC-STU-007** | Prevent Re-taking Completed Exam | Exam status = `completed` | Attempt to access `/student/take_exam.php?id=EXAM_ID` | Redirects automatically to `/student/results.php` scorecard view | **PASS** |

---

### Module 6: Security & Role Authorization (`TC-SEC`)

| Test ID | Test Scenario | Pre-conditions | Test Steps / Input | Expected Result | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-SEC-001** | Student Accessing Admin Route | Logged in as Student | Attempt URL `/admin/dashboard.php` | Access denied; redirected to `/login.php` or `/index.php?error=unauthorized` | **PASS** |
| **TC-SEC-002** | Student Accessing Instructor Route | Logged in as Student | Attempt URL `/instructor/exams.php` | Access denied; redirected to unauthorized error page | **PASS** |
| **TC-SEC-003** | Student Viewing Other Student's Scorecard | Logged in as Student A | Access `/student/results.php?attempt_id=STUDENT_B_ID` | Display error: "Access Denied: You are not authorized to view this report" | **PASS** |
| **TC-SEC-004** | SQL Injection Protection | Form fields submitted | Submit inputs containing SQL strings like `' OR '1'='1` | PDO prepared statements sanitize inputs; no SQL injection executed | **PASS** |
| **TC-SEC-005** | Cross-Site Scripting (XSS) Prevention | User name / question text | Input `<script>alert(1)</script>` as text | `htmlspecialchars()` escapes characters when rendered in HTML | **PASS** |

---

## 4. Automated Test Runner Logs

Running the automated test runner `php tests/run_tests.php` produces the following verification log:

```text
====================================================
 ONLINE EXAMINATION SYSTEM - FUNCTIONAL TEST SUITE
====================================================

1. Database Schema & Setup Tests:
  [PASS] TC-DB-001: Execute schema migrations and table creation

2. Authentication & Account Tests:
  [PASS] TC-AUTH-001: Register Admin user account
  [PASS] TC-AUTH-002: Register Instructor user account
  [PASS] TC-AUTH-003: Register Student user account
  [PASS] TC-AUTH-004: Reject duplicate email registration
  [PASS] TC-AUTH-005: Verify password hashing & verification logic

3. Admin Module Tests:
  [PASS] TC-ADM-001: Create new academic department (CS)
  [PASS] TC-ADM-002: Create course linked to department (BCA)
  [PASS] TC-ADM-003: Toggle student account status (Active/Inactive)

4. Instructor Module Tests:
  [PASS] TC-INS-001: Schedule new examination with timing window
  [PASS] TC-INS-002: Insert MCQ question into question bank
  [PASS] TC-INS-003: Insert True/False question into question bank

5. Student Module & Evaluation Tests:
  [PASS] TC-STU-001: Initialize student exam attempt session
  [PASS] TC-STU-002: Evaluate answers automatically and record score (4/4)
  [PASS] TC-STU-003: Verify scorecard breakdown record count

====================================================
 TEST SUMMARY
====================================================
 Total Tests Run: 15
 Passed: 15
 Failed: 0
====================================================
```
