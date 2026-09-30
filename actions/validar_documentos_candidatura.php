<?php

require_once __DIR__ . '/../includes/auth.php';

require_any_role(['admin', 'secretaria']);

$user = current_user();

$applicationId = (int) ($_POST['application_id'] ?? ($_POST['id'] ?? 0));
$message = trim($_POST['message'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $applicationId <= 0) {
    $_SESSION['flash_error'] = 'Pedido inválido.';
    redirect(APP_URL . '/pages/candidaturas.php');
}

if ($message === '') {
    $message = 'Documentos analisados e validados pela secretaria académica.';
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

    if (!in_array($application['status'], ['pending', 'in_review', 'missing_documents'], true)) {
        throw new Exception('Os documentos desta candidatura não podem ser validados neste estado.');
    }

    $oldStatus = $application['status'];

    $updateStmt = $pdo->prepare("
        UPDATE applications
        SET status = 'documents_validated',
            rejection_reason = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    $updateStmt->execute([
        $message,
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
        'documents_validated',
        'APPLICATION_DOCUMENTS_VALIDATED',
        $message,
        current_ip()
    ]);

    $logStmt = $pdo->prepare("
        INSERT INTO activity_logs (
            user_id,
            action,
            description,
            ip_address
        ) VALUES (?, 'APPLICATION_DOCUMENTS_VALIDATED', ?, ?)
    ");

    $logStmt->execute([
        (int) $user['id'],
        'Documentos validados para candidatura ' . $application['application_code'] . ' — ' . $application['full_name'],
        current_ip()
    ]);

    $_SESSION['flash_success'] = 'Documentos validados com sucesso. A candidatura já pode seguir para criação de credenciais pelo administrador.';

    redirect(APP_URL . '/pages/candidatura_detalhes.php?id=' . $applicationId);
} catch (Throwable $error) {
    $_SESSION['flash_error'] = $error->getMessage();

    redirect(APP_URL . '/pages/candidatura_detalhes.php?id=' . $applicationId);
}