<?php use Exam\Web as W; ?><div class="page-heading">
    <div><span class="eyebrow">FOCUS MODE · YOUR ONE ATTEMPT</span>
        <h1><?= W::e($attempt['exam']['title']) ?></h1>
        <p class="muted">Take your time. Read carefully. You’ve got this.</p>
    </div>
    <div class="timer-pill" role="timer" aria-label="Time remaining"><span>Time remaining</span><strong
            id="exam-timer">Loading…</strong></div>
</div>
<noscript>
    <div class="notice">JavaScript is disabled. Answer autosave and the visible timer are unavailable. Submit this form
        before <?= W::date(
            $attempt['deadline_at'],
        ) ?> to save and evaluate your answers. The server still enforces your
        deadline.</div>
</noscript>
<form id="attempt-form" method="post" action="<?= W::url(
    '/attempt/submit',
) ?>" data-attempt="<?= $attempt['attempt_id'] ?>"
    data-deadline="<?= W::e($attempt['deadline_at']) ?>Z" data-server-time="<?= W::e(
    $attempt['server_time'],
) ?>Z"
    data-save-url="<?= W::url('/api/answers') ?>" data-result-url="<?= W::url('/result', [
    'id' => $attempt['attempt_id'],
]) ?>">
    <?= W::csrf() ?><input type="hidden" name="attempt_id" value="<?= $attempt['attempt_id'] ?>">
    <div class="attempt-layout">
        <div class="question-stack"><?php foreach ($attempt['questions'] as $idx => $q): ?><fieldset
                class="panel exam-question" id="question-<?= $q['question_id'] ?>">
                <legend><span>Question <?= $idx + 1 ?></span><span class="badge neutral"><?= W::e(
    $q['marks'],
) ?>
                        marks</span></legend>
                <h2><?= nl2br(W::e($q['question_text'])) ?></h2>
                <div class="answer-options"><?php foreach ($q['options'] as $oi => $o): ?><label
                        class="answer-option"><input type="radio" name="answers[<?= $q[
                            'question_id'
                        ] ?>]"
                            value="<?= $o['option_id'] ?>" data-question="<?= $q['question_id'] ?>"
                            <?= isset($attempt['answers'][$q['question_id']]) &&
                            (int) $attempt['answers'][$q['question_id']] === (int) $o['option_id']
                                ? 'checked'
                                : '' ?>><span
                            class="option-letter"
                            aria-hidden="true"><?= chr(65 + $oi) ?></span><span><?= W::e(
    $o['option_text'],
) ?></span></label><?php endforeach; ?>
                </div><button type="button" class="link-button clear-answer"
                    data-question="<?= $q['question_id'] ?>">Clear answer</button>
            </fieldset><?php endforeach; ?></div>
        <aside class="panel attempt-summary"><span class="eyebrow">YOUR PROGRESS</span>
            <h2><span id="answered-count">0</span> / <?= count(
                $attempt['questions'],
            ) ?> answered</h2>
            <div class="progress-track">
                <div id="answer-progress"></div>
            </div>
            <div class="question-nav"><?php foreach ($attempt['questions'] as $idx => $q): ?><a
                    href="#question-<?= $q['question_id'] ?>" data-nav-question="<?= $q[
    'question_id'
] ?>"
                    aria-label="Go to question <?= $idx + 1 ?>"><?= $idx +
    1 ?></a><?php endforeach; ?></div>
            <p id="save-status" class="save-status" role="status" aria-live="polite">Only server-confirmed answers are
                saved.</p><button id="retry-save" type="button" class="button secondary full" hidden>Retry
                saving</button>
            <p class="field-hint">Unanswered questions receive zero marks. Submission is final.</p><button
                id="submit-exam" class="button primary full" type="submit">Submit examination →</button>
            <p class="field-hint">Deadline: <?= W::date($attempt['deadline_at'], 'h:i:s A') ?></p>
        </aside>
    </div>
</form>