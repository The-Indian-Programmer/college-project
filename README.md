# Online Examination System

A complete, responsive PHP/MySQL application based on `../output/pdf/Online-Examination-System-Detailed-Report-64-Pages.pdf`. The interface is branded **Examina**. No frontend build step or PHP framework is required.

For local startup, production deployment, server commands, cron, backups, and troubleshooting, see the [complete running and deployment guide](docs/RUNNING-AND-DEPLOYMENT.md).

## Included modules

- **Identity:** student registration, login, inactive-account checks, logout, password changes, single-use email recovery and request throttling.
- **Administrator:** department maintenance, student/instructor/admin account creation, profile/status changes, reference-safe deletion and examination creation with a responsible instructor.
- **Instructor:** examination schedules, MCQ/true-false questions, options, answer keys, marks and participation reports. Editing locks after the first attempt.
- **Student:** department-scoped examinations, explicit registration, one timed attempt, answer autosave, retry feedback, final submission and private results.
- **Evaluation:** exact integer-cent scoring, no negative marks, deadline enforcement, transactional finalization and idempotent submission.
- **Reporting:** distinct registered/attended/completed counts and authorized result views.

The layouts adapt to phone, tablet and desktop widths. Tables scroll within their own containers. Forms are labeled, keyboard focus is visible, and answer-save failures are announced without a false success message.

## Requirements

- PHP **8.2 or newer**, with `pdo_mysql`, `mbstring`, `session` and `openssl`.
- MySQL **8.0.16+** or MariaDB **10.6+**, using InnoDB. Execution was verified on the version recorded in `docs/verification.md`; other supported targets still require an environment smoke test.
- A modern browser. Apache/XAMPP or PHP's local development server.
- Node.js is needed **only** for optional Playwright browser tests.

## Quick start with XAMPP

1. Copy this `project` directory to a convenient local directory.
2. Start the database service in XAMPP.
3. Copy `.env.example` to `.env`. Set the real database host, port, database name, user and password. The example currently follows the local XAMPP-style configuration (`root` with an empty password); replace these values for your installation.
4. Create `online_examination_system` in phpMyAdmin, with `utf8mb4_unicode_ci` collation. Alternatively, execute `database/create_database.sql` as a database administrator after reviewing its account configuration. That script does not overwrite an existing root password.
5. Open a terminal in `project` and run:

```sh
php bin/migrate.php
php bin/seed.php
php bin/check.php
php -S 127.0.0.1:8080 -t public
```

On Windows, use `C:\xampp\php\php.exe` if PHP is not on PATH. Then open **http://127.0.0.1:8080**.

### Apache / XAMPP document root

Point the site's `DocumentRoot` to **`project/public`**, not the project root. Only that directory contains public files. For example:

```apache
<VirtualHost *:80>
    ServerName exams.local
    DocumentRoot "C:/xampp/htdocs/examination/project/public"
    <Directory "C:/xampp/htdocs/examination/project/public">
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>
</VirtualHost>
```

Add the matching local host entry if using a custom hostname. Set `APP_URL` to the actual URL, including a subdirectory if applicable. Routes use `index.php?r=...`, so URL rewriting is unnecessary. `.htaccess` denies accidental access to the project root and explicitly permits `public/`.

### Configuration on the development computer

The configured application uses localhost port **3307** for an isolated MySQL instance, and port **8080** for the application. It does not use a pre-existing database service. `.env` and `.env.test` are local, ignored files. Change `DB_PORT` to the port of your own XAMPP/MySQL installation when copying the project.

## Demo accounts

Set `SEED_PASSWORD` before seeding if you want a different initial password. Otherwise new seeded accounts use **`DemoPass123!`**.

| Username | Role | Department |
| --- | --- | --- |
| `admin` | Administrator | Institution-wide |
| `instructor` | Instructor | Computer Applications |
| `instructor2` | Instructor | Business Studies |
| `student` | Student | Computer Applications |
| `student2` | Student | Computer Applications |
| `student3` | Student | Computer Applications |
| `business.student` | Student | Business Studies |
| `inactive.student` | Inactive student | Computer Applications |

The seeder creates three roles, two departments, eight accounts, five examinations, mixed MCQ/true-false items and example completed participation. It includes live, upcoming and completed exams. Schedules are relative to the **first** seed run; re-running does not move an existing exam or overwrite its questions, accounts or passwords. Inactive users intentionally cannot sign in. Seeding is disabled when `APP_ENV=production`.

`database/Seeder.php` contains the fixture definitions. `bin/seed.php` is the safe CLI entry point. The `roles` table is seeded by both initial migration and the seeder; public registration always stores `student`, regardless of a submitted role value.

## Demonstrating the project

1. Sign in as `instructor`, create an examination in an open window and add valid questions.
2. Sign out. Sign in as `student`, open that exam and register.
3. Start your one permitted attempt, select answers and wait for save confirmation.
4. Submit and inspect the persisted marks. A repeated submission returns the completed state.
5. Sign in as the instructor and review registered, attended and completed counts.
6. Use `admin` for user/department management or to create an exam on behalf of an active instructor in that department.

