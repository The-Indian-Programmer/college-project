<?php use Exam\Web as W;
$opts = $question ? array_column($question['options'], 'option_text') : ['', '', '', ''];
$correct = 0;
if ($question) {
    foreach ($question['options'] as $idx => $o) {
        if ($o['is_correct']) {
            $correct = $idx;
        }
    };
}
?><a class="back-link" href="<?= W::url('/exams/show', ['id' => $exam['exam_id']]) ?>">← <?= W::e(
    $exam['title'],
) ?></a>
<div class="page-heading">
    <div><span class="eyebrow">MAKE EVERY QUESTION COUNT</span>
        <h1><?= W::e(
    $title,
) ?></h1>
    </div>
</div>
<section class="panel narrow">
    <form id="question-editor" method="post" action="<?= W::url(
    '/questions/save',
) ?>" class="stack-form"><?= W::csrf() ?><input type="hidden" name="exam_id" value="<?= $exam[
    'exam_id'
] ?>"><input type="hidden" name="question_id" value="<?= W::e(
    $question['question_id'] ?? '',
) ?>"><label>Question text<textarea name="question_text" rows="4" maxlength="10000" required><?= W::e(
    $question['question_text'] ?? '',
) ?></textarea></label>
        <div class="form-grid"><label>Question type<select name="question_type" id="question-type">
                    <option value="MCQ" <?= W::selected(
    $question['question_type'] ?? '',
    'MCQ',
) ?>>Multiple choice</option>
                    <option value="TF" <?= W::selected(
    $question['question_type'] ?? '',
    'TF',
) ?>>True / false</option>
                </select></label><label>Marks<input type="number" name="marks" min="0.01" max="9999.99" step="0.01"
                    value="<?= W::e(
    $question['marks'] ?? '1.00',
) ?>" required></label></div>
        <fieldset class="option-editor">
            <legend>Answer options</legend>
            <p class="field-hint">Select the radio button beside the one correct answer. True/false uses exactly True
                and False.</p>
            <div id="option-fields"><?php foreach (
    $opts
    as $idx => $text
): ?><div class="option-row"><input type="radio" name="correct" value="<?= $idx ?>" aria-label="Option <?= $idx +
    1 ?> is correct" <?= $idx === $correct
     ? 'checked'
     : '' ?> required><input name="options[]" value="<?= W::e(
     $text,
 ) ?>" aria-label="Option <?= $idx +
    1 ?> text" maxlength="500" required><button type="button" class="icon-button remove-option"
                        aria-label="Remove option">×</button></div><?php endforeach; ?></div><button type="button"
                class="button secondary" id="add-option">+ Add option</button>
        </fieldset>
        <div class="button-row"><button class="button primary">Save question →</button><a class="button secondary" href="<?= W::url(
    '/exams/show',
    ['id' => $exam['exam_id']],
) ?>">Cancel</a></div>
    </form>
</section>