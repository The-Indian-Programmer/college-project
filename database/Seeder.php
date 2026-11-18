<?php
declare(strict_types=1);
namespace Exam;
use PDO;
final class Seeder
{
    public static function run(PDO $db): array
    {
        if (Config::get('APP_ENV') === 'production') {
            throw new \RuntimeException('Demo seeding is disabled in production.');
        }
        $password = Rules::password(Config::get('SEED_PASSWORD', 'DemoPass123!'));
        return DB::transaction($db, function () use ($db, $password) {
            foreach (
                ['admin' => 'Administrator', 'instructor' => 'Instructor', 'student' => 'Student']
                as $code => $label
            ) {
                DB::run($db, 'INSERT IGNORE INTO roles VALUES (?,?)', [$code, $label]);
            }
            foreach (['Computer Applications', 'Business Studies'] as $name) {
                DB::run($db, 'INSERT IGNORE INTO departments(department_name) VALUES (?)', [$name]);
            }
            $cs = (int) DB::one(
                $db,
                'SELECT department_id FROM departments WHERE department_name=?',
                ['Computer Applications'],
            )['department_id'];
            $business = (int) DB::one(
                $db,
                'SELECT department_id FROM departments WHERE department_name=?',
                ['Business Studies'],
            )['department_id'];
            $definitions = [
                ['admin', 'Aditi Sharma', 'admin', null, 'active'],
                ['instructor', 'Dr. Meera Rao', 'instructor', $cs, 'active'],
                ['instructor2', 'Prof. Kabir Shah', 'instructor', $business, 'active'],
                ['student', 'Aarav Mehta', 'student', $cs, 'active'],
                ['student2', 'Isha Patel', 'student', $cs, 'active'],
                ['student3', 'Rohan Das', 'student', $cs, 'active'],
                ['business.student', 'Sara Khan', 'student', $business, 'active'],
                ['inactive.student', 'Inactive Demo Student', 'student', $cs, 'inactive'],
            ];
            $users = [];
            foreach ($definitions as [$username, $name, $role, $department, $status]) {
                $existing = DB::one($db, 'SELECT * FROM users WHERE username=?', [$username]);
                if (!$existing) {
                    DB::run(
                        $db,
                        'INSERT INTO users(username,email,password_hash,full_name,role,department_id,status) VALUES(?,?,?,?,?,?,?)',
                        [
                            $username,
                            $username . '@example.test',
                            password_hash($password, PASSWORD_DEFAULT),
                            $name,
                            $role,
                            $department,
                            $status,
                        ],
                    );
                    $existing = DB::one($db, 'SELECT * FROM users WHERE user_id=?', [
                        (int) $db->lastInsertId(),
                    ]);
                }
                $users[$username] = $existing;
            }
            $bank = [
                [
                    'Which SQL clause filters rows before grouping?',
                    'MCQ',
                    '2.00',
                    ['WHERE', 'HAVING', 'ORDER BY', 'GROUP BY'],
                    0,
                ],
                [
                    'A primary key uniquely identifies each record in a table.',
                    'TF',
                    '2.00',
                    ['True', 'False'],
                    0,
                ],
                [
                    'Which relationship is resolved using a junction table?',
                    'MCQ',
                    '3.00',
                    ['One-to-one', 'Many-to-many', 'No relationship', 'A calculated field'],
                    1,
                ],
                [
                    'What is the main purpose of a foreign key?',
                    'MCQ',
                    '3.00',
                    [
                        'Encrypt passwords',
                        'Enforce referential integrity',
                        'Sort a table',
                        'Generate CSS',
                    ],
                    1,
                ],
                [
                    'An SQL transaction can group multiple writes into one atomic operation.',
                    'TF',
                    '2.00',
                    ['True', 'False'],
                    0,
                ],
                [
                    'Which normal form removes partial dependencies on a composite key?',
                    'MCQ',
                    '4.00',
                    ['First normal form', 'Second normal form', 'Unnormalized form', 'None'],
                    1,
                ],
                [
                    'Which operation retrieves records without changing them?',
                    'MCQ',
                    '2.00',
                    ['INSERT', 'DELETE', 'SELECT', 'UPDATE'],
                    2,
                ],
                ['A NULL value always means the number zero.', 'TF', '2.00', ['True', 'False'], 1],
            ];
            $web = [
                [
                    'Which language defines the structure of a web page?',
                    'MCQ',
                    '2.00',
                    ['CSS', 'HTML', 'SQL', 'PHP'],
                    1,
                ],
                [
                    'CSS media queries can adapt layouts to different viewport widths.',
                    'TF',
                    '3.00',
                    ['True', 'False'],
                    0,
                ],
                [
                    'Which HTTP method normally submits a state-changing form?',
                    'MCQ',
                    '5.00',
                    ['GET', 'HEAD', 'POST', 'OPTIONS'],
                    2,
                ],
            ];
            $businessBank = [
                [
                    'Which statement summarizes revenue and expenses?',
                    'MCQ',
                    '5.00',
                    ['Income statement', 'Attendance sheet', 'Question bank', 'Browser history'],
                    0,
                ],
                [
                    'Revenue and profit always mean the same amount.',
                    'TF',
                    '5.00',
                    ['True', 'False'],
                    1,
                ],
            ];
            $now = time();
            $definitions = [
                ['Database Fundamentals', $cs, 'instructor', -3600, 7 * 86400, 30, $bank],
                ['Web Development Essentials', $cs, 'instructor', -3600, 7 * 86400, 25, $web],
                [
                    'Operating Systems · Upcoming',
                    $cs,
                    'instructor',
                    2 * 86400,
                    3 * 86400,
                    40,
                    [
                        [
                            'A process is a program in execution.',
                            'TF',
                            '2.00',
                            ['True', 'False'],
                            0,
                        ],
                        [
                            'Which resource does a scheduler allocate?',
                            'MCQ',
                            '3.00',
                            ['CPU time', 'HTML tags', 'Email addresses', 'Database keys'],
                            0,
                        ],
                    ],
                ],
                [
                    'Database Practice · Completed',
                    $cs,
                    'instructor',
                    -2 * 86400,
                    -86400,
                    30,
                    array_slice($bank, 0, 3),
                ],
                [
                    'Business Fundamentals',
                    $business,
                    'instructor2',
                    -3600,
                    7 * 86400,
                    30,
                    $businessBank,
                ],
            ];
            $exams = [];
            foreach (
                $definitions
                as [$title, $dept, $owner, $open, $close, $duration, $questions]
            ) {
                $old = DB::one($db, 'SELECT exam_id FROM exams WHERE title=? AND created_by=?', [
                    $title,
                    $users[$owner]['user_id'],
                ]);
                if ($old) {
                    $exams[$title] = (int) $old['exam_id'];
                    continue;
                }
                DB::run(
                    $db,
                    'INSERT INTO exams(title,department_id,created_by,starts_at,ends_at,duration_minutes) VALUES(?,?,?,?,?,?)',
                    [
                        $title,
                        $dept,
                        $users[$owner]['user_id'],
                        gmdate('Y-m-d H:i:s', $now + $open),
                        gmdate('Y-m-d H:i:s', $now + $close),
                        $duration,
                    ],
                );
                $id = (int) $db->lastInsertId();
                $exams[$title] = $id;
                foreach ($questions as [$text, $type, $marks, $options, $correct]) {
                    DB::run(
                        $db,
                        'INSERT INTO questions(exam_id,question_text,question_type,marks) VALUES(?,?,?,?)',
                        [$id, $text, $type, $marks],
                    );
                    $qid = (int) $db->lastInsertId();
                    foreach ($options as $i => $option) {
                        DB::run(
                            $db,
                            'INSERT INTO question_options(question_id,option_text,is_correct) VALUES(?,?,?)',
                            [$qid, $option, (int) ($i === $correct)],
                        );
                    }
                }
            }
            $completed = $exams['Database Practice · Completed'];
            foreach (['student', 'student2', 'student3'] as $name) {
                DB::run(
                    $db,
                    'INSERT IGNORE INTO exam_registrations(student_id,exam_id,registered_at) VALUES(?,?,?)',
                    [$users[$name]['user_id'], $completed, gmdate('Y-m-d H:i:s', $now - 2 * 86400)],
                );
            }
            foreach (['student', 'student2'] as $idx => $name) {
                $reg = DB::one(
                    $db,
                    'SELECT registration_id FROM exam_registrations WHERE student_id=? AND exam_id=?',
                    [$users[$name]['user_id'], $completed],
                );
                if (
                    DB::one($db, 'SELECT attempt_id FROM attempts WHERE registration_id=?', [
                        $reg['registration_id'],
                    ])
                ) {
                    continue;
                }
                $start = gmdate('Y-m-d H:i:s', $now - 2 * 86400 + 3600);
                $end = gmdate('Y-m-d H:i:s', $now - 2 * 86400 + 4800);
                DB::run(
                    $db,
                    "INSERT INTO attempts(registration_id,started_at,deadline_at,submitted_at,status) VALUES(?,?,?,?,'submitted')",
                    [
                        $reg['registration_id'],
                        $start,
                        gmdate('Y-m-d H:i:s', $now - 2 * 86400 + 5400),
                        $end,
                    ],
                );
                $aid = (int) $db->lastInsertId();
                $score = 0;
                $max = 0;
                foreach (new ExamService($db)->questions($completed, true) as $qi => $q) {
                    $option = current(
                        array_filter(
                            $q['options'],
                            fn($o) => (int) $o['is_correct'] === ($idx === 1 && $qi === 1 ? 0 : 1),
                        ),
                    );
                    DB::run($db, 'INSERT INTO responses VALUES(?,?,?)', [
                        $aid,
                        $q['question_id'],
                        $option['option_id'],
                    ]);
                    $max += Rules::cents($q['marks']);
                    if ($option['is_correct']) {
                        $score += Rules::cents($q['marks']);
                    }
                }
                DB::run($db, 'INSERT INTO exam_results VALUES(?,?,?,?)', [
                    $aid,
                    Rules::money($score),
                    Rules::money($max),
                    $end,
                ]);
            }
            return ['users' => count($users), 'exams' => count($exams), 'password' => $password];
        });
    }
}
