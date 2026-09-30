<?php

require_once __DIR__ . '/../includes/auth.php';

require_any_role(['admin', 'secretaria', 'funcionario']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/pages/validar_pagamentos.php');
}

$currentUser = current_user();

$paymentId = (int) ($_POST['payment_id'] ?? 0);
$status = trim($_POST['status'] ?? '');
$notes = trim($_POST['notes'] ?? '');

$allowedStatus = [
    'em_analise',
    'confirmado',
    'rejeitado',
    'em_atraso'
];

if ($paymentId <= 0 || !in_array($status, $allowedStatus, true)) {
    $_SESSION['flash_error'] = 'Pagamento inválido ou estado inválido.';
    redirect(APP_URL . '/pages/validar_pagamentos.php');
}

function validar_pagamento_status_label(string $status): string
{
    $labels = [
        'em_analise' => 'Em análise',
        'confirmado' => 'Confirmado',
        'rejeitado' => 'Rejeitado',
        'em_atraso' => 'Em atraso'
    ];

    return $labels[$status] ?? $status;
}

try {
    $pdo->beginTransaction();

    $paymentStmt = $pdo->prepare("
        SELECT
            payments.id,
            payments.status,
            payments.amount_cve,
            fees.title,
            fees.month_reference,
            students.student_code,
            users.id AS student_user_id,
            users.full_name
        FROM payments
        INNER JOIN fees ON fees.id = payments.fee_id
        INNER JOIN students ON students.id = payments.student_id
        INNER JOIN users ON users.id = students.user_id
        WHERE payments.id = ?
        LIMIT 1
    ");

    $paymentStmt->execute([$paymentId]);
    $payment = $paymentStmt->fetch();

    if (!$payment) {
        throw new Exception('Pagamento não encontrado.');
    }

    $updateStmt = $pdo->prepare("
        UPDATE payments
        SET
            status = ?,
            notes = ?
        WHERE id = ?
    ");

    $updateStmt->execute([
        $status,
        $notes !== '' ? $notes : null,
        $paymentId
    ]);

    $statusLabel = validar_pagamento_status_label($status);

    $notificationType = 'info';

    if ($status === 'confirmado') {
        $notificationType = 'success';
    }

    if ($status === 'rejeitado' || $status === 'em_atraso') {
        $notificationType = 'warning';
    }

    $insertNotification = $pdo->prepare("
        INSERT INTO notifications (
            user_id,
            title,
            message,
            type
        ) VALUES (?, ?, ?, ?)
    ");

    $message = 'O pagamento da propina "' . $payment['title'] . ' — ' . $payment['month_reference'] . '" foi atualizado para: ' . $statusLabel . '.';

    if ($notes !== '') {
        $message .= ' Observação da secretaria: ' . $notes;
    }

    $insertNotification->execute([
        $payment['student_user_id'],
        'Pagamento atualizado',
        $message,
        $notificationType
    ]);

    $insertLog = $pdo->prepare("
        INSERT INTO activity_logs (
            user_id,
            action,
            description,
            ip_address
        ) VALUES (?, 'PAGAMENTO_VALIDADO', ?, ?)
    ");

    $insertLog->execute([
        $currentUser['id'],
        'Pagamento #' . $paymentId . ' atualizado para ' . $statusLabel . ' — ' . $payment['full_name'],
        current_ip()
    ]);

    $pdo->commit();

    $_SESSION['flash_success'] = 'Pagamento atualizado com sucesso.';

    redirect(APP_URL . '/pages/validar_pagamentos.php');
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['flash_error'] = 'Erro ao atualizar pagamento: ' . $error->getMessage();

    redirect(APP_URL . '/pages/validar_pagamentos.php');
}