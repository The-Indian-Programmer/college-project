<?php
declare(strict_types=1);
namespace Exam;
use PDO;
final class AdminService
{
    public function __construct(private PDO $db) {}
    public function department(array $actor, array $input): int
    {
        Rules::role($actor, ['admin']);
        $name = Rules::text($input['department_name'] ?? null, 'Department name', 100);
        $id = empty($input['department_id']) ? null : Rules::id($input['department_id']);
        if (
            $id &&
            !DB::one($this->db, 'SELECT department_id FROM departments WHERE department_id=?', [
                $id,
            ])
        ) {
            throw new DomainError('Department not found.', 404);
        }
        try {
            if ($id) {
                DB::run(
                    $this->db,
                    'UPDATE departments SET department_name=? WHERE department_id=?',
                    [$name, $id],
                );
            } else {
                DB::run($this->db, 'INSERT INTO departments(department_name) VALUES (?)', [$name]);
                $id = (int) $this->db->lastInsertId();
            }
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new DomainError('That department already exists.');
            }
            throw $e;
        }
        return $id;
    }
    public function user(array $actor, array $input): int
    {
        Rules::role($actor, ['admin']);
        $id = empty($input['user_id']) ? null : Rules::id($input['user_id']);
        $name = Rules::text($input['full_name'] ?? null, 'Full name', 120);
        $username = Rules::text($input['username'] ?? null, 'Username', 60, 3);
        if (!preg_match('/^[a-zA-Z0-9_.-]+$/D', $username)) {
            throw new DomainError('Username contains unsupported characters.');
        }
        $email = strtolower(Rules::text($input['email'] ?? null, 'Email', 254));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainError('Enter a valid email address.');
        }
        $role = $input['role'] ?? '';
        $status = $input['status'] ?? '';
        if (
            !in_array($role, ['admin', 'instructor', 'student'], true) ||
            !in_array($status, ['active', 'inactive'], true)
        ) {
            throw new DomainError('Select a valid role and status.');
        }
        $department = empty($input['department_id']) ? null : Rules::id($input['department_id']);
        if (
            ($role !== 'admin' && !$department) ||
            ($department &&
                !DB::one($this->db, 'SELECT department_id FROM departments WHERE department_id=?', [
                    $department,
                ]))
        ) {
            throw new DomainError('Select an existing department.');
        }
        return DB::transaction($this->db, function () use (
            $id,
            $name,
            $username,
            $email,
            $role,
            $status,
            $department,
            $actor,
            $input,
        ) {
            if ($id) {
                $old = DB::one($this->db, 'SELECT * FROM users WHERE user_id=? FOR UPDATE', [$id]);
                if (!$old) {
                    throw new DomainError('User not found.', 404);
                }
                if (
                    (int) $actor['user_id'] === $id &&
                    ($role !== 'admin' || $status !== 'active')
                ) {
                    throw new DomainError('You cannot remove your own administrator access.');
                }
                $hasHistory =
                    DB::one(
                        $this->db,
                        'SELECT exam_id FROM exams WHERE created_by=? OR assigned_to=? LIMIT 1',
                        [$id, $id],
                    ) ||
                    DB::one(
                        $this->db,
                        'SELECT registration_id FROM exam_registrations WHERE student_id=? LIMIT 1',
                        [$id],
                    );
                if (
                    $hasHistory &&
                    ($old['role'] !== $role || (int) $old['department_id'] !== (int) $department)
                ) {
                    throw new DomainError(
                        'Role and department cannot change after examination records exist. You may update the profile or deactivate the account.',
                    );
                }
                DB::run(
                    $this->db,
                    'UPDATE users SET full_name=?,username=?,email=?,role=?,status=?,department_id=?,auth_version=auth_version+1 WHERE user_id=?',
                    [$name, $username, $email, $role, $status, $department, $id],
                );
            } else {
                $password = Rules::password($input['password'] ?? null);
                DB::run(
                    $this->db,
                    'INSERT INTO users(full_name,username,email,role,status,department_id,password_hash) VALUES(?,?,?,?,?,?,?)',
                    [
                        $name,
                        $username,
                        $email,
                        $role,
                        $status,
                        $department,
                        password_hash($password, PASSWORD_DEFAULT),
                    ],
                );
                $id = (int) $this->db->lastInsertId();
            }
            return $id;
        });
    }
    public function delete(array $actor, string $entity, int $id): void
    {
        Rules::role($actor, ['admin']);
        if ($entity === 'user' && $id === (int) $actor['user_id']) {
            throw new DomainError('You cannot delete your own administrator account.');
        }
        $table = match ($entity) {
            'user' => 'users',
            'department' => 'departments',
            default => throw new DomainError('Unsupported record type.'),
        };
        $column = $entity === 'user' ? 'user_id' : 'department_id';
        try {
            $n = DB::run($this->db, "DELETE FROM $table WHERE $column=?", [$id]);
            if (!$n) {
                throw new DomainError('Record not found.', 404);
            }
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new DomainError(
                    'This record has examination or user dependencies and must be retained.',
                );
            }
            throw $e;
        }
    }
}
