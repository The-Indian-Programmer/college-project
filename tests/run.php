<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';
require ROOT . '/database/Seeder.php';
use Exam\{
    DB,
    Rules,
    DomainError,
    AuthService,
    AdminService,
    ExamService,
    AttemptService,
    Config,
    Seeder,
    Web,
};
$results = [];
$failures = 0;
function test(string $id, string $description, callable $fn): void
{
    global $results, $failures;
    try {
        $fn();
        $status = 'PASS';
        $detail = '';
    } catch (Throwable $e) {
        $status = 'FAIL';
        $detail = $e->getMessage();
        $failures++;
    }
    $results[] = compact('id', 'description', 'status', 'detail');
    echo "$status $id $description" . ($detail ? " — $detail" : '') . "\n";
}
function same(mixed $actual, mixed $expected): void
{
    if ($actual !== $expected) {
        throw new RuntimeException(
            'Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true),
        );
    }
}
function rejects(callable $fn, ?int $status = null): void
{
    try {
        $fn();
    } catch (DomainError $e) {
        if ($status !== null) {
            same($e->status, $status);
        }
        return;
    }
    throw new RuntimeException('Expected DomainError.');
}
test('UNIT-01', 'Exact decimal scoring values', function () {
    same(Rules::cents('12.05'), 1205);
    same(Rules::money(1205), '12.05');
    same(Rules::cents('0.01'), 1);
    rejects(fn() => Rules::cents('1.001'));
    rejects(fn() => Rules::cents('0'));
    rejects(fn() => Rules::cents('-1'));
});
test('UNIT-02', 'Deadline is clamped to exam close', function () {
    same(Rules::deadline('2026-01-01 09:50:00', 30, '2026-01-01 10:00:00'), '2026-01-01 10:00:00');
    same(Rules::deadline('2026-01-01 09:10:00', 30, '2026-01-01 10:00:00'), '2026-01-01 09:40:00');
});
test('UNIT-03', 'Question validation and single key', function () {
    $q = [
        'question_text' => 'A valid question',
        'question_type' => 'TF',
        'marks' => '2',
        'options' => ['True', 'False'],
        'correct' => 0,
    ];
    same(Rules::question($q)['marks'], '2.00');
    rejects(fn() => Rules::question(array_replace($q, ['correct' => [0, 1]])));
    rejects(fn() => Rules::question(array_replace($q, ['options' => ['True', 'False', 'Maybe']])));
    rejects(fn() => Rules::question(array_replace($q, ['question_text' => ''])));
});
test('UNIT-04', 'Markup is escaped', function () {
    same(Web::e('<script>'), '&lt;script&gt;');
});
test('UNIT-05', 'Identifiers and lengths are validated', function () {
    rejects(fn() => Rules::id('1 OR 1=1'));
    same(Rules::text(str_repeat('a', 100), 'Name', 100), str_repeat('a', 100));
    rejects(fn() => Rules::text(str_repeat('a', 101), 'Name', 100));
});
test('UNIT-06', 'Server rejects malformed and impossible dates', function () {
    rejects(fn() => Rules::date('2026-02-31T12:00'));
    rejects(fn() => Rules::date('not-a-date'));
});
test('UNIT-07', 'CSRF requires constant session token', function () {
    $_SESSION['csrf'] = 'test-token';
    Web::verifyCsrf('test-token');
    rejects(fn() => Web::verifyCsrf('wrong'), 419);
});
if (in_array('--unit', $argv, true)) {
    echo "Unit tests complete.\n";
    exit($failures ? 1 : 0);
}
$name = Config::get('DB_DATABASE');
if (!str_ends_with($name, '_test') || !in_array('--integration', $argv, true)) {
    fwrite(
        STDERR,
        "Integration tests require DB_DATABASE ending in _test and --integration. Use ENV_FILE=.env.test. No application data was changed.\n",
    );
    exit(2);
}
$db = DB::connect();
Exam\Migrator::run($db);
// Only the explicitly selected test database is reset; foreign-key order is preserved.
$db->exec('SET FOREIGN_KEY_CHECKS=0');
foreach (
    [
        'responses',
        'exam_results',
        'attempts',
        'exam_registrations',
        'question_options',
        'questions',
        'exams',
        'password_resets',
        'users',
        'departments',
        'auth_throttles',
    ]
    as $table
) {
    $db->exec("TRUNCATE TABLE $table");
}
$db->exec('SET FOREIGN_KEY_CHECKS=1');
Seeder::run($db);
$all = [];
foreach (DB::all($db, 'SELECT * FROM users') as $u) {
    $all[$u['username']] = $u;
}
$admin = $all['admin'];
$instructor = $all['instructor'];
$other = $all['instructor2'];
$student = $all['student'];
$student2 = $all['student2'];
$auth = new AuthService($db);
$adm = new AdminService($db);
$ex = new ExamService($db);
$exam = (int) DB::one($db, "SELECT exam_id FROM exams WHERE title='Database Fundamentals'")[
    'exam_id'
];
$fixture = (int) DB::one($db, "SELECT exam_id FROM exams WHERE title='Web Development Essentials'")[
    'exam_id'
];
$now = gmdate('Y-m-d H:i:s');
$attempts = new AttemptService($db, fn() => $now);
$password = Config::get('SEED_PASSWORD', 'DemoPass123!');
$registration = [
    'full_name' => 'Testing Student',
    'username' => 'new.student',
    'email' => 'new@example.test',
    'department_id' => $student['department_id'],
    'password' => 'TestingPass123!',
    'password_confirmation' => 'TestingPass123!',
    'role' => 'admin',
];
test('ID-01/04', 'Public registration always creates student with a hash', function () use (
    $auth,
    $registration,
    $db,
) {
    $id = $auth->register($registration);
    $u = DB::one($db, 'SELECT * FROM users WHERE user_id=?', [$id]);
    same($u['role'], 'student');
    same(password_verify($registration['password'], $u['password_hash']), true);
});
test('ID-02', 'Duplicate and blank registrations rejected', function () use ($auth, $registration) {
    rejects(fn() => $auth->register($registration));
    rejects(fn() => $auth->register(array_replace($registration, ['full_name' => ''])));
});
test('ID-03', 'Active credentials only; injection is data', function () use ($auth, $password) {
    same($auth->login('student', $password)['role'], 'student');
    rejects(fn() => $auth->login('student', 'wrong'), 401);
    rejects(fn() => $auth->login('inactive.student', $password), 401);
    rejects(fn() => $auth->login("' OR 1=1 --", 'anything'), 401);
});
test('ID-05', 'Password change verifies current password', function () use (
    $auth,
    $all,
    $password,
    $db,
) {
    $u = $all['student3'];
    rejects(fn() => $auth->changePassword($u, 'wrong', 'ChangedPass123!', 'ChangedPass123!'));
    $auth->changePassword($u, $password, 'ChangedPass123!', 'ChangedPass123!');
    same($auth->login('student3', 'ChangedPass123!')['username'], 'student3');
    same(
        (int) DB::one($db, 'SELECT auth_version FROM users WHERE user_id=?', [$u['user_id']])[
            'auth_version'
        ],
        2,
    );
});
test('ID-06', 'Recovery is single use and rejects expired tokens', function () use (
    $db,
    $auth,
    $all,
) {
    $id = $all['student3']['user_id'];
    $token = str_repeat('a', 64);
    DB::run($db, 'INSERT INTO password_resets(user_id,token_hash,expires_at) VALUES(?,?,?)', [
        $id,
        hash('sha256', $token),
        gmdate('Y-m-d H:i:s', time() + 600),
    ]);
    $auth->reset($token, 'RecoveredPass123!', 'RecoveredPass123!');
    rejects(fn() => $auth->reset($token, 'RecoveredPass123!', 'RecoveredPass123!'));
    $expired = str_repeat('b', 64);
    DB::run($db, 'INSERT INTO password_resets(user_id,token_hash,expires_at) VALUES(?,?,?)', [
        $id,
        hash('sha256', $expired),
        gmdate('Y-m-d H:i:s', time() - 1),
    ]);
    rejects(fn() => $auth->reset($expired, 'RecoveredPass123!', 'RecoveredPass123!'));
});
test('AD-01/02', 'Department maintenance validates duplicates', function () use ($adm, $admin) {
    $id = $adm->department($admin, ['department_name' => 'Temporary department']);
    $adm->department($admin, ['department_id' => $id, 'department_name' => 'Renamed department']);
    rejects(fn() => $adm->department($admin, ['department_name' => 'Renamed department']));
    $adm->delete($admin, 'department', $id);
});
test('AD-03/04', 'Referenced records and unauthorized maintenance protected', function () use (
    $adm,
    $admin,
    $student,
) {
    rejects(fn() => $adm->delete($admin, 'department', (int) $student['department_id']));
    rejects(fn() => $adm->department($student, ['department_name' => 'Hacked']), 403);
    rejects(fn() => $adm->delete($admin, 'user', (int) $student['user_id']));
});
$input = [
    'title' => 'Integration fixture',
    'starts_at' => Web::date(gmdate('Y-m-d H:i:s', time() - 3600), 'Y-m-d\TH:i'),
    'ends_at' => Web::date(gmdate('Y-m-d H:i:s', time() + 3600), 'Y-m-d\TH:i'),
    'duration_minutes' => '30',
];
$temp = 0;
test('EX-01/02', 'Exam creation and schedule boundaries', function () use (
    $ex,
    $instructor,
    $input,
    &$temp,
) {
    $temp = $ex->save($instructor, $input);
    same($ex->get($temp)['title'], 'Integration fixture');
    rejects(fn() => $ex->save($instructor, array_replace($input, ['duration_minutes' => 0])));
    rejects(
        fn() => $ex->save($instructor, array_replace($input, ['ends_at' => $input['starts_at']])),
    );
});
test('EX-03', 'Other instructor cannot revise an examination', function () use (
    $ex,
    $other,
    $input,
    $exam,
) {
    rejects(fn() => $ex->save($other, $input + ['exam_id' => $exam]), 403);
});
test('EX-04', 'Empty examination cannot be started', function () use ($attempts, $student, &$temp) {
    $attempts->register($student, $temp);
    rejects(fn() => $attempts->start($student, $temp));
});
$qid = 0;
$qinput = [
    'question_text' => 'Fixture question',
    'question_type' => 'MCQ',
    'marks' => '2.00',
    'options' => ['First', 'Second'],
    'correct' => 0,
];
test('EX-ADMIN', 'Admin-created exams have an explicit responsible instructor', function () use (
    $ex,
    $admin,
    $instructor,
    $other,
    $input,
    $qinput,
) {
    $id = $ex->save(
        $admin,
        $input + [
            'department_id' => $instructor['department_id'],
            'assigned_to' => $instructor['user_id'],
        ],
    );
    $ex->saveQuestion($instructor, $id, $qinput);
    rejects(fn() => $ex->saveQuestion($other, $id, $qinput), 403);
    rejects(
        fn() => $ex->save(
            $admin,
            $input + [
                'department_id' => $instructor['department_id'],
                'assigned_to' => $other['user_id'],
            ],
        ),
    );
});

