<?php
// instructor/questions.php
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/header.php';

restrict_to(['instructor']);

$instructorId = $_SESSION['user_id'];
$error = '';
$success = '';

// Get exam_id
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// Verify exam ownership if exam_id is set
$exam = null;
if ($exam_id > 0) {
    $stmt = $pdo->prepare("SELECT e.*, c.code as course_code FROM exams e JOIN courses c ON e.course_id = c.id WHERE e.id = ? AND e.instructor_id = ?");
    $stmt->execute([$exam_id, $instructorId]);
    $exam = $stmt->fetch();
    if (!$exam) {
        $exam_id = 0; // Reset
        $error = "Exam not found or access denied.";
    }
}

// Handle question delete
if ($exam && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['qid'])) {
    $qid = intval($_GET['qid']);
    $del = $pdo->prepare("DELETE FROM questions WHERE id = ? AND exam_id = ?");
    $del->execute([$qid, $exam_id]);
    $success = "Question removed successfully.";
}

// Handle question edit mode loading
$editMode = false;
$editQuest = null;
if ($exam && isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['qid'])) {
    $qid = intval($_GET['qid']);
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ? AND exam_id = ?");
    $stmt->execute([$qid, $exam_id]);
    $editQuest = $stmt->fetch();
    if ($editQuest) {
        $editMode = true;
    }
}

