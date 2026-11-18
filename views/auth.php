<?php use Exam\Web as W;
$isRegister = $route === '/register';
?><div class="auth-content"><span class="eyebrow">YOUR
        EXAMINATION WORKSPACE</span>
    <h1><?= W::e($title) ?><span class="green-text">.</span></h1>
    <p class="muted">
        <?= match ($route) {
            '/register' => 'Start your learning journey. Student registration takes just a moment.',
            '/forgot' => 'Enter your account email to receive a password recovery link.',
            '/reset' => 'Choose a strong password to securely restore access.',
            default => 'Sign in to pick up where you left off.',
        } ?>
    </p>
    <form method="post" action="<?= W::url($route) ?>" class="stack-form"><?= W::csrf() ?>
        <?php if (
            $isRegister
        ): ?><label>Full name<input name="full_name" autocomplete="name" maxlength="120"
                required></label>
        <div class="form-grid"><label>Username<input name="username" autocomplete="username" minlength="3"
                    maxlength="60" pattern="[a-zA-Z0-9_.-]+" required></label><label>Department<select
                    name="department_id" required>
                    <option value="">Select department</option><?php foreach (
                        $departments
                        as $d
                    ): ?><option
                        value="<?= $d['department_id'] ?>"><?= W::e(
    $d['department_name'],
) ?></option><?php endforeach; ?>
                </select></label></div><?php endif; ?>
        <?php if (
            $route === '/login'
        ): ?><label>Username or email<input name="identifier" placeholder="you@example.com"
                autocomplete="username" maxlength="254" required
                autofocus></label><?php elseif (
            $route !== '/reset'
        ): ?><label>Email address<input type="email" name="email"
                placeholder="you@example.com" autocomplete="email" maxlength="254" required></label><?php endif; ?>
        <?php if ($route !== '/forgot'): ?><label>Password<input type="password" name="password"
                autocomplete="<?= $route === '/login' ? 'current-password' : 'new-password' ?>"
                minlength="<?= $route === '/login'
                    ? 1
                    : 10 ?>" maxlength="72" required></label><?php endif; ?>
        <?php if (
            $isRegister ||
            $route === '/reset'
        ): ?><label>Confirm password<input type="password"
                name="password_confirmation" autocomplete="new-password" minlength="10" maxlength="72" required></label>
        <p class="field-hint">Use 10–72 characters. Avoid reusing another account's password.</p><?php endif; ?>
        <?php if ($route === '/reset'): ?><input type="hidden" name="token"
            value="<?= W::e($_GET['token'] ?? '') ?>"><?php endif; ?>
        <?php if ($route === '/login'): ?><a class="forgot-link" href="<?= W::url(
    '/forgot',
) ?>">Forgot your
            password?</a><?php endif; ?><button class="button primary full"
            type="submit"><?= match ($route) {
                '/register' => 'Create student account',
                '/forgot' => 'Send recovery link',
                '/reset' => 'Update password',
                default => 'Sign in to your workspace',
            } ?>
            <span aria-hidden="true">→</span></button>
    </form>
    <p class="auth-switch"><?php if ($route === '/login'): ?>New here? <a href="<?= W::url(
    '/register',
) ?>">Create a student
            account</a><?php else: ?><a href="<?= W::url(
    '/login',
) ?>">← Back to sign in</a><?php endif; ?></p>
</div>