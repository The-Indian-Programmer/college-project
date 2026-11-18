<?php
// student/take_exam.php
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/header.php';

restrict_to(['student']);

$studentId = $_SESSION['user_id'];
$exam_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$currentTime = date('Y-m-d H:i:s');

// 1. Fetch exam details and verify it is active
$stmt = $pdo->prepare("
    SELECT e.*, c.code as course_code
    FROM exams e
    JOIN courses c ON e.course_id = c.id
    WHERE e.id = ? AND ? BETWEEN e.start_time AND e.end_time
");
$stmt->execute([$exam_id, $currentTime]);
$exam = $stmt->fetch();

if (!$exam) {
    echo "<div class='container' style='padding: 50px;'><div class='alert alert-danger'>This examination is not currently available or active.</div><a href='/student/dashboard.php' class='btn btn-secondary'>Back to Dashboard</a></div>";
    require_once dirname(__DIR__) . '/includes/footer.php';
    exit;
}

// 2. Check if student has already completed or started this exam
$stmt = $pdo->prepare("SELECT * FROM student_exams WHERE student_id = ? AND exam_id = ?");
$stmt->execute([$studentId, $exam_id]);
$attempt = $stmt->fetch();

if ($attempt && $attempt['status'] === 'completed') {
    header("Location: /student/results.php?attempt_id=" . $attempt['id']);
    exit;
}

if (!$attempt) {
    // Start attempt
    $ins = $pdo->prepare("INSERT INTO student_exams (student_id, exam_id, score, status, started_at) VALUES (?, ?, NULL, 'started', ?)");
    $ins->execute([$studentId, $exam_id, date('Y-m-d H:i:s')]);
    
    // Re-fetch attempt details
    $stmt = $pdo->prepare("SELECT * FROM student_exams WHERE student_id = ? AND exam_id = ?");
    $stmt->execute([$studentId, $exam_id]);
    $attempt = $stmt->fetch();
}

// Calculate timer
$startedTimestamp = strtotime($attempt['started_at']);
$durationSeconds = $exam['duration_minutes'] * 60;
$elapsedSeconds = time() - $startedTimestamp;
$secondsLeft = $durationSeconds - $elapsedSeconds;

if ($secondsLeft <= 0) {
    // Time expired: auto-submit immediately (processed below)
    $_POST['submit_exam'] = 1;
}

// 3. Fetch questions
$stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id ASC");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll();
$totalQuestions = count($questions);

if ($totalQuestions === 0) {
    echo "<div class='container' style='padding: 50px;'><div class='alert alert-danger'>This exam does not contain any questions. Please notify the instructor.</div><a href='/student/dashboard.php' class='btn btn-secondary'>Back to Dashboard</a></div>";
    require_once dirname(__DIR__) . '/includes/footer.php';
    exit;
}

// 4. Handle submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exam'])) {
    $answers = isset($_POST['answers']) ? $_POST['answers'] : []; // [question_id => selected_option]
    $totalScore = 0;
    
    // Enable SQLite foreign keys check in case it was modified
    if (DB_TYPE === 'sqlite') {
        $pdo->exec("PRAGMA foreign_keys = ON;");
    }

    // Insert answers
    $insertAns = $pdo->prepare("INSERT INTO student_answers (student_exam_id, question_id, selected_option, is_correct) VALUES (?, ?, ?, ?)");
    
    foreach ($questions as $q) {
        $qid = $q['id'];
        $selected = isset($answers[$qid]) ? $answers[$qid] : '';
        
        $isCorrect = (strtoupper($selected) === strtoupper($q['correct_option'])) ? 1 : 0;
        
        if ($isCorrect) {
            $totalScore += $q['points'];
        }
        
        // Insert into database if selected
        if (!empty($selected)) {
            $insertAns->execute([$attempt['id'], $qid, $selected, $isCorrect]);
        } else {
            // Write a blank selection or skip (skip makes sense, or save as blank)
            $insertAns->execute([$attempt['id'], $qid, '', 0]);
        }
    }

    // Update attempt as completed
    $up = $pdo->prepare("UPDATE student_exams SET score = ?, status = 'completed', completed_at = ? WHERE id = ?");
    $up->execute([$totalScore, date('Y-m-d H:i:s'), $attempt['id']]);

    header("Location: /student/results.php?attempt_id=" . $attempt['id']);
    exit;
}
?>

