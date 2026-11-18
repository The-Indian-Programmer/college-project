<?php
// index.php
require_once __DIR__ . '/config/db.php';

$page_title = "Welcome to ExamPortal";
require_once __DIR__ . '/includes/header.php';

// Check if database tables need setup
$setup_needed = false;
try {
    $pdo->query("SELECT 1 FROM users LIMIT 1");
} catch (Exception $e) {
    $setup_needed = true;
}
?>

<div class="container" style="padding: 60px 24px; flex: 1;">
    <?php if ($setup_needed): ?>
        <div class="alert alert-danger" style="flex-direction: column; align-items: flex-start; gap: 12px; padding: 24px; border-radius: var(--radius-md);">
            <div style="display: flex; align-items: center; gap: 12px; font-size: 18px; font-weight: 600;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 24px;"></i>
                Database Initialization Required
            </div>
            <p style="color: var(--text-main); opacity: 0.9;">It looks like the database has not been initialized yet. Click the button below to automatically generate tables and seed default mock accounts.</p>
            <a href="/db_setup.php" target="_blank" class="btn btn-primary" style="margin-top: 8px;"><i class="fa-solid fa-screwdriver-wrench"></i> Run Database Setup</a>
        </div>
    <?php endif; ?>

    <div class="text-center" style="margin-bottom: 60px;">
        <h1 style="font-size: 48px; font-weight: 800; margin-bottom: 16px; background: linear-gradient(135deg, #fff 30%, var(--text-muted) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            Modern Online Examination System
        </h1>
        <p style="font-size: 18px; color: var(--text-muted); max-width: 600px; margin: 0 auto; line-height: 1.6;">
            A secure, automated platform for managing academic assessments, live grading, and examination records. Designed for Students, Instructors, and Administrators.
        </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; margin-bottom: 50px;">
        <div class="card text-center" style="display: flex; flex-direction: column; align-items: center; gap: 16px;">
            <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(99, 102, 241, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 32px;">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <h2 style="font-size: 22px; font-weight: 700;">Administrator Portal</h2>
            <p style="color: var(--text-muted); font-size: 14px; line-height: 1.5; flex: 1;">
                Manage students and instructors, configure departments, academic courses, and update accounts.
            </p>
            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
                <a href="/admin/dashboard.php" class="btn btn-primary" style="width: 100%;">Go to Admin Dashboard</a>
            <?php else: ?>
                <a href="/login.php?role=admin" class="btn btn-secondary" style="width: 100%;">Login as Admin</a>
            <?php endif; ?>
        </div>

        <div class="card text-center" style="display: flex; flex-direction: column; align-items: center; gap: 16px;">
            <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(168, 85, 247, 0.1); color: var(--secondary); display: flex; align-items: center; justify-content: center; font-size: 32px;">
                <i class="fa-solid fa-chalkboard-user"></i>
            </div>
            <h2 style="font-size: 22px; font-weight: 700;">Instructor Portal</h2>
            <p style="color: var(--text-muted); font-size: 14px; line-height: 1.5; flex: 1;">
                Schedule exam timetables, insert/update multiple choice or True/False questions, and track attendance reports.
            </p>
            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'instructor'): ?>
                <a href="/instructor/dashboard.php" class="btn btn-primary" style="width: 100%;">Go to Instructor Dashboard</a>
            <?php else: ?>
                <a href="/login.php?role=instructor" class="btn btn-secondary" style="width: 100%;">Login as Instructor</a>
            <?php endif; ?>
        </div>

        <div class="card text-center" style="display: flex; flex-direction: column; align-items: center; gap: 16px;">
            <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(16, 185, 129, 0.1); color: var(--success); display: flex; align-items: center; justify-content: center; font-size: 32px;">
                <i class="fa-solid fa-user-graduate"></i>
            </div>
            <h2 style="font-size: 22px; font-weight: 700;">Student Portal</h2>
            <p style="color: var(--text-muted); font-size: 14px; line-height: 1.5; flex: 1;">
                Register/login, review active scheduled tests, solve MCQs/True-False under a live count-down timer, and view immediate results.
            </p>
            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student'): ?>
                <a href="/student/dashboard.php" class="btn btn-primary" style="width: 100%;">Go to Student Dashboard</a>
            <?php else: ?>
                <a href="/login.php?role=student" class="btn btn-secondary" style="width: 100%;">Login as Student</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="card" style="text-align: center; max-width: 500px; margin: 0 auto; padding: 24px;">
            <h3 style="font-size: 16px; margin-bottom: 8px;">New Student?</h3>
            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 16px;">Create your personal student account to attend examinations.</p>
            <a href="/register.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-plus"></i> Register Student Account</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
