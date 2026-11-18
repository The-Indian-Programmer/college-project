# Alignment with the 64-page report

Primary project reference: `../../output/pdf/Online-Examination-System-Detailed-Report-64-Pages.pdf`.

| Report area | Implementation |
| --- | --- |
| Pages 13–18: FR-01…FR-09, permissions and rules | AuthService, AdminService, ExamService, AttemptService and protected App routes. |
| Pages 19–21: PHP/MySQL, HTML/CSS/JavaScript, Windows/XAMPP | Framework-free PHP, PDO, InnoDB, responsive server-rendered views. No runtime Node dependency. |
| Pages 22–28: DFDs and ER relationships | Separate identity, maintenance, configuration, participation and reporting responsibilities; registration and response junction tables. |
| Pages 31–38: dictionary, concurrency and recovery | schema.sql, non-destructive Migrator, transaction boundaries and single-use recovery tokens. |
| Pages 39–48: module procedures and interface | Three role workspaces; timed answer form; saved/pending/error feedback; private results. |
| Pages 49–51: plan and risks | Implementation is delivered; the original relative schedule is not represented as historical execution evidence. |
| Pages 52–59: tests and acceptance | Executable PHP and Playwright suites, CSV/Markdown module test catalogue and recorded execution results. |
| Pages 60–64: deployment, maintenance and limits | Setup instructions, expiry CLI, private recovery delivery, backup procedure and explicit verification limits. |

## Explicit refinements

1. **Responsible instructor:** the report deliberately leaves delegation of administrator-created exams unresolved. `exams.assigned_to` resolves this: the administrator selects a same-department instructor at creation, `created_by` retains the actual creator, and instructor ownership checks use assigned_to with a created_by fallback. Admins do not gain question-edit rights.
2. **Role data:** a three-row `roles` lookup provides the requested role seeder without changing the three-role scope. `users.role` retains the documented string values.
3. **Session revocation and throttling:** `auth_version` and `auth_throttles` support protected identity workflows; they are implementation infrastructure rather than new user modules.
4. **Registration:** students explicitly register for an exam before starting. Public account registration always creates a student. Only an administrator can provision staff or administrative access.
5. **Expiry:** deadline checks occur on answer save, submission and attempt/result access. `bin/expire.php` supports periodic unattended finalization. Unsent browser answers cannot be recovered after a connection failure or accepted late.
6. **Local email:** recovery messages can be inspected in a private file outbox in local mode. Actual email delivery requires configuring PHP's mail transport; it is not simulated as successfully delivered to an external account.
7. **Testable bounds:** MCQ has 2–6 options, duration is 1–1440 minutes, and password length is 10–72 bytes. Positive two-decimal item marks fit the report's DECIMAL(6,2) column.
8. **No added assessment scope:** no proctoring, essay grading, repeated attempts, negative marking, rankings, or student-detail search module is introduced. The small exam-title filter affects only displayed examination cards.

The report's old hardware values are not presented as a capacity recommendation for the deployed runtime. All schedules, credentials and sample identities in the seeder are demonstration data. Browser-generated records and known-answer fixtures are test data, not real assessment results.
