<?php

require_once __DIR__ . '/../includes/auth.php';

require_any_role(['admin', 'secretaria']);

$user = current_user();

$applicationId = (int) ($_POST['application_id'] ?? ($_POST['id'] ?? 0));
$reason = trim($_POST['reason'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $applicationId <= 0) {
    $_SESSION['flash_error'] = 'Pedido inválido.';
    redirect(APP_URL . '/pages/candidaturas.php');
}

if ($reason === '') {
    $reason = 'Candidatura rejeitada pela secretaria/administração.';
}

try {
    $stmt = $pdo->prepare("
        SELECT id, application_code, full_name, status
        FROM applications
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$applicationId]);
    $application = $stmt->fetch();

    if (!$application) {
        throw new Exception('Candidatura não encontrada.');
    }

    if (in_array($application['status'], ['approved', 'credentials_sent'], true)) {
        throw new Exception('Esta candidatura já foi aprovada e não pode ser rejeitada diretamente.');
    }

    if ($application['status'] === 'rejected') {
        throw new Exception('Esta candidatura já está rejeitada.');
    }

    $oldStatus = $application['status'];

    $updateStmt = $pdo->prepare("
        UPDATE applications
        SET status = 'rejected',
            rejection_reason = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    $updateStmt->execute([
        $reason,
        $applicationId
    ]);

    $historyStmt = $pdo->prepare("
        INSERT INTO application_status_history (
            application_id,
            user_id,
            old_status,
            new_status,
            action,
            message,
            ip_address
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $historyStmt->execute([
        $applicationId,
        (int) $user['id'],
        $oldStatus,
        'rejected',
        'APPLICATION_REJECTED',
        $reason,
        current_ip()
    ]);

    $logStmt = $pdo->prepare("
        INSERT INTO activity_logs (
            user_id,
            action,
            description,
            ip_address
        ) VALUES (?, 'APPLICATION_REJECTED', ?, ?)
    ");

    $logStmt->execute([
        (int) $user['id'],
        'Candidatura rejeitada: ' . $application['application_code'] . ' — ' . $application['full_name'] . '. Motivo: ' . $reason,
        current_ip()
    ]);

    $_SESSION['flash_success'] = 'Candidatura rejeitada com sucesso.';

    redirect(APP_URL . '/pages/candidatura_detalhes.php?id=' . $applicationId);
} catch (Throwable $error) {
    $_SESSION['flash_error'] = $error->getMessage();

    redirect(APP_URL . '/pages/candidatura_detalhes.php?id=' . $applicationId);
}