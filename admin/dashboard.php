<?php
// admin/dashboard.php
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/header.php';

// Enforce admin permission
restrict_to(['admin']);

// Fetch counts
$studentCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$instructorCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'instructor'")->fetchColumn();
$departmentCount = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$examCount = $pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn();

// Fetch latest registered users
$latestUsers = $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT 5")->fetchAll();
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div>
            <h2 style="font-size: 16px; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px;">Admin Actions</h2>
            <ul class="sidebar-nav">
                <li><a href="/admin/dashboard.php" class="sidebar-link active"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                <li><a href="/admin/students.php" class="sidebar-link"><i class="fa-solid fa-user-graduate"></i> Students</a></li>
                <li><a href="/admin/instructors.php" class="sidebar-link"><i class="fa-solid fa-chalkboard-user"></i> Instructors</a></li>
                <li><a href="/admin/departments.php" class="sidebar-link"><i class="fa-solid fa-building-columns"></i> Departments</a></li>
            </ul>
        </div>
        <div style="margin-top: auto;">
            <a href="/logout.php" class="sidebar-link" style="color: var(--danger);"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <h1 class="page-title">Admin Dashboard</h1>
        <p class="page-subtitle">Configure institutions, modify student states, and track users</p>

        <!-- Stats Grid -->
        <div class="metrics-grid">
            <div class="card metric-card">
                <div class="metric-icon" style="background-color: rgba(99, 102, 241, 0.1); color: var(--primary);">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
                <div class="metric-info">
                    <h3>Total Students</h3>
                    <p><?php echo $studentCount; ?></p>
                </div>
            </div>

            <div class="card metric-card">
                <div class="metric-icon" style="background-color: rgba(168, 85, 247, 0.1); color: var(--secondary);">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>
                <div class="metric-info">
                    <h3>Instructors</h3>
                    <p><?php echo $instructorCount; ?></p>
                </div>
            </div>

            <div class="card metric-card">
                <div class="metric-icon" style="background-color: rgba(16, 185, 129, 0.1); color: var(--success);">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <div class="metric-info">
                    <h3>Departments</h3>
                    <p><?php echo $departmentCount; ?></p>
                </div>
            </div>

            <div class="card metric-card">
                <div class="metric-icon" style="background-color: rgba(245, 158, 11, 0.1); color: var(--warning);">
                    <i class="fa-solid fa-file-signature"></i>
                </div>
                <div class="metric-info">
                    <h3>Exams Launched</h3>
                    <p><?php echo $examCount; ?></p>
                </div>
            </div>
        </div>

        <!-- Latest Activities -->
        <div class="card">
            <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;"><i class="fa-solid fa-clock-rotate-left" style="color: var(--primary); margin-right: 8px;"></i> Recent Users Added</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($latestUsers)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted);">No users found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($latestUsers as $user): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge" style="background-color: <?php 
                                            if ($user['role'] === 'admin') echo 'rgba(99, 102, 241, 0.15); color: var(--primary);';
                                            elseif ($user['role'] === 'instructor') echo 'rgba(168, 85, 247, 0.15); color: var(--secondary);';
                                            else echo 'rgba(16, 185, 129, 0.15); color: var(--success);';
                                        ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $user['status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td style="color: var(--text-muted); font-size: 13px;"><?php echo $user['created_at']; ?></td>
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
