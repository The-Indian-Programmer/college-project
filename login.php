<?php
// login.php
require_once __DIR__ . '/config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') header('Location: /admin/dashboard.php');
    elseif ($_SESSION['role'] === 'instructor') header('Location: /instructor/dashboard.php');
    else header('Location: /student/dashboard.php');
    exit;
}

$error = '';
$pre_role = isset($_GET['role']) ? $_GET['role'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'active') {
                $error = 'Your account is inactive. Please contact the administrator.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: /admin/dashboard.php');
                } elseif ($user['role'] === 'instructor') {
                    header('Location: /instructor/dashboard.php');
                } else {
                    header('Location: /student/dashboard.php');
                }
                exit;
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$page_title = "Login";
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="card auth-card">
        <h2 class="auth-title">Welcome Back</h2>
        <p class="auth-subtitle">Sign in to access your ExamPortal workspace</p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="e.g. admin@exam.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group" style="margin-bottom: 8px;">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>
            
            <div style="text-align: right; margin-bottom: 24px;">
                <a href="/forgot_password.php" style="font-size: 13px; color: var(--primary); text-decoration: none;">Forgot Password?</a>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fa-solid fa-right-to-bracket"></i> Sign In</button>
        </form>

        <?php if ($pre_role): ?>
            <div style="margin-top: 24px; padding: 12px; background: rgba(255,255,255,0.03); border-radius: var(--radius-sm); border: 1px solid var(--border-glass); font-size: 13px; text-align: center;">
                <span style="color: var(--warning);"><i class="fa-solid fa-circle-info"></i> Quick Test Credentials:</span><br>
                <code style="color: var(--text-main); font-weight: 600; display: block; margin-top: 4px;">
                    <?php 
                    if ($pre_role === 'admin') echo "admin@exam.com / admin123";
                    elseif ($pre_role === 'instructor') echo "instructor@exam.com / instructor123";
                    else echo "student@exam.com / student123";
                    ?>
                </code>
            </div>
        <?php endif; ?>
        
        <div class="auth-footer">
            Don't have an account? <a href="/register.php">Register as Student</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/header.php'; ?>
