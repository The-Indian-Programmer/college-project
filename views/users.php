<?php use Exam\Web as W; ?><div class="page-heading">
    <div><span class="eyebrow">A PLACE FOR EVERY LEARNER</span>
        <h1>People & access</h1>
        <p class="muted">Manage accounts, departments and access to your workspace.</p>
    </div>
</div>
<section class="panel">
    <h2><?= $edit
    ? 'Edit account'
    : 'Create an account' ?></h2>
    <form method="post" action="<?= W::url(
    '/admin/users/save',
) ?>" class="stack-form"><?= W::csrf() ?><input type="hidden" name="user_id" value="<?= W::e(
    $edit['user_id'] ?? '',
) ?>">
        <div class="form-grid three"><label>Full name<input name="full_name" value="<?= W::e(
    $edit['full_name'] ?? '',
) ?>" maxlength="120" required></label><label>Username<input name="username" value="<?= W::e(
    $edit['username'] ?? '',
) ?>" minlength="3" maxlength="60" required></label><label>Email<input name="email" type="email" value="<?= W::e(
    $edit['email'] ?? '',
) ?>" maxlength="254" required></label><label>Role<select name="role"><?php foreach (
    ['student', 'instructor', 'admin']
    as $role
): ?><option<?= W::selected(
    $edit['role'] ?? 'student',
    $role,
) ?>><?= $role ?></option><?php endforeach; ?></select></label><label>Department<select name="department_id">
                    <option value="">None (administrator only)</option><?php foreach (
    $departments
    as $d
): ?><option value="<?= $d['department_id'] ?>" <?= W::selected(
    $edit['department_id'] ?? '',
    $d['department_id'],
) ?>><?= W::e(
    $d['department_name'],
) ?></option><?php endforeach; ?>
                </select></label><label>Status<select name="status"><?php foreach (
    ['active', 'inactive']
    as $s
): ?><option<?= W::selected(
    $edit['status'] ?? 'active',
    $s,
) ?>><?= $s ?></option><?php endforeach; ?></select></label><?php if (
    !$edit
): ?><label>Initial password<input type="password" name="password" minlength="10" maxlength="72"
                    autocomplete="new-password" required></label><?php endif; ?></div>
        <div class="button-row"><button class="button primary"><?= $edit
    ? 'Save changes'
    : 'Create account' ?></button><?php if ($edit): ?><a class="button secondary" href="<?= W::url(
    '/admin/users',
) ?>">Cancel</a><?php endif; ?></div>
    </form>
</section>
<section class="panel">
    <div class="section-heading">
        <h2>Workspace members</h2><span class="muted"><?= count(
    $users,
) ?> accounts</span>
    </div>
    <div class="table-wrap" tabindex="0" aria-label="Workspace members">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Manage</th>
                </tr>
            </thead>
            <tbody><?php foreach (
     $users
     as $row
 ): ?><tr>
                    <td><strong><?= W::e($row['full_name']) ?></strong><small><?= W::e(
    $row['email'],
) ?></small></td>
                    <td><span class="badge neutral"><?= W::e($row['role']) ?></span></td>
                    <td><?= W::e(
    $row['department_name'] ?? 'Institution',
) ?></td>
                    <td><span class="badge <?= $row['status'] === 'active'
    ? 'open-now'
    : 'closed' ?>"><?= W::e(
    $row['status'],
) ?></span></td>
                    <td>
                        <div class="button-row"><a class="text-link" href="<?= W::url('/admin/users', [
    'id' => $row['user_id'],
]) ?>">Edit</a>
                            <form method="post" action="<?= W::url(
    '/admin/delete',
) ?>" data-confirm="Delete this unreferenced account? Historical records are protected."><?= W::csrf() ?><input
                                    type="hidden" name="entity" value="user"><input type="hidden" name="id" value="<?= $row[
    'user_id'
] ?>"><button class="link-button danger">Delete</button></form>
                        </div>
                    </td>
                </tr><?php endforeach; ?></tbody>
        </table>
    </div>
</section>