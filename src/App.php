<?php
declare(strict_types=1);
namespace Exam;
use PDO;
final class App
{
    private ?array $user = null;
    private AuthService $auth;
    private ExamService $exams;
    private AttemptService $attempts;
    public function __construct(private PDO $db)
    {
        $this->auth = new AuthService($db);
        $this->exams = new ExamService($db);
        $this->attempts = new AttemptService($db);
        if (isset($_SESSION['user_id'])) {
            $u = DB::one($db, 'SELECT * FROM users WHERE user_id=?', [$_SESSION['user_id']]);
            if (
                $u &&
                $u['status'] === 'active' &&
                (int) $u['auth_version'] === (int) ($_SESSION['auth_version'] ?? 0)
            ) {
                $this->user = $u;
            } else {
                unset($_SESSION['user_id'], $_SESSION['auth_version']);
            }
        }
    }
    private function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['auth_version'] = $user['auth_version'];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    private function render(string $template, array $data = []): void
    {
        Web::view(
            $template,
            $data + ['user' => $this->user, 'route' => $_GET['r'] ?? '/', 'title' => 'Overview'],
        );
    }
    private function requireUser(array $roles = ['admin', 'instructor', 'student']): array
    {
        if (!$this->user) {
            if (str_starts_with($_GET['r'] ?? '', '/api/')) {
                throw new DomainError('Please sign in again.', 401);
            }
            Web::redirect('/login');
        }
        Rules::role($this->user, $roles);
        return $this->user;
    }
    private function departments(): array
    {
        return DB::all($this->db, 'SELECT * FROM departments ORDER BY department_name');
    }
    public function run(): void
    {
        $route = $_GET['r'] ?? '/';
        $method = $_SERVER['REQUEST_METHOD'];
        if (!is_string($route)) {
            throw new DomainError('Page not found.', 404);
        }
        if (!in_array($method, ['GET', 'POST'], true)) {
            throw new DomainError('Method not allowed.', 405);
        }
        if ($method === 'POST') {
            $input = $_POST;
            if (str_starts_with($route, '/api/')) {
                $raw = file_get_contents('php://input');
                if (strlen($raw) > 200000) {
                    throw new DomainError('Request is too large.', 413);
                }
                $input = json_decode($raw, true);
                if (!is_array($input)) {
                    throw new DomainError('Invalid request body.');
                }
            }
            Web::verifyCsrf($input['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null));
            $this->post($route, $input);
            return;
        }
        if (in_array($route, ['/login', '/register', '/forgot', '/reset'], true)) {
            if ($this->user) {
                Web::redirect('/');
            }
            $this->render('auth', [
                'title' => match ($route) {
                    '/register' => 'Create your account',
                    '/forgot' => 'Recover access',
                    '/reset' => 'Choose a new password',
                    default => 'Welcome back',
                },
                'departments' => $this->departments(),
            ]);
            return;
        }
        $u = $this->requireUser();
        if ($route === '/') {
            $exams = $this->exams->listing($u);
            $registrations =
                $u['role'] === 'student'
                    ? DB::all(
                        $this->db,
                        'SELECT r.*,a.attempt_id,a.status FROM exam_registrations r LEFT JOIN attempts a ON a.registration_id=r.registration_id WHERE r.student_id=?',
                        [$u['user_id']],
                    )
                    : [];
            $this->render('dashboard', [
                'title' => 'Overview',
                'exams' => $exams,
                'registrations' => $registrations,
            ]);
            return;
        }
        if ($route === '/profile') {
            $this->render('profile', ['title' => 'Account settings']);
            return;
        }
        if ($route === '/admin/users') {
            $this->requireUser(['admin']);
            $edit = isset($_GET['id'])
                ? DB::one($this->db, 'SELECT * FROM users WHERE user_id=?', [
                    Rules::id($_GET['id']),
                ])
                : null;
            $this->render('users', [
                'title' => 'People & access',
                'users' => DB::all(
                    $this->db,
                    'SELECT u.*,d.department_name FROM users u LEFT JOIN departments d ON d.department_id=u.department_id ORDER BY u.user_id DESC',
                ),
                'departments' => $this->departments(),
                'edit' => $edit,
            ]);
            return;
        }
        if ($route === '/admin/departments') {
            $this->requireUser(['admin']);
            $this->render('departments', [
                'title' => 'Departments',
                'departments' => $this->departments(),
            ]);
            return;
        }
        if ($route === '/exams') {
            $this->render('exams', [
                'title' => 'Examinations',
                'exams' => $this->exams->listing($u),
            ]);
            return;
        }
        if ($route === '/exams/edit') {
            $this->requireUser(['admin', 'instructor']);
            $exam = isset($_GET['id']) ? $this->exams->get(Rules::id($_GET['id'])) : null;
            if ($exam) {
                $this->exams->scope($u, $exam, true);
                if ($this->exams->frozen((int) $exam['exam_id'])) {
                    throw new DomainError('This examination is locked after its first attempt.');
                }
            }
            $this->render('exam_form', [
                'title' => $exam ? 'Edit examination' : 'Create examination',
                'exam' => $exam,
                'departments' => $this->departments(),
                'instructors' => DB::all(
                    $this->db,
                    "SELECT u.user_id,u.full_name,d.department_name FROM users u JOIN departments d ON d.department_id=u.department_id WHERE u.role='instructor' AND u.status='active' ORDER BY u.full_name",
                ),
            ]);
            return;
        }
        if ($route === '/exams/show') {
            $id = Rules::id($_GET['id'] ?? null);
            $exam = $this->exams->get($id);
            $this->exams->scope($u, $exam);
            $reg =
                $u['role'] === 'student'
                    ? DB::one(
                        $this->db,
                        'SELECT r.*,a.attempt_id,a.status FROM exam_registrations r LEFT JOIN attempts a ON a.registration_id=r.registration_id WHERE r.student_id=? AND r.exam_id=?',
                        [$u['user_id'], $id],
                    )
                    : null;
            $questions = $u['role'] === 'student' ? [] : $this->exams->questions($id, true);
            $meta = DB::one(
                $this->db,
                'SELECT COUNT(*) question_count,COALESCE(SUM(marks),0) total_marks FROM questions WHERE exam_id=?',
                [$id],
            );
            $this->render('exam', [
                'title' => $exam['title'],
                'exam' => $exam,
                'questions' => $questions,
                'reg' => $reg,
                'meta' => $meta,
                'frozen' => $this->exams->frozen($id),
            ]);
            return;
        }
        if ($route === '/questions/edit') {
            $id = Rules::id($_GET['exam_id'] ?? null);
            $exam = $this->exams->get($id);
            $this->exams->scope($u, $exam, true);
            if ($this->exams->frozen($id)) {
                throw new DomainError('This examination is locked.');
            }
            $question = null;
            if (isset($_GET['id'])) {
                foreach ($this->exams->questions($id, true) as $q) {
                    if ((int) $q['question_id'] === Rules::id($_GET['id'])) {
                        $question = $q;
                    }
                };
            }
            if (isset($_GET['id']) && !$question) {
                throw new DomainError('Question not found.', 404);
            }
            $this->render('question_form', [
                'title' => $question ? 'Edit question' : 'Add question',
                'exam' => $exam,
                'question' => $question,
            ]);
            return;
        }
        if ($route === '/attempt') {
            $a = $this->attempts->state($u, Rules::id($_GET['id'] ?? null));
            if ($a['status'] === 'submitted') {
                Web::redirect('/result', ['id' => $a['attempt_id']]);
            }
            $this->render('attempt', ['title' => $a['exam']['title'], 'attempt' => $a]);
            return;
        }
        if ($route === '/result') {
            $this->render('result', [
                'title' => 'Examination result',
                'result' => $this->attempts->result($u, Rules::id($_GET['id'] ?? null)),
            ]);
            return;
        }
        if ($route === '/results') {
            $where =
                $u['role'] === 'student'
                    ? 'r.student_id=?'
                    : ($u['role'] === 'instructor'
                        ? 'COALESCE(e.assigned_to,e.created_by)=?'
                        : '1=1');
            $args = $u['role'] === 'admin' ? [] : [$u['user_id']];
            $rows = DB::all(
                $this->db,
                "SELECT e.title,u.full_name,a.attempt_id,s.* FROM exam_results s JOIN attempts a ON a.attempt_id=s.attempt_id JOIN exam_registrations r ON r.registration_id=a.registration_id JOIN exams e ON e.exam_id=r.exam_id JOIN users u ON u.user_id=r.student_id WHERE $where ORDER BY s.evaluated_at DESC",
                $args,
            );
            $this->render('results', ['title' => 'Results', 'results' => $rows]);
            return;
        }
        if ($route === '/reports') {
            $this->requireUser(['admin', 'instructor']);
            $exams = $this->exams->listing($u);
            $exam = null;
            $rows = [];
            if (isset($_GET['id'])) {
                $exam = $this->exams->get(Rules::id($_GET['id']));
                $this->exams->scope($u, $exam);
                $rows = DB::all(
                    $this->db,
                    'SELECT u.full_name,u.username,r.registered_at,a.attempt_id,a.status,a.started_at,s.score,s.max_score FROM exam_registrations r JOIN users u ON u.user_id=r.student_id LEFT JOIN attempts a ON a.registration_id=r.registration_id LEFT JOIN exam_results s ON s.attempt_id=a.attempt_id WHERE r.exam_id=? ORDER BY u.full_name',
                    [$exam['exam_id']],
                );
            }
            $this->render('reports', [
                'title' => 'Participation reports',
                'exams' => $exams,
                'exam' => $exam,
                'rows' => $rows,
            ]);
            return;
        }
        throw new DomainError('Page not found.', 404);
    }
    private function post(string $r, array $i): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'local';
        if (in_array($r, ['/login', '/register', '/forgot', '/reset'], true)) {
            $this->auth->throttle($r, $ip, $r === '/login' ? 30 : 15);
            if ($r === '/login') {
                $this->auth->throttle('account', strtolower((string) ($i['identifier'] ?? '')), 15);
                $u = $this->auth->login(
                    (string) ($i['identifier'] ?? ''),
                    (string) ($i['password'] ?? ''),
                );
                $this->login($u);
                Web::redirect('/');
            }
            if ($r === '/register') {
                $this->auth->register($i);
                Web::flash('Your account is ready. Sign in to explore examinations.');
                Web::redirect('/login');
            }
            if ($r === '/forgot') {
                $this->auth->requestReset((string) ($i['email'] ?? ''));
                Web::flash('If an active account matches, a recovery message has been sent.');
                Web::redirect('/forgot');
            }
            if ($r === '/reset') {
                $this->auth->reset(
                    (string) ($i['token'] ?? ''),
                    (string) ($i['password'] ?? ''),
                    (string) ($i['password_confirmation'] ?? ''),
                );
                Web::flash('Password updated. Sign in with your new password.');
                Web::redirect('/login');
            }
        }
        $u = $this->requireUser();
        $admin = new AdminService($this->db);
        switch ($r) {
            case '/logout':
                $_SESSION = [];
                session_regenerate_id(true);
                session_destroy();
                Web::redirect('/login');
            case '/profile':
                $this->auth->changePassword(
                    $u,
                    (string) ($i['current_password'] ?? ''),
                    (string) ($i['password'] ?? ''),
                    (string) ($i['password_confirmation'] ?? ''),
                );
                unset($_SESSION['user_id']);
                Web::flash('Password updated. Sign in again.');
                Web::redirect('/login');
            case '/admin/users/save':
                $admin->user($u, $i);
                Web::flash('User details saved.');
                Web::redirect('/admin/users');
            case '/admin/departments/save':
                $admin->department($u, $i);
                Web::flash('Department saved.');
                Web::redirect('/admin/departments');
            case '/admin/delete':
                $admin->delete($u, (string) ($i['entity'] ?? ''), Rules::id($i['id'] ?? null));
                Web::flash('Unreferenced record deleted.');
                Web::redirect(
                    ($i['entity'] ?? '') === 'user' ? '/admin/users' : '/admin/departments',
                );
            case '/exams/save':
                $id = $this->exams->save($u, $i);
                Web::flash('Examination saved.');
                Web::redirect('/exams/show', ['id' => $id]);
            case '/questions/save':
                $id = Rules::id($i['exam_id'] ?? null);
                $this->exams->saveQuestion($u, $id, $i);
                Web::flash('Question and answer key saved.');
                Web::redirect('/exams/show', ['id' => $id]);
            case '/questions/delete':
                $id = Rules::id($i['exam_id'] ?? null);
                $this->exams->deleteQuestion($u, $id, Rules::id($i['question_id'] ?? null));
                Web::flash('Question removed.');
                Web::redirect('/exams/show', ['id' => $id]);
            case '/exams/register':
                $id = Rules::id($i['exam_id'] ?? null);
                $this->attempts->register($u, $id);
                Web::flash('You are registered. Start when the exam window opens.');
                Web::redirect('/exams/show', ['id' => $id]);
            case '/attempt/start':
                $id = $this->attempts->start($u, Rules::id($i['exam_id'] ?? null));
                Web::redirect('/attempt', ['id' => $id]);
            case '/api/answers':
                if (!isset($i['answers']) || !is_array($i['answers'])) {
                    throw new DomainError('Answers must be an object.');
                }
                Web::json(
                    $this->attempts->save(
                        $u,
                        Rules::id($i['attempt_id'] ?? null),
                        $i['answers'],
                        ($i['finish'] ?? false) === true,
                    ),
                );
            case '/attempt/submit':
                $id = Rules::id($i['attempt_id'] ?? null);
                $answers = $i['answers'] ?? [];
                if (!is_array($answers)) {
                    throw new DomainError('Invalid answers.');
                }
                $this->attempts->save($u, $id, $answers, true);
                Web::redirect('/result', ['id' => $id]);
            default:
                throw new DomainError('Action not found.', 404);
        }
    }
    public function error(\Throwable $error): void
    {
        $status = $error instanceof DomainError ? $error->status : 500;
        $message =
            $error instanceof DomainError
                ? $error->getMessage()
                : 'We could not complete this operation. Please try again or contact the administrator.';
        if ($error instanceof \PDOException && $error->getCode() === '23000') {
            $status = 422;
            $message = 'A unique value already exists or a related record prevents this change.';
        }
        if ($status === 500) {
            error_log((string) $error);
        }
        if (str_starts_with($_GET['r'] ?? '', '/api/')) {
            Web::json(['error' => $message], $status);
        }
        http_response_code($status);
        $this->render('error', [
            'title' => 'Unable to complete request',
            'message' => $message,
            'status' => $status,
        ]);
    }
}