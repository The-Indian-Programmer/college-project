<?php
// instructor/dashboard.php
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/header.php';

restrict_to(['instructor']);

$instructorId = $_SESSION['user_id'];

// Fetch instructor-specific stats
$examCount = $pdo->prepare("SELECT COUNT(*) FROM exams WHERE instructor_id = ?");
$examCount->execute([$instructorId]);
$totalExams = $examCount->fetchColumn();

$questionCount = $pdo->prepare("SELECT COUNT(*) FROM questions q JOIN exams e ON q.exam_id = e.id WHERE e.instructor_id = ?");
$questionCount->execute([$instructorId]);
$totalQuestions = $questionCount->fetchColumn();

$completionCount = $pdo->prepare("SELECT COUNT(*) FROM student_exams se JOIN exams e ON se.exam_id = e.id WHERE e.instructor_id = ? AND se.status = 'completed'");
$completionCount->execute([$instructorId]);
$totalCompletions = $completionCount->fetchColumn();

// Fetch instructor's exams with attendee stats
$stmt = $pdo->prepare("
    SELECT e.*, c.code as course_code,
    (SELECT COUNT(*) FROM student_exams se WHERE se.exam_id = e.id) as total_attempts,
    (SELECT AVG(se.score) FROM student_exams se WHERE se.exam_id = e.id AND se.status = 'completed') as avg_score
    FROM exams e 
    JOIN courses c ON e.course_id = c.id
    WHERE e.instructor_id = ? 
    ORDER BY e.start_time DESC
");
$stmt->execute([$instructorId]);
$examsList = $stmt->fetchAll();
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div>
            <h2 style="font-size: 16px; font-weight: 700; color: var(--secondary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px;">Faculty Panel</h2>
            <ul class="sidebar-nav">
                <li><a href="/instructor/dashboard.php" class="sidebar-link active"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                <li><a href="/instructor/exams.php" class="sidebar-link"><i class="fa-solid fa-file-invoice"></i> Manage Exams</a></li>
                <li><a href="/instructor/questions.php" class="sidebar-link"><i class="fa-solid fa-circle-question"></i> Question Bank</a></li>
            </ul>
        </div>
        <div style="margin-top: auto;">
            <a href="/logout.php" class="sidebar-link" style="color: var(--danger);"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <h1 class="page-title">Instructor Dashboard</h1>
        <p class="page-subtitle">Schedule exams, configure questions, and review grading indices</p>

        <!-- Stats -->
        <div class="metrics-grid">
            <div class="card metric-card">
                <div class="metric-icon" style="background-color: rgba(168, 85, 247, 0.1); color: var(--secondary);">
                    <i class="fa-solid fa-file-signature"></i>
                </div>
                <div class="metric-info">
                    <h3>My Exams</h3>
                    <p><?php echo $totalExams; ?></p>
                </div>
            </div>

            <div class="card metric-card">
                <div class="metric-icon" style="background-color: rgba(99, 102, 241, 0.1); color: var(--primary);">
                    <i class="fa-solid fa-circle-question"></i>
                </div>
                <div class="metric-info">
                    <h3>Total Questions</h3>
                    <p><?php echo $totalQuestions; ?></p>
                </div>
            </div>

            <div class="card metric-card">
                <div class="metric-icon" style="background-color: rgba(16, 185, 129, 0.1); color: var(--success);">
                    <i class="fa-solid fa-user-check"></i>
                </div>
                <div class="metric-info">
                    <h3>Exam Attempts</h3>
                    <p><?php echo $totalCompletions; ?></p>
                </div>
            </div>
        </div>

        <!-- Exams List -->
        <div class="card">
            <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;"><i class="fa-solid fa-folder-open" style="color: var(--secondary); margin-right: 8px;"></i> My Examinations Overview</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Exam Code/Title</th>
                            <th>Timeline (Start - End)</th>
                            <th>Duration</th>
                            <th>Attempts</th>
                            <th>Avg Score</th>
                            <th style="text-align: right;">Analytics</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($examsList)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted);">No exams scheduled yet. Go to <a href="exams.php" style="color: var(--primary); font-weight:600;">Manage Exams</a> to schedule one.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($examsList as $exam): ?>
                                <tr>
                                    <td>
                                        <span class="badge" style="background: rgba(168, 85, 247, 0.1); color: var(--secondary); font-weight:700; margin-bottom: 4px;"><?php echo htmlspecialchars($exam['course_code']); ?></span><br>
                                        <strong><?php echo htmlspecialchars($exam['title']); ?></strong>
                                    </td>
                                    <td style="font-size: 13px; color: var(--text-muted);">
                                        <span style="color: var(--text-main);"><?php echo date('M d, Y h:i A', strtotime($exam['start_time'])); ?></span><br>
                                        to <?php echo date('M d, Y h:i A', strtotime($exam['end_time'])); ?>
                                    </td>
                                    <td><?php echo $exam['duration_minutes']; ?> Mins</td>
                                    <td>
                                        <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: var(--success); font-weight:700;">
                                            <?php echo $exam['total_attempts']; ?> students
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo $exam['avg_score'] !== null ? number_format($exam['avg_score'], 1) : '-'; ?></strong>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="/instructor/exam_details.php?id=<?php echo $exam['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 6px 12px;">
                                            <i class="fa-solid fa-chart-line"></i> View Report
                                        </a>
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
