<?php
declare(strict_types=1);
namespace Exam;
use PDO;
final class AuthService {
    public function __construct(private PDO $db) {}
    public function register(array $input): int {
        $name=Rules::text($input['full_name']??null,'Full name',120);
        $username=Rules::text($input['username']??null,'Username',60,3);
        if (!preg_match('/^[a-zA-Z0-9_.-]+$/D',$username)) throw new DomainError('Username may contain letters, numbers, dots, hyphens and underscores.');
        $email=strtolower(Rules::text($input['email']??null,'Email',254));
        if (!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new DomainError('Enter a valid email address.');
        $password=Rules::password($input['password']??null);
        if ($password!==($input['password_confirmation']??'')) throw new DomainError('Passwords do not match.');
        $department=Rules::id($input['department_id']??null);
        if (!DB::one($this->db,'SELECT department_id FROM departments WHERE department_id=?',[$department])) throw new DomainError('Select an existing department.');
        try { DB::run($this->db,"INSERT INTO users (full_name,username,email,password_hash,department_id,role) VALUES (?,?,?,?,?,'student')",[$name,$username,$email,password_hash($password,PASSWORD_DEFAULT),$department]); }
        catch (\PDOException $e) { if ($e->getCode()==='23000') throw new DomainError('That username or email is already in use.'); throw $e; }
        return (int)$this->db->lastInsertId();
    }
    public function throttle(string $scope, string $key, int $limit=10): void {
        $hash=hash('sha256',$scope.':'.$key);
        DB::transaction($this->db,function() use($hash,$limit) {
            DB::run($this->db,'INSERT IGNORE INTO auth_throttles VALUES (?,0,UTC_TIMESTAMP())',[$hash]);
            $row=DB::one($this->db,'SELECT * FROM auth_throttles WHERE throttle_key=? FOR UPDATE',[$hash]);
            if (strtotime($row['window_at'].' UTC')<time()-900) DB::run($this->db,'UPDATE auth_throttles SET hits=1,window_at=UTC_TIMESTAMP() WHERE throttle_key=?',[$hash]);
            elseif ((int)$row['hits'] >= $limit) throw new DomainError('Too many requests. Try again in 15 minutes.',429);
            else DB::run($this->db,'UPDATE auth_throttles SET hits=hits+1 WHERE throttle_key=?',[$hash]);
        });
    }
    public function login(string $identifier,string $password): array {
        $user=DB::one($this->db,'SELECT * FROM users WHERE username=? OR email=?',[trim($identifier),strtolower(trim($identifier))]);
        // Always perform password verification, including for unknown accounts.
        $hash=$user['password_hash']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        if (!password_verify($password,$hash) || !$user || $user['status']!=='active') throw new DomainError('The credentials are incorrect or the account is inactive.',401);
        return $user;
    }
    public function changePassword(array $user,string $current,string $password,string $confirm): void {
        Rules::password($password);
        if ($password!==$confirm) throw new DomainError('Passwords do not match.');
        DB::transaction($this->db,function() use($user,$current,$password) {
            $row=DB::one($this->db,'SELECT * FROM users WHERE user_id=? FOR UPDATE',[$user['user_id']]);
            if (!$row || !password_verify($current,$row['password_hash'])) throw new DomainError('Current password is incorrect.');
            DB::run($this->db,'UPDATE users SET password_hash=?,auth_version=auth_version+1 WHERE user_id=?',[password_hash($password,PASSWORD_DEFAULT),$user['user_id']]);
            DB::run($this->db,'UPDATE password_resets SET used_at=UTC_TIMESTAMP() WHERE user_id=? AND used_at IS NULL',[$user['user_id']]);
        });
    }
    public function requestReset(string $email): void {
        $user=DB::one($this->db,"SELECT * FROM users WHERE email=? AND status='active'",[strtolower(trim($email))]);
        if (!$user) return;
        $token=bin2hex(random_bytes(32)); $hash=hash('sha256',$token);
        $expires=gmdate('Y-m-d H:i:s',time()+max(5,(int)Config::get('RESET_TTL_MINUTES','30'))*60);
        DB::run($this->db,'INSERT INTO password_resets(user_id,token_hash,expires_at) VALUES(?,?,?)',[$user['user_id'],$hash,$expires]);
        $link=rtrim(Config::get('APP_URL','http://127.0.0.1:8080'),'/').'/index.php?r=/reset&token='.$token;
        $body="Hello {$user['full_name']},\n\nReset your examination account password:\n$link\n\nThis link expires at $expires UTC and can be used once.\nIgnore this message if you did not request it.\n";
        try { Mailer::send($user['email'],'Reset your Examina password',$body); }
        catch (\Throwable $e) { DB::run($this->db,'DELETE FROM password_resets WHERE token_hash=?',[$hash]); error_log('Password recovery delivery failed.'); }
    }
    public function reset(string $token,string $password,string $confirm): void {
        Rules::password($password);
        if ($password!==$confirm) throw new DomainError('Passwords do not match.');
        if (!preg_match('/^[a-f0-9]{64}$/D',$token)) throw new DomainError('This recovery link is invalid or expired.');
        $lookup=DB::one($this->db,'SELECT user_id FROM password_resets WHERE token_hash=?',[hash('sha256',$token)]);
        if (!$lookup) throw new DomainError('This recovery link is invalid or expired.');
        DB::transaction($this->db,function() use($lookup,$token,$password) {
            $user=DB::one($this->db,'SELECT * FROM users WHERE user_id=? FOR UPDATE',[$lookup['user_id']]);
            $row=DB::one($this->db,'SELECT * FROM password_resets WHERE token_hash=? FOR UPDATE',[hash('sha256',$token)]);
            if (!$row || $row['used_at'] || $row['expires_at']<=gmdate('Y-m-d H:i:s') || $user['status']!=='active') throw new DomainError('This recovery link is invalid or expired.');
            DB::run($this->db,'UPDATE users SET password_hash=?,auth_version=auth_version+1 WHERE user_id=?',[password_hash($password,PASSWORD_DEFAULT),$user['user_id']]);
            DB::run($this->db,'UPDATE password_resets SET used_at=UTC_TIMESTAMP() WHERE user_id=? AND used_at IS NULL',[$user['user_id']]);
        });
    }
}
