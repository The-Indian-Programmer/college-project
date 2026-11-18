<?php
// student/dashboard.php
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/header.php';

restrict_to(['student']);

$studentId = $_SESSION['user_id'];
$currentTime = date('Y-m-d H:i:s');

// Fetch completed exams stats
$completedStmt = $pdo->prepare("SELECT COUNT(*) FROM student_exams WHERE student_id = ? AND status = 'completed'");
$completedStmt->execute([$studentId]);
$completedCount = $completedStmt->fetchColumn();

// Fetch average score
$avgStmt = $pdo->prepare("SELECT AVG(score) FROM student_exams WHERE student_id = ? AND status = 'completed'");
$avgStmt->execute([$studentId]);
$avgScore = $avgStmt->fetchColumn();

// Fetch active exams currently available to take
// Active window: start_time <= current_time <= end_time, and not yet completed
$stmt = $pdo->prepare("
    SELECT e.*, c.code as course_code, se.status as attempt_status, se.id as attempt_id
    FROM exams e
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN student_exams se ON e.id = se.exam_id AND se.student_id = ?
    WHERE ? BETWEEN e.start_time AND e.end_time
      AND (se.status IS NULL OR se.status = 'started')
    ORDER BY e.end_time ASC
");
$stmt->execute([$studentId, $currentTime]);
$availableExams = $stmt->fetchAll();

// Fetch history of completed exams
$stmt = $pdo->prepare("
    SELECT se.*, e.title, c.code as course_code
    FROM student_exams se
    JOIN exams e ON se.exam_id = e.id
    JOIN courses c ON e.course_id = c.id
    WHERE se.student_id = ? AND se.status = 'completed'
    ORDER BY se.completed_at DESC
");
$stmt->execute([$studentId]);
$completedExams = $stmt->fetchAll();
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div>
            <h2 style="font-size: 16px; font-weight: 700; color: var(--success); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px;">Student Hub</h2>
            <ul class="sidebar-nav">
                <li><a href="/student/dashboard.php" class="sidebar-link active"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                <li><a href="/student/dashboard.php#completed-section" class="sidebar-link"><i class="fa-solid fa-square-poll-vertical"></i> Exam History</a></li>
            </ul>
        </div>
        <div style="margin-top: auto;">
            <a href="/logout.php" class="sidebar-link" style="color: var(--danger);"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <h1 class="page-title">Student Dashboard</h1>
        <p class="page-subtitle">Attend active examinations, view scores, and track progress</p>

        <!-- Stats -->
        <div class="metrics-grid">
            <div class="card metric-card">
                <div class="metric-icon" style="background-color: rgba(99, 102, 241, 0.1); color: var(--primary);">
                    <i class="fa-solid fa-scroll"></i>
                </div>
                <div class="metric-info">
                    <h3>Available Tests</h3>
                    <p><?php echo count($availableExams); ?></p>
                </div>
            </div>

            <div class="card metric-card">
                <div class="metric-icon" style="background-color: rgba(16, 185, 129, 0.1); color: var(--success);">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="metric-info">
                    <h3>Tests Completed</h3>
                    <p><?php echo $completedCount; ?></p>
                </div>
            </div>

            <div class="card metric-card">
                <div class="metric-icon" style="background-color: rgba(245, 158, 11, 0.1); color: var(--warning);">
                    <i class="fa-solid fa-star"></i>
                </div>
                <div class="metric-info">
                    <h3>Average Marks</h3>
                    <p><?php echo $avgScore !== null ? number_format($avgScore, 1) : 'N/A'; ?></p>
                </div>
            </div>
        </div>

        <!-- Available Exams -->
        <div class="card" style="margin-bottom: 40px;">
            <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;"><i class="fa-solid fa-pen-fancy" style="color: var(--primary); margin-right: 8px;"></i> Active Tests Available</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Course Subject</th>
                            <th>Exam Description</th>
                            <th>Duration</th>
                            <th>Closing Date</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($availableExams)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted);">No exams are currently active. Please contact your instructor.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($availableExams as $ex): ?>
                                <tr>
                                    <td><span class="badge" style="background: rgba(99, 102, 241, 0.1); color: var(--primary); font-weight:700;"><?php echo htmlspecialchars($ex['course_code']); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($ex['title']); ?></strong></td>
                                    <td><?php echo $ex['duration_minutes']; ?> Mins</td>
                                    <td style="font-size: 13px; color: var(--text-muted);"><?php echo date('M d, Y h:i A', strtotime($ex['end_time'])); ?></td>
                                    <td style="text-align: right;">
                                        <a href="/student/take_exam.php?id=<?php echo $ex['id']; ?>" class="btn btn-primary btn-sm" style="box-shadow:none;">
                                            <i class="fa-solid fa-circle-play"></i> <?php echo $ex['attempt_status'] === 'started' ? 'Resume Exam' : 'Start Exam'; ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Completed Exams (History) -->
        <div class="card" id="completed-section">
            <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;"><i class="fa-solid fa-clock-rotate-left" style="color: var(--success); margin-right: 8px;"></i> Past Examination Scores</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Exam Title</th>
                            <th>Date Completed</th>
                            <th>Score Secured</th>
                            <th style="text-align: right;">Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($completedExams)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted);">You have not completed any exams yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($completedExams as $ce): ?>
                                <tr>
                                    <td><span class="badge" style="background: rgba(16, 185, 129, 0.1); color: var(--success); font-weight:700;"><?php echo htmlspecialchars($ce['course_code']); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($ce['title']); ?></strong></td>
                                    <td style="font-size: 13px; color: var(--text-muted);"><?php echo $ce['completed_at']; ?></td>
                                    <td><strong><?php echo $ce['score']; ?> Points</strong></td>
                                    <td style="text-align: right;">
                                        <a href="/student/results.php?attempt_id=<?php echo $ce['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 6px 12px;">
                                            <i class="fa-solid fa-square-poll-vertical"></i> View Scorecard
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
