<?php use Exam\Web as W; ?><div class="page-heading">
    <div><span class="eyebrow">PREPARE. PARTICIPATE. PROGRESS.</span>
        <h1>Examinations</h1>
        <p class="muted">Your schedule, your questions, your next opportunity.</p>
    </div><?php if (
    $user['role'] !== 'student'
): ?><a class="button primary" href="<?= W::url(
    '/exams/edit',
) ?>">+ Create examination</a><?php endif; ?>
</div>
<div class="filter-bar"><label class="search-field"><span aria-hidden="true">⌕</span><input id="exam-search"
            type="search" placeholder="Find an examination…" aria-label="Find an examination"></label>
    <p class="muted"><?= count(
    $exams,
) ?> examinations in your workspace</p>
</div><?php
 $cards = $exams;
 require ROOT . '/views/_exam_cards.php';
 ?><p id="no-search-results" class="empty-state" hidden>No examinations match that title.</p>