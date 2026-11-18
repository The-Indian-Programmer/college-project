-- MySQL 8.0.16+ / MariaDB 10.6+. All application timestamps are UTC.
CREATE TABLE IF NOT EXISTS roles (
 role_code VARCHAR(15) PRIMARY KEY, label VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO roles VALUES ('admin','Administrator'),('instructor','Instructor'),('student','Student');
CREATE TABLE IF NOT EXISTS departments (
 department_id INT PRIMARY KEY AUTO_INCREMENT, department_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS users (
 user_id INT PRIMARY KEY AUTO_INCREMENT, department_id INT NULL,
 username VARCHAR(60) NOT NULL UNIQUE, email VARCHAR(254) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL, full_name VARCHAR(120) NOT NULL,
 role VARCHAR(15) NOT NULL, status VARCHAR(10) NOT NULL DEFAULT 'active', auth_version INT NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE RESTRICT,
 FOREIGN KEY (role) REFERENCES roles(role_code) ON DELETE RESTRICT,
 CHECK (status IN ('active','inactive')), CHECK (role='admin' OR department_id IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS exams (
 exam_id INT PRIMARY KEY AUTO_INCREMENT, department_id INT NOT NULL, created_by INT NOT NULL, assigned_to INT NULL,
 title VARCHAR(150) NOT NULL, starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL,
 duration_minutes INT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE RESTRICT,
 FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
 FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE RESTRICT,
 CHECK (ends_at > starts_at), CHECK (duration_minutes BETWEEN 1 AND 1440)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS questions (
 question_id INT PRIMARY KEY AUTO_INCREMENT, exam_id INT NOT NULL, question_text TEXT NOT NULL,
 question_type VARCHAR(10) NOT NULL, marks DECIMAL(6,2) NOT NULL,
 FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE RESTRICT,
 CHECK (question_type IN ('MCQ','TF')), CHECK (marks > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS question_options (
 option_id INT PRIMARY KEY AUTO_INCREMENT, question_id INT NOT NULL,
 option_text VARCHAR(500) NOT NULL, is_correct BOOLEAN NOT NULL DEFAULT 0,
 UNIQUE KEY option_question (question_id,option_id),
 FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE RESTRICT,
 CHECK (is_correct IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS exam_registrations (
 registration_id INT PRIMARY KEY AUTO_INCREMENT, student_id INT NOT NULL, exam_id INT NOT NULL,
 registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY student_exam (student_id,exam_id),
 FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE RESTRICT,
 FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS attempts (
 attempt_id INT PRIMARY KEY AUTO_INCREMENT, registration_id INT NOT NULL UNIQUE,
 started_at DATETIME NOT NULL, deadline_at DATETIME NOT NULL, submitted_at DATETIME NULL,
 status VARCHAR(15) NOT NULL DEFAULT 'in_progress',
 FOREIGN KEY (registration_id) REFERENCES exam_registrations(registration_id) ON DELETE RESTRICT,
 INDEX expiry (status,deadline_at), CHECK (status IN ('in_progress','submitted')),
 CHECK (deadline_at > started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS responses (
 attempt_id INT NOT NULL, question_id INT NOT NULL, selected_option_id INT NULL,
 PRIMARY KEY (attempt_id,question_id),
 FOREIGN KEY (attempt_id) REFERENCES attempts(attempt_id) ON DELETE RESTRICT,
 FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE RESTRICT,
 FOREIGN KEY (question_id,selected_option_id) REFERENCES question_options(question_id,option_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS exam_results (
 attempt_id INT PRIMARY KEY, score DECIMAL(10,2) NOT NULL, max_score DECIMAL(10,2) NOT NULL,
 evaluated_at DATETIME NOT NULL,
 FOREIGN KEY (attempt_id) REFERENCES attempts(attempt_id) ON DELETE RESTRICT,
 CHECK (score >= 0 AND score <= max_score AND max_score > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS password_resets (
 reset_id INT PRIMARY KEY AUTO_INCREMENT, user_id INT NOT NULL, token_hash CHAR(64) NOT NULL UNIQUE,
 expires_at DATETIME NOT NULL, used_at DATETIME NULL,
 FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS auth_throttles (
 throttle_key CHAR(64) PRIMARY KEY, hits INT NOT NULL, window_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS schema_migrations (
 version VARCHAR(80) PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO schema_migrations(version) VALUES ('001_initial');
