<?php use Exam\Web as W;
use Exam\Config as C;
?><div class="page-heading">
    <div><span class="eyebrow">MAKE SPACE FOR LEARNING</span>
        <h1><?= W::e(
    $title,
) ?></h1>
        <p class="muted">Set the schedule. Shape the next assessment.</p>
    </div>
</div>
<section class="panel narrow">
    <form method="post" action="<?= W::url(
    '/exams/save',
) ?>" class="stack-form"><?= W::csrf() ?><input type="hidden" name="exam_id" value="<?= W::e(
    $exam['exam_id'] ?? '',
) ?>"><label>Examination title<input name="title" value="<?= W::e(
    $exam['title'] ?? '',
) ?>" maxlength="150" placeholder="e.g. Database Fundamentals" required></label><?php if (
    $user['role'] === 'admin'
): ?><label>Department<select name="department_id" required>
                <option value="">Select department</option><?php foreach (
    $departments
    as $d
): ?><option value="<?= $d['department_id'] ?>"><?= W::e(
    $d['department_name'],
) ?></option><?php endforeach; ?>
            </select></label><label>Responsible instructor<select name="assigned_to" required>
                <option value="">Select an instructor in the same department</option><?php foreach (
    $instructors
    as $person
): ?><option value="<?= $person['user_id'] ?>"><?= W::e(
    $person['full_name'] . ' · ' . $person['department_name'],
) ?></option><?php endforeach; ?>
            </select></label>
        <p class="field-hint">The responsible instructor prepares and maintains the questions. You can review
            participation and results.</p><?php endif; ?><div class="form-grid"><label>Opens at<input
                    type="datetime-local" name="starts_at" value="<?= $exam
    ? W::date($exam['starts_at'], 'Y-m-d\TH:i')
    : '' ?>" required></label><label>Closes at<input type="datetime-local" name="ends_at" value="<?= $exam
    ? W::date($exam['ends_at'], 'Y-m-d\TH:i')
    : '' ?>" required></label></div>
        <p class="field-hint">All schedule times are shown in <?= W::e(
    C::get('APP_TIMEZONE', 'Asia/Kolkata'),
) ?>. Closing time is exclusive.</p><label>Duration in minutes<input type="number" name="duration_minutes" min="1"
                max="1440" value="<?= W::e(
    $exam['duration_minutes'] ?? 30,
) ?>" required></label>
        <div class="notice">Questions and schedule become locked when the first student starts. Each student receives
            one attempt.</div>
        <div class="button-row"><button class="button primary">Save examination →</button><a class="button secondary"
                href="<?= W::url(
    '/exams',
) ?>">Cancel</a></div>
    </form>
</section>