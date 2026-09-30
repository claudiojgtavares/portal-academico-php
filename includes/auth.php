<?php

require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('portal_academico_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf_or_abort($_POST['_csrf'] ?? null);
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function current_user(): ?array
{
    global $pdo;

    if (!is_logged_in()) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT
            users.id,
            users.institutional_id,
            users.full_name,
            users.email,
            users.status,
            users.must_change_password,
            GROUP_CONCAT(roles.code) AS roles
        FROM users
        INNER JOIN user_roles ON user_roles.user_id = users.id
        INNER JOIN roles ON roles.id = user_roles.role_id
        WHERE users.id = ?
        GROUP BY users.id
        LIMIT 1
    ");

    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        return null;
    }

    $user['roles_array'] = $user['roles'] ? explode(',', $user['roles']) : [];

    return $user;
}

function user_has_role(string $role): bool
{
    $user = current_user();

    if (!$user) {
        return false;
    }

    return in_array($role, $user['roles_array'], true);
}

function is_password_change_route(): bool
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    return str_ends_with($script, '/pages/alterar_password.php')
        || str_ends_with($script, '/actions/alterar_password.php')
        || str_ends_with($script, '/logout.php');
}

function is_access_denied_route(): bool
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    return str_ends_with($script, '/pages/acesso_negado.php');
}

function requested_path_for_log(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';

    if ($uri === '') {
        return 'Caminho não identificado';
    }

    return $uri;
}

function home_url_by_roles(array $roles): string
{
    if (in_array('admin', $roles, true)) {
        return APP_URL . '/dashboards/admin.php';
    }

    if (in_array('secretaria', $roles, true)) {
        return APP_URL . '/dashboards/secretaria.php';
    }

    if (in_array('aluno', $roles, true)) {
        return APP_URL . '/dashboards/aluno.php';
    }

    if (in_array('professor', $roles, true)) {
        return APP_URL . '/dashboards/professor.php';
    }

    if (in_array('coordenador', $roles, true)) {
        return APP_URL . '/dashboards/coordenador.php';
    }

    if (in_array('funcionario', $roles, true)) {
        return APP_URL . '/dashboards/funcionario.php';
    }

    if (in_array('direcao', $roles, true)) {
        return APP_URL . '/dashboards/direcao.php';
    }

    return APP_URL . '/login.php?erro=perfil';
}

function redirect_by_role(array $roles): void
{
    redirect(home_url_by_roles($roles));
}

function log_security_event(int $userId, string $action, string $description): void
{
    global $pdo;

    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (
            user_id,
            action,
            description,
            ip_address
        ) VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $userId,
        $action,
        $description,
        current_ip()
    ]);
}

function deny_access(): void
{
    $user = current_user();

    if ($user && !is_access_denied_route()) {
        log_security_event(
            (int) $user['id'],
            'ACCESS_DENIED',
            'Tentativa de acesso negado ao caminho: ' . requested_path_for_log()
        );
    }

    redirect(APP_URL . '/pages/acesso_negado.php');
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect(APP_URL . '/login.php?erro=sessao');
    }

    $user = current_user();

    if (!$user) {
        logout_user();
        redirect(APP_URL . '/login.php?erro=sessao');
    }

    if (($user['status'] ?? '') !== 'active') {
        logout_user();
        redirect(APP_URL . '/login.php?erro=bloqueado');
    }

    if ((int) ($user['must_change_password'] ?? 0) === 1 && !is_password_change_route()) {
        redirect(APP_URL . '/pages/alterar_password.php?aviso=inicial');
    }
}

function require_role(string $role): void
{
    require_login();

    if (!user_has_role($role)) {
        deny_access();
    }
}

function require_any_role(array $roles): void
{
    require_login();

    $user = current_user();

    if (!$user) {
        redirect(APP_URL . '/login.php?erro=sessao');
    }

    foreach ($roles as $role) {
        if (in_array($role, $user['roles_array'], true)) {
            return;
        }
    }

    deny_access();
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
