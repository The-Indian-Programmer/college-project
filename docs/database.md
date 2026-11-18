# Database configuration and design

## Configuration

`Config::load()` reads `.env`; process environment values override the file. `ENV_FILE` selects another file for isolated testing. The PDO connection uses native prepared statements, exception mode, UTF-8, UTC timestamps and READ COMMITTED isolation. The SQL schema uses InnoDB with restrictive foreign keys for retained examination records.

| Variable | Purpose |
| --- | --- |
| DB_HOST / DB_PORT | MySQL host and listening port. `127.0.0.1` forces TCP; `localhost` can use the local client socket. |
| DB_DATABASE | Database must already exist before `bin/migrate.php`. |
| DB_USERNAME / DB_PASSWORD | Local credentials. They are never returned in responses. |
| APP_TIMEZONE | Interpretation of schedule forms and display timezone; persistence remains UTC. |
| APP_URL | Absolute public base URL used for password-recovery links. |
| SESSION_SECURE | Enable with HTTPS; false only for local HTTP development. |

## Tables

| Table | Primary key | Relationships and purpose |
| --- | --- | --- |
| roles | role_code | Three fixed roles. Referenced by users.role. No public role creation. |
| departments | department_id | Unique department_name. Referenced by users and exams. |
| users | user_id | Unique username and email; optional department for admin, required for other roles; password_hash; active/inactive status; auth_version revokes sessions. |
| exams | exam_id | Department, actual creator, optional responsible instructor, title, opening/closing timestamps, positive duration. |
| questions | question_id | Exam FK, text, MCQ/TF type, positive DECIMAL(6,2) marks. |
| question_options | option_id | Question FK, text and server-only correct flag. Unique (question_id,option_id) supports the composite response FK. |
| exam_registrations | registration_id | Student and exam FKs; unique student/exam pair. An absent student can have a registration without an attempt. |
| attempts | attempt_id | Unique registration FK; server start/deadline/submission times and in_progress/submitted state. |
| responses | (attempt_id,question_id) | One selected option or NULL per item per attempt; composite option FK prevents a foreign question's option. |
| exam_results | attempt_id (shared FK/PK) | Awarded and maximum DECIMAL(10,2) totals, evaluation timestamp; score within range. |
| password_resets | reset_id | User FK, unique token hash, expiry and used timestamp. Plain token exists only in the recovery message. |
| auth_throttles | throttle_key | Hashed account/IP scope, count and 15-minute window; never stores plaintext credentials. |
| schema_migrations | version | Applied non-destructive schema revisions. |

## Invariants

- Student and instructor department membership is required. The admin module rejects role/department changes once relevant registration/ownership history exists; status/profile changes remain possible.
- Only the responsible instructor may change schedule/questions. For legacy/self-created exams, responsible instructor falls back to created_by.
- Administrator creation must select an active instructor from the selected department.
- Start and question/schedule writes take the same exam row lock. Once an attempt exists, edits fail.
- Starting requires explicit registration, department eligibility, an open window and a valid complete question set. The unique attempt registration FK protects against concurrent starts.
- MCQs have 2–6 distinct options and one answer key; TF has exactly True and False. Maximum duration is 1440 minutes. These bounded input choices make the report's unspecified limits explicit.
- Answer writes and submission lock the attempt. Option/question/exam membership is rechecked server-side.
- Evaluation uses integer cents and full exam marks, including unanswered questions. Result insertion and attempt completion commit together.
- At/after the deadline, late changes are ignored, accepted responses are finalized, and the API explicitly reports expiry. No automatic background scheduler is assumed.
- Password changes/resets lock the user, and token consumption is transactional. Session authentication rechecks status and auth_version on every protected request.
- Foreign-key-protected records are not deleted. Recovery records may cascade when an otherwise unreferenced user is intentionally deleted.

## Backup and restoration

Use the installed MySQL/MariaDB tools with appropriate credentials; avoid putting the password in a committed command:

```sh
mysqldump --single-transaction --routines --triggers -u YOUR_BACKUP_USER -p online_examination_system > examination-backup.sql
mysql -u YOUR_RESTORE_USER -p RESTORE_DATABASE < examination-backup.sql
```

For the isolated local instance, add `--host=127.0.0.1 --port=3307`. Create the restore database first. Restore into a separate environment, verify known results and FK relationships, then document the institution's retention interval. No external backup destination or retention policy is configured by this project.
