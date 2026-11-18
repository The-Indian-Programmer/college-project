# Online Examination System (ExamPortal)

This project is a fully-featured, highly-polished **Online Examination System** web application built with **PHP, HTML, CSS, JavaScript, and MySQL / SQLite**. It is designed as a template for a Bachelor of Computer Applications (BCA) academic project, referenced from [Online-Examination-System-Synopsis.pdf](Online-Examination-System-Synopsis.pdf).

## Features

- **Database Portability**: Detects MySQL (running locally on port 3306) and falls back dynamically to local **SQLite** (`online_exam.db`) for immediate testing with zero setup.
- **Admin Panel**: Add and remove students and instructors, toggle user accounts (Active/Inactive), and create departments and course codes.
- **Instructor Dashboard**: Schedule examinations, construct question banks (MCQs and True/False questions), and view student performance and attendance metrics.
- **Student Portal**: Take scheduled active examinations under a live countdown timer, select answers, submit for instant grading, and review scorecards.
- **Responsive Layout**: Designed with Vanilla CSS, featuring a beautiful glassmorphic dark theme, custom typography, animations, and icons.

---

## Getting Started

### 1. Start the PHP Built-in Server
Open your terminal in the project directory and run the following command to bind the application to local port 8000:
```bash
php -S 127.0.0.1:8000
```

### 2. Initialize the Database
Before logging in, run the database builder to construct the schema tables and load default seed records. You can do this in two ways:
- **Option A (Terminal)**:
  ```bash
  php db_setup.php
  ```
- **Option B (Browser)**:
  Open your web browser and navigate to: [http://127.0.0.1:8000/db_setup.php](http://127.0.0.1:8000/db_setup.php)

---

## Test Accounts (Seeded Credentials)

The following login credentials are loaded during setup for immediate evaluation:

| Portal / Role | Email Address | Password | Features |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@exam.com` | `admin123` | Add/edit users, toggle status, define courses |
| **Instructor** | `instructor@exam.com` | `instructor123` | Schedule exams, add questions, view reports |
| **Student** | `student@exam.com` | `student123` | Take live timed tests, view score reviews |

---

## File Structure

```
├── admin/
│   ├── dashboard.php         # Admin statistics & recent registration log
│   ├── students.php          # Manage student profiles, credentials & statuses
│   ├── instructors.php       # Manage instructor profiles
│   └── departments.php       # Manage academic branches & course lists
│
├── instructor/
│   ├── dashboard.php         # Faculty metrics, exam checklists
│   ├── exams.php             # Schedule timed exams
│   ├── questions.php         # Manage MCQs & True/False questions
│   └── exam_details.php      # Student attendance, scorecard reports
│
├── student/
│   ├── dashboard.php         # Active exam listings, past score summaries
│   ├── take_exam.php         # Timed test client with question-swapping JS
│   └── results.php           # Score review and answer breakdowns
│
├── includes/
│   ├── header.php            # Shared navbar with role-based restriction helpers
│   └── footer.php            # Page closures and copyright notes
│
├── config/
│   └── db.php                # PDO connector supporting MySQL & SQLite
│
├── css/
│   └── style.css             # Main stylesheet (Glassmorphic dark theme)
│
├── db_setup.php              # Table generator and mock seeder script
├── login.php                 # User login page
├── register.php              # Student registration page
├── forgot_password.php       # Mock password recovery simulator
├── change_password.php       # User password updater
├── logout.php                # Session destroyer
└── README.md                 # Instructions manual
```
