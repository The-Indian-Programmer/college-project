<?php use Exam\Web as W; ?><div class="page-heading">
    <div><span class="eyebrow">ORGANIZE YOUR INSTITUTION</span>
        <h1>Departments</h1>
        <p class="muted">Connect people and examinations to the right academic department.</p>
    </div>
</div>
<section class="panel">
    <h2>Add department</h2>
    <form class="inline-form" method="post" action="<?= W::url('/admin/departments/save') ?>">
        <?= W::csrf() ?><label>Department name<input name="department_name" maxlength="100" required
                placeholder="e.g. Computer Applications"></label><button class="button primary">+ Add
            department</button></form>
</section>
<div class="department-grid"><?php foreach (
    $departments
    as $d
): ?><section class="panel department-card"><span
            class="subject-icon">▦</span>
        <h2><?= W::e($d['department_name']) ?></h2>
        <form method="post" action="<?= W::url(
            '/admin/departments/save',
        ) ?>" class="stack-form"><?= W::csrf() ?><input
                type="hidden" name="department_id" value="<?= $d[
                    'department_id'
                ] ?>"><label>Update name<input
                    name="department_name" value="<?= W::e(
                        $d['department_name'],
                    ) ?>" maxlength="100"
                    required></label><button class="button secondary">Save name</button></form>
        <form method="post" action="<?= W::url('/admin/delete') ?>"
            data-confirm="Delete this department? A department with dependent records cannot be deleted.">
            <?= W::csrf() ?><input type="hidden" name="entity" value="department"><input type="hidden" name="id"
                value="<?= $d[
                    'department_id'
                ] ?>"><button class="link-button danger">Delete unused department</button>
        </form>
    </section><?php endforeach; ?></div>