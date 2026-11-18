<?php use Exam\Web as W; ?><div class="page-heading">
    <div><span class="eyebrow">PROGRESS, MADE VISIBLE</span>
        <h1>Results</h1>
        <p class="muted">Completed examinations and their final, recorded scores.</p>
    </div>
</div>
<section class="panel"><?php if (
    !$results
): ?><div class="empty-state"><span>◷</span>
        <h3>No completed results yet</h3>
        <p>Results appear after an attempt is finalized.</p><a class="button primary" href="<?= W::url(
    '/exams',
) ?>">Explore examinations</a>
    </div><?php else: ?><div class="table-wrap" tabindex="0" aria-label="Results">
        <table>
            <thead>
                <tr>
                    <th>Examination</th>
                    <th>Student</th>
                    <th>Score</th>
                    <th>Evaluated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody><?php foreach (
    $results
    as $r
): ?><tr>
                    <td><strong><?= W::e($r['title']) ?></strong></td>
                    <td><?= W::e(
    $r['full_name'],
) ?></td>
                    <td><span class="badge open-now"><?= W::e($r['score']) ?> / <?= W::e(
     $r['max_score'],
 ) ?></span></td>
                    <td><?= W::date(
    $r['evaluated_at'],
) ?></td>
                    <td><a class="text-link" href="<?= W::url('/result', [
    'id' => $r['attempt_id'],
]) ?>">View →</a></td>
                </tr><?php endforeach; ?></tbody>
        </table>
    </div><?php endif; ?></section>