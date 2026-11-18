<?php
// admin/instructors.php
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/header.php';

restrict_to(['admin']);

$error = '';
$success = '';

// Handle status toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ? AND role = 'instructor'");
    $stmt->execute([$id]);
    $currentStatus = $stmt->fetchColumn();
    
    if ($currentStatus) {
        $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
        $updateStmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $updateStmt->execute([$newStatus, $id]);
        $success = "Instructor status updated successfully.";
    }
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $deleteStmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'instructor'");
    $deleteStmt->execute([$id]);
    $success = "Instructor deleted successfully.";
}

// Handle edit or add
$editMode = false;
$editInstructor = null;

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'instructor'");
    $stmt->execute([$id]);
    $editInstructor = $stmt->fetch();
    if ($editInstructor) {
        $editMode = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_instructor'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (empty($name) || empty($email)) {
            $error = 'Name and email are required.';
        } else {
            if ($id > 0) {
                // Update
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ? AND role = 'instructor'");
                    $stmt->execute([$name, $email, $hashed, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ? AND role = 'instructor'");
                    $stmt->execute([$name, $email, $id]);
                }
                $success = 'Instructor updated successfully.';
                $editMode = false;
            } else {
                // Insert
                if (empty($password)) {
                    $error = 'Password is required for new instructor.';
                } else {
                    // Check duplicate email
                    $chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                    $chk->execute([$email]);
                    if ($chk->fetchColumn() > 0) {
                        $error = 'Email already exists.';
                    } else {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'instructor', 'active')");
                        $stmt->execute([$name, $email, $hashed]);
                        $success = 'Instructor added successfully.';
                    }
                }
            }
        }
    }
}

// Fetch all instructors
$instructors = $pdo->query("SELECT * FROM users WHERE role = 'instructor' ORDER BY name ASC")->fetchAll();
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div>
            <h2 style="font-size: 16px; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px;">Admin Actions</h2>
            <ul class="sidebar-nav">
                <li><a href="/admin/dashboard.php" class="sidebar-link"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                <li><a href="/admin/students.php" class="sidebar-link"><i class="fa-solid fa-user-graduate"></i> Students</a></li>
                <li><a href="/admin/instructors.php" class="sidebar-link active"><i class="fa-solid fa-chalkboard-user"></i> Instructors</a></li>
                <li><a href="/admin/departments.php" class="sidebar-link"><i class="fa-solid fa-building-columns"></i> Departments</a></li>
            </ul>
        </div>
        <div style="margin-top: auto;">
            <a href="/logout.php" class="sidebar-link" style="color: var(--danger);"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <h1 class="page-title">Manage Instructors</h1>
        <p class="page-subtitle">Add, delete, or review active statuses for institutional instructors</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Instructor Form -->
        <div class="card" style="margin-bottom: 40px;">
            <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">
                <i class="fa-solid <?php echo $editMode ? 'fa-user-pen' : 'fa-user-plus'; ?>" style="color: var(--primary); margin-right: 8px;"></i>
                <?php echo $editMode ? 'Edit Instructor Details' : 'Add New Instructor'; ?>
            </h2>
            <form action="instructors.php" method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) 150px; gap: 15px; align-items: end;">
                <?php if ($editMode): ?>
                    <input type="hidden" name="id" value="<?php echo $editInstructor['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Instructor Name" required value="<?php echo $editMode ? htmlspecialchars($editInstructor['name']) : ''; ?>">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="instructor@example.com" required value="<?php echo $editMode ? htmlspecialchars($editInstructor['email']) : ''; ?>">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Password <?php echo $editMode ? '(Leave blank to keep same)' : ''; ?></label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" <?php echo $editMode ? '' : 'required'; ?>>
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" name="save_instructor" class="btn btn-primary" style="width: 100%; padding: 12px 16px;">Save</button>
                    <?php if ($editMode): ?>
                        <a href="instructors.php" class="btn btn-secondary" style="padding: 12px 16px;">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Instructor List -->
        <div class="card">
            <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;"><i class="fa-solid fa-list-ul" style="color: var(--primary); margin-right: 8px;"></i> Institutional Instructors</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($instructors)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted);">No instructors registered.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($instructors as $instructor): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($instructor['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($instructor['email']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $instructor['status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo ucfirst($instructor['status']); ?>
                                        </span>
                                    </td>
                                    <td style="color: var(--text-muted); font-size: 13px;"><?php echo $instructor['created_at']; ?></td>
                                    <td style="text-align: right; display: flex; justify-content: flex-end; gap: 8px;">
                                        <a href="instructors.php?action=toggle&id=<?php echo $instructor['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 6px 12px;" title="Toggle Active/Inactive">
                                            <i class="fa-solid <?php echo $instructor['status'] === 'active' ? 'fa-user-slash' : 'fa-user-check'; ?>"></i> Status
                                        </a>
                                        <a href="instructors.php?action=edit&id=<?php echo $instructor['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 6px 12px; color: var(--primary);" title="Edit Instructor">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <a href="instructors.php?action=delete&id=<?php echo $instructor['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 6px 12px; color: var(--danger);" title="Delete Instructor" onclick="return confirm('Are you sure you want to delete this instructor?');">
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
