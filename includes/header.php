<?php
// includes/header.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security helper: restrict page to specific roles
function restrict_to($allowed_roles) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header('Location: /index.php?error=unauthorized');
        exit;
    }
}

// Current page detection helper
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . " - Online Exam System" : "Online Examination System"; ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<header class="app-header">
    <div class="container header-container">
        <a href="/index.php" class="logo-link">
            <i class="fa-solid fa-graduation-cap" style="font-size: 26px; color: var(--primary);"></i>
            <span class="logo-text">ExamPortal</span>
        </a>
        
        <nav>
            <ul class="nav-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><span style="color: var(--text-muted); font-size: 14px; margin-right: 10px;">Hello, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong> (<?php echo ucfirst($_SESSION['role']); ?>)</span></li>
                    
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li><a href="/admin/dashboard.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'active' : ''; ?>"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                    <?php elseif ($_SESSION['role'] === 'instructor'): ?>
                        <li><a href="/instructor/dashboard.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/instructor/') !== false ? 'active' : ''; ?>"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                    <?php elseif ($_SESSION['role'] === 'student'): ?>
                        <li><a href="/student/dashboard.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/student/') !== false ? 'active' : ''; ?>"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
                    <?php endif; ?>
                    
                    <li><a href="/change_password.php" class="nav-link <?php echo $current_page === 'change_password.php' ? 'active' : ''; ?>"><i class="fa-solid fa-key"></i> Pass</a></li>
                    <li><a href="/logout.php" class="btn btn-secondary btn-sm" style="padding: 6px 12px;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="/login.php" class="nav-link <?php echo $current_page === 'login.php' ? 'active' : ''; ?>">Login</a></li>
                    <li><a href="/register.php" class="btn btn-primary btn-sm">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
