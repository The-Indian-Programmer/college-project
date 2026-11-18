<?php
// instructor/exam_details.php
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/header.php';

restrict_to(['instructor']);

$instructorId = $_SESSION['user_id'];
$exam_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verify exam ownership
$stmt = $pdo->prepare("
    SELECT e.*, c.name as course_name, c.code as course_code
    FROM exams e 
    JOIN courses c ON e.course_id = c.id
    WHERE e.id = ? AND e.instructor_id = ?
");
$stmt->execute([$exam_id, $instructorId]);
$exam = $stmt->fetch();

if (!$exam) {
    echo "<div class='container' style='padding: 50px;'><div class='alert alert-danger'>Exam not found or access denied.</div><a href='/instructor/dashboard.php' class='btn btn-secondary'>Back to Dashboard</a></div>";
    require_once dirname(__DIR__) . '/includes/footer.php';
    exit;
}

// Fetch totals
$totalRegisteredStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active'")->fetchColumn();

// Fetch attempts/attendees
$stmt = $pdo->prepare("
    SELECT se.*, u.name as student_name, u.email as student_email
    FROM student_exams se
    JOIN users u ON se.student_id = u.id
    WHERE se.exam_id = ?
    ORDER BY se.started_at DESC
");
$stmt->execute([$exam_id]);
$attendees = $stmt->fetchAll();
$totalAttended = count($attendees);
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div>
            <h2 style="font-size: 16px; font-weight: 700; color: var(--secondary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px;">Faculty Panel</h2>
            <ul class="sidebar-nav">
                <li><a href="/instructor/dashboard.php" class="sidebar-link"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                <li><a href="/instructor/exams.php" class="sidebar-link"><i class="fa-solid fa-file-invoice"></i> Manage Exams</a></li>
                <li><a href="/instructor/questions.php" class="sidebar-link"><i class="fa-solid fa-circle-question"></i> Question Bank</a></li>
            </ul>
        </div>
        <div style="margin-top: auto;">
            <a href="/logout.php" class="sidebar-link" style="color: var(--danger);"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 30px;">
            <div>
                <h1 class="page-title">Exam Performance Report</h1>
                <p class="page-subtitle" style="margin-bottom:0;">Detailed scorecard and analytics for <strong><?php echo htmlspecialchars($exam['title']); ?></strong> (<?php echo htmlspecialchars($exam['course_code']); ?>)</p>
            </div>
            <a href="/instructor/dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>

        <!-- Metric Boxes -->
        <div class="metrics-grid">
            <div class="card metric-card">
                <div class="metric-icon" style="background-color: rgba(99, 102, 241, 0.1); color: var(--primary);">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="metric-info">
                    <h3>Potential Takers</h3>
                    <p><?php echo $totalRegisteredStudents; ?></p>
                </div>
            </div>

            <div class="card metric-card">
                <div class="metric-icon" style="background-color: rgba(16, 185, 129, 0.1); color: var(--success);">
                    <i class="fa-solid fa-user-check"></i>
                </div>
                <div class="metric-info">
                    <h3>Actual Attendees</h3>
                    <p><?php echo $totalAttended; ?></p>
                </div>
            </div>

            <div class="card metric-card">
                <div class="metric-icon" style="background-color: rgba(245, 158, 11, 0.1); color: var(--warning);">
                    <i class="fa-solid fa-percentage"></i>
                </div>
                <div class="metric-info">
                    <h3>Attendance Rate</h3>
                    <p><?php echo $totalRegisteredStudents > 0 ? number_format(($totalAttended / $totalRegisteredStudents) * 100, 1) . "%" : "0%"; ?></p>
                </div>
            </div>
        </div>

        <!-- Attendees Table -->
        <div class="card">
            <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;"><i class="fa-solid fa-clipboard-user" style="color: var(--secondary); margin-right: 8px;"></i> Student Attendance Details</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Email Address</th>
                            <th>Status</th>
                            <th>Time Commenced</th>
                            <th>Duration / Complete</th>
                            <th>Obtained Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendees)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted);">No student has attempted this exam yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attendees as $student): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($student['student_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($student['student_email']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $student['status'] === 'completed' ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo ucfirst($student['status']); ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 13px; color: var(--text-muted);"><?php echo $student['started_at']; ?></td>
                                    <td style="font-size: 13px; color: var(--text-muted);">
                                        <?php 
                                        if ($student['completed_at']) {
                                            $diff = strtotime($student['completed_at']) - strtotime($student['started_at']);
                                            $m = floor($diff / 60);
                                            $s = $diff % 60;
                                            echo "{$m}m {$s}s";
                                        } else {
                                            echo "In Progress";
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <strong style="font-size: 15px; color: <?php echo $student['score'] >= 5 ? 'var(--success)' : 'var(--text-main)'; ?>">
                                            <?php echo $student['score'] !== null ? $student['score'] . ' Points' : '-'; ?>
                                        </strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
