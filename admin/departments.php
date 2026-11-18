<?php
// admin/departments.php
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/header.php';

restrict_to(['admin']);

$error = '';
$success = '';

// Handle department actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_department'])) {
    $name = trim($_POST['name']);
    $code = strtoupper(trim($_POST['code']));
    
    if (empty($name) || empty($code)) {
        $error = 'Both department name and code are required.';
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE code = ?");
        $stmt->execute([$code]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Department code already exists.';
        } else {
            $ins = $pdo->prepare("INSERT INTO departments (name, code) VALUES (?, ?)");
            $ins->execute([$name, $code]);
            $success = 'Department created successfully.';
        }
    }
}

// Handle course actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_course'])) {
    $dept_id = intval($_POST['department_id']);
    $name = trim($_POST['name']);
    $code = strtoupper(trim($_POST['code']));
    
    if (empty($name) || empty($code) || $dept_id <= 0) {
        $error = 'All course fields are required.';
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE code = ?");
        $stmt->execute([$code]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Course code already exists.';
        } else {
            $ins = $pdo->prepare("INSERT INTO courses (department_id, name, code) VALUES (?, ?, ?)");
            $ins->execute([$dept_id, $name, $code]);
            $success = 'Course created successfully.';
        }
    }
}

// Handle deletes
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);
    
    if ($action === 'delete_dept') {
        $del = $pdo->prepare("DELETE FROM departments WHERE id = ?");
        $del->execute([$id]);
        $success = 'Department and its courses deleted successfully.';
    } elseif ($action === 'delete_course') {
        $del = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $del->execute([$id]);
        $success = 'Course deleted successfully.';
    }
}

// Fetch lists
$departments = $pdo->query("SELECT * FROM departments ORDER BY code ASC")->fetchAll();
$courses = $pdo->query("SELECT c.*, d.code as dept_code FROM courses c JOIN departments d ON c.department_id = d.id ORDER BY c.code ASC")->fetchAll();
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div>
            <h2 style="font-size: 16px; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px;">Admin Actions</h2>
            <ul class="sidebar-nav">
                <li><a href="/admin/dashboard.php" class="sidebar-link"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                <li><a href="/admin/students.php" class="sidebar-link"><i class="fa-solid fa-user-graduate"></i> Students</a></li>
                <li><a href="/admin/instructors.php" class="sidebar-link"><i class="fa-solid fa-chalkboard-user"></i> Instructors</a></li>
                <li><a href="/admin/departments.php" class="sidebar-link active"><i class="fa-solid fa-building-columns"></i> Departments</a></li>
            </ul>
        </div>
        <div style="margin-top: auto;">
            <a href="/logout.php" class="sidebar-link" style="color: var(--danger);"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <h1 class="page-title">Manage Departments & Courses</h1>
        <p class="page-subtitle">Configure academic branches and course structures</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 30px;">
            <!-- Departments column -->
            <div>
                <!-- Add Department Form -->
                <div class="card" style="margin-bottom: 30px;">
                    <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 20px;"><i class="fa-solid fa-folder-plus" style="color: var(--primary); margin-right: 8px;"></i> Create Department</h2>
                    <form action="departments.php" method="POST" style="display: grid; grid-template-columns: 1fr 120px 100px; gap: 15px; align-items: end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Computer Science" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" placeholder="CS" required>
                        </div>
                        <button type="submit" name="add_department" class="btn btn-primary" style="width: 100%; padding: 12px 10px;">Create</button>
                    </form>
                </div>

                <!-- Departments Table -->
                <div class="card">
                    <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 20px;"><i class="fa-solid fa-list" style="color: var(--primary); margin-right: 8px;"></i> Departments</h2>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($departments)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; color: var(--text-muted);">No departments found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($departments as $dept): ?>
                                        <tr>
                                            <td><span class="badge" style="background: rgba(99, 102, 241, 0.1); color: var(--primary); font-weight: 700;"><?php echo htmlspecialchars($dept['code']); ?></span></td>
                                            <td><strong><?php echo htmlspecialchars($dept['name']); ?></strong></td>
                                            <td style="text-align: right;">
                                                <a href="departments.php?action=delete_dept&id=<?php echo $dept['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 6px 12px; color: var(--danger);" title="Delete Department" onclick="return confirm('Deleting this department will delete ALL courses and exams belonging to it. Continue?');">
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
            </div>

            <!-- Courses column -->
            <div>
                <!-- Add Course Form -->
                <div class="card" style="margin-bottom: 30px;">
                    <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 20px;"><i class="fa-solid fa-folder-plus" style="color: var(--secondary); margin-right: 8px;"></i> Create Course</h2>
                    <form action="departments.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Department</label>
                                <select name="department_id" class="form-control" required>
                                    <option value="">Select Dept</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['code'] . " - " . $dept['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Course Code</label>
                                <input type="text" name="code" class="form-control" placeholder="BCA" required>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 100px; gap: 15px; align-items: end;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Course Title</label>
                                <input type="text" name="name" class="form-control" placeholder="Bachelor of Computer Applications" required>
                            </div>
                            <button type="submit" name="add_course" class="btn btn-primary" style="width:100%; padding:12px 10px; background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);">Create</button>
                        </div>
                    </form>
                </div>

                <!-- Courses Table -->
                <div class="card">
                    <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 20px;"><i class="fa-solid fa-list" style="color: var(--secondary); margin-right: 8px;"></i> Course Structures</h2>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Title</th>
                                    <th>Dept</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($courses)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; color: var(--text-muted);">No courses found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($courses as $crs): ?>
                                        <tr>
                                            <td><span class="badge" style="background: rgba(168, 85, 247, 0.1); color: var(--secondary); font-weight: 700;"><?php echo htmlspecialchars($crs['code']); ?></span></td>
                                            <td><strong><?php echo htmlspecialchars($crs['name']); ?></strong></td>
                                            <td><span style="color: var(--text-muted); font-size: 13px; font-weight: 500;"><?php echo htmlspecialchars($crs['dept_code']); ?></span></td>
                                            <td style="text-align: right;">
                                                <a href="departments.php?action=delete_course&id=<?php echo $crs['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 6px 12px; color: var(--danger);" title="Delete Course" onclick="return confirm('Are you sure you want to delete this course? All associated exams will be deleted.');">
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
            </div>
        </div>
    </main>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