<div class="container" style="padding: 40px 24px; flex: 1;">
    <div style="margin-bottom: 24px;">
        <span class="badge" style="background: rgba(99, 102, 241, 0.1); color: var(--primary); font-weight:700;"><?php echo htmlspecialchars($exam['course_code']); ?></span>
        <h1 style="font-size: 28px; font-weight: 700; margin-top: 8px;"><?php echo htmlspecialchars($exam['title']); ?></h1>
    </div>

    <div class="exam-layout">
        <!-- Question Pane -->
        <div>
            <form action="take_exam.php?id=<?php echo $exam_id; ?>" method="POST" id="examForm">
                <input type="hidden" name="submit_exam" value="1">
                
                <?php foreach ($questions as $index => $q): ?>
                    <div class="card question-card-block" id="question_block_<?php echo $index; ?>" style="display: <?php echo $index === 0 ? 'block' : 'none'; ?>;">
                        <div class="flex-between" style="border-bottom: 1px solid var(--border-glass); padding-bottom: 15px; margin-bottom: 20px;">
                            <span style="font-size: 14px; font-weight:600; color: var(--primary); text-transform: uppercase;">Question <?php echo ($index + 1); ?> of <?php echo $totalQuestions; ?></span>
                            <span style="font-size: 13px; color: var(--text-muted);"><?php echo $q['points']; ?> Points</span>
                        </div>
                        
                        <p style="font-size: 18px; font-weight:600; line-height: 1.5; margin-bottom: 24px;"><?php echo htmlspecialchars($q['question_text']); ?></p>
                        
                        <div class="option-container">
                            <label class="option-card" id="label_<?php echo $q['id']; ?>_A">
                                <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="A" onchange="markAnswered(<?php echo $index; ?>, 'A', <?php echo $q['id']; ?>)">
                                <span><strong>A.</strong> <?php echo htmlspecialchars($q['option_a']); ?></span>
                            </label>

                            <label class="option-card" id="label_<?php echo $q['id']; ?>_B">
                                <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="B" onchange="markAnswered(<?php echo $index; ?>, 'B', <?php echo $q['id']; ?>)">
                                <span><strong>B.</strong> <?php echo htmlspecialchars($q['option_b']); ?></span>
                            </label>

                            <?php if (!empty($q['option_c'])): ?>
                                <label class="option-card" id="label_<?php echo $q['id']; ?>_C">
                                    <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="C" onchange="markAnswered(<?php echo $index; ?>, 'C', <?php echo $q['id']; ?>)">
                                    <span><strong>C.</strong> <?php echo htmlspecialchars($q['option_c']); ?></span>
                                </label>
                            <?php endif; ?>

                            <?php if (!empty($q['option_d'])): ?>
                                <label class="option-card" id="label_<?php echo $q['id']; ?>_D">
                                    <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="D" onchange="markAnswered(<?php echo $index; ?>, 'D', <?php echo $q['id']; ?>)">
                                    <span><strong>D.</strong> <?php echo htmlspecialchars($q['option_d']); ?></span>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Control Buttons -->
                <div class="flex-between" style="margin-top: 30px;">
                    <button type="button" id="prevBtn" class="btn btn-secondary" onclick="changeQuestion(-1)" disabled><i class="fa-solid fa-chevron-left"></i> Previous</button>
                    
                    <div style="display:flex; gap:10px;">
                        <button type="button" id="nextBtn" class="btn btn-secondary" onclick="changeQuestion(1)">Next <i class="fa-solid fa-chevron-right"></i></button>
                        <button type="button" class="btn btn-primary" onclick="confirmSubmit()"><i class="fa-solid fa-check-double"></i> Submit Examination</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sidebar timer and navigation tracker -->
        <div>
            <!-- Timer Card -->
            <div class="card timer-box">
                <i class="fa-solid fa-hourglass-half" style="font-size: 28px; color: var(--warning);"></i>
                <div style="font-size: 13px; font-weight: 500; color: var(--text-muted); text-transform: uppercase; margin-top: 8px;">Time Remaining</div>
                <div class="timer-display" id="timeRemainingDisplay">00:00</div>
            </div>

            <!-- Quiz Navigation Card -->
            <div class="card">
                <h3 style="font-size: 15px; font-weight:700; margin-bottom: 12px;">Test Navigation</h3>
                <div class="question-nav">
                    <?php for ($i = 0; $i < $totalQuestions; $i++): ?>
                        <button type="button" class="nav-btn <?php echo $i === 0 ? 'current' : ''; ?>" id="nav_btn_<?php echo $i; ?>" onclick="showQuestion(<?php echo $i; ?>)">
                            <?php echo ($i + 1); ?>
                        </button>
                    <?php endfor; ?>
                </div>
                <div style="margin-top: 20px; display:flex; flex-direction:column; gap:8px; font-size:12px; color: var(--text-muted);">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="width:12px; height:12px; border-radius:3px; background:var(--primary); display:inline-block;"></span> Answered
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="width:12px; height:12px; border-radius:3px; border:2px solid var(--secondary); display:inline-block;"></span> Current Question
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentIndex = 0;
const totalQuestions = <?php echo $totalQuestions; ?>;
let secondsRemaining = <?php echo $secondsLeft; ?>;

