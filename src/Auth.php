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
        $statement = $this->db->prepare('SELECT id, name, email, created_at FROM users WHERE id = ? LIMIT 1');
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
        $statement = $this->db->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
        $statement->execute([strtolower(trim($email))]);
        $user = $statement->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        $this->startSession((int)$user['id']);
        return true;
    }

    public function logout(): void
    {
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
    }
}

