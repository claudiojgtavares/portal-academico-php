<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_ui_helpers.php';
require_login();

$user = current_user();
$role = portal_user_role($user);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/pages/documentos.php');
}

if ($role !== 'aluno') {
    $_SESSION['flash_error'] = 'Apenas o estudante pode solicitar documentos nesta área.';
    redirect(APP_URL . '/pages/documentos.php');
}

$student = portal_one($pdo, "SELECT id FROM students WHERE user_id = ? LIMIT 1", [(int) $user['id']]);
$studentId = (int) ($student['id'] ?? 0);
$documentType = trim($_POST['document_type'] ?? '');
$purpose = trim($_POST['purpose'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if ($studentId <= 0 || $documentType === '' || $purpose === '') {
    $_SESSION['flash_error'] = 'Preencha o tipo de documento e a finalidade do pedido.';
    redirect(APP_URL . '/pages/documentos.php');
}

try {
    $stmt = $pdo->prepare("INSERT INTO document_requests (student_id, document_type, purpose, status, notes, requested_at) VALUES (?, ?, ?, 'pending', ?, NOW())");
    $stmt->execute([$studentId, $documentType, $purpose, $notes !== '' ? $notes : null]);

    if (portal_db_table_exists($pdo, 'notifications')) {
        $secretariaUsers = portal_rows($pdo, "SELECT id FROM users WHERE roles LIKE '%secretaria%' OR role = 'secretaria' LIMIT 20");
        foreach ($secretariaUsers as $sec) {
            $n = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, 'info', 0, NOW())");
            $n->execute([(int) $sec['id'], 'Novo pedido de documento', 'O estudante ' . ($user['full_name'] ?? '') . ' solicitou: ' . $documentType . '.',]);
        }
    }

    $_SESSION['flash_success'] = 'Pedido de documento enviado com sucesso.';
} catch (Throwable $error) {
    $_SESSION['flash_error'] = 'Não foi possível registar o pedido: ' . $error->getMessage();
}

redirect(APP_URL . '/pages/documentos.php');
