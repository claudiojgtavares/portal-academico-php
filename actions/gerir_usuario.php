<?php

require_once __DIR__ . '/../includes/auth.php';

require_any_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/pages/usuarios.php');
}

$admin = current_user();
$userId = (int) ($_POST['user_id'] ?? 0);
$acao = $_POST['acao'] ?? '';

if ($userId <= 0 || $acao === '') {
    $_SESSION['flash_error'] = 'Pedido inválido.';
    redirect(APP_URL . '/pages/usuarios.php');
}

function generate_temp_password(): string
{
    $prefix = 'Instituto Horizonte@';
    $number = random_int(1000, 9999);

    return $prefix . $number;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            id,
            institutional_id,
            full_name,
            email,
            status
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$userId]);
    $targetUser = $stmt->fetch();

    if (!$targetUser) {
        throw new Exception('Utilizador não encontrado.');
    }

    if ($acao === 'reset_password') {
        $tempPassword = generate_temp_password();
        $hash = password_hash($tempPassword, PASSWORD_DEFAULT);

        $updateStmt = $pdo->prepare("
            UPDATE users
            SET
                password_hash = ?,
                must_change_password = 1,
                status = 'active',
                updated_at = NOW()
            WHERE id = ?
        ");

        $updateStmt->execute([
            $hash,
            $userId
        ]);

        $clearStmt = $pdo->prepare("
            DELETE FROM login_attempts
            WHERE identifier = ?
               OR identifier = ?
        ");

        $clearStmt->execute([
            $targetUser['email'],
            $targetUser['institutional_id']
        ]);

        $logStmt = $pdo->prepare("
            INSERT INTO activity_logs (
                user_id,
                action,
                description,
                ip_address
            ) VALUES (?, 'USER_PASSWORD_RESET', ?, ?)
        ");

        $logStmt->execute([
            (int) $admin['id'],
            'Administrador repôs a palavra-passe de ' . $targetUser['full_name'] . '.',
            current_ip()
        ]);

        $_SESSION['temp_credentials'] = [
            'name' => $targetUser['full_name'],
            'email' => $targetUser['email'],
            'institutional_id' => $targetUser['institutional_id'],
            'password' => $tempPassword
        ];

        $_SESSION['flash_success'] = 'Palavra-passe reposta com sucesso. Copie as credenciais temporárias apresentadas.';
        redirect(APP_URL . '/pages/usuarios.php');
    }

    if ($acao === 'clear_attempts') {
        $clearStmt = $pdo->prepare("
            DELETE FROM login_attempts
            WHERE identifier = ?
               OR identifier = ?
        ");

        $clearStmt->execute([
            $targetUser['email'],
            $targetUser['institutional_id']
        ]);

        $logStmt = $pdo->prepare("
            INSERT INTO activity_logs (
                user_id,
                action,
                description,
                ip_address
            ) VALUES (?, 'LOGIN_ATTEMPTS_CLEARED', ?, ?)
        ");

        $logStmt->execute([
            (int) $admin['id'],
            'Administrador limpou tentativas de login de ' . $targetUser['full_name'] . '.',
            current_ip()
        ]);

        $_SESSION['flash_success'] = 'Tentativas de login limpas com sucesso.';
        redirect(APP_URL . '/pages/usuarios.php');
    }

    if (in_array($acao, ['activate', 'block', 'inactive'], true)) {
        $newStatus = [
            'activate' => 'active',
            'block' => 'blocked',
            'inactive' => 'inactive'
        ][$acao];

        $updateStmt = $pdo->prepare("
            UPDATE users
            SET
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $updateStmt->execute([
            $newStatus,
            $userId
        ]);

        $logStmt = $pdo->prepare("
            INSERT INTO activity_logs (
                user_id,
                action,
                description,
                ip_address
            ) VALUES (?, 'USER_STATUS_UPDATED', ?, ?)
        ");

        $logStmt->execute([
            (int) $admin['id'],
            'Administrador alterou o estado de ' . $targetUser['full_name'] . ' para ' . $newStatus . '.',
            current_ip()
        ]);

        $_SESSION['flash_success'] = 'Estado da conta atualizado com sucesso.';
        redirect(APP_URL . '/pages/usuarios.php');
    }

    if ($acao === 'force_password_change') {
        $updateStmt = $pdo->prepare("
            UPDATE users
            SET
                must_change_password = 1,
                updated_at = NOW()
            WHERE id = ?
        ");

        $updateStmt->execute([$userId]);

        $logStmt = $pdo->prepare("
            INSERT INTO activity_logs (
                user_id,
                action,
                description,
                ip_address
            ) VALUES (?, 'PASSWORD_CHANGE_REQUIRED', ?, ?)
        ");

        $logStmt->execute([
            (int) $admin['id'],
            'Administrador exigiu alteração de palavra-passe para ' . $targetUser['full_name'] . '.',
            current_ip()
        ]);

        $_SESSION['flash_success'] = 'O utilizador será obrigado a alterar a palavra-passe no próximo acesso.';
        redirect(APP_URL . '/pages/usuarios.php');
    }

    throw new Exception('Ação não reconhecida.');
} catch (Throwable $error) {
    $_SESSION['flash_error'] = 'Erro: ' . $error->getMessage();
    redirect(APP_URL . '/pages/usuarios.php');
}