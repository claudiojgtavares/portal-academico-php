<?php

require_once __DIR__ . '/../includes/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/pages/alterar_password.php');
}

$user = current_user();

if (!$user) {
    redirect(APP_URL . '/login.php?erro=sessao');
}

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

function password_policy_is_valid(string $password): bool
{
    if (strlen($password) < 8) {
        return false;
    }

    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }

    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }

    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }

    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return false;
    }

    return true;
}

try {
    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        throw new Exception('Preencha todos os campos.');
    }

    if ($newPassword !== $confirmPassword) {
        throw new Exception('A confirmação da nova palavra-passe não corresponde.');
    }

    if (!password_policy_is_valid($newPassword)) {
        throw new Exception('A nova palavra-passe deve ter pelo menos 8 caracteres, letra maiúscula, letra minúscula, número e símbolo.');
    }

    $stmt = $pdo->prepare("
        SELECT password_hash
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([(int) $user['id']]);
    $dbUser = $stmt->fetch();

    if (!$dbUser) {
        throw new Exception('Utilizador não encontrado.');
    }

    if (!password_verify($currentPassword, $dbUser['password_hash'])) {
        throw new Exception('A palavra-passe atual está incorreta.');
    }

    if (password_verify($newPassword, $dbUser['password_hash'])) {
        throw new Exception('A nova palavra-passe deve ser diferente da palavra-passe atual.');
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $updateStmt = $pdo->prepare("
        UPDATE users
        SET
            password_hash = ?,
            must_change_password = 0,
            updated_at = NOW()
        WHERE id = ?
    ");

    $updateStmt->execute([
        $newHash,
        (int) $user['id']
    ]);

    $logStmt = $pdo->prepare("
        INSERT INTO activity_logs (
            user_id,
            action,
            description,
            ip_address
        ) VALUES (?, 'PASSWORD_CHANGED', 'Utilizador alterou a palavra-passe.', ?)
    ");

    $logStmt->execute([
        (int) $user['id'],
        current_ip()
    ]);

    logout_user();

    redirect(APP_URL . '/login.php?sucesso=password_alterada');
} catch (Throwable $error) {
    $_SESSION['flash_error'] = $error->getMessage();

    redirect(APP_URL . '/pages/alterar_password.php');
}