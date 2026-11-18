<?php
declare(strict_types=1);
namespace Exam;
use PDO;
final class AttemptService
{
    private \Closure $clock;
    public function __construct(private PDO $db, ?callable $clock = null)
    {
        $this->clock = $clock ? \Closure::fromCallable($clock) : fn() => gmdate('Y-m-d H:i:s');
    }
    private function now(): string
    {
        return ($this->clock)();
    }
    public function register(array $student, int $examId): int
    {
        Rules::role($student, ['student']);
        $exam = new ExamService($this->db)->get($examId);
        new ExamService($this->db)->scope($student, $exam);
        if ($this->now() >= $exam['ends_at']) {
            throw new DomainError('Registration for this examination has closed.');
        }
        DB::run(
            $this->db,
            'INSERT INTO exam_registrations(student_id,exam_id,registered_at) VALUES(?,?,?) ON DUPLICATE KEY UPDATE registration_id=registration_id',
            [$student['user_id'], $examId, $this->now()],
        );
        return (int) DB::one(
            $this->db,
            'SELECT registration_id FROM exam_registrations WHERE student_id=? AND exam_id=?',
            [$student['user_id'], $examId],
        )['registration_id'];
    }
    public function start(array $student, int $examId): int
    {
        Rules::role($student, ['student']);
        return DB::transaction($this->db, function () use ($student, $examId) {
            // The exam mutex is also taken by every configuration writer.
            $exam =
                DB::one($this->db, 'SELECT * FROM exams WHERE exam_id=? FOR UPDATE', [$examId]) ??
                throw new DomainError('Examination not found.', 404);
            $exams = new ExamService($this->db);
            $exams->scope($student, $exam);
            $reg = DB::one(
                $this->db,
                'SELECT * FROM exam_registrations WHERE student_id=? AND exam_id=? FOR UPDATE',
                [$student['user_id'], $examId],
            );
            if (!$reg) {
                throw new DomainError('Register for this examination before starting.');
            }
            $existing = DB::one(
                $this->db,
                'SELECT * FROM attempts WHERE registration_id=? FOR UPDATE',
                [$reg['registration_id']],
            );
            if ($existing) {
                if ($existing['status'] === 'submitted') {
                    throw new DomainError(
                        'Your single attempt is already complete. View your result.',
                    );
                }
                return (int) $existing['attempt_id'];
            }
            $now = $this->now();
            if ($now < $exam['starts_at'] || $now >= $exam['ends_at']) {
                throw new DomainError('This examination is outside its scheduled window.');
            }
            $exams->validateReady($examId);
            DB::run(
                $this->db,
                'INSERT INTO attempts(registration_id,started_at,deadline_at) VALUES(?,?,?)',
                [
                    $reg['registration_id'],
                    $now,
                    Rules::deadline($now, (int) $exam['duration_minutes'], $exam['ends_at']),
                ],
            );
            return (int) $this->db->lastInsertId();
        });
    }
    private function owned(array $student, int $id, bool $lock = false): array
    {
        Rules::role($student, ['student']);
        $row = DB::one(
            $this->db,
            'SELECT a.*,r.student_id,r.exam_id FROM attempts a JOIN exam_registrations r ON r.registration_id=a.registration_id WHERE a.attempt_id=?' .
                ($lock ? ' FOR UPDATE' : ''),
            [$id],
        );
        if (!$row || (int) $row['student_id'] !== (int) $student['user_id']) {
            throw new DomainError('Attempt not found.', 404);
        }
        return $row;
    }
    public function state(array $student, int $id): array
    {
        $this->save($student, $id, [], false);
        $a = $this->owned($student, $id);
        $a['exam'] = new ExamService($this->db)->get((int) $a['exam_id']);
        $a['questions'] = new ExamService($this->db)->questions((int) $a['exam_id']);
        $a['answers'] = [];
        foreach (
            DB::all(
                $this->db,
                'SELECT question_id,selected_option_id FROM responses WHERE attempt_id=?',
                [$id],
            )
            as $r
        ) {
            $a['answers'][$r['question_id']] = $r['selected_option_id'];
        }
        $a['server_time'] = $this->now();
        return $a;
    }
    public function save(array $student, int $id, array $answers, bool $finish = false): array
    {
        return DB::transaction($this->db, function () use ($student, $id, $answers, $finish) {
            $a = $this->owned($student, $id, true);
            $now = $this->now();
            if ($a['status'] === 'submitted') {
                return ['completed' => true, 'attempt_id' => $id];
            }
            $expired = $now >= $a['deadline_at'];
            if (!$expired) {
                foreach ($answers as $question => $option) {
                    $question = Rules::id($question);
                    if (
                        !DB::one(
                            $this->db,
                            'SELECT question_id FROM questions WHERE question_id=? AND exam_id=?',
                            [$question, $a['exam_id']],
                        )
                    ) {
                        throw new DomainError(
                            'A submitted question does not belong to this examination.',
                        );
                    }
                    $option = $option === null || $option === '' ? null : Rules::id($option);
                    if (
                        $option !== null &&
                        !DB::one(
                            $this->db,
                            'SELECT option_id FROM question_options WHERE option_id=? AND question_id=?',
                            [$option, $question],
                        )
                    ) {
                        throw new DomainError(
                            'A submitted option does not belong to its question.',
                        );
                    }
                    DB::run(
                        $this->db,
                        'INSERT INTO responses(attempt_id,question_id,selected_option_id) VALUES(?,?,?) ON DUPLICATE KEY UPDATE selected_option_id=?',
                        [$id, $question, $option, $option],
                    );
                }
            }
            if ($finish || $expired) {
                $this->finalize($a, $now);
            }
            return [
                'completed' => $finish || $expired,
                'expired' => $expired,
                'accepted' => !$expired,
                'attempt_id' => $id,
                'server_time' => $now,
            ];
        });
    }
    private function finalize(array $a, string $now): void
    {
        $questions = DB::all(
            $this->db,
            'SELECT q.marks,o.is_correct FROM questions q LEFT JOIN responses r ON r.question_id=q.question_id AND r.attempt_id=? LEFT JOIN question_options o ON o.option_id=r.selected_option_id AND o.question_id=q.question_id WHERE q.exam_id=?',
            [$a['attempt_id'], $a['exam_id']],
        );
        $score = 0;
        $max = 0;
        foreach ($questions as $q) {
            $marks = Rules::cents($q['marks']);
            $max += $marks;
            if ((int) $q['is_correct'] === 1) {
                $score += $marks;
            }
        }
        if ($max <= 0 || $max > 9999999999) {
            throw new DomainError('Invalid examination total. Contact your instructor.');
        }
        DB::run(
            $this->db,
            'INSERT INTO exam_results(attempt_id,score,max_score,evaluated_at) VALUES(?,?,?,?)',
            [$a['attempt_id'], Rules::money($score), Rules::money($max), $now],
        );
        DB::run(
            $this->db,
            "UPDATE attempts SET status='submitted',submitted_at=? WHERE attempt_id=?",
            [$now, $a['attempt_id']],
        );
    }
    public function result(array $actor, int $id): array
    {
        Rules::role($actor, ['admin', 'instructor', 'student']);
        if ($actor['role'] === 'student') {
            $this->save($actor, $id, [], false);
        }
        $row = DB::one(
            $this->db,
            'SELECT a.*,r.student_id,e.exam_id,e.title,e.created_by,COALESCE(e.assigned_to,e.created_by) responsible_id,u.full_name,s.score,s.max_score,s.evaluated_at FROM attempts a JOIN exam_registrations r ON r.registration_id=a.registration_id JOIN exams e ON e.exam_id=r.exam_id JOIN users u ON u.user_id=r.student_id LEFT JOIN exam_results s ON s.attempt_id=a.attempt_id WHERE a.attempt_id=?',
            [$id],
        );
        if (
            !$row ||
            ($actor['role'] === 'student' &&
                (int) $row['student_id'] !== (int) $actor['user_id']) ||
            ($actor['role'] === 'instructor' &&
                (int) $row['responsible_id'] !== (int) $actor['user_id'])
        ) {
            throw new DomainError('Result not found.', 404);
        }
        if ($row['score'] === null) {
            throw new DomainError('This attempt has no completed result yet.', 409);
        }
        return $row;
    }
    public function expire(): int
    {
        $rows = DB::all(
            $this->db,
            "SELECT a.attempt_id FROM attempts a WHERE a.status='in_progress' AND a.deadline_at<=?",
            [$this->now()],
        );
        $count = 0;
        foreach ($rows as $row) {
            DB::transaction($this->db, function () use ($row, &$count) {
                $a = DB::one(
                    $this->db,
                    'SELECT a.*,r.exam_id FROM attempts a JOIN exam_registrations r ON r.registration_id=a.registration_id WHERE a.attempt_id=? FOR UPDATE',
                    [$row['attempt_id']],
                );
                if ($a['status'] === 'in_progress' && $a['deadline_at'] <= $this->now()) {
                    $this->finalize($a, $this->now());
                    $count++;
                }
            });
        }
        return $count;
    }
}
