<?php

declare(strict_types=1);

final class Auth
{
    public function __construct(private PDO $db) {}

    public function user(): ?array
    {
        $id = (int)($_SESSION['user_id'] ?? 0);
        if ($id < 1) {
            return null;
        }
        if(!empty($_SESSION['session_key'])){$statement=$this->db->prepare('SELECT id FROM user_sessions WHERE user_id=? AND session_hash=? AND revoked_at IS NULL');$statement->execute([$id,hash('sha256',(string)$_SESSION['session_key'])]);if(!$statement->fetchColumn())return null;$this->db->prepare('UPDATE user_sessions SET last_seen_at=NOW() WHERE user_id=? AND session_hash=?')->execute([$id,hash('sha256',(string)$_SESSION['session_key'])]);}
        $statement = $this->db->prepare('SELECT u.id,u.name,u.email,u.created_at,(ve.user_id IS NOT NULL) email_verified FROM users u LEFT JOIN verified_emails ve ON ve.user_id=u.id WHERE u.id = ? LIMIT 1');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    public function register(string $name, string $email, string $password): array
    {
        $name = trim($name);
        $email = strtolower(trim($email));
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Enter your name and a valid email address.');
        }
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Your password must be at least 8 characters.');
        }
        $statement = $this->db->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
        try {
            $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) {
                throw new InvalidArgumentException('An account already exists for that email address.');
            }
            throw $exception;
        }
        $id = (int)$this->db->lastInsertId();
        $this->startSession($id);
        return ['id' => $id, 'name' => $name, 'email' => $email];
    }

    public function login(string $email, string $password): bool
    {
        $email=strtolower(trim($email));$ip=$_SERVER['REMOTE_ADDR']??null;$q=$this->db->prepare('SELECT COUNT(*) FROM login_attempts WHERE email=? AND (ip_address<=>?) AND successful=0 AND attempted_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)');$q->execute([$email,$ip]);if((int)$q->fetchColumn()>=5)throw new InvalidArgumentException('Too many unsuccessful attempts. Try again in 15 minutes.');
        $statement = $this->db->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        $user = $statement->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->db->prepare('INSERT INTO login_attempts (email,ip_address,successful) VALUES (?,?,0)')->execute([$email,$ip]);
            return false;
        }
        $this->db->prepare('INSERT INTO login_attempts (email,ip_address,successful) VALUES (?,?,1)')->execute([$email,$ip]);
        $this->startSession((int)$user['id']);
        return true;
    }

    public function logout(): void
    {
        if(!empty($_SESSION['session_key']))$this->db->prepare('UPDATE user_sessions SET revoked_at=NOW() WHERE session_hash=?')->execute([hash('sha256',(string)$_SESSION['session_key'])]);
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    private function startSession(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $key=bin2hex(random_bytes(32));$_SESSION['session_key']=$key;$this->db->prepare('INSERT INTO user_sessions (user_id,session_hash,ip_address,user_agent,last_seen_at) VALUES (?,?,?,?,NOW())')->execute([$userId,hash('sha256',$key),$_SERVER['REMOTE_ADDR']??null,mb_substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,500)]);
    }

    public function requestPasswordReset(string $email): ?string
    {
        $q=$this->db->prepare('SELECT id FROM users WHERE email=?');$q->execute([strtolower(trim($email))]);$id=(int)$q->fetchColumn();if(!$id)return null;$token=bin2hex(random_bytes(32));$this->db->prepare('UPDATE password_reset_tokens SET used_at=NOW() WHERE user_id=? AND used_at IS NULL')->execute([$id]);$this->db->prepare('INSERT INTO password_reset_tokens (user_id,token_hash,expires_at) VALUES (?,?,DATE_ADD(NOW(),INTERVAL 60 MINUTE))')->execute([$id,hash('sha256',$token)]);return $token;
    }

    public function resetPassword(string $token,string $password): void
    {
        if(strlen($password)<8)throw new InvalidArgumentException('Your password must be at least 8 characters.');$q=$this->db->prepare('SELECT id,user_id FROM password_reset_tokens WHERE token_hash=? AND used_at IS NULL AND expires_at>NOW()');$q->execute([hash('sha256',$token)]);$row=$q->fetch();if(!$row)throw new InvalidArgumentException('That password reset link is invalid or expired.');$this->db->beginTransaction();try{$this->db->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($password,PASSWORD_DEFAULT),$row['user_id']]);$this->db->prepare('UPDATE password_reset_tokens SET used_at=NOW() WHERE id=?')->execute([$row['id']]);$this->db->prepare('UPDATE user_sessions SET revoked_at=NOW() WHERE user_id=?')->execute([$row['user_id']]);$this->db->commit();}catch(Throwable $e){$this->db->rollBack();throw $e;}
    }

    public function requestEmailVerification(int $userId): string
    {
        $token=bin2hex(random_bytes(32));$this->db->prepare('UPDATE email_verification_tokens SET used_at=NOW() WHERE user_id=? AND used_at IS NULL')->execute([$userId]);$this->db->prepare('INSERT INTO email_verification_tokens (user_id,token_hash,expires_at) VALUES (?,?,DATE_ADD(NOW(),INTERVAL 24 HOUR))')->execute([$userId,hash('sha256',$token)]);return $token;
    }

    public function verifyEmail(string $token): void
    {
        $q=$this->db->prepare('SELECT id,user_id FROM email_verification_tokens WHERE token_hash=? AND used_at IS NULL AND expires_at>NOW()');$q->execute([hash('sha256',$token)]);$row=$q->fetch();if(!$row)throw new InvalidArgumentException('That verification link is invalid or expired.');$this->db->beginTransaction();try{$this->db->prepare('INSERT INTO verified_emails (user_id,verified_at) VALUES (?,NOW()) ON DUPLICATE KEY UPDATE verified_at=VALUES(verified_at)')->execute([$row['user_id']]);$this->db->prepare('UPDATE email_verification_tokens SET used_at=NOW() WHERE id=?')->execute([$row['id']]);$this->db->commit();}catch(Throwable $e){$this->db->rollBack();throw $e;}
    }

    public function sessions(int $userId): array
    {
        $q=$this->db->prepare('SELECT id,ip_address,user_agent,last_seen_at,revoked_at,created_at,session_hash=? current_session FROM user_sessions WHERE user_id=? ORDER BY created_at DESC LIMIT 20');$q->execute([hash('sha256',(string)($_SESSION['session_key']??'')),$userId]);return $q->fetchAll();
    }

    public function revokeSession(int $userId,int $sessionId): void
    {
        $q=$this->db->prepare('UPDATE user_sessions SET revoked_at=NOW() WHERE id=? AND user_id=? AND revoked_at IS NULL');$q->execute([$sessionId,$userId]);if(!$q->rowCount())throw new InvalidArgumentException('Session unavailable.');
    }
}
