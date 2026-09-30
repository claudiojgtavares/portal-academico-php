<?php

require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/login.php');
}

$identifier = trim($_POST['identifier'] ?? '');
$password = $_POST['password'] ?? '';
$ipAddress = current_ip();

$maxFailedAttempts = 5;
$blockWindowMinutes = 15;

if ($identifier === '' || $password === '') {
    redirect(APP_URL . '/login.php?erro=credenciais');
}

$recentFailuresStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM login_attempts
    WHERE identifier = ?
      AND success = 0
      AND attempted_at >= DATE_SUB(NOW(), INTERVAL {$blockWindowMinutes} MINUTE)
");

$recentFailuresStmt->execute([$identifier]);
$recentFailures = (int) ($recentFailuresStmt->fetch()['total'] ?? 0);

if ($recentFailures >= $maxFailedAttempts) {
    $blockedUserStmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE email = ? OR institutional_id = ?
        LIMIT 1
    ");

    $blockedUserStmt->execute([$identifier, $identifier]);
    $blockedUser = $blockedUserStmt->fetch();

    if ($blockedUser) {
        $logBlock = $pdo->prepare("
            INSERT INTO activity_logs (
                user_id,
                action,
                description,
                ip_address
            ) VALUES (?, 'LOGIN_TEMPORARILY_BLOCKED', ?, ?)
        ");

        $logBlock->execute([
            $blockedUser['id'],
            'Login temporariamente bloqueado por excesso de tentativas falhadas.',
            $ipAddress
        ]);
    }

    redirect(APP_URL . '/login.php?erro=tentativas');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM users
    WHERE email = ? OR institutional_id = ?
    LIMIT 1
");

$stmt->execute([$identifier, $identifier]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    $logAttempt = $pdo->prepare("
        INSERT INTO login_attempts (
            identifier,
            success,
            ip_address
        ) VALUES (?, 0, ?)
    ");

    $logAttempt->execute([$identifier, $ipAddress]);

    $recentFailuresStmt->execute([$identifier]);
    $recentFailuresAfter = (int) ($recentFailuresStmt->fetch()['total'] ?? 0);

    if ($user && $recentFailuresAfter >= $maxFailedAttempts) {
        $logBlock = $pdo->prepare("
            INSERT INTO activity_logs (
                user_id,
                action,
                description,
                ip_address
            ) VALUES (?, 'LOGIN_TEMPORARILY_BLOCKED', ?, ?)
        ");

        $logBlock->execute([
            $user['id'],
            'Login temporariamente bloqueado por excesso de tentativas falhadas.',
            $ipAddress
        ]);

        redirect(APP_URL . '/login.php?erro=tentativas');
    }

    redirect(APP_URL . '/login.php?erro=credenciais');
}

if ($user['status'] !== 'active') {
    $logAttempt = $pdo->prepare("
        INSERT INTO login_attempts (
            identifier,
            success,
            ip_address
        ) VALUES (?, 0, ?)
    ");

    $logAttempt->execute([$identifier, $ipAddress]);

    redirect(APP_URL . '/login.php?erro=bloqueado');
}

$logAttempt = $pdo->prepare("
    INSERT INTO login_attempts (
        identifier,
        success,
        ip_address
    ) VALUES (?, 1, ?)
");

$logAttempt->execute([$identifier, $ipAddress]);

$updateLogin = $pdo->prepare("
    UPDATE users
    SET last_login_at = NOW()
    WHERE id = ?
");

$updateLogin->execute([$user['id']]);

$logAction = $pdo->prepare("
    INSERT INTO activity_logs (
        user_id,
        action,
        description,
        ip_address
    ) VALUES (?, 'LOGIN', 'Utilizador iniciou sessão.', ?)
");

$logAction->execute([$user['id'], $ipAddress]);

$rolesStmt = $pdo->prepare("
    SELECT roles.code
    FROM roles
    INNER JOIN user_roles ON user_roles.role_id = roles.id
    WHERE user_roles.user_id = ?
");

$rolesStmt->execute([$user['id']]);
$roles = $rolesStmt->fetchAll(PDO::FETCH_COLUMN);

session_regenerate_id(true);

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['institutional_id'] = $user['institutional_id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['email'] = $user['email'];
$_SESSION['roles'] = $roles;

if ((int) $user['must_change_password'] === 1) {
    redirect(APP_URL . '/pages/alterar_password.php?aviso=inicial');
}

redirect_by_role($roles);