const { test, expect } = require('@playwright/test');
const fs = require('fs');
const password = process.env.SEED_PASSWORD || 'DemoPass123!';
const route = (r, extra = '') => `/index.php?r=${encodeURIComponent(r)}${extra}`;
async function login(page, user) {
    await page.goto(route('/login'));
    await page.getByLabel('Username or email').fill(user);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Sign in to your workspace' }).click();
    await expect(page.getByRole('heading', { name: /Hello,/ })).toBeVisible();
}
async function noOverflow(page) {
    if (page.viewportSize().width <= 850)
        await expect
            .poll(() =>
                page
                    .locator('#sidebar')
                    .evaluate((el) => Math.round(el.getBoundingClientRect().right)),
            )
            .toBeLessThanOrEqual(0);
    expect(
        await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1),
    ).toBeTruthy();
}
test('ID-07: login, role navigation, logout and expired CSRF', async ({ page }) => {
    await login(page, 'student');
    await expect(page.getByRole('link', { name: 'People & access' })).toHaveCount(0);
    await page.goto(route('/admin/users'));
    await expect(page.getByText('You do not have permission for this action.')).toBeVisible();
    const res = await page.request.post(route('/exams/register'), {
        form: { _csrf: 'forged', exam_id: 1 },
    });
    expect(res.status()).toBe(419);
    await page.getByRole('button', { name: 'Sign out' }).click();
    await page.goto(route('/'));
    await expect(page.getByRole('heading', { name: 'Welcome back.' })).toBeVisible();
});
test('Responsive student overview, exams, profile and results', async ({ page }) => {
    await login(page, 'student');
    fs.mkdirSync('tests/artifacts/screenshots', { recursive: true });
    for (const [width, height] of [
        [1440, 1000],
        [768, 1024],
        [390, 844],
        [320, 740],
    ]) {
        await page.setViewportSize({ width, height });
        for (const screen of ['/', '/exams', '/profile', '/results']) {
            await page.goto(route(screen));
            await noOverflow(page);
            await expect(page.locator('main h1')).toBeVisible();
        }
        await page.goto(route('/'));
        await page.screenshot({
            path: `tests/artifacts/screenshots/student-${width}.png`,
            fullPage: true,
            animations: 'disabled',
        });
        if (width <= 850) {
            await page.getByRole('button', { name: 'Toggle navigation' }).click();
            await expect(page.locator('#sidebar')).toHaveClass(/open/);
            await page.locator('#sidebar').getByRole('link', { name: 'Examinations' }).click();
            await expect(
                page.getByRole('heading', { name: 'Examinations', exact: true }),
            ).toBeVisible();
        }
    }
});
test('Administrator creates and edits records; tables fit mobile container', async ({ page }) => {
    await login(page, 'admin');
    await page.goto(route('/admin/departments'));
    const name = `Browser department ${Date.now()}`;
    await page.getByLabel('Department name', { exact: true }).fill(name);
    await page.getByRole('button', { name: 'Add department', exact: false }).click();
    await expect(page.getByRole('heading', { name, exact: true })).toBeVisible();
    await page.goto(route('/admin/users'));
    await page.getByLabel('Full name', { exact: true }).fill('Browser Sample Student');
    const id = `browser.${Date.now()}`;
    await page.getByLabel('Username', { exact: true }).fill(id);
    await page.getByLabel('Email', { exact: true }).fill(`${id}@example.test`);
    await page.getByRole('combobox', { name: 'Role', exact: true }).selectOption('student');
    await page
        .getByRole('combobox', { name: 'Department', exact: true })
        .selectOption({ label: name });
    await page.getByLabel('Initial password').fill(password);
    await page.getByRole('button', { name: 'Create account', exact: true }).click();
    await expect(page.getByText(`${id}@example.test`, { exact: true })).toBeVisible();
    await page.setViewportSize({ width: 390, height: 844 });
    await noOverflow(page);
    await page.screenshot({
        path: 'tests/artifacts/screenshots/admin-mobile.png',
        fullPage: true,
        animations: 'disabled',
    });
    await page.goto(route('/exams/edit'));
    await page.getByLabel('Examination title').fill(`Browser assigned exam ${Date.now()}`);
    await page
        .getByRole('combobox', { name: 'Department', exact: true })
        .selectOption({ label: 'Computer Applications' });
    const responsible = page.getByRole('combobox', { name: 'Responsible instructor', exact: true });
    await responsible.selectOption({ label: 'Dr. Meera Rao · Computer Applications' });
    const localDate = (d) => new Date(d.getTime() + 330 * 60000).toISOString().slice(0, 16);
    await page.getByLabel('Opens at').fill(localDate(new Date(Date.now() - 3600000)));
    await page.getByLabel('Closes at').fill(localDate(new Date(Date.now() + 86400000)));
    await page.getByRole('button', { name: 'Save examination' }).click();
    await expect(page.getByText('Prepared by Dr. Meera Rao')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Add question' })).toHaveCount(0);
});
test('Instructor creates assessment; student saves, submits and sees exact result', async ({
    browser,
}) => {
    const teacherContext = await browser.newContext();
    const teacher = await teacherContext.newPage();
    await login(teacher, 'instructor');
    await teacher.goto(route('/exams/edit'));
    const title = `Browser assessment ${Date.now()}`;
    await teacher.getByLabel('Examination title').fill(title);
    // Inputs are interpreted in the configured Asia/Kolkata zone.
    const localInput = (d) => new Date(d.getTime() + 330 * 60000).toISOString().slice(0, 16);
    await teacher.getByLabel('Opens at').fill(localInput(new Date(Date.now() - 3600000)));
    await teacher.getByLabel('Closes at').fill(localInput(new Date(Date.now() + 86400000)));
    await teacher.getByLabel('Duration in minutes').fill('30');
    await teacher.getByRole('button', { name: 'Save examination' }).click();
    const examId = new URL(teacher.url()).searchParams.get('id');
    await teacher.getByRole('link', { name: 'Add question' }).click();
    await teacher.getByLabel('Question text').fill('The browser test question is true.');
    await teacher.getByLabel('Question type').selectOption('TF');
    await teacher.getByLabel('Marks', { exact: true }).fill('5');
    await teacher.getByRole('button', { name: 'Save question' }).click();
    await expect(teacher.getByText('✓ Correct answer', { exact: true })).toBeVisible();
    const studentContext = await browser.newContext();
    const student = await studentContext.newPage();
    await login(student, 'student');
    await student.goto(route('/exams/show', `&id=${examId}`));
    await student.getByRole('button', { name: 'Register for examination' }).click();
    student.on('dialog', (dialog) => dialog.accept());
    await student.getByRole('button', { name: 'Start examination' }).click();
    await expect(student.locator('#exam-timer')).not.toHaveText('Loading…');
    expect(await student.content()).not.toContain('is_correct');
    await student.getByRole('radio', { name: 'True', exact: true }).check();
    await expect(
        student.getByText('All current answers are saved.', { exact: false }),
    ).toBeVisible();
    await student.reload();
    await expect(student.getByRole('radio', { name: 'True', exact: true })).toBeChecked();
    await student.setViewportSize({ width: 390, height: 844 });
    await noOverflow(student);
    await student.screenshot({
        path: 'tests/artifacts/screenshots/attempt-mobile.png',
        fullPage: true,
        animations: 'disabled',
    });
    await student.getByRole('button', { name: 'Submit examination' }).click();
    await expect(student.locator('.score-display')).toContainText('5.00');
    await expect(student.locator('.score-caption')).toHaveText('100% of available marks');
    await student.screenshot({
        path: 'tests/artifacts/screenshots/result-mobile.png',
        fullPage: true,
        animations: 'disabled',
    });
    await teacher.goto(route('/exams/edit', `&id=${examId}`));
    await expect(
        teacher.getByText('This examination is locked after its first attempt.'),
    ).toBeVisible();
    await teacher.goto(route('/reports', `&id=${examId}`));
    await expect(teacher.getByText('Aarav Mehta', { exact: true })).toBeVisible();
    await teacher.screenshot({
        path: 'tests/artifacts/screenshots/instructor-report.png',
        fullPage: true,
        animations: 'disabled',
    });
    await teacherContext.close();
    await studentContext.close();
});
test('SF-08: failed autosave is not represented as saved; retry recovers', async ({ page }) => {
    await login(page, 'student2');
    await page.goto(route('/exams'));
    const card = page
        .locator('.exam-card')
        .filter({ has: page.getByRole('heading', { name: 'Database Fundamentals', exact: true }) });
    await card.getByRole('link').click();
    if (await page.getByRole('button', { name: 'Register for examination' }).count())
        await page.getByRole('button', { name: 'Register for examination' }).click();
    page.on('dialog', (d) => d.accept());
    if (await page.getByRole('button', { name: 'Start examination' }).count())
        await page.getByRole('button', { name: 'Start examination' }).click();
    else await page.getByRole('link', { name: 'Resume your attempt' }).click();
    await page.route('**/index.php?r=%2Fapi%2Fanswers', (route) => route.abort());
    await page.locator('.answer-option input:not(:checked)').first().check();
    await expect(page.locator('#save-status')).toHaveClass(/error/);
    await expect(page.locator('#save-status')).toContainText('may not be saved');
    await page.unroute('**/index.php?r=%2Fapi%2Fanswers');
    await page.getByRole('button', { name: 'Retry saving' }).click();
    await expect(page.locator('#save-status')).toContainText('All current answers are saved');
});
