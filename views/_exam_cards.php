<?php use Exam\Web as W;
$now = gmdate('Y-m-d H:i:s');
if (!$cards): ?><div class="empty-state"><span>▤</span>
    <h3>No examinations yet</h3>
    <p>Examinations for your workspace will appear here when they are created.</p>
</div><?php else: ?><div class="exam-grid">
    <?php foreach ($cards as $e):
        $state =
            $now < $e['starts_at']
                ? 'Upcoming'
                : ($now >= $e['ends_at']
                    ? 'Closed'
                    : 'Open now'); ?>
    <article class="exam-card">
        <div class="card-top"><span class="subject-icon">▤</span><span
                class="badge <?= strtolower(
                    str_replace(' ', '-', $state),
                ) ?>"><?= $state ?></span></div>
        <p class="eyebrow"><?= W::e($e['department_name']) ?></p>
        <h3><?= W::e($e['title']) ?></h3>
        <p class="muted">Prepared by <?= W::e($e['creator_name']) ?></p>
        <div class="exam-meta"><span>◷ <?= (int) $e[
            'duration_minutes'
        ] ?> min</span><span>▤ <?= (int) $e['question_count'] ?>
                questions</span><span>◎ <?= W::e($e['total_marks']) ?> marks</span></div>
        <div class="exam-card-footer"><span><?= W::date(
            $e['starts_at'],
            'd M, h:i A',
        ) ?></span><a class="circle-link"
                aria-label="View <?= W::e($e['title']) ?>" href="<?= W::url('/exams/show', [
    'id' => $e['exam_id'],
]) ?>">↗</a>
        </div>
    </article><?php
    endforeach; ?></div><?php endif; ?>