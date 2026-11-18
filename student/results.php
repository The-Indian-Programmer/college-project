<?php
// student/results.php
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/header.php';

// Enforce login
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$attempt_id = isset($_GET['attempt_id']) ? intval($_GET['attempt_id']) : 0;

// Fetch attempt details
$stmt = $pdo->prepare("
    SELECT se.*, e.title as exam_title, e.duration_minutes, c.code as course_code, u.name as student_name
    FROM student_exams se
    JOIN exams e ON se.exam_id = e.id
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON se.student_id = u.id
    WHERE se.id = ?
");
$stmt->execute([$attempt_id]);
$attempt = $stmt->fetch();

if (!$attempt) {
    echo "<div class='container' style='padding: 50px;'><div class='alert alert-danger'>Scorecard attempt not found.</div><a href='/index.php' class='btn btn-secondary'>Back to Portal Home</a></div>";
    require_once dirname(__DIR__) . '/includes/footer.php';
    exit;
}

// Student security check (students can only view their own results)
if ($_SESSION['role'] === 'student' && $attempt['student_id'] != $_SESSION['user_id']) {
    echo "<div class='container' style='padding: 50px;'><div class='alert alert-danger'>Access Denied: You are not authorized to view this report.</div><a href='/student/dashboard.php' class='btn btn-secondary'>Back to Dashboard</a></div>";
    require_once dirname(__DIR__) . '/includes/footer.php';
    exit;
}

// Fetch questions and answers
$stmt = $pdo->prepare("
    SELECT q.*, sa.selected_option, sa.is_correct
    FROM questions q
    LEFT JOIN student_answers sa ON q.id = sa.question_id AND sa.student_exam_id = ?
    WHERE q.exam_id = ?
    ORDER BY q.id ASC
");
$stmt->execute([$attempt_id, $attempt['exam_id']]);
$details = $stmt->fetchAll();

// Calculate total points possible
$totalPointsPossible = 0;
foreach ($details as $d) {
    $totalPointsPossible += $d['points'];
}

$percentage = $totalPointsPossible > 0 ? ($attempt['score'] / $totalPointsPossible) * 100 : 0;
$passed = $percentage >= 50;
?>

<div class="container" style="padding: 40px 24px; flex: 1;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 30px;">
        <div>
            <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: var(--success); font-weight:700;"><?php echo htmlspecialchars($attempt['course_code']); ?> Scorecard</span>
            <h1 style="font-size: 28px; font-weight: 700; margin-top: 8px;">Results: <?php echo htmlspecialchars($attempt['exam_title']); ?></h1>
        </div>
        <a href="<?php echo $_SESSION['role'] === 'student' ? '/student/dashboard.php' : '/instructor/exam_details.php?id=' . $attempt['exam_id']; ?>" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Back to Panel
        </a>
    </div>

    <!-- Scoring Card Panel -->
    <div class="card" style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 40px; align-items: center;">
        <div>
            <h2 style="font-size: 20px; font-weight:700; margin-bottom: 8px;">Performance Summary</h2>
            <p style="color: var(--text-muted); font-size:14px; margin-bottom: 20px;">Candidate Name: <strong><?php echo htmlspecialchars($attempt['student_name']); ?></strong> | Duration: <?php echo $attempt['duration_minutes']; ?> Mins</p>
            
            <div style="display:flex; gap:30px;">
                <div>
                    <span style="font-size: 13px; color: var(--text-muted); display:block; text-transform:uppercase;">Score Secured</span>
                    <strong style="font-size: 28px; color: <?php echo $passed ? 'var(--success)' : 'var(--danger)'; ?>;"><?php echo $attempt['score']; ?> / <?php echo $totalPointsPossible; ?></strong>
                </div>
                <div>
                    <span style="font-size: 13px; color: var(--text-muted); display:block; text-transform:uppercase;">Grade Index</span>
                    <strong style="font-size: 28px; color: <?php echo $passed ? 'var(--success)' : 'var(--danger)'; ?>;"><?php echo number_format($percentage, 1); ?>%</strong>
                </div>
                <div>
                    <span style="font-size: 13px; color: var(--text-muted); display:block; text-transform:uppercase;">Outcome</span>
                    <span class="badge <?php echo $passed ? 'badge-success' : 'badge-danger'; ?>" style="font-size: 16px; padding: 6px 16px; margin-top: 4px;">
                        <?php echo $passed ? 'PASSED' : 'FAILED'; ?>
                    </span>
                </div>
            </div>
        </div>

        <div style="text-align: center; border-left: 1px solid var(--border-glass); padding-left: 30px;">
            <i class="fa-solid <?php echo $passed ? 'fa-square-check' : 'fa-circle-xmark'; ?>" style="font-size: 80px; color: <?php echo $passed ? 'var(--success)' : 'var(--danger)'; ?>; margin-bottom: 12px;"></i>
            <h3 style="font-size: 16px;"><?php echo $passed ? 'Excellent Work!' : 'Requires Revision'; ?></h3>
        </div>
    </div>

    <!-- Question-by-Question Review -->
    <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;"><i class="fa-solid fa-list-check" style="color: var(--primary); margin-right: 8px;"></i> Question Review Breakdown</h2>
    
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <?php foreach ($details as $index => $q): ?>
            <div class="card" style="border-left: 4px solid <?php echo $q['is_correct'] ? 'var(--success)' : 'var(--danger)'; ?>;">
                <div class="flex-between" style="margin-bottom: 15px;">
                    <span style="font-size: 13px; font-weight: 600; color: var(--text-muted);">QUESTION <?php echo ($index + 1); ?></span>
                    <span class="badge <?php echo $q['is_correct'] ? 'badge-success' : 'badge-danger'; ?>">
                        <i class="fa-solid <?php echo $q['is_correct'] ? 'fa-check' : 'fa-xmark'; ?>"></i> 
                        <?php echo $q['is_correct'] ? '+' . $q['points'] . ' Pts' : '0 Pts'; ?>
                    </span>
                </div>

                <p style="font-size: 16px; font-weight: 600; margin-bottom: 18px;"><?php echo htmlspecialchars($q['question_text']); ?></p>

                <!-- Options -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; font-size:14px;">
                    <div style="padding: 10px 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-glass); background: <?php 
                        if ($q['correct_option'] === 'A') echo 'rgba(16, 185, 129, 0.1); border-color: var(--success);';
                        elseif ($q['selected_option'] === 'A') echo 'rgba(239, 68, 68, 0.1); border-color: var(--danger);';
                    ?>">
                        <strong>A.</strong> <?php echo htmlspecialchars($q['option_a']); ?>
                    </div>

                    <div style="padding: 10px 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-glass); background: <?php 
                        if ($q['correct_option'] === 'B') echo 'rgba(16, 185, 129, 0.1); border-color: var(--success);';
                        elseif ($q['selected_option'] === 'B') echo 'rgba(239, 68, 68, 0.1); border-color: var(--danger);';
                    ?>">
                        <strong>B.</strong> <?php echo htmlspecialchars($q['option_b']); ?>
                    </div>

                    <?php if (!empty($q['option_c'])): ?>
                        <div style="padding: 10px 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-glass); background: <?php 
                            if ($q['correct_option'] === 'C') echo 'rgba(16, 185, 129, 0.1); border-color: var(--success);';
                            elseif ($q['selected_option'] === 'C') echo 'rgba(239, 68, 68, 0.1); border-color: var(--danger);';
                        ?>">
                            <strong>C.</strong> <?php echo htmlspecialchars($q['option_c']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($q['option_d'])): ?>
                        <div style="padding: 10px 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-glass); background: <?php 
                            if ($q['correct_option'] === 'D') echo 'rgba(16, 185, 129, 0.1); border-color: var(--success);';
                            elseif ($q['selected_option'] === 'D') echo 'rgba(239, 68, 68, 0.1); border-color: var(--danger);';
                        ?>">
                            <strong>D.</strong> <?php echo htmlspecialchars($q['option_d']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 15px; font-size:13px; color: var(--text-muted); display:flex; gap:15px; border-top:1px dashed var(--border-glass); padding-top:12px;">
                    <span>Your Selection: <strong style="color: <?php echo $q['is_correct'] ? 'var(--success)' : 'var(--danger)'; ?>;"><?php echo !empty($q['selected_option']) ? $q['selected_option'] : 'None (Skipped)'; ?></strong></span>
                    <span>Correct Answer: <strong style="color: var(--success);"><?php echo $q['correct_option']; ?></strong></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
