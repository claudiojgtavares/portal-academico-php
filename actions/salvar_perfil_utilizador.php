<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_ui_helpers.php';

require_any_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/pages/perfis_utilizadores.php');
}

$userId = (int) ($_POST['user_id'] ?? 0);
$roles = $_POST['roles'] ?? [];
$status = trim($_POST['status'] ?? 'active');

$allowedRoles = ['admin', 'secretaria', 'direcao', 'coordenador', 'professor', 'funcionario', 'aluno'];
$roles = array_values(array_intersect($allowedRoles, array_unique(array_map('trim', (array) $roles))));

try {
    if ($userId <= 0) {
        throw new Exception('Utilizador inválido.');
    }

    if (empty($roles)) {
        throw new Exception('Selecione pelo menos um perfil.');
    }

    if (!in_array($status, ['active', 'inactive', 'blocked'], true)) {
        $status = 'active';
    }

    if (!portal_db_table_exists($pdo, 'roles') || !portal_db_table_exists($pdo, 'user_roles')) {
        throw new Exception('As tabelas roles e user_roles são necessárias para gerir perfis.');
    }

    $target = portal_one($pdo, "SELECT id, full_name FROM users WHERE id = ? LIMIT 1", [$userId]);
    if (!$target) {
        throw new Exception('Utilizador não encontrado.');
    }

    $roleRows = portal_rows($pdo, "SELECT id, code FROM roles WHERE code IN ('" . implode("','", array_map('addslashes', $allowedRoles)) . "')");
    $roleIds = [];
    foreach ($roleRows as $roleRow) {
        $roleIds[(string) $roleRow['code']] = (int) $roleRow['id'];
    }

    foreach ($roles as $role) {
        if (empty($roleIds[$role])) {
            throw new Exception('O perfil ' . $role . ' não existe na tabela roles.');
        }
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?");
    $stmt->execute([$userId]);

    $insert = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
    foreach ($roles as $role) {
        $insert->execute([$userId, $roleIds[$role]]);
    }

    $userColumns = portal_db_columns($pdo, 'users');
    $fields = ['status = ?'];
    $params = [$status];
    if (in_array('role', $userColumns, true)) {
        $fields[] = 'role = ?';
        $params[] = $roles[0];
    }
    if (in_array('roles', $userColumns, true)) {
        $fields[] = 'roles = ?';
        $params[] = json_encode($roles, JSON_UNESCAPED_UNICODE);
    }
    if (in_array('updated_at', $userColumns, true)) {
        $fields[] = 'updated_at = NOW()';
    }
    $params[] = $userId;

    $stmt = $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
    $stmt->execute($params);

    $pdo->commit();

    $_SESSION['flash_success'] = 'Perfis do utilizador atualizados com sucesso.';
    redirect(APP_URL . '/pages/perfis_utilizadores.php');
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = $error->getMessage();
    redirect(APP_URL . '/pages/perfis_utilizadores.php');
}
