<?php use Exam\Web as W;
$pct = round(((float) $result['score'] / (float) $result['max_score']) * 100, 1);
?><div class="page-heading">
    <div><span class="eyebrow">ONE MORE STEP FORWARD</span>
        <h1>Your result is ready<span class="green-text">.</span></h1>
        <p class="muted">A clear picture of this examination, saved in your records.</p>
    </div>
</div>
<section class="panel result-panel"><span class="result-emblem" aria-hidden="true">✳</span><span
        class="badge open-now">Completed & evaluated</span>
    <h2><?= W::e(
    $result['title'],
) ?></h2>
    <p class="muted"><?= W::e($result['full_name']) ?></p>
    <div class="score-display"><?= W::e(
    $result['score'],
) ?><span>/ <?= W::e(
    $result['max_score'],
) ?></span></div>
    <p class="score-caption"><?= $pct ?>% of available marks</p>
    <div class="result-details">
        <div><span>Started</span><strong><?= W::date(
    $result['started_at'],
) ?></strong></div>
        <div><span>Submitted</span><strong><?= W::date(
    $result['submitted_at'],
) ?></strong></div>
        <div><span>Attempt</span><strong>#<?= (int) $result[
    'attempt_id'
] ?> · Final</strong></div>
    </div>
    <p class="field-hint">Correct responses receive their configured marks. Incorrect or unanswered questions receive
        zero.</p><a class="button primary" href="<?= W::url(
     '/results',
 ) ?>">View all results →</a>
</section>