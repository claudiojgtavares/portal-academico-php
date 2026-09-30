<?php

require_once __DIR__ . '/../includes/auth.php';

require_role('aluno');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/pages/pagamentos.php');
}

$user = current_user();

$feeId = (int) ($_POST['fee_id'] ?? 0);
$paymentMethod = trim($_POST['payment_method'] ?? '');
$bankReference = trim($_POST['bank_reference'] ?? '');

if ($feeId <= 0 || $paymentMethod === '') {
    $_SESSION['flash_error'] = 'Preencha os dados do pagamento.';
    redirect(APP_URL . '/pages/pagamentos.php');
}

if (!isset($_FILES['proof_file']) || $_FILES['proof_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash_error'] = 'Envie o comprovativo de pagamento.';
    redirect(APP_URL . '/pages/pagamentos.php');
}

try {
    $pdo->beginTransaction();

    $studentStmt = $pdo->prepare("
        SELECT id, student_code
        FROM students
        WHERE user_id = ?
        LIMIT 1
    ");

    $studentStmt->execute([$user['id']]);
    $student = $studentStmt->fetch();

    if (!$student) {
        throw new Exception('Registo de aluno não encontrado.');
    }

    $feeStmt = $pdo->prepare("
        SELECT id, amount_cve, title, month_reference
        FROM fees
        WHERE id = ?
          AND status = 'ativa'
        LIMIT 1
    ");

    $feeStmt->execute([$feeId]);
    $fee = $feeStmt->fetch();

    if (!$fee) {
        throw new Exception('Propina não encontrada ou inativa.');
    }

    $file = $_FILES['proof_file'];
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        throw new Exception('Formato inválido. Use PDF, JPG ou PNG.');
    }

    if ((int) $file['size'] > 5 * 1024 * 1024) {
        throw new Exception('O ficheiro não pode ultrapassar 5MB.');
    }

    $uploadDir = __DIR__ . '/../assets/uploads/comprovativos/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0750, true);
    }

    $safeStudentCode = preg_replace('/[^a-zA-Z0-9_-]/', '_', $student['student_code']);
    $safeOriginalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);

    $newFileName = $safeStudentCode . '_PROPINA_' . $feeId . '_' . uniqid() . '_' . $safeOriginalName;
    $destination = $uploadDir . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Erro ao guardar o comprovativo.');
    }

    $relativePath = 'assets/uploads/comprovativos/' . $newFileName;

    $existingPaymentStmt = $pdo->prepare("
        SELECT id
        FROM payments
        WHERE student_id = ?
          AND fee_id = ?
        LIMIT 1
    ");

    $existingPaymentStmt->execute([$student['id'], $feeId]);
    $existingPayment = $existingPaymentStmt->fetch();

    if ($existingPayment) {
        $updatePayment = $pdo->prepare("
            UPDATE payments
            SET
                amount_cve = ?,
                payment_method = ?,
                bank_reference = ?,
                proof_file = ?,
                status = 'em_analise',
                submitted_at = NOW(),
                notes = NULL
            WHERE id = ?
        ");

        $updatePayment->execute([
            $fee['amount_cve'],
            $paymentMethod,
            $bankReference !== '' ? $bankReference : null,
            $relativePath,
            $existingPayment['id']
        ]);
    } else {
        $insertPayment = $pdo->prepare("
            INSERT INTO payments (
                student_id,
                fee_id,
                amount_cve,
                payment_method,
                bank_reference,
                proof_file,
                status,
                submitted_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'em_analise', NOW())
        ");

        $insertPayment->execute([
            $student['id'],
            $feeId,
            $fee['amount_cve'],
            $paymentMethod,
            $bankReference !== '' ? $bankReference : null,
            $relativePath
        ]);
    }

    $insertNotification = $pdo->prepare("
        INSERT INTO notifications (
            user_id,
            title,
            message,
            type
        ) VALUES (?, ?, ?, 'info')
    ");

    $insertNotification->execute([
        $user['id'],
        'Comprovativo enviado',
        'O comprovativo da propina de ' . $fee['month_reference'] . ' foi enviado e está em análise.'
    ]);

    $insertLog = $pdo->prepare("
        INSERT INTO activity_logs (
            user_id,
            action,
            description,
            ip_address
        ) VALUES (?, 'PAYMENT_PROOF_SUBMITTED', ?, ?)
    ");

    $insertLog->execute([
        $user['id'],
        'Comprovativo enviado para ' . $fee['title'] . ' — ' . $fee['month_reference'],
        current_ip()
    ]);

    $pdo->commit();

    $_SESSION['flash_success'] = 'Comprovativo enviado com sucesso. O pagamento está em análise.';

    redirect(APP_URL . '/pages/pagamentos.php');
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['flash_error'] = 'Erro ao enviar comprovativo: ' . $error->getMessage();

    redirect(APP_URL . '/pages/pagamentos.php');
}