A student may register before opening. Starting is allowed at opening but not at closing. Effective deadline is `min(start + duration, exam closing)`. A late response cannot change accepted answers. Expired attempts are finalized when accessed; for unattended finalization, schedule:

```sh
php bin/expire.php
```

Run this command every minute using cron or Windows Task Scheduler if desired. It also finalizes expired attempts for inactive accounts. It never accepts late answer changes.

## Password recovery

- **Local:** `MAIL_DRIVER=log` writes recovery messages under `storage/mail/`, outside the document root. Open the latest message locally to follow its reset link. The web page never exposes the token. These files contain recovery links and should remain private.
- **Mail delivery:** set `MAIL_DRIVER=mail` and a valid `MAIL_FROM`; configure PHP's mail transport in the actual server/XAMPP environment. An external mail service is not bundled. SMTP credentials are not hard-coded.
- `RESET_TTL_MINUTES` defaults to 30. Tokens are stored as SHA-256 hashes, expire, can be used once, and are invalidated together with a successful reset. Password changes/resets revoke existing authenticated sessions.
- `MAIL_DRIVER=log` is deliberately unavailable when `APP_ENV=production`.

## Database

- `database/schema.sql`: complete schema, keys, unique constraints, relationships and initial role data.
- `src/Migrator.php`: non-destructive initial setup and the responsible-instructor extension.
- `database/create_database.sql`: local database/account setup example.
- `docs/database.md`: table dictionary, configuration and integrity decisions.

Migration is repeatable and preserves records. It does not delete data, reset passwords or refresh seeded exam dates. Application times are stored in UTC and displayed in `APP_TIMEZONE` (default `Asia/Kolkata`). Login names and email addresses are case-insensitively unique under the selected collation.

Use a dedicated application database account for a deployed installation. Runtime needs SELECT/INSERT/UPDATE/DELETE; migration additionally needs CREATE/ALTER/INDEX/REFERENCES. Integration tests need their own database account with schema/trigger privileges. Do not expose the development server to an untrusted network.

## Module-wise tests

The complete 41-scenario report catalogue is provided in:

- `docs/module-test-cases.md` — readable module-wise cases.
- `docs/module-test-cases.csv` — editable execution worksheet.
- `tests/run.php` — automated unit, service, database, failure-injection and concurrency tests.
- `tests/browser/application.spec.js` — browser workflows and responsive checks.
- `docs/verification.md` — what was actually executed and remaining acceptance work.

### Unit tests

```sh
php tests/run.php --unit
```

### Integration tests

Create an isolated database whose name **ends with `_test`**, for example `online_examination_system_test`. Copy `.env` to `.env.test` and change only the database name and any test-specific connection credentials. Run:

```sh
ENV_FILE=.env.test php tests/run.php --integration
```

PowerShell:

```powershell
$env:ENV_FILE = '.env.test'
php tests/run.php --integration
Remove-Item Env:ENV_FILE
```

The suite **resets only the explicitly configured `_test` database** and refuses any other database name. It seeds its fixtures, checks invariants and writes `tests/artifacts/results.json`. The simultaneous-start case uses `pcntl_fork` when available; on Windows run that case on a PHP environment with PCNTL. It is not silently claimed as executed when the extension is absent.

### Browser tests

```sh
npm install
npm run test:browser
```

For an isolated browser run after integration setup:

```sh
ENV_FILE=.env.test php -S 127.0.0.1:8081 -t public
# In a second terminal:
BASE_URL=http://127.0.0.1:8081 npm run test:browser
```

A local Chrome installation is used by default. Start the app before running the tests. `BASE_URL` can point to a separate test instance. The browser suite creates clearly named browser fixture records and modifies demo attempts; use an isolated seeded test database for repeatable acceptance runs. To use Playwright's bundled Chromium instead, install it with `npx playwright install chromium` and change/remove `channel` in `playwright.config.js`.

Desktop (1440), tablet (768), and phone (390 and 320 pixel) layouts are checked for page-level overflow. Screenshots, traces on failure and JSON reports are placed in `tests/artifacts/`.

## Project layout

```text
project/
  config/bootstrap.php       Configuration and autoloading
  src/                       Domain services, PDO access, routing, validation
  public/index.php           Web entry point (only public document root)
  public/assets/             Responsive CSS and browser interaction
  views/                     Escaped, server-rendered screens
  database/                  Schema, role/user/question fixtures, setup SQL
  bin/                       Migrate, seed, environment check, expiry worker
  tests/                     Executable PHP and browser tests
  docs/                      Test catalogue, design alignment, verification
  storage/mail/              Private local recovery messages
```

## Source alignment and implementation choices

See `docs/source-alignment.md`. The report left administrator-created exam delegation unresolved. The implementation resolves that gap with nullable `exams.assigned_to`, an FK to the responsible instructor, while preserving `created_by` as the creator. No extra application role is introduced. This is an explicit implementation refinement; the provided PDF itself has not been rewritten.

See `docs/references.md` for the primary technical documentation consulted. No executed-test claim, mail-delivery claim or concurrent-user capacity claim is made without the corresponding verification record.
