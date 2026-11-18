<?php use Exam\Web as W; ?><section class="error-panel panel"><span class="eyebrow">REQUEST <?= W::e(
    $status,
) ?></span>
    <h1>Let’s get you<br>back on track.</h1>
    <p><?= W::e(
    $message,
) ?></p>
    <div class="button-row"><button type="button" class="button secondary" data-back>← Go back</button><a
            class="button primary" href="<?= W::url(
    $user ? '/' : '/login',
) ?>"><?= $user ? 'Go to overview' : 'Sign in' ?></a></div>
</section>