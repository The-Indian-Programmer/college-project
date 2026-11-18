# Running and Deploying the Online Examination System

This guide covers development, database setup, production deployment, scheduled examination expiry, updates, and troubleshooting.

The application uses PHP and MySQL. It has no frontend build step, framework server, or Composer dependencies. Node.js is required only for optional browser tests. There are no `npm run dev`, `npm run build`, or `npm start` commands in this project.

Commands run from the `project` directory unless otherwise stated. Replace example domains, paths, usernames, and passwords before using production examples.

## Contents

1. [Requirements](#1-requirements)
2. [Run the existing local installation](#2-run-the-existing-local-installation)
3. [Fresh local installation](#3-fresh-local-installation)
4. [Demo data and accounts](#4-demo-data-and-accounts)
5. [Configuration reference](#5-configuration-reference)
6. [Production deployment overview](#6-production-deployment-overview)
7. [Production database and first administrator](#7-production-database-and-first-administrator)
8. [Deploy on Apache with Ubuntu or Debian](#8-deploy-on-apache-with-ubuntu-or-debian)
9. [Deploy on shared hosting or cPanel](#9-deploy-on-shared-hosting-or-cpanel)
10. [Automatic examination expiry](#10-automatic-examination-expiry)
11. [Updates, backups, and rollback](#11-updates-backups-and-rollback)
12. [Tests and deployment verification](#12-tests-and-deployment-verification)
13. [Troubleshooting](#13-troubleshooting)
14. [Command reference](#14-command-reference)
15. [Related documentation](#15-related-documentation)

## 1. Requirements

- PHP 8.2 or newer, with `pdo_mysql`, `mbstring`, `session`, and `openssl`.
- MySQL 8.0.16+ or MariaDB 10.6+, using InnoDB and UTF-8.
- A modern web browser.
- For production: Apache or Nginx with PHP execution configured, a domain, and HTTPS.
- For browser tests only: Node.js, npm, and Chrome, or the configured Playwright browser.

Check PHP:

```bash
php -v
php -m
```

The actual verification environment and untested targets are recorded in [verification.md](verification.md). Windows/XAMPP and MariaDB should receive an environment smoke test before use.

## 2. Run the existing local installation

On the development Mac:

```bash
cd /Users/tnvlei/Documents/tnv-projects/tnvlou/docs/project
php bin/check.php
php -S 127.0.0.1:8080 -t public
```

Open **http://127.0.0.1:8080**. Keep the terminal open; press **Ctrl+C** to stop the development server. If that port already serves this application, open the URL instead of starting another instance.

The existing `.env` uses an isolated MySQL instance on **port 3307**. The application server does not start MySQL. This database instance was started separately and is not guaranteed to survive a restart of the computer.

If the previously initialized local database directory still exists, it can be restarted in a separate terminal with:

```bash
/opt/homebrew/bin/mysqld \
  --datadir=/private/tmp/examina-mysql-runtime \
  --socket=/private/tmp/examina-mysql.sock \
  --port=3307 \
  --bind-address=127.0.0.1 \
  --mysqlx=OFF
```

Run that only if the instance is stopped. `/private/tmp` is temporary storage, so use a persistent MySQL installation and backups for ongoing development. If that directory is missing, follow the fresh installation instructions instead; do not initialize over an existing database directory.

If `php` is not on PATH on this Mac, substitute `/opt/homebrew/bin/php` in the commands.

## 3. Fresh local installation

### 3.1 Start MySQL

Start MySQL using XAMPP, your database service manager, or your installed MySQL service. Confirm its host, port, username, and password. Most local installations use port 3306; this project's existing Mac environment uses 3307.

### 3.2 Create the environment file

For a fresh checkout that has no `.env`:

```bash
cp .env.example .env
```

Do not overwrite an existing configured `.env`. Edit the file to match your installation:

```dotenv
APP_NAME="Online Examination System"
APP_ENV=local
APP_URL=http://127.0.0.1:8080
APP_TIMEZONE=Asia/Kolkata
SESSION_SECURE=false
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=online_examination_system
DB_USERNAME=root
DB_PASSWORD=
MAIL_DRIVER=log
MAIL_FROM=no-reply@example.test
RESET_TTL_MINUTES=30
SEED_PASSWORD=DemoPass123!
```

The blank root password is only an example for a local XAMPP installation. Use the actual credentials of your database installation.

### 3.3 Create the database

In phpMyAdmin's SQL tab, or a MySQL administrator console, run:

```sql
CREATE DATABASE IF NOT EXISTS online_examination_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

The migration command creates tables inside the configured database; it does not create the database itself.

### 3.4 Create tables, seed data, and start

```bash
php bin/migrate.php
php bin/seed.php
php bin/check.php
php -S 127.0.0.1:8080 -t public
```

Open **http://127.0.0.1:8080**.

### 3.5 Windows/XAMPP

Start MySQL from the XAMPP control panel. In PowerShell, enter your project directory and use the full PHP executable path if necessary:

```powershell
cd C:\path\to\project
Copy-Item .env.example .env
& 'C:\xampp\php\php.exe' bin/migrate.php
& 'C:\xampp\php\php.exe' bin/seed.php
& 'C:\xampp\php\php.exe' bin/check.php
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8080 -t public
```

Copy `.env.example` only once and configure `.env` and the database before running migration. Apache is not required when using the PHP development server. If using XAMPP Apache instead, its document root must point to `project/public`.

## 4. Demo data and accounts

```bash
php bin/seed.php
```

The seeder creates three roles, two departments, eight accounts, five examinations, objective questions, and sample participation/results.

| Username | Role |
| --- | --- |
| `admin` | Administrator |
| `instructor` | Computer Applications instructor |
| `instructor2` | Business Studies instructor |
| `student`, `student2`, `student3` | Computer Applications students |
| `business.student` | Business Studies student |
| `inactive.student` | Inactive student; sign-in is denied |

Default password for newly seeded accounts: **`DemoPass123!`**.

Set `SEED_PASSWORD` before the first seed to use a different password. Re-running the seeder preserves existing passwords, questions, and examination dates. Exam windows are relative to the first seed run; create or edit an eligible exam in the instructor interface if the demo window has passed.

The sample-data seeder is disabled when `APP_ENV=production`. Migration creates the role records without creating demo users.

## 5. Configuration reference

| Variable | Purpose |
| --- | --- |
| `APP_NAME` | Application name |
| `APP_ENV` | `local` for development; `production` for deployed use |
| `APP_URL` | Actual website base URL, including any deployment subdirectory |
| `APP_TIMEZONE` | Display timezone; stored application times use UTC |
| `SESSION_SECURE` | `true` for HTTPS production cookies; `false` for local HTTP |
| `DB_HOST`, `DB_PORT` | Database connection endpoint |
| `DB_DATABASE` | Database name |
| `DB_USERNAME`, `DB_PASSWORD` | Database credentials |
| `MAIL_DRIVER` | `log` locally; `mail` for configured server mail transport |
| `MAIL_FROM` | Valid sender address for outgoing recovery messages |
| `RESET_TTL_MINUTES` | Password reset validity; default 30 minutes |
| `SEED_PASSWORD` | Initial password for newly created demo accounts |
| `ENV_FILE` | Optional process environment variable selecting another configuration file |

Process environment variables override file values. Keep `.env` private and outside the document root.

With `MAIL_DRIVER=log`, local recovery messages are saved in `storage/mail`. In production, use `MAIL_DRIVER=mail` and configure PHP's mail transport through the server or hosting provider. This application does not bundle an SMTP client or an SMTP credential configuration. Production disables the log mail driver.

## 6. Production deployment overview

1. Provision PHP, its required extensions, a database, and a PHP-capable web server.
2. Upload the application, for example to `/var/www/examina`.
3. Point the website document root to **`/var/www/examina/public`**.
4. Create a production database and dedicated database user.
5. Create the production `.env` on the server.
6. Configure domain DNS, HTTPS, and the server's mail transport.
7. Run migration and the environment check.
8. Provision the first administrator and verify the main workflows.
9. Configure examination expiry and database backups.

Upload the runtime directories `bin`, `config`, `database`, `public`, `src`, `views`, and `storage`. Do not upload local `.env` files, local recovery messages, `node_modules`, test artifacts, or development databases. Retain the documentation privately for operators.

Example production `.env`:

```dotenv
APP_NAME="Online Examination System"
APP_ENV=production
APP_URL=https://exams.yourdomain.com
APP_TIMEZONE=Asia/Kolkata
SESSION_SECURE=true
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=online_examination_system
DB_USERNAME=examina_app
DB_PASSWORD=REPLACE_WITH_STRONG_DATABASE_PASSWORD
MAIL_DRIVER=mail
MAIL_FROM=no-reply@yourdomain.com
RESET_TTL_MINUTES=30
```

Use the database hostname assigned by your provider if it is not on the same server. Enable HTTPS before signing in with secure cookies.

PHP's built-in `php -S` server is for development, not production: [PHP manual](https://www.php.net/features.commandline.webserver.php).

## 7. Production database and first administrator

### 7.1 Create the database and accounts

For a database on the same server, run the following in an administrator MySQL session, replacing both example passwords:

```sql
CREATE DATABASE IF NOT EXISTS online_examination_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'examina_app'@'localhost'
  IDENTIFIED BY 'REPLACE_WITH_RUNTIME_PASSWORD';
GRANT SELECT, INSERT, UPDATE, DELETE
  ON online_examination_system.* TO 'examina_app'@'localhost';

CREATE USER 'examina_migrate'@'localhost'
  IDENTIFIED BY 'REPLACE_WITH_MIGRATION_PASSWORD';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES
  ON online_examination_system.* TO 'examina_migrate'@'localhost';
```

Use the host/account configuration appropriate to your database provider. On shared hosting, create the database and user through its control panel instead. Do not use the local root-account example in `database/create_database.sql` for production.

Keep runtime credentials in `.env`. For migration, create a private `.env.migrate` containing the same configuration with `DB_USERNAME` and `DB_PASSWORD` replaced by the migration account. Do not commit or publish either file.

```bash
cd /var/www/examina
ENV_FILE=.env.migrate php bin/migrate.php
php bin/check.php
```

If your hosting account uses a single database user with migration permissions, `php bin/migrate.php` can use `.env` directly. Keep database access restricted to the application database.

### 7.2 Provision the first administrator

The project has no production administrator-creation CLI. On a fresh database, use this controlled setup sequence before public launch:

1. Add your institution's first department through the database console:

   ```sql
   INSERT INTO departments (department_name)
   VALUES ('Computer Applications');
   ```

2. Open the application registration page and register your own account using that department, your real email address, and a strong password. Registration initially creates a student account and hashes the password.
3. In the database console, promote that exact account:

   ```sql
   UPDATE users
   SET role = 'admin',
       department_id = NULL,
       auth_version = auth_version + 1
   WHERE username = 'your_registered_username'
     AND email = 'your_real_email@example.com';
   ```

4. Confirm exactly one intended account was updated, then sign in again. Use administration to add departments, instructors, and other accounts.

Do not start examinations with this setup account before promoting it. Do not enable demo seeding by temporarily changing a production installation to local mode.

## 8. Deploy on Apache with Ubuntu or Debian

This example assumes Apache 2.4 and PHP execution are already installed and configured, either through an Apache PHP module or PHP-FPM integration. A static-only Apache installation is insufficient. Package names and PHP-FPM service names depend on the server's operating system and PHP version.

### 8.1 Configure the document root

For an example installation at `/var/www/examina`, create `/etc/apache2/sites-available/examina.conf`:

```apache
<VirtualHost *:80>
    ServerName exams.yourdomain.com
    DocumentRoot /var/www/examina/public

    <Directory /var/www/examina/public>
        AllowOverride All
        Require all granted
        Options -Indexes
        DirectoryIndex index.php
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/examina-error.log
    CustomLog ${APACHE_LOG_DIR}/examina-access.log combined
</VirtualHost>
```

Enable and check it:

```bash
sudo a2ensite examina.conf
sudo apache2ctl configtest
sudo systemctl enable --now apache2
sudo systemctl reload apache2
```

This is the HTTP bootstrap configuration. Configure a valid TLS certificate and an HTTPS virtual host with the same document root using your hosting provider or certificate tooling. Redirect HTTP to HTTPS before public launch. The exact certificate command depends on your provider and DNS setup.

The application uses `index.php?r=...` routes, so URL rewriting is not required. Apache configuration testing and reload controls are documented in the [Apache manual](https://httpd.apache.org/docs/2.4/programs/apachectl.html).

### 8.2 File permissions

Keep application code owned by the deployment account and readable by the PHP service. Only the required storage directories should be writable by PHP. Example for a server whose PHP service group is `www-data`:

```bash
cd /var/www/examina
sudo chgrp www-data .env
chmod 640 .env
chmod 600 .env.migrate
sudo chgrp -R www-data storage
sudo chmod -R g+rwX storage
```

Adapt the group for your server. The deployment account and cron user also need appropriate access. Do not use blanket `chmod 777`. If PHP sessions cannot be written, correct the server's configured PHP session directory permissions separately.

### 8.3 Start after deployment

Apache serves requests continuously; there is no separate application process to start:

```bash
cd /var/www/examina
ENV_FILE=.env.migrate php bin/migrate.php
php bin/check.php
sudo apache2ctl configtest
sudo systemctl reload apache2
```

Visit **https://exams.yourdomain.com**. If PHP-FPM is used, its configured service must also be running. Do not substitute a guessed PHP-FPM version in service commands.

For Nginx hosting, configure its root to the same `public` directory and PHP execution through PHP-FPM. Nginx does not read `.htaccess`; configure dotfile denial and other access rules in its own server configuration.

## 9. Deploy on shared hosting or cPanel

1. Select a supported PHP version and enable the required extensions.
2. Upload the application outside the publicly served directory where possible.
3. Configure the domain or subdomain document root to the application's `public` directory.
4. Create the database and database user through the hosting panel. Use its exact names, which may have an account prefix.
5. Create `.env` with production settings and the provider's database hostname.
6. Enable HTTPS and configure the sender/mail transport.
7. Use the hosting terminal or SSH to run:

   ```bash
   cd /home/YOUR_ACCOUNT/apps/examina
   php bin/migrate.php
   php bin/check.php
   ```

8. Provision the administrator using Section 7.2.
9. Add a cron task using the provider's PHP executable path.

After deployment, **no start command is needed**. The hosting web server runs PHP when the domain is accessed.

If the panel cannot point the document root to `public`, ask the provider to configure it. Do not expose the whole project or upload only `public` without its sibling application directories. If shell access is unavailable, arrange CLI migration with the provider; do not create a public web endpoint for database setup commands.

## 10. Automatic examination expiry

Run once manually:

```bash
php bin/expire.php
```

This finalizes expired attempts using already accepted answers. The application also finalizes expired attempts when accessed; the scheduled command handles unattended attempts.

On Linux, run `crontab -e` under an account with access to `.env`, the application, and its database, then add:

```cron
* * * * * cd /var/www/examina && /usr/bin/php bin/expire.php >> storage/logs/expire.log 2>&1
```

Use the actual PHP path from `command -v php`. Configure log rotation for the expiry log. On shared hosting, use the cron interface and your account's application path.

On Windows, create a Task Scheduler task that runs every minute:

- Program: `C:\xampp\php\php.exe`
- Arguments: `C:\path\to\project\bin\expire.php`
- Start in: `C:\path\to\project`

## 11. Updates, backups, and rollback

### 11.1 Back up before updating

Use your hosting backup facility or a database account with the privileges required by the backup tool. Example:

```bash
mysqldump -h 127.0.0.1 -P 3306 -u YOUR_BACKUP_USER -p \
  --single-transaction --no-tablespaces \
  online_examination_system > /private/backup/path/examina-before-update.sql
```

Replace the destination with an existing private backup directory outside the document root. `-p` prompts for a password. Preserve the current release and configuration securely as well. Coordinate updates outside examination windows and avoid concurrent schema changes during a backup.

### 11.2 Deploy an update

Upload the new application code, preserving server `.env` files and storage. Then:

```bash
cd /var/www/examina
ENV_FILE=.env.migrate php bin/migrate.php
php bin/check.php
```

With a single database account authorized for migrations:

```bash
php bin/migrate.php
php bin/check.php
```

There is no `npm build` or Composer install step. Existing migrations preserve records; do not run the demo seeder or integration tests against production. If OPcache timestamp checking is disabled, reload the relevant PHP service through the server or hosting panel. The exact service depends on whether PHP runs inside Apache or through PHP-FPM.

### 11.3 Rollback

Retain the previous application release and a verified database backup. The project does not provide an automatic down-migration command. A code rollback must remain compatible with the current schema. Restore a database backup only as a deliberate recovery action, accounting for records created since that backup. Test restoration on a separate database first.

## 12. Tests and deployment verification

### Unit tests

```bash
php tests/run.php --unit
```

### Integration tests

Use a separate database whose name ends in `_test`. Create `.env.test` from your local configuration, change its database credentials/name, and use `APP_ENV=local`:

```bash
ENV_FILE=.env.test php tests/run.php --integration
```

The suite resets its configured test database. Never point it at production. See the README for required test privileges and PCNTL concurrency support.

### Browser tests

After integration tests initialize the test database:

```bash
npm ci
ENV_FILE=.env.test php -S 127.0.0.1:8081 -t public
```

In a second terminal:

```bash
BASE_URL=http://127.0.0.1:8081 npm run test:browser
```

The default browser configuration uses installed Chrome. The suite creates fixture records, so use a separate test installation. Test evidence and screenshots are stored in `tests/artifacts`.

### Production smoke test

After deployment, verify:

- HTTPS loads the application and its assets without exposing PHP source.
- Administrator, instructor, and student access is appropriately restricted.
- A controlled test examination supports registration, saving, submission, and correct results.
- Password recovery reaches a real mailbox and its link uses the correct domain.
- Cron finalizes a controlled expired attempt.
- Mobile navigation and forms work on the target devices.
- `.env`, `database`, and `storage` are not publicly accessible.

`bin/check.php` checks extensions, storage writability, and database/schema access. It does not certify mail delivery, HTTPS, cron, or the complete deployed workflow. CLI PHP and web-server PHP may use different versions or configuration files.

## 13. Troubleshooting

| Problem | Action |
| --- | --- |
| `php: command not found` | Add PHP to PATH or use `/opt/homebrew/bin/php` on the configured Mac or the XAMPP executable on Windows. |
| Database connection refused | Start MySQL and confirm host/port. The existing Mac configuration uses 3307; most other installations use 3306. |
| Database access denied | Check credentials and the database account's permitted connection host and grants. |
| Unknown database | Create the database first; migration only creates its tables. |
| Missing tables | Run migration against the intended environment and verify `ENV_FILE`. |
| Permission error during migration | Use the migration account with CREATE/ALTER/INDEX/REFERENCES privileges. |
| Port 8080 already in use | Open the existing server, or use port 8082 and update local `APP_URL` accordingly. |
| Login does not persist over HTTP | Use `SESSION_SECURE=false` locally. Production requires HTTPS with `SESSION_SECURE=true`. Check PHP session storage too. |
| Recovery email does not arrive locally | Read the private files in `storage/mail` when `MAIL_DRIVER=log`. |
| Recovery email does not arrive in production | Check `MAIL_DRIVER=mail`, sender configuration, server transport, and mail logs. |
| 403 at the website root | Check that DocumentRoot points to `public`, its directory grant is active, and file traversal/read permissions are correct. |
| PHP code downloads or appears as text | PHP execution is not configured. Take the site out of public service and correct the server PHP handler. |
| Environment check reports storage failure | Give the executing PHP user appropriate write access to `storage/mail` and `storage/logs`. |
| Demo examination has closed | Seed dates do not refresh. Create a new exam or edit an eligible exam before any attempts. |
| Demo password no longer works | Seeder does not overwrite existing passwords. Use the password-change/recovery workflow. |
| Changes do not appear after deployment | Check deployment path and browser cache; reload the PHP service if OPcache is configured without timestamp validation. |
| Seeder refuses production | Expected behavior. Use migrations and provision real accounts instead. |

## 14. Command reference

| Task | Command |
| --- | --- |
| Start local development server | `php -S 127.0.0.1:8080 -t public` |
| Create/update tables | `php bin/migrate.php` |
| Run migration with separate credentials | `ENV_FILE=.env.migrate php bin/migrate.php` |
| Add local demo data | `php bin/seed.php` |
| Check environment/database | `php bin/check.php` |
| Finalize expired attempts | `php bin/expire.php` |
| Run unit tests | `php tests/run.php --unit` |
| Run isolated database tests | `ENV_FILE=.env.test php tests/run.php --integration` |
| Run browser tests against a test server | `BASE_URL=http://127.0.0.1:8081 npm run test:browser` |
| Start configured Apache on Ubuntu/Debian | `sudo systemctl enable --now apache2` |
| Check Apache configuration | `sudo apache2ctl configtest` |
| Reload Apache configuration | `sudo systemctl reload apache2` |
| Start application on shared hosting | No command; the configured web server serves it automatically. |

## 15. Related documentation

- [Project README](../README.md)
- [Database design](database.md)
- [Module-wise test cases](module-test-cases.md)
- [Recorded verification results and limitations](verification.md)
- [Implementation alignment with the report](source-alignment.md)
- [PHP development web server](https://www.php.net/features.commandline.webserver.php)
- [Apache service control](https://httpd.apache.org/docs/2.4/programs/apachectl.html)
