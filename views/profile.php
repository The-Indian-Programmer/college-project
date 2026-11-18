<?php use Exam\Web as W; ?><div class="page-heading">
    <div><span class="eyebrow">YOUR WORKSPACE IDENTITY</span>
        <h1>Account settings</h1>
        <p class="muted">Keep your account secure and your details close at hand.</p>
    </div>
</div>
<div class="two-column">
    <section class="panel">
        <h2>Your profile</h2>
        <dl class="detail-list">
            <dt>Full name</dt>
            <dd><?= W::e(
    $user['full_name'],
) ?></dd>
            <dt>Username</dt>
            <dd><?= W::e($user['username']) ?></dd>
            <dt>Email</dt>
            <dd><?= W::e(
    $user['email'],
) ?></dd>
            <dt>Role</dt>
            <dd><?= W::e(
    ucfirst($user['role']),
) ?></dd>
        </dl>
        <p class="field-hint">Contact your administrator to update profile or department details.</p>
    </section>
    <section class="panel">
        <h2>Change password</h2>
        <form class="stack-form" method="post" action="<?= W::url(
    '/profile',
) ?>"><?= W::csrf() ?><label>Current password<input type="password" name="current_password"
                    autocomplete="current-password" required></label><label>New password<input type="password"
                    name="password" minlength="10" maxlength="72" autocomplete="new-password"
                    required></label><label>Confirm new password<input type="password" name="password_confirmation"
                    minlength="10" maxlength="72" autocomplete="new-password" required></label><button
                class="button primary">Update password</button>
            <p class="field-hint">Changing your password signs out existing sessions.</p>
        </form>
    </section>
</div>