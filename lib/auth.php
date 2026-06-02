<?php

require_once __DIR__ . '/security.php';

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function isAuthenticated(): bool
{
    return currentUser() !== null;
}

function loginUser(string $username, string $password, array $users): bool
{
    if (!isset($users[$username])) return false;
    if (!password_verify($password, $users[$username]['password_hash'])) return false;

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'username' => $username,
        'role' => $users[$username]['role'] ?? 'guest',
        'permissions' => $users[$username]['permissions'] ?? [],
    ];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return true;
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function requireCsrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        echo 'CSRF invalido';
        exit;
    }
}

function requireAuth(): void
{
    if (!isAuthenticated()) {
        http_response_code(401);
        echo 'No autenticado';
        exit;
    }
}

function canDo(string $permission): bool
{
    $user = currentUser();
    if (!$user) return false;
    if (($user['role'] ?? '') === 'admin') return true;
    return in_array($permission, $user['permissions'] ?? [], true);
}

function requirePermission(string $permission): void
{
    requireAuth();
    if (!canDo($permission)) {
        http_response_code(403);
        echo 'No tienes permiso para esta accion';
        exit;
    }
}
