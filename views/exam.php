<?php use Exam\Web as W;
$now = gmdate('Y-m-d H:i:s');
$open = $now >= $exam['starts_at'] && $now < $exam['ends_at'];
$canEdit =
    $user['role'] === 'instructor' &&
    (int) ($exam['assigned_to'] ?? $exam['created_by']) === (int) $user['user_id'] &&
    !$frozen;
?><a class="back-link" href="<?= W::url(
    '/exams',
) ?>">← All examinations</a>
<div class="page-heading">
    <div><span class="eyebrow"><?= W::e(
    $exam['department_name'],
) ?></span>
        <h1><?= W::e($exam['title']) ?></h1>
        <p class="muted">Prepared by <?= W::e(
    $exam['creator_name'],
) ?></p>
    </div><span class="badge <?= $open
    ? 'open-now'
    : ($now < $exam['starts_at']
        ? 'upcoming'
        : 'closed') ?>"><?= $open
    ? 'Open now'
    : ($now < $exam['starts_at']
        ? 'Upcoming'
        : 'Closed') ?></span>
</div>
<div class="stats-grid compact">
    <article class="stat-card">
        <p>Duration</p><strong><?= $exam[
    'duration_minutes'
] ?><small> min</small></strong>
    </article>
    <article class="stat-card">
        <p>Questions</p><strong><?= $meta[
    'question_count'
] ?></strong>
    </article>
    <article class="stat-card">
        <p>Available marks</p><strong><?= W::e(
    $meta['total_marks'],
) ?></strong>
    </article>
    <article class="stat-card">
        <p>Attempt policy</p><strong>01<small> attempt</small></strong>
    </article>
</div>
<div class="two-column">
    <section class="panel">
        <h2>Examination schedule</h2>
        <dl class="detail-list">
            <dt>Opens</dt>
            <dd><?= W::date(
    $exam['starts_at'],
) ?></dd>
            <dt>Closes</dt>
            <dd><?= W::date(
    $exam['ends_at'],
) ?></dd>
        </dl>
        <p class="field-hint">Your deadline is the earlier of your allowed duration or the examination closing time.</p><?php if (
    $user['role'] === 'student'
): ?><div class="button-row"><?php if (
    $reg &&
    $reg['attempt_id']
): ?><a class="button primary" href="<?= W::url(
    $reg['status'] === 'submitted' ? '/result' : '/attempt',
    ['id' => $reg['attempt_id']],
) ?>"><?= $reg['status'] === 'submitted'
    ? 'View your result'
    : 'Resume your attempt' ?> →</a><?php elseif (
    !$reg &&
    $now < $exam['ends_at']
): ?><form method="post" action="<?= W::url(
    '/exams/register',
) ?>"><?= W::csrf() ?><input type="hidden" name="exam_id" value="<?= $exam[
    'exam_id'
] ?>"><button class="button primary">Register for examination</button></form><?php elseif (
    $reg &&
    $open
): ?><form method="post" action="<?= W::url(
    '/attempt/start',
) ?>" data-confirm="Start your one permitted attempt? The timer starts immediately."><?= W::csrf() ?><input
                    type="hidden" name="exam_id" value="<?= $exam[
    'exam_id'
] ?>"><button class="button primary">Start examination →</button></form><?php else: ?><span class="badge neutral"><?=
($reg ? 'You are registered. ' : '') .
($now < $exam['starts_at'] ? 'The exam has not opened yet.' : 'The exam window has closed.')
?></span><?php endif; ?></div><?php else: ?><div class="button-row"><?php if (
    $canEdit
): ?><a class="button primary" href="<?= W::url('/exams/edit', [
    'id' => $exam['exam_id'],
]) ?>">Edit schedule</a><?php endif; ?><a class="button secondary" href="<?= W::url('/reports', [
    'id' => $exam['exam_id'],
]) ?>">Participation report</a></div><?php endif; ?>
    </section>
    <section class="panel rules-panel"><span class="eyebrow">BEFORE YOU BEGIN</span>
        <h2>A few things to know</h2>
        <ul class="check-list">
            <li>Choose one answer for each question.</li>
            <li>Correct answers receive the configured marks.</li>
            <li>Incorrect and unanswered items receive zero.</li>
            <li>Save confirmation means your answer reached the server.</li>
            <li>You cannot change answers after submitting.</li>
        </ul>
    </section>
</div>
<?php if (
    $user['role'] !== 'student'
): ?><div class="section-heading">
    <div>
        <h2>Question set</h2>
        <p class="muted"><?= $frozen
    ? 'Locked to protect existing attempts and results.'
    : 'Review your answer keys before students begin.' ?></p>
    </div><?php if (
    $canEdit
): ?><a class="button primary" href="<?= W::url('/questions/edit', [
    'exam_id' => $exam['exam_id'],
]) ?>">+ Add question</a><?php endif; ?>
</div><?php
if (
    !$questions
): ?><div class="empty-state">
    <h3>No questions yet</h3>
    <p>Students cannot begin until the examination has a valid question set.</p>
</div><?php endif;
foreach (
    $questions
    as $idx => $q
): ?><article class="panel question-preview">
    <div class="section-heading"><span class="eyebrow">QUESTION <?= $idx +
    1 ?> · <?= W::e($q['question_type']) ?></span><span class="badge neutral"><?= W::e(
    $q['marks'],
) ?> marks</span></div>
    <h3><?= nl2br(
     W::e($q['question_text']),
 ) ?></h3>
    <ul class="option-preview"><?php foreach ($q['options'] as $o): ?><li class="<?= $o[
    'is_correct'
]
    ? 'correct'
    : '' ?>"><?= W::e($o['option_text']) ?><?php
if ($o['is_correct']): ?><span>✓ Correct answer</span><?php endif;
?></li><?php endforeach; ?></ul><?php if (
    $canEdit
): ?><div class="button-row"><a class="text-link" href="<?= W::url('/questions/edit', [
    'exam_id' => $exam['exam_id'],
    'id' => $q['question_id'],
]) ?>">Edit question</a>
        <form method="post" action="<?= W::url(
    '/questions/delete',
) ?>" data-confirm="Delete this question and its options?"><?= W::csrf() ?><input type="hidden" name="exam_id" value="<?= $exam[
    'exam_id'
] ?>"><input type="hidden" name="question_id" value="<?= $q[
    'question_id'
] ?>"><button class="link-button danger">Delete</button></form>
    </div><?php endif; ?>
</article><?php endforeach;
endif; ?>