// Handle question save (add or edit)
if ($exam && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_question'])) {
    $question_text = trim($_POST['question_text']);
    $qtype = trim($_POST['qtype']); // 'mcq' or 'tf'
    $points = intval($_POST['points']);
    $correct = trim($_POST['correct_option']);
    $qid = isset($_POST['qid']) ? intval($_POST['qid']) : 0;
    
    if ($qtype === 'tf') {
        $opt_a = 'True';
        $opt_b = 'False';
        $opt_c = '';
        $opt_d = '';
    } else {
        $opt_a = trim($_POST['option_a']);
        $opt_b = trim($_POST['option_b']);
        $opt_c = trim($_POST['option_c']);
        $opt_d = trim($_POST['option_d']);
    }

    if (empty($question_text) || empty($opt_a) || empty($opt_b) || empty($correct) || $points <= 0) {
        $error = 'Please fill in all required question details.';
    } else {
        if ($qid > 0) {
            // Update
            $up = $pdo->prepare("UPDATE questions SET question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ?, points = ? WHERE id = ? AND exam_id = ?");
            $up->execute([$question_text, $opt_a, $opt_b, $opt_c, $opt_d, $correct, $points, $qid, $exam_id]);
            $success = "Question updated successfully.";
            $editMode = false;
        } else {
            // Insert
            $ins = $pdo->prepare("INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option, points) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$exam_id, $question_text, $opt_a, $opt_b, $opt_c, $opt_d, $correct, $points]);
            $success = "Question added successfully.";
        }
    }
}

// Fetch instructor's exams for select menu
$stmt = $pdo->prepare("SELECT e.id, e.title, c.code FROM exams e JOIN courses c ON e.course_id = c.id WHERE e.instructor_id = ? ORDER BY e.start_time DESC");
$stmt->execute([$instructorId]);
$instructorExams = $stmt->fetchAll();

// Fetch questions for currently selected exam
$questionsList = [];
if ($exam) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id ASC");
    $stmt->execute([$exam_id]);
    $questionsList = $stmt->fetchAll();
}
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div>
            <h2 style="font-size: 16px; font-weight: 700; color: var(--secondary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px;">Faculty Panel</h2>
            <ul class="sidebar-nav">
                <li><a href="/instructor/dashboard.php" class="sidebar-link"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                <li><a href="/instructor/exams.php" class="sidebar-link"><i class="fa-solid fa-file-invoice"></i> Manage Exams</a></li>
                <li><a href="/instructor/questions.php" class="sidebar-link active"><i class="fa-solid fa-circle-question"></i> Question Bank</a></li>
            </ul>
        </div>
        <div style="margin-top: auto;">
            <a href="/logout.php" class="sidebar-link" style="color: var(--danger);"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <h1 class="page-title">Question Bank</h1>
        <p class="page-subtitle">Draft multiple choice or True/False question sets</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Exam Selection -->
        <div class="card" style="margin-bottom: 30px;">
            <label class="form-label" style="font-size: 16px; font-weight:600; margin-bottom: 12px;">Select Scheduled Examination</label>
            <select class="form-control" onchange="location = this.value;" style="max-width: 400px;">
                <option value="questions.php">-- Choose an Exam --</option>
                <?php foreach ($instructorExams as $ie): ?>
                    <option value="questions.php?exam_id=<?php echo $ie['id']; ?>" <?php echo ($exam_id == $ie['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($ie['code'] . " - " . $ie['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($exam): ?>
            <div style="margin-bottom: 24px; padding: 12px; background: rgba(99, 102, 241, 0.05); border-radius: var(--radius-sm); border: 1px solid var(--border-glass);">
                <span style="color: var(--text-muted);">Active Exam Context:</span> 
                <strong style="color: var(--primary);"><?php echo htmlspecialchars($exam['course_code'] . " - " . $exam['title']); ?></strong> 
                <span style="color: var(--text-muted); font-size:13px; margin-left: 10px;">(Duration: <?php echo $exam['duration_minutes']; ?> mins)</span>
            </div>

            <!-- Form: Add/Edit Question -->
            <div class="card" style="margin-bottom: 40px;">
                <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 20px;">
                    <i class="fa-solid <?php echo $editMode ? 'fa-pen-to-square' : 'fa-plus'; ?>" style="color: var(--secondary); margin-right: 8px;"></i>
                    <?php echo $editMode ? 'Edit Question' : 'Add Question'; ?>
                </h2>
                
                <form action="questions.php?exam_id=<?php echo $exam_id; ?>" method="POST" id="questionForm">
                    <?php if ($editMode): ?>
                        <input type="hidden" name="qid" value="<?php echo $editQuest['id']; ?>">
                    <?php endif; ?>

                    <div style="display: grid; grid-template-columns: 1fr 200px 150px; gap: 20px; margin-bottom: 20px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Question Text</label>
                            <input type="text" name="question_text" class="form-control" placeholder="Enter question description..." required value="<?php echo $editMode ? htmlspecialchars($editQuest['question_text']) : ''; ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Question Type</label>
                            <select name="qtype" id="qtype" class="form-control" onchange="toggleQuestionType(this.value)">
                                <?php 
                                $isTF = $editMode && empty($editQuest['option_c']) && empty($editQuest['option_d']) && $editQuest['option_a'] === 'True';
                                ?>
                                <option value="mcq" <?php echo !$isTF ? 'selected' : ''; ?>>Multiple Choice (MCQ)</option>
                                <option value="tf" <?php echo $isTF ? 'selected' : ''; ?>>True / False</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Points</label>
                            <input type="number" name="points" class="form-control" required value="<?php echo $editMode ? intval($editQuest['points']) : '2'; ?>" min="1">
                        </div>
                    </div>

                    <!-- MCQ Option Fields -->
                    <div id="mcqOptions" style="display: <?php echo $isTF ? 'none' : 'grid'; ?>; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Option A</label>
                            <input type="text" name="option_a" class="form-control mcq-input" placeholder="Option A Description" value="<?php echo $editMode ? htmlspecialchars($editQuest['option_a']) : ''; ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Option B</label>
                            <input type="text" name="option_b" class="form-control mcq-input" placeholder="Option B Description" value="<?php echo $editMode ? htmlspecialchars($editQuest['option_b']) : ''; ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Option C</label>
                            <input type="text" name="option_c" class="form-control mcq-input" placeholder="Option C Description" value="<?php echo $editMode ? htmlspecialchars($editQuest['option_c']) : ''; ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Option D</label>
                            <input type="text" name="option_d" class="form-control mcq-input" placeholder="Option D Description" value="<?php echo $editMode ? htmlspecialchars($editQuest['option_d']) : ''; ?>">
                        </div>
                    </div>

                    <!-- True/False Display Indicator -->
                    <div id="tfDisplay" style="display: <?php echo $isTF ? 'block' : 'none'; ?>; padding: 12px; background: rgba(255,255,255,0.03); border:1px dashed var(--border-glass); border-radius: var(--radius-sm); margin-bottom: 20px; font-size:14px; color: var(--text-muted);">
                        <i class="fa-solid fa-info-circle"></i> Option A is automatically set to <strong>True</strong> and Option B is set to <strong>False</strong>.
                    </div>

                    <div style="display: grid; grid-template-columns: 200px 1fr; gap: 20px; align-items: end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Correct Option</label>
                            <select name="correct_option" id="correct_option" class="form-control" required>
                                <option value="A" <?php echo ($editMode && $editQuest['correct_option'] === 'A') ? 'selected' : ''; ?>>Option A (True)</option>
                                <option value="B" <?php echo ($editMode && $editQuest['correct_option'] === 'B') ? 'selected' : ''; ?>>Option B (False)</option>
                                <option value="C" id="optC_select" style="display: <?php echo $isTF ? 'none' : 'block'; ?>;" <?php echo ($editMode && $editQuest['correct_option'] === 'C') ? 'selected' : ''; ?>>Option C</option>
                                <option value="D" id="optD_select" style="display: <?php echo $isTF ? 'none' : 'block'; ?>;" <?php echo ($editMode && $editQuest['correct_option'] === 'D') ? 'selected' : ''; ?>>Option D</option>
                            </select>
                        </div>
                        <div style="display: flex; gap: 10px; justify-content: flex-end;">
                            <button type="submit" name="save_question" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Question</button>
                            <?php if ($editMode): ?>
                                <a href="questions.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- List of Questions -->
            <div class="card">
                <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;"><i class="fa-solid fa-folder-tree" style="color: var(--secondary); margin-right: 8px;"></i> Exam Questions Checklist</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Question</th>
                                <th>Options Setup</th>
                                <th>Correct</th>
                                <th>Score</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($questionsList)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-muted);">No questions added to this exam yet. Add your first question above.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($questionsList as $index => $q): ?>
                                    <tr>
                                        <td><strong><?php echo ($index + 1); ?></strong></td>
                                        <td><strong><?php echo htmlspecialchars($q['question_text']); ?></strong></td>
                                        <td style="font-size: 13px; color: var(--text-muted);">
                                            <?php if (empty($q['option_c'])): ?>
                                                A: True | B: False
                                            <?php else: ?>
                                                A: <?php echo htmlspecialchars($q['option_a']); ?> | B: <?php echo htmlspecialchars($q['option_b']); ?><br>
                                                C: <?php echo htmlspecialchars($q['option_c']); ?> | D: <?php echo htmlspecialchars($q['option_d']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge" style="background: rgba(16, 185, 129, 0.15); color: var(--success); font-weight: 700;"><?php echo $q['correct_option']; ?></span></td>
                                        <td><?php echo $q['points']; ?> Pts</td>
                                        <td style="text-align: right; display:flex; justify-content:flex-end; gap:8px;">
                                            <a href="questions.php?exam_id=<?php echo $exam_id; ?>&action=edit&qid=<?php echo $q['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 6px 12px; color: var(--primary);" title="Edit Question">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <a href="questions.php?exam_id=<?php echo $exam_id; ?>&action=delete&qid=<?php echo $q['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 6px 12px; color: var(--danger);" title="Remove Question" onclick="return confirm('Are you sure you want to delete this question?');">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Client Script for UI toggling -->
            <script>
            function toggleQuestionType(val) {
                var mcqGrid = document.getElementById('mcqOptions');
                var tfNote = document.getElementById('tfDisplay');
                var optC = document.getElementById('optC_select');
                var optD = document.getElementById('optD_select');
                var mcqInputs = document.querySelectorAll('.mcq-input');
                
                if (val === 'tf') {
                    mcqGrid.style.display = 'none';
                    tfNote.style.display = 'block';
                    optC.style.display = 'none';
                    optD.style.display = 'none';
                    
                    // Remove required flags for MCQ inputs
                    mcqInputs.forEach(el => el.removeAttribute('required'));
                } else {
                    mcqGrid.style.display = 'grid';
                    tfNote.style.display = 'none';
                    optC.style.display = 'block';
                    optD.style.display = 'block';
                    
                    // Set required for MCQ inputs
                    mcqInputs.forEach(el => el.setAttribute('required', 'required'));
                }
            }
            // Execute once on load in case of edit mode
            document.addEventListener("DOMContentLoaded", function() {
                toggleQuestionType(document.getElementById('qtype').value);
            });
            </script>

        <?php else: ?>
            <div class="card text-center" style="padding: 50px 20px;">
                <i class="fa-solid fa-folder-open" style="font-size: 48px; color: var(--text-muted); margin-bottom: 16px;"></i>
                <h3 style="margin-bottom: 8px;">No Examination Selected</h3>
                <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto;">Select an exam from the dropdown list above to view, create, or update its question bank sheets.</p>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
