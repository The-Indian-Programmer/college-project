'use strict';
const navToggle = document.querySelector('#nav-toggle');
navToggle?.addEventListener('click', () => {
    const open = document.querySelector('#sidebar').classList.toggle('open');
    navToggle.setAttribute('aria-expanded', String(open));
});
document.addEventListener('click', (e) => {
    if (!e.target.closest('#sidebar, #nav-toggle')) {
        document.querySelector('#sidebar')?.classList.remove('open');
        navToggle?.setAttribute('aria-expanded', 'false');
    }
});
document.querySelectorAll('[data-confirm]').forEach((form) =>
    form.addEventListener('submit', (e) => {
        if (!window.confirm(form.dataset.confirm)) e.preventDefault();
    }),
);
document
    .querySelectorAll('[data-back]')
    .forEach((button) => button.addEventListener('click', () => history.back()));
document.querySelector('#exam-search')?.addEventListener('input', (e) => {
    let count = 0;
    document.querySelectorAll('.exam-card').forEach((card) => {
        card.hidden = !card.textContent.toLowerCase().includes(e.target.value.toLowerCase());
        if (!card.hidden) count++;
    });
    document.querySelector('#no-search-results').hidden = count > 0;
});
const editor = document.querySelector('#question-editor');
if (editor) {
    const list = document.querySelector('#option-fields');
    const type = document.querySelector('#question-type');
    const add = document.querySelector('#add-option');
    function numberOptions() {
        const rows = [...list.children];
        rows.forEach((row, index) => {
            const radio = row.querySelector('[type=radio]');
            radio.value = index;
            radio.setAttribute('aria-label', `Option ${index + 1} is correct`);
            row.querySelector('[name="options[]"]').setAttribute(
                'aria-label',
                `Option ${index + 1} text`,
            );
            row.querySelector('button').disabled = type.value === 'TF' || rows.length <= 2;
        });
        add.hidden = type.value === 'TF';
        add.disabled = rows.length >= 6;
        if (!list.querySelector(':checked')) list.querySelector('[type=radio]').checked = true;
    }
    function makeOption(value = '') {
        const row = document.createElement('div');
        row.className = 'option-row';
        const radio = document.createElement('input');
        radio.type = 'radio';
        radio.name = 'correct';
        radio.required = true;
        const input = document.createElement('input');
        input.name = 'options[]';
        input.value = value;
        input.maxLength = 500;
        input.required = true;
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'icon-button remove-option';
        remove.textContent = '×';
        remove.setAttribute('aria-label', 'Remove option');
        row.append(radio, input, remove);
        list.append(row);
    }
    add.addEventListener('click', () => {
        if (list.children.length < 6) makeOption();
        numberOptions();
    });
    list.addEventListener('click', (e) => {
        if (e.target.closest('.remove-option') && list.children.length > 2 && type.value !== 'TF') {
            e.target.closest('.option-row').remove();
            numberOptions();
        }
    });
    type.addEventListener('change', () => {
        if (type.value === 'TF') {
            list.replaceChildren();
            makeOption('True');
            makeOption('False');
        }
        list.querySelectorAll('[name="options[]"]').forEach(
            (input) => (input.readOnly = type.value === 'TF'),
        );
        numberOptions();
    });
    list.querySelectorAll('[name="options[]"]').forEach(
        (input) => (input.readOnly = type.value === 'TF'),
    );
    numberOptions();
}
const attempt = document.querySelector('#attempt-form');
if (attempt) {
    const status = document.querySelector('#save-status');
    const retry = document.querySelector('#retry-save');
    const submit = document.querySelector('#submit-exam');
    const timer = document.querySelector('#exam-timer');
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const parseUTC = (value) => Date.parse(value.replace(' ', 'T'));
    const deadline = parseUTC(attempt.dataset.deadline);
    const serverAtLoad = parseUTC(attempt.dataset.serverTime);
    const loaded = performance.now();
    let chain = Promise.resolve();
    let revision = 0;
    let committed = 0;
    let submitting = false;
    let navigating = false;
    let expiredStarted = false;
    const questions = [...attempt.querySelectorAll('.exam-question')];
    function snapshot() {
        const answers = {};
        questions.forEach((q) => {
            const first = q.querySelector('input[type=radio]');
            answers[first.dataset.question] = q.querySelector('input:checked')?.value ?? null;
        });
        return answers;
    }
    function progress() {
        const count = attempt.querySelectorAll('input[type=radio]:checked').length;
        document.querySelector('#answered-count').textContent = count;
        document.querySelector('#answer-progress').style.width =
            `${(count / questions.length) * 100}%`;
        questions.forEach((q) =>
            document
                .querySelector(`[data-nav-question="${q.id.replace('question-', '')}"]`)
                ?.classList.toggle('answered', !!q.querySelector(':checked')),
        );
    }
    function feedback(text, error = false) {
        status.textContent = text;
        status.classList.toggle('error', error);
        retry.hidden = !error;
    }
    async function request(answers, finish, requestRevision) {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 15000);
        try {
            const response = await fetch(attempt.dataset.saveUrl, {
                method: 'POST',
                credentials: 'same-origin',
                signal: controller.signal,
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                body: JSON.stringify({ attempt_id: attempt.dataset.attempt, answers, finish }),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'Unable to save answers.');
            if (data.completed) {
                navigating = true;
                location.assign(attempt.dataset.resultUrl);
                return;
            }
            committed = Math.max(committed, requestRevision);
            if (committed === revision) feedback('✓ All current answers are saved.');
        } finally {
            clearTimeout(timeout);
        }
    }
    function enqueueSave() {
        const answers = snapshot();
        const current = revision;
        feedback('Saving your answers…');
        chain = chain
            .catch(() => {})
            .then(() => request(answers, false, current))
            .catch((error) => {
                feedback(
                    `${error.name === 'AbortError' ? 'Connection timed out.' : error.message} Current choices may not be saved. Retry before the deadline.`,
                    true,
                );
            });
    }
    attempt.addEventListener('change', (e) => {
        if (e.target.matches('input[type=radio]')) {
            revision++;
            progress();
            enqueueSave();
        }
    });
    attempt.querySelectorAll('.clear-answer').forEach((button) =>
        button.addEventListener('click', () => {
            if (submitting) return;
            attempt
                .querySelectorAll(`[data-question="${button.dataset.question}"][type=radio]`)
                .forEach((input) => (input.checked = false));
            revision++;
            progress();
            enqueueSave();
        }),
    );
    retry.addEventListener('click', () => {
        expiredStarted = false;
        if (remaining() <= 0) finish(true);
        else enqueueSave();
    });
    function remaining() {
        return Math.max(0, deadline - (serverAtLoad + performance.now() - loaded));
    }
    async function finish(expired = false) {
        if (submitting || navigating) return;
        if (
            !expired &&
            !confirm('Submit your examination? You cannot change answers after submission.')
        )
            return;
        submitting = true;
        submit.disabled = true;
        attempt
            .querySelectorAll('input[type=radio], .clear-answer')
            .forEach((input) => (input.disabled = true));
        const answers = snapshot();
        feedback(
            expired ? 'Time is up. Finalizing your saved answers…' : 'Submitting your examination…',
        );
        try {
            await chain;
            if (!navigating) await request(answers, true, revision);
        } catch (error) {
            submitting = false;
            submit.disabled = false;
            if (remaining() > 0)
                attempt
                    .querySelectorAll('input[type=radio], .clear-answer')
                    .forEach((input) => (input.disabled = false));
            feedback(
                `Submission is not confirmed. ${error.message} Retry to retrieve the committed result or complete submission.`,
                true,
            );
        }
    }
    attempt.addEventListener('submit', (e) => {
        e.preventDefault();
        finish();
    });
    function updateTimer() {
        const seconds = Math.ceil(remaining() / 1000);
        timer.textContent = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
        timer.closest('.timer-pill').classList.toggle('urgent', seconds < 60);
        if (seconds === 0 && !expiredStarted) {
            expiredStarted = true;
            finish(true);
        }
    }
    updateTimer();
    setInterval(updateTimer, 300);
    window.addEventListener('beforeunload', (e) => {
        if (!navigating && revision > committed) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
    window.addEventListener('online', () => {
        if (!submitting && !navigating) {
            if (remaining() <= 0) finish(true);
            else enqueueSave();
        }
    });
    progress();
}
