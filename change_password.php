<?php
// change_password.php
require_once __DIR__ . '/config/db.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } else {
        // Retrieve current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $stored_hash = $stmt->fetchColumn();
        
        if (password_verify($current_password, $stored_hash)) {
            // Update password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->execute([$new_hash, $_SESSION['user_id']]);
            $success = 'Password changed successfully!';
        } else {
            $error = 'Incorrect current password.';
        }
    }
}

$page_title = "Change Password";
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="card auth-card">
        <h2 class="auth-title">Change Password</h2>
        <p class="auth-subtitle">Update your credentials to secure your account</p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form action="change_password.php" method="POST">
            <div class="form-group">
                <label for="current_password" class="form-label">Current Password</label>
                <input type="password" name="current_password" id="current_password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Minimum 6 characters" required>
            </div>
            
            <div class="form-group" style="margin-bottom: 28px;">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fa-solid fa-key"></i> Change Password</button>
        </form>
        
        <div class="auth-footer">
            <a href="/index.php"><i class="fa-solid fa-arrow-left"></i> Back to Portal Home</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/header.php'; ?>
