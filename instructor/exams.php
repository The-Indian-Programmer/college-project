<?php
// instructor/exams.php
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/header.php';

restrict_to(['instructor']);

$instructorId = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Verify ownership
    $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ? AND instructor_id = ?");
    $stmt->execute([$id, $instructorId]);
    $success = "Exam deleted successfully.";
}

// Handle edit mode
$editMode = false;
$editExam = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ? AND instructor_id = ?");
    $stmt->execute([$id, $instructorId]);
    $editExam = $stmt->fetch();
    if ($editExam) {
        $editMode = true;
    }
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_exam'])) {
    $course_id = intval($_POST['course_id']);
    $title = trim($_POST['title']);
    $duration = intval($_POST['duration_minutes']);
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    // Parse datetime for database compatibility (replace 'T' from datetime-local with space)
    $db_start = str_replace('T', ' ', $start_time);
    $db_end = str_replace('T', ' ', $end_time);

    if ($course_id <= 0 || empty($title) || $duration <= 0 || empty($start_time) || empty($end_time)) {
        $error = 'All fields are required and duration must be greater than 0.';
    } elseif (strtotime($start_time) >= strtotime($end_time)) {
        $error = 'End time must be after start time.';
    } else {
        if ($id > 0) {
            // Update
            $stmt = $pdo->prepare("UPDATE exams SET course_id = ?, title = ?, duration_minutes = ?, start_time = ?, end_time = ? WHERE id = ? AND instructor_id = ?");
            $stmt->execute([$course_id, $title, $duration, $db_start, $db_end, $id, $instructorId]);
            $success = 'Exam updated successfully.';
            $editMode = false;
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO exams (course_id, instructor_id, title, duration_minutes, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$course_id, $instructorId, $title, $duration, $db_start, $db_end]);
            $success = 'Exam scheduled successfully.';
        }
    }
}

// Fetch all courses for dropdown
$courses = $pdo->query("SELECT c.id, c.name, c.code, d.code as dept_code FROM courses c JOIN departments d ON c.department_id = d.id ORDER BY c.code ASC")->fetchAll();

// Fetch scheduled exams
$stmt = $pdo->prepare("SELECT e.*, c.code as course_code FROM exams e JOIN courses c ON e.course_id = c.id WHERE e.instructor_id = ? ORDER BY e.start_time DESC");
$stmt->execute([$instructorId]);
$exams = $stmt->fetchAll();
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div>
            <h2 style="font-size: 16px; font-weight: 700; color: var(--secondary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px;">Faculty Panel</h2>
            <ul class="sidebar-nav">
                <li><a href="/instructor/dashboard.php" class="sidebar-link"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                <li><a href="/instructor/exams.php" class="sidebar-link active"><i class="fa-solid fa-file-invoice"></i> Manage Exams</a></li>
                <li><a href="/instructor/questions.php" class="sidebar-link"><i class="fa-solid fa-circle-question"></i> Question Bank</a></li>
            </ul>
        </div>
        <div style="margin-top: auto;">
            <a href="/logout.php" class="sidebar-link" style="color: var(--danger);"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <h1 class="page-title">Manage Examinations</h1>
        <p class="page-subtitle">Schedule, update, and manage test settings</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div class="card" style="margin-bottom: 40px;">
            <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">
                <i class="fa-solid <?php echo $editMode ? 'fa-pen-to-square' : 'fa-calendar-plus'; ?>" style="color: var(--secondary); margin-right: 8px;"></i>
                <?php echo $editMode ? 'Edit Examination Schedule' : 'Schedule New Examination'; ?>
            </h2>
            <form action="exams.php" method="POST" style="display: flex; flex-direction: column; gap: 20px;">
                <?php if ($editMode): ?>
                    <input type="hidden" name="id" value="<?php echo $editExam['id']; ?>">
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Course Subject</label>
                        <select name="course_id" class="form-control" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($editMode && $editExam['course_id'] == $c['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['code'] . " - " . $c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Examination Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Computer Networks Midterm" required value="<?php echo $editMode ? htmlspecialchars($editExam['title']) : ''; ?>">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Duration (Minutes)</label>
                        <input type="number" name="duration_minutes" class="form-control" placeholder="e.g. 60" required value="<?php echo $editMode ? intval($editExam['duration_minutes']) : ''; ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 150px; gap: 20px; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Start Time</label>
                        <input type="datetime-local" name="start_time" class="form-control" required value="<?php echo $editMode ? date('Y-m-d\TH:i', strtotime($editExam['start_time'])) : ''; ?>">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">End Time</label>
                        <input type="datetime-local" name="end_time" class="form-control" required value="<?php echo $editMode ? date('Y-m-d\TH:i', strtotime($editExam['end_time'])) : ''; ?>">
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button type="submit" name="save_exam" class="btn btn-primary" style="width: 100%;"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                        <?php if ($editMode): ?>
                            <a href="exams.php" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- Scheduled Exams Table -->
        <div class="card">
            <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;"><i class="fa-solid fa-list-ul" style="color: var(--secondary); margin-right: 8px;"></i> Scheduled Examinations</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Exam Title</th>
                            <th>Duration</th>
                            <th>Timing Window</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($exams)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted);">No exams scheduled.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($exams as $ex): ?>
                                <tr>
                                    <td><span class="badge" style="background: rgba(168, 85, 247, 0.1); color: var(--secondary); font-weight:700;"><?php echo htmlspecialchars($ex['course_code']); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($ex['title']); ?></strong></td>
                                    <td><?php echo $ex['duration_minutes']; ?> Mins</td>
                                    <td style="font-size: 13px; color: var(--text-muted);">
                                        <span style="color: var(--text-main); font-weight:500;"><?php echo date('M d, Y h:i A', strtotime($ex['start_time'])); ?></span><br>
                                        to <?php echo date('M d, Y h:i A', strtotime($ex['end_time'])); ?>
                                    </td>
                                    <td style="text-align: right; display: flex; justify-content: flex-end; gap: 8px;">
                                        <a href="questions.php?exam_id=<?php echo $ex['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 6px 12px; color: var(--primary);" title="Manage Questions">
                                            <i class="fa-solid fa-circle-question"></i> Questions
                                        </a>
                                        <a href="exams.php?action=edit&id=<?php echo $ex['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 6px 12px;" title="Edit Schedule">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <a href="exams.php?action=delete&id=<?php echo $ex['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 6px 12px; color: var(--danger);" title="Delete Exam" onclick="return confirm('Are you sure you want to delete this exam? All student attempt histories and questions will be permanently deleted.');">
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
    </main>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
