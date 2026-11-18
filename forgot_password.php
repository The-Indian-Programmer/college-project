<?php
// forgot_password.php
require_once __DIR__ . '/config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $success = "A simulated password recovery dispatch has been logged for " . htmlspecialchars($email) . ". In this local deployment, contact the Administrator (admin@exam.com) directly to manually reset your credentials.";
        } else {
            $error = "No user found with that email address.";
        }
    }
}

$page_title = "Forgot Password";
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="card auth-card">
        <h2 class="auth-title">Forgot Password</h2>
        <p class="auth-subtitle">Verify your email to recover access</p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success" style="flex-direction: column; align-items: flex-start; gap: 8px;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-circle-check"></i>
                    Reset Simulating Logged!
                </div>
                <p style="font-size:13px; opacity:0.9; margin: 0;"><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>
        
        <form action="forgot_password.php" method="POST">
            <div class="form-group" style="margin-bottom: 24px;">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="e.g. user@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fa-solid fa-paper-plane"></i> Reset Password</button>
        </form>
        
        <div class="auth-footer">
            Remember your credentials? <a href="/login.php">Sign In</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/header.php'; ?>
