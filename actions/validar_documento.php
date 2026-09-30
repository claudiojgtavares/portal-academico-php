<?php

require_once __DIR__ . '/../includes/auth.php';

require_any_role(['admin', 'secretaria', 'funcionario']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/pages/validar_documentos.php');
}

$currentUser = current_user();

$requestId = (int) ($_POST['request_id'] ?? 0);
$status = trim($_POST['status'] ?? '');
$notes = trim($_POST['notes'] ?? '');

$allowedStatus = [
    'pending',
    'in_review',
    'ready',
    'rejected',
    'delivered'
];

if ($requestId <= 0 || !in_array($status, $allowedStatus, true)) {
    $_SESSION['flash_error'] = 'Pedido inválido ou estado inválido.';
    redirect(APP_URL . '/pages/validar_documentos.php');
}

function validar_documento_status_label(string $status): string
{
    $labels = [
        'pending' => 'Pendente',
        'in_review' => 'Em análise',
        'ready' => 'Pronto',
        'rejected' => 'Rejeitado',
        'delivered' => 'Entregue'
    ];

    return $labels[$status] ?? $status;
}

try {
    $pdo->beginTransaction();

    $requestStmt = $pdo->prepare("
        SELECT
            document_requests.id,
            document_requests.document_type,
            document_requests.file_path,
            students.student_code,
            users.id AS student_user_id,
            users.full_name
        FROM document_requests
        INNER JOIN students ON students.id = document_requests.student_id
        INNER JOIN users ON users.id = students.user_id
        WHERE document_requests.id = ?
        LIMIT 1
    ");

    $requestStmt->execute([$requestId]);
    $request = $requestStmt->fetch();

    if (!$request) {
        throw new Exception('Pedido de documento não encontrado.');
    }

    $relativePath = $request['file_path'];

    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro ao receber o ficheiro do documento.');
        }

        $file = $_FILES['document_file'];
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new Exception('Formato inválido. Use PDF, JPG ou PNG.');
        }

        if ((int) $file['size'] > 5 * 1024 * 1024) {
            throw new Exception('O ficheiro não pode ultrapassar 5MB.');
        }

        $uploadDir = __DIR__ . '/../assets/uploads/documentos_emitidos/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0750, true);
        }

        $safeStudentCode = preg_replace('/[^a-zA-Z0-9_-]/', '_', $request['student_code']);
        $safeOriginalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);

        $newFileName = $safeStudentCode . '_DOCUMENTO_' . $requestId . '_' . uniqid() . '_' . $safeOriginalName;
        $destination = $uploadDir . $newFileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Erro ao guardar o ficheiro do documento.');
        }

        $relativePath = 'assets/uploads/documentos_emitidos/' . $newFileName;
    }

    $updateStmt = $pdo->prepare("
        UPDATE document_requests
        SET
            status = ?,
            notes = ?,
            file_path = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    $updateStmt->execute([
        $status,
        $notes !== '' ? $notes : null,
        $relativePath,
        $requestId
    ]);

    $statusLabel = validar_documento_status_label($status);

    $notificationType = 'info';

    if ($status === 'ready' || $status === 'delivered') {
        $notificationType = 'success';
    }

    if ($status === 'rejected') {
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

    $message = 'O estado do seu pedido de "' . $request['document_type'] . '" foi atualizado para: ' . $statusLabel . '.';

    if ($notes !== '') {
        $message .= ' Observação da secretaria: ' . $notes;
    }

    $insertNotification->execute([
        $request['student_user_id'],
        'Pedido de documento atualizado',
        $message,
        $notificationType
    ]);

    $insertLog = $pdo->prepare("
        INSERT INTO activity_logs (
            user_id,
            action,
            description,
            ip_address
        ) VALUES (?, 'DOCUMENTO_ATUALIZADO', ?, ?)
    ");

    $insertLog->execute([
        $currentUser['id'],
        'Pedido de documento #' . $requestId . ' atualizado para ' . $statusLabel . ' — ' . $request['full_name'],
        current_ip()
    ]);

    $pdo->commit();

    $_SESSION['flash_success'] = 'Pedido de documento atualizado com sucesso.';

    redirect(APP_URL . '/pages/validar_documentos.php');
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['flash_error'] = 'Erro ao atualizar pedido de documento: ' . $error->getMessage();

    redirect(APP_URL . '/pages/validar_documentos.php');
}