test('QU-01', 'Questions and options can be created and updated', function () use (
    $ex,
    $instructor,
    &$temp,
    &$qid,
    $qinput,
) {
    $qid = $ex->saveQuestion($instructor, $temp, $qinput);
    $ex->saveQuestion($instructor, $temp, $qinput + ['question_id' => $qid]);
    same(count($ex->questions($temp, true)[0]['options']), 2);
});
test('QU-02/03/04', 'Invalid keys, TF options and marks are rejected', function () use (
    $ex,
    $instructor,
    &$temp,
    $qinput,
) {
    foreach (
        [
            ['correct' => 99],
            ['correct' => [0, 1]],
            ['question_type' => 'TF'],
            ['marks' => '0'],
            ['question_text' => ''],
        ]
        as $change
    ) {
        rejects(fn() => $ex->saveQuestion($instructor, $temp, array_replace($qinput, $change)));
    }
});
test('QU-05', 'Student question projection never contains answer key', function () use (
    $ex,
    $fixture,
) {
    $encoded = json_encode($ex->questions($fixture));
    same(str_contains($encoded, 'is_correct'), false);
});
$aid = 0;
test('ST-01', 'Registered student starts one timed attempt', function () use (
    $attempts,
    $student,
    $fixture,
    $db,
    &$aid,
) {
    $attempts->register($student, $fixture);
    $attempts->register($student, $fixture);
    $aid = $attempts->start($student, $fixture);
    same($attempts->start($student, $fixture), $aid);
    same((int) DB::one($db, 'SELECT COUNT(*) n FROM attempts WHERE attempt_id=?', [$aid])['n'], 1);
});
test('EX-05/QU-06', 'First attempt freezes schedule and questions', function () use (
    $ex,
    $instructor,
    $fixture,
    $input,
    $qinput,
) {
    rejects(fn() => $ex->save($instructor, $input + ['exam_id' => $fixture]));
    rejects(fn() => $ex->saveQuestion($instructor, $fixture, $qinput));
});
test('ST-04', 'Foreign questions and options fail without partial writes', function () use (
    $attempts,
    $ex,
    $student,
    $aid,
    $fixture,
    $exam,
    $db,
) {
    $qs = $ex->questions($fixture, true);
    $foreign = $ex->questions($exam, true)[0];
    rejects(
        fn() => $attempts->save($student, $aid, [
            $foreign['question_id'] => $foreign['options'][0]['option_id'],
        ]),
    );
    rejects(
        fn() => $attempts->save($student, $aid, [
            $qs[0]['question_id'] => $qs[1]['options'][0]['option_id'],
        ]),
    );
    same((int) DB::one($db, 'SELECT COUNT(*) n FROM responses WHERE attempt_id=?', [$aid])['n'], 0);
});
test(
    'ST-06/07',
    'Atomic scoring yields 2/10 and repeated submission is idempotent',
    function () use ($attempts, $ex, $student, $aid, $fixture, $db) {
        $qs = $ex->questions($fixture, true);
        $correct = current(array_filter($qs[0]['options'], fn($o) => $o['is_correct']));
        $wrong = current(array_filter($qs[1]['options'], fn($o) => !$o['is_correct']));
        $attempts->save(
            $student,
            $aid,
            [
                $qs[0]['question_id'] => $correct['option_id'],
                $qs[1]['question_id'] => $wrong['option_id'],
            ],
            true,
        );
        $attempts->save($student, $aid, [], true);
        $r = $attempts->result($student, $aid);
        same($r['score'], '2.00');
        same($r['max_score'], '10.00');
        same(
            (int) DB::one($db, 'SELECT COUNT(*) n FROM exam_results WHERE attempt_id=?', [$aid])[
                'n'
            ],
            1,
        );
    },
);
test('ST-05/RP-02', 'Completed attempts cannot restart and results are private', function () use (
    $attempts,
    $student,
    $student2,
    $fixture,
    $aid,
) {
    rejects(fn() => $attempts->start($student, $fixture));
    rejects(fn() => $attempts->result($student2, $aid), 404);
});
test('ST-02', 'Opening inclusive and closing exclusive', function () use (
    $db,
    $ex,
    $instructor,
    $student2,
    $input,
    $qinput,
) {
    $id = $ex->save($instructor, array_replace($input, ['title' => 'Timing boundary']));
    $ex->saveQuestion($instructor, $id, $qinput);
    $e = $ex->get($id);
    $before = new AttemptService(
        $db,
        fn() => gmdate('Y-m-d H:i:s', strtotime($e['starts_at'] . ' UTC') - 1),
    );
    $before->register($student2, $id);
    rejects(fn() => $before->start($student2, $id));
    $at = new AttemptService($db, fn() => $e['starts_at']);
    $attempt = $at->start($student2, $id);
    same($attempt > 0, true);
    $other = DB::one($db, "SELECT * FROM users WHERE username='new.student'");
    $before->register($other, $id);
    $close = new AttemptService($db, fn() => $e['ends_at']);
    rejects(fn() => $close->start($other, $id));
});
test(
    'ST-03/08',
    'Deadline rejects new answers and grades previously saved values',
    function () use ($db, $attempts, $ex, $student2, $fixture) {
        $attempts->register($student2, $fixture);
        $id = $attempts->start($student2, $fixture);
        $q = $ex->questions($fixture, true)[0];
        $correct = current(array_filter($q['options'], fn($o) => $o['is_correct']));
        $wrong = current(array_filter($q['options'], fn($o) => !$o['is_correct']));
        $attempts->save($student2, $id, [$q['question_id'] => $correct['option_id']]);
        $a = DB::one($db, 'SELECT * FROM attempts WHERE attempt_id=?', [$id]);
        $late = new AttemptService($db, fn() => $a['deadline_at']);
        $r = $late->save($student2, $id, [$q['question_id'] => $wrong['option_id']]);
        same($r['expired'], true);
        same($late->result($student2, $id)['score'], '2.00');
    },
);
test('RP-01', 'Attendance counts are unaffected by response multiplicity', function () use (
    $ex,
    $instructor,
) {
    $rows = $ex->listing($instructor);
    $r = current(array_filter($rows, fn($r) => $r['title'] === 'Database Practice · Completed'));
    same((int) $r['registered_count'], 3);
    same((int) $r['attended_count'], 2);
});
test('SC-01', 'Stored markup is rendered as text', function () use ($adm, $admin, $db) {
    $id = $adm->department($admin, ['department_name' => '<script>alert(1)</script>']);
    $row = DB::one($db, 'SELECT department_name FROM departments WHERE department_id=?', [$id]);
    same(str_contains(Web::e($row['department_name']), '<script>'), false);
});
test('SF-03', 'Failure during finalization rolls back result and completion', function () use (
    $db,
    $attempts,
    $student,
    $temp,
) {
    $id = $attempts->start($student, $temp);
    $db->exec(
        "CREATE TRIGGER test_fail_completion BEFORE UPDATE ON attempts FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Injected completion failure'",
    );
    try {
        $failed = false;
        try {
            $attempts->save($student, $id, [], true);
        } catch (PDOException $e) {
            $failed = true;
        }
        same($failed, true);
        same(
            DB::one($db, 'SELECT status FROM attempts WHERE attempt_id=?', [$id])['status'],
            'in_progress',
        );
        same(DB::one($db, 'SELECT * FROM exam_results WHERE attempt_id=?', [$id]), null);
    } finally {
        $db->exec('DROP TRIGGER test_fail_completion');
    }
});
test('SF-07', 'Recovery failure rolls back password and token together', function () use (
    $db,
    $auth,
    $student2,
) {
    $token = str_repeat('c', 64);
    DB::run($db, 'INSERT INTO password_resets(user_id,token_hash,expires_at) VALUES(?,?,?)', [
        $student2['user_id'],
        hash('sha256', $token),
        gmdate('Y-m-d H:i:s', time() + 500),
    ]);
    $before = DB::one($db, 'SELECT password_hash FROM users WHERE user_id=?', [
        $student2['user_id'],
    ])['password_hash'];
    $db->exec(
        "CREATE TRIGGER test_fail_recovery BEFORE UPDATE ON password_resets FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Injected token failure'",
    );
    try {
        $failed = false;
        try {
            $auth->reset($token, 'AnotherPass123!', 'AnotherPass123!');
        } catch (PDOException $e) {
            $failed = true;
        }
        same($failed, true);
        same(
            DB::one($db, 'SELECT password_hash FROM users WHERE user_id=?', [$student2['user_id']])[
                'password_hash'
            ],
            $before,
        );
        same(
            DB::one($db, 'SELECT used_at FROM password_resets WHERE token_hash=?', [
                hash('sha256', $token),
            ])['used_at'],
            null,
        );
    } finally {
        $db->exec('DROP TRIGGER test_fail_recovery');
    }
});
test('SEED-01', 'Seeder reruns preserve existing data and question counts', function () use ($db) {
    $before = (int) $db->query('SELECT COUNT(*) FROM questions')->fetchColumn();
    Seeder::run($db);
    same((int) $db->query('SELECT COUNT(*) FROM questions')->fetchColumn(), $before);
});
if (function_exists('pcntl_fork')) {
    test('SF-01', 'Concurrent starts create only one attempt', function () use (
        $db,
        $ex,
        $instructor,
        $input,
        $qinput,
        $student2,
    ) {
        $id = $ex->save($instructor, array_replace($input, ['title' => 'Concurrent starts']));
        $ex->saveQuestion($instructor, $id, $qinput);
        new AttemptService($db)->register($student2, $id);
        $files = [];
        $pids = [];
        for ($i = 0; $i < 2; $i++) {
            $file = tempnam(sys_get_temp_dir(), 'exam-race-');
            $files[] = $file;
            $pid = pcntl_fork();
            if ($pid === 0) {
                try {
                    $own = DB::connect();
                    $aid = new AttemptService($own)->start($student2, $id);
                    file_put_contents($file, (string) $aid);
                    exit(0);
                } catch (Throwable $e) {
                    file_put_contents($file, 'ERROR ' . $e->getMessage());
                    exit(1);
                }
            }
            $pids[] = $pid;
        }
        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
            same(pcntl_wexitstatus($status), 0);
        }
        same(file_get_contents($files[0]), file_get_contents($files[1]));
        foreach ($files as $file) {
            unlink($file);
        }
    });
    // Forked PDO connections can close the inherited parent socket: reconnect.
    $db = DB::connect();
}
@mkdir(ROOT . '/tests/artifacts', 0770, true);
file_put_contents(
    ROOT . '/tests/artifacts/results.json',
    json_encode(
        [
            'executed_at' => gmdate(DATE_ATOM),
            'php' => PHP_VERSION,
            'database' => $db->query('SELECT VERSION()')->fetchColumn(),
            'tests' => $results,
            'failures' => $failures,
        ],
        JSON_PRETTY_PRINT,
    ),
);
echo count($results) . " cases executed; $failures failures.\n";
exit($failures ? 1 : 0);
