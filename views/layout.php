<?php use Exam\Web as W;
use Exam\Config as C;
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light"><meta name="csrf-token" content="<?= W::e(
    $_SESSION['csrf'] ?? '',
) ?>"><title><?= W::e(
    $title,
) ?> · Examina</title><link rel="stylesheet" href="assets/app.css"><script src="assets/app.js" defer></script></head>
<body class="<?= $user
    ? 'signed-in'
    : 'signed-out' ?>"><a class="skip-link" href="#main">Skip to content</a>
<?php if ($user): ?><aside class="sidebar" id="sidebar"><a class="brand" href="<?= W::url(
    '/',
) ?>"><span class="brand-icon">e</span>examina<span class="brand-dot">.</span></a><p class="nav-label">YOUR WORKSPACE</p><nav aria-label="Main navigation">
<?php
$links = [['/', '◫', 'Overview'], ['/exams', '▤', 'Examinations'], ['/results', '◷', 'Results']];
if ($user['role'] !== 'student') {
    $links[] = ['/reports', '▥', 'Reports'];
}
if ($user['role'] === 'admin') {
    $links[] = ['/admin/users', '♙', 'People & access'];
    $links[] = ['/admin/departments', '▦', 'Departments'];
}
foreach ($links as [$path, $icon, $label]): ?><a class="nav-link <?= $route === $path ||
($path === '/exams' && str_starts_with($route, '/exams/'))
    ? 'active'
    : '' ?>" href="<?= W::url($path) ?>"><span aria-hidden="true"><?= W::e($icon) ?></span><?= W::e(
    $label,
) ?></a><?php endforeach;
?></nav><div class="sidebar-bottom"><div class="workspace-note"><span class="live-dot"></span> Your learning, in focus.<p>A simpler space for better assessments.</p></div><a class="nav-link" href="<?= W::url(
    '/profile',
) ?>"><span aria-hidden="true">⚙</span><span>Account settings</span></a><form method="post" action="<?= W::url(
    '/logout',
) ?>"><?= W::csrf() ?><button class="nav-link signout" type="submit"><span aria-hidden="true">↗</span><span>Sign out</span></button></form></div></aside>
<div class="workspace"><header class="topbar"><button id="nav-toggle" class="icon-button mobile-only" aria-label="Toggle navigation" aria-expanded="false" aria-controls="sidebar">☰</button><div class="breadcrumb">Workspace <span>/</span> <?= W::e(
    $title,
) ?></div><a class="user-chip" href="<?= W::url('/profile') ?>"><span class="avatar"><?= W::e(
    mb_substr($user['full_name'], 0, 1),
) ?></span><span><strong><?= W::e($user['full_name']) ?></strong><small><?= W::e(
    ucfirst($user['role']),
) ?></small></span></a></header><main id="main" class="main-content"><?php
if ($flash): ?><div class="notice success" role="status"><?= W::e($flash) ?></div><?php endif; ?>
<?= $content ?>
</main><footer>Examina · Online Examination System <span><?= W::e(
    C::get('APP_TIMEZONE', 'Asia/Kolkata'),
) ?></span></footer></div>
<?php else: ?><main id="main" class="auth-shell"><section class="auth-story"><a class="brand light" href="<?= W::url(
    '/login',
) ?>"><span class="brand-icon">e</span>examina.</a><div class="story-copy"><span class="eyebrow light-text">A BETTER WAY TO ASSESS</span><h1>Big ideas.<br>Clear outcomes.</h1><p>One thoughtful space to prepare, participate, and see your progress.</p><div class="story-art" aria-hidden="true"><div class="orbit orbit-one"></div><div class="orbit orbit-two"></div><div class="art-card"><span>YOUR NEXT CHAPTER</span><strong>Make it count.</strong><div class="art-bars"><i></i><i></i><i></i><i></i><i></i></div></div></div></div><p class="story-footer">Built for students. Designed for possibility.</p></section><section class="auth-panel"><?php
if ($flash): ?><div class="notice success" role="status"><?= W::e($flash) ?></div><?php endif; ?>
<?= $content ?>
</section></main><?php endif; ?></body></html>