// Countdown Timer Handler
const timerDisplay = document.getElementById('timeRemainingDisplay');
function updateTimer() {
    if (secondsRemaining <= 0) {
        timerDisplay.textContent = "00:00";
        alert("Time is up! Your examination will be submitted automatically.");
        document.getElementById('examForm').submit();
        return;
    }
    
    let mins = Math.floor(secondsRemaining / 60);
    let secs = secondsRemaining % 60;
    
    mins = mins < 10 ? '0' + mins : mins;
    secs = secs < 10 ? '0' + secs : secs;
    
    timerDisplay.textContent = mins + ":" + secs;
    secondsRemaining--;
    
    setTimeout(updateTimer, 1000);
}
updateTimer();

// Question Swapping Handler
function showQuestion(index) {
    if (index < 0 || index >= totalQuestions) return;
    
    // Hide current block
    document.getElementById('question_block_' + currentIndex).style.display = 'none';
    document.getElementById('nav_btn_' + currentIndex).classList.remove('current');
    
    // Show new block
    currentIndex = index;
    document.getElementById('question_block_' + currentIndex).style.display = 'block';
    document.getElementById('nav_btn_' + currentIndex).classList.add('current');
    
    // Button controls
    document.getElementById('prevBtn').disabled = (currentIndex === 0);
    document.getElementById('nextBtn').disabled = (currentIndex === totalQuestions - 1);
}

function changeQuestion(dir) {
    showQuestion(currentIndex + dir);
}

// Answer tracking visual updates
function markAnswered(index, option, qid) {
    // Add answered class to navigation button
    document.getElementById('nav_btn_' + index).classList.add('answered');
    
    // Remove "selected" styling from options
    const options = ['A', 'B', 'C', 'D'];
    options.forEach(opt => {
        const el = document.getElementById('label_' + qid + '_' + opt);
        if (el) el.classList.remove('selected');
    });
    
    // Add "selected" styling to current option
    const activeLabel = document.getElementById('label_' + qid + '_' + option);
    if (activeLabel) activeLabel.classList.add('selected');
}

// Confirmation submit dialog
function confirmSubmit() {
    if (confirm("Are you sure you want to finish and submit your examination? Your answers will be graded instantly.")) {
        document.getElementById('examForm').submit();
    }
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
