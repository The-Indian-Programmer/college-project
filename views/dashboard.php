<?php use Exam\Web as W;
$now = gmdate('Y-m-d H:i:s');
$live = count(array_filter($exams, fn($e) => $e['starts_at'] <= $now && $e['ends_at'] > $now));
$upcoming = count(array_filter($exams, fn($e) => $e['starts_at'] > $now));
$first = explode(' ', $user['full_name'])[0];
$isStudent = $user['role'] === 'student';
?>
<div class="page-heading">
    <div><span class="eyebrow">YOUR LEARNING, AT A GLANCE</span>
        <h1>Hello, <?= W::e($first) ?><span class="green-text">.</span></h1>
        <p class="muted">
            <?= $isStudent
                ? 'A little preparation. A lot of possibility. Find your next examination below.'
                : 'Everything you need to keep assessments moving forward.' ?>
        </p>
    </div><span class="date-pill"><?= W::date($now, 'D, d M Y') ?></span>
</div>
<section class="hero-banner">
    <div><span class="eyebrow">READY FOR WHAT’S NEXT</span>
        <h2><?= $isStudent
            ? 'Your next chapter<br>starts with a question.'
            : 'Great assessments.<br>Better learning.' ?></h2>
        <p><?= $isStudent
            ? 'Explore your examinations, keep track of your schedule, and turn preparation into progress.'
            : 'Create meaningful questions, manage examinations, and understand participation in one place.' ?>
        </p><a class="button dark"
            href="<?= W::url($isStudent ? '/exams' : '/exams/edit') ?>"><?= $isStudent
    ? 'Explore examinations'
    : 'Create examination' ?>
            <span>↗</span></a>
    </div>
    <div class="hero-symbol" aria-hidden="true"><span>✳</span>
        <div class="symbol-caption">STAY CURIOUS<br>KEEP GROWING</div>
    </div>
</section>
<div class="stats-grid">
    <article class="stat-card"><span class="stat-icon">▤</span>
        <p>Available examinations</p><strong><?= count(
            $exams,
        ) ?></strong><small>In your workspace</small>
    </article>
    <article class="stat-card"><span class="stat-icon mint">◉</span>
        <p>Open now</p><strong><?= $live ?></strong><small>Ready for participation</small>
    </article>
    <article class="stat-card"><span class="stat-icon lavender">◷</span>
        <p>Coming up</p><strong><?= $upcoming ?></strong><small>Scheduled ahead</small>
    </article>
    <article class="stat-card"><span class="stat-icon peach">✓</span>
        <p><?= $isStudent ? 'Registered exams' : 'Total registrations' ?></p>
        <strong><?= $isStudent
            ? count($registrations)
            : array_sum(array_column($exams, 'registered_count')) ?></strong><small><?= $isStudent
    ? 'Your examination plan'
    : 'Across your examinations' ?></small>
    </article>
</div>
<div class="section-heading">
    <div>
        <h2><?= $isStudent ? 'Your examination board' : 'Recent examinations' ?></h2>
        <p class="muted">A clear view of what’s open and what’s ahead.</p>
    </div><a class="text-link" href="<?= W::url('/exams') ?>">View all →</a>
</div>
<?php
$cards = array_slice($exams, 0, 3);
require ROOT . '/views/_exam_cards.php';
 ?>
