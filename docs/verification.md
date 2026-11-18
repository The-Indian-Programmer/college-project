# Verification record

Verified on 2026-09-16T10:38:29+00:00 (backend) and 2026-09-16T10:43:38.889Z (browser).

## Executed checks

| Check | Actual result |
| --- | --- |
| PHP syntax validation | All project PHP files passed `php -l`. |
| Browser JavaScript syntax | `node --check public/assets/app.js` passed. |
| Backend tests | **34 passed, 0 failed**. Includes unit, service/database, rollback injection and simultaneous starts. |
| Browser workflows | **5 passed, 0 failed**, no skipped or flaky cases in the final run. |
| Responsive layout | Chrome viewports 320×740, 390×844, 768×1024 and 1440×1000 passed page-level overflow checks. |
| Visual inspection | Desktop/student overview and mobile administration, attempt and result screenshots inspected. |
| Database migration and seeding | Applied, rerun safely, and environment check passed. |

Environment: PHP **8.5.10**, MySQL **26.7.0**, local Google Chrome through Playwright. Tests used a separate `_test` database. The final browser instance used port 8081; the clean seeded demonstration remains on port 8080.

### Browser scenarios

1. Login, student authorization, invalid CSRF rejection, logout and protected-page behavior.
2. Responsive overview, examination list, profile and results; mobile navigation.
3. Administrator department/account creation and instructor assignment for an administrator-created examination.
4. Instructor exam/question creation, student registration/start, autosave, reload persistence, final result, edit lock and participation view.
5. Aborted answer save does not show success; retry confirms server persistence.

### Backend execution details

| ID | Executed case | Result |
| --- | --- | --- |
| UNIT-01 | Exact decimal scoring values | PASS |
| UNIT-02 | Deadline is clamped to exam close | PASS |
| UNIT-03 | Question validation and single key | PASS |
| UNIT-04 | Markup is escaped | PASS |
| UNIT-05 | Identifiers and lengths are validated | PASS |
| UNIT-06 | Server rejects malformed and impossible dates | PASS |
| UNIT-07 | CSRF requires constant session token | PASS |
| ID-01/04 | Public registration always creates student with a hash | PASS |
| ID-02 | Duplicate and blank registrations rejected | PASS |
| ID-03 | Active credentials only; injection is data | PASS |
| ID-05 | Password change verifies current password | PASS |
| ID-06 | Recovery is single use and rejects expired tokens | PASS |
| AD-01/02 | Department maintenance validates duplicates | PASS |
| AD-03/04 | Referenced records and unauthorized maintenance protected | PASS |
| EX-01/02 | Exam creation and schedule boundaries | PASS |
| EX-03 | Other instructor cannot revise an examination | PASS |
| EX-04 | Empty examination cannot be started | PASS |
| EX-ADMIN | Admin-created exams have an explicit responsible instructor | PASS |
| QU-01 | Questions and options can be created and updated | PASS |
| QU-02/03/04 | Invalid keys, TF options and marks are rejected | PASS |
| QU-05 | Student question projection never contains answer key | PASS |
| ST-01 | Registered student starts one timed attempt | PASS |
| EX-05/QU-06 | First attempt freezes schedule and questions | PASS |
| ST-04 | Foreign questions and options fail without partial writes | PASS |
| ST-06/07 | Atomic scoring yields 2/10 and repeated submission is idempotent | PASS |
| ST-05/RP-02 | Completed attempts cannot restart and results are private | PASS |
| ST-02 | Opening inclusive and closing exclusive | PASS |
| ST-03/08 | Deadline rejects new answers and grades previously saved values | PASS |
| RP-01 | Attendance counts are unaffected by response multiplicity | PASS |
| SC-01 | Stored markup is rendered as text | PASS |
| SF-03 | Failure during finalization rolls back result and completion | PASS |
| SF-07 | Recovery failure rolls back password and token together | PASS |
| SEED-01 | Seeder reruns preserve existing data and question counts | PASS |
| SF-01 | Concurrent starts create only one attempt | PASS |

## Evidence

- `tests/artifacts/results.json`: backend execution time, platform versions and individual outcomes.
- `tests/artifacts/browser-results.json`: final Playwright outcomes and timing.
- `tests/artifacts/screenshots/`: responsive review images. These contain test/demo records only.
- `docs/module-test-cases.csv`: reusable 41-case acceptance worksheet; it is not populated with invented results.

## Limits and remaining acceptance work

The executed checks are meaningful samples, not proof against every input or every device. In particular, the 41-case catalogue is broader than the automated suite and should not be read as 41 fully executed independent acceptance cases.

- Real external email delivery was not exercised. Local file delivery is implemented; configure PHP mail transport and verify delivery in the target installation.
- Actual XAMPP/Windows, MariaDB, Safari, Firefox and physical mobile devices were not executed in this environment.
- Concurrent attempt opening and transaction rollback were executed. The dedicated save-versus-submit race (SF-02), extreme aggregate-mark capacity fixture (SF-06), prolonged load, and network behavior beyond the simulated save failure remain acceptance work.
- No concurrent-student capacity, availability percentage, or production security certification is claimed.
- Database restoration and institution-specific backup retention must be verified before operational use.

Failures discovered during development were corrected before the final passing runs: browser selectors and repeat-run fixture assertions, a relative alternate-environment path under the PHP server, and viewport-transition screenshot timing. The current code initializes the exam timer immediately, and final screenshot checks wait for mobile navigation to settle.
