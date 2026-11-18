<?php use Exam\Web as W; ?><div class="page-heading">
    <div><span class="eyebrow">UNDERSTAND PARTICIPATION</span>
        <h1>Examination reports</h1>
        <p class="muted">Registration shows intent. Attendance shows a started attempt.</p>
    </div>
</div>
<section class="panel">
    <form method="get" action="index.php" class="inline-form"><input type="hidden" name="r"
            value="/reports"><label>Choose an examination<select name="id" required>
                <option value="">Select examination</option><?php foreach (
    $exams
    as $e
): ?><option value="<?= $e['exam_id'] ?>" <?= W::selected(
    $exam['exam_id'] ?? '',
    $e['exam_id'],
) ?>><?= W::e(
    $e['title'],
) ?></option><?php endforeach; ?>
            </select></label><button class="button primary">View report</button></form>
</section><?php if (
    $exam
):

    $attended = count(array_filter($rows, fn($r) => $r['attempt_id'] !== null));
    $completed = count(array_filter($rows, fn($r) => $r['score'] !== null));
    ?><div class="stats-grid three">
    <article class="stat-card">
        <p>Registered</p><strong><?= count(
    $rows,
) ?></strong>
    </article>
    <article class="stat-card">
        <p>Attended</p><strong><?= $attended ?></strong>
    </article>
    <article class="stat-card">
        <p>Completed</p><strong><?= $completed ?></strong>
    </article>
</div>
<section class="panel">
    <h2><?= W::e(
    $exam['title'],
) ?></h2><?php if (
    !$rows
): ?><div class="empty-state">
        <h3>No registrations yet</h3>
        <p>Student participation will appear here.</p>
    </div><?php else: ?><div class="table-wrap" tabindex="0" aria-label="Participation">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Registered</th>
                    <th>Participation</th>
                    <th>Score</th>
                    <th></th>
                </tr>
            </thead>
            <tbody><?php foreach (
    $rows
    as $r
): ?><tr>
                    <td><strong><?= W::e($r['full_name']) ?></strong><small>@<?= W::e(
    $r['username'],
) ?></small></td>
                    <td><?= W::date($r['registered_at']) ?></td>
                    <td><span class="badge <?= $r[
    'status'
] === 'submitted'
    ? 'open-now'
    : 'neutral' ?>"><?= $r['status'] === 'submitted'
    ? 'Completed'
    : ($r['attempt_id']
        ? 'Started'
        : 'Not started') ?></span></td>
                    <td><?= $r['score'] !== null
    ? W::e($r['score'] . ' / ' . $r['max_score'])
    : '—' ?></td>
                    <td><?php if ($r['score'] !== null): ?><a class="text-link" href="<?= W::url(
    '/result',
    ['id' => $r['attempt_id']],
) ?>">Result →</a><?php endif; ?></td>
                </tr><?php endforeach; ?></tbody>
        </table>
    </div><?php endif; ?>
</section><?php
else:
     ?><div class="empty-state"><span>▥</span>
    <h3>A clearer view of your examinations</h3>
    <p>Select an examination to see registrations, attendance and results.</p>
</div><?php
endif; ?>