<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_ui_helpers.php';

require_login();

$user = current_user();
$reportId = (int) ($_GET['id'] ?? 0);
$download = (int) ($_GET['download'] ?? 0) === 1;

if ($reportId <= 0 || !portal_db_table_exists($pdo, 'academic_reports')) {
    redirect(APP_URL . '/pages/relatorios.php');
}

$report = portal_one($pdo, "
    SELECT academic_reports.*, users.full_name AS submitted_by_name
    FROM academic_reports
    LEFT JOIN users ON users.id = academic_reports.submitted_by
    WHERE academic_reports.id = ?
    LIMIT 1
", [$reportId]);

if (!$report) {
    redirect(APP_URL . '/pages/relatorios.php');
}

$roles = $user['roles_array'] ?? [];
$isAdmin = in_array('admin', $roles, true);
$isDirector = in_array('direcao', $roles, true);
$isSecretaria = in_array('secretaria', $roles, true);
$isCoordinator = in_array('coordenador', $roles, true);
$isOwner = (int) $report['submitted_by'] === (int) $user['id'];

if (!$isAdmin && !$isDirector && !$isOwner && !($isSecretaria && $report['category'] === 'documento_secretaria') && !($isCoordinator && $report['category'] === 'relatorio_coordenacao')) {
    redirect(APP_URL . '/pages/acesso_negado.php');
}

$filePath = (string) ($report['file_path'] ?? '');
$absolutePath = realpath(__DIR__ . '/../' . $filePath);
$baseUploads = realpath(__DIR__ . '/../assets/uploads/relatorios');

if (!$absolutePath || !$baseUploads || strpos($absolutePath, $baseUploads) !== 0 || !is_file($absolutePath)) {
    $_SESSION['flash_error'] = 'Ficheiro não encontrado.';
    redirect(APP_URL . '/pages/relatorios.php');
}

try {
    $stmt = $pdo->prepare("
        UPDATE academic_reports
        SET status = CASE WHEN status = 'submitted' THEN 'reviewed' ELSE status END,
            reviewed_by = COALESCE(reviewed_by, ?),
            reviewed_at = COALESCE(reviewed_at, NOW())
        WHERE id = ?
    ");
    $stmt->execute([(int) $user['id'], $reportId]);
} catch (Throwable $error) {}

$originalName = (string) ($report['original_name'] ?? basename($absolutePath));
$mime = (string) ($report['mime_type'] ?? '');
if ($mime === '') {
    $mime = 'application/octet-stream';
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($absolutePath));
header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . basename($originalName) . '"');
header('X-Content-Type-Options: nosniff');

readfile($absolutePath);
exit;
