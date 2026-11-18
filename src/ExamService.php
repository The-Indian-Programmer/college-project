<?php
declare(strict_types=1);
namespace Exam;
use PDO;
final class ExamService
{
    public function __construct(private PDO $db) {}
    public function get(int $id): array
    {
        return DB::one(
            $this->db,
            'SELECT e.*,d.department_name,u.full_name creator_name FROM exams e JOIN departments d ON d.department_id=e.department_id JOIN users u ON u.user_id=COALESCE(e.assigned_to,e.created_by) WHERE e.exam_id=?',
            [$id],
        ) ?? throw new DomainError('Examination not found.', 404);
    }
    public function scope(array $actor, array $exam, bool $edit = false): void
    {
        Rules::role($actor, ['admin', 'instructor', 'student']);
        if ($edit) {
            if (
                $actor['role'] !== 'instructor' ||
                (int) ($exam['assigned_to'] ?? $exam['created_by']) !== (int) $actor['user_id'] ||
                (int) $exam['department_id'] !== (int) $actor['department_id']
            ) {
                throw new DomainError('Only the owning instructor can edit this examination.', 403);
            }
        } elseif (
            $actor['role'] === 'instructor' &&
            (int) ($exam['assigned_to'] ?? $exam['created_by']) !== (int) $actor['user_id']
        ) {
            throw new DomainError('This examination belongs to another instructor.', 403);
        } elseif (
            $actor['role'] === 'student' &&
            (int) $exam['department_id'] !== (int) $actor['department_id']
        ) {
            throw new DomainError('This examination belongs to another department.', 403);
        }
    }
    public function frozen(int $exam): bool
    {
        return (bool) DB::one(
            $this->db,
            'SELECT a.attempt_id FROM attempts a JOIN exam_registrations r ON r.registration_id=a.registration_id WHERE r.exam_id=? LIMIT 1',
            [$exam],
        );
    }
    private function lockEditable(array $actor, int $id): array
    {
        $exam =
            DB::one($this->db, 'SELECT * FROM exams WHERE exam_id=? FOR UPDATE', [$id]) ??
            throw new DomainError('Examination not found.', 404);
        $this->scope($actor, $exam, true);
        if ($this->frozen($id)) {
            throw new DomainError(
                'This examination is locked because a student has started an attempt.',
            );
        }
        return $exam;
    }
    public function save(array $actor, array $input): int
    {
        Rules::role($actor, ['admin', 'instructor']);
        $id = empty($input['exam_id']) ? null : Rules::id($input['exam_id']);
        $title = Rules::text($input['title'] ?? null, 'Title', 150);
        $start = Rules::date((string) ($input['starts_at'] ?? ''));
        $end = Rules::date((string) ($input['ends_at'] ?? ''));
        $minutes = filter_var($input['duration_minutes'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 1440],
        ]);
        if ($minutes === false || $end <= $start) {
            throw new DomainError(
                'Set a duration of 1 to 1440 minutes and a closing time after opening.',
            );
        }
        $department =
            $actor['role'] === 'instructor'
                ? (int) $actor['department_id']
                : Rules::id($input['department_id'] ?? null);
        if (
            !DB::one($this->db, 'SELECT department_id FROM departments WHERE department_id=?', [
                $department,
            ])
        ) {
            throw new DomainError('Select an existing department.');
        }
        $assigned =
            $actor['role'] === 'instructor'
                ? (int) $actor['user_id']
                : Rules::id($input['assigned_to'] ?? null);
        if (
            !DB::one(
                $this->db,
                "SELECT user_id FROM users WHERE user_id=? AND role='instructor' AND status='active' AND department_id=?",
                [$assigned, $department],
            )
        ) {
            throw new DomainError(
                'Select an active responsible instructor in the examination department.',
            );
        }
        return DB::transaction($this->db, function () use (
            $actor,
            $id,
            $title,
            $start,
            $end,
            $minutes,
            $department,
            $assigned,
        ) {
            if ($id) {
                $this->lockEditable($actor, $id);
                DB::run(
                    $this->db,
                    'UPDATE exams SET title=?,starts_at=?,ends_at=?,duration_minutes=? WHERE exam_id=?',
                    [$title, $start, $end, $minutes, $id],
                );
            } else {
                DB::run(
                    $this->db,
                    'INSERT INTO exams(title,starts_at,ends_at,duration_minutes,department_id,created_by,assigned_to) VALUES(?,?,?,?,?,?,?)',
                    [$title, $start, $end, $minutes, $department, $actor['user_id'], $assigned],
                );
                $id = (int) $this->db->lastInsertId();
            }
            return $id;
        });
    }
    public function questions(int $exam, bool $withKey = false): array
    {
        $questions = DB::all(
            $this->db,
            'SELECT question_id,question_text,question_type,marks FROM questions WHERE exam_id=? ORDER BY question_id',
            [$exam],
        );
        foreach ($questions as &$q) {
            $q['options'] = DB::all(
                $this->db,
                'SELECT option_id,option_text' .
                    ($withKey ? ',is_correct' : '') .
                    ' FROM question_options WHERE question_id=? ORDER BY option_id',
                [$q['question_id']],
            );
        }
        return $questions;
    }
    public function saveQuestion(array $actor, int $examId, array $input): int
    {
        $value = Rules::question($input);
        $id = empty($input['question_id']) ? null : Rules::id($input['question_id']);
        return DB::transaction($this->db, function () use ($actor, $examId, $input, $value, $id) {
            $this->lockEditable($actor, $examId);
            $total = DB::one(
                $this->db,
                'SELECT COALESCE(SUM(marks),0) total FROM questions WHERE exam_id=? AND question_id<>?',
                [$examId, $id ?? 0],
            );
            if (
                (int) round((float) $total['total'] * 100) + Rules::cents($value['marks']) >
                9999999999
            ) {
                throw new DomainError('The exam total exceeds the result capacity.');
            }
            if ($id) {
                if (
                    !DB::one(
                        $this->db,
                        'SELECT question_id FROM questions WHERE question_id=? AND exam_id=?',
                        [$id, $examId],
                    )
                ) {
                    throw new DomainError('Question not found in this examination.', 404);
                }
                DB::run($this->db, 'DELETE FROM question_options WHERE question_id=?', [$id]);
                DB::run(
                    $this->db,
                    'UPDATE questions SET question_text=?,question_type=?,marks=? WHERE question_id=?',
                    [$value['text'], $value['type'], $value['marks'], $id],
                );
            } else {
                DB::run(
                    $this->db,
                    'INSERT INTO questions(exam_id,question_text,question_type,marks) VALUES(?,?,?,?)',
                    [$examId, $value['text'], $value['type'], $value['marks']],
                );
                $id = (int) $this->db->lastInsertId();
            }
            foreach ($value['options'] as $i => $text) {
                DB::run(
                    $this->db,
                    'INSERT INTO question_options(question_id,option_text,is_correct) VALUES(?,?,?)',
                    [$id, $text, (int) ($i === $value['correct'])],
                );
            }
            return $id;
        });
    }
    public function deleteQuestion(array $actor, int $examId, int $questionId): void
    {
        DB::transaction($this->db, function () use ($actor, $examId, $questionId) {
            $this->lockEditable($actor, $examId);
            if (
                !DB::one(
                    $this->db,
                    'SELECT question_id FROM questions WHERE question_id=? AND exam_id=?',
                    [$questionId, $examId],
                )
            ) {
                throw new DomainError('Question not found.', 404);
            }
            DB::run($this->db, 'DELETE FROM question_options WHERE question_id=?', [$questionId]);
            DB::run($this->db, 'DELETE FROM questions WHERE question_id=?', [$questionId]);
        });
    }
    public function validateReady(int $id): void
    {
        $questions = $this->questions($id, true);
        if (!$questions) {
            throw new DomainError('This examination has no questions yet.');
        }
        $total = 0;
        foreach ($questions as $q) {
            $keys = array_keys(array_filter($q['options'], fn($o) => (int) $o['is_correct'] === 1));
            if (count($keys) !== 1) {
                throw new DomainError('This examination contains an invalid answer key.');
            }
            Rules::question([
                'question_text' => $q['question_text'],
                'question_type' => $q['question_type'],
                'marks' => $q['marks'],
                'options' => array_column($q['options'], 'option_text'),
                'correct' => $keys[0],
            ]);
            $total += Rules::cents($q['marks']);
        }
        if ($total > 9999999999) {
            throw new DomainError('The exam total exceeds the result capacity.');
        }
    }
    public function listing(array $actor): array
    {
        Rules::role($actor, ['admin', 'instructor', 'student']);
        $where = match ($actor['role']) {
            'student' => 'e.department_id=?',
            'instructor' => 'COALESCE(e.assigned_to,e.created_by)=?',
            default => '1=1',
        };
        $args = match ($actor['role']) {
            'student' => [$actor['department_id']],
            'instructor' => [$actor['user_id']],
            default => [],
        };
        return DB::all(
            $this->db,
            "SELECT e.*,d.department_name,u.full_name creator_name,
            (SELECT COUNT(*) FROM questions q WHERE q.exam_id=e.exam_id) question_count,
            (SELECT COALESCE(SUM(marks),0) FROM questions q WHERE q.exam_id=e.exam_id) total_marks,
            (SELECT COUNT(*) FROM exam_registrations r WHERE r.exam_id=e.exam_id) registered_count,
            (SELECT COUNT(*) FROM attempts a JOIN exam_registrations r ON r.registration_id=a.registration_id WHERE r.exam_id=e.exam_id) attended_count
            FROM exams e JOIN departments d ON d.department_id=e.department_id JOIN users u ON u.user_id=COALESCE(e.assigned_to,e.created_by) WHERE $where ORDER BY e.starts_at DESC,e.exam_id DESC",
            $args,
        );
    }
}
