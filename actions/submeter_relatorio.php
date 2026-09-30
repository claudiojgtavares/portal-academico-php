<?php

require_once __DIR__ . '/../includes/auth.php';

require_login();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/pages/relatorios.php');
}

function report_current_role(array $user): string
{
    $roles = $user['roles_array'] ?? [];

    foreach (['admin', 'direcao', 'secretaria', 'coordenador', 'funcionario', 'professor', 'aluno'] as $role) {
        if (in_array($role, $roles, true)) {
            return $role;
        }
    }

    return 'aluno';
}

function report_notify_direction(PDO $pdo, string $title, string $message): void
{
    try {
        if (!function_exists('portal_db_table_exists')) {
            require_once __DIR__ . '/../includes/portal_ui_helpers.php';
        }

        if (!portal_db_table_exists($pdo, 'notifications')) {
            return;
        }

        $directors = portal_rows($pdo, "
            SELECT id
            FROM users
            WHERE role = 'direcao'
               OR roles LIKE '%direcao%'
               OR roles LIKE '%direção%'
        ");

        if (empty($directors)) {
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
            VALUES (?, ?, ?, 'info', 0, NOW())
        ");

        foreach ($directors as $director) {
            $stmt->execute([
                (int) $director['id'],
                $title,
                $message
            ]);
        }
    } catch (Throwable $error) {
        // A notificação não deve impedir a submissão do relatório.
    }
}

$role = report_current_role($user);
$allowedRoles = ['admin', 'secretaria', 'coordenador'];

if (!in_array($role, $allowedRoles, true)) {
    $_SESSION['flash_error'] = 'A sua conta não tem autorização para submeter relatórios.';
    redirect(APP_URL . '/pages/relatorios.php');
}

if (!function_exists('portal_db_table_exists')) {
    require_once __DIR__ . '/../includes/portal_ui_helpers.php';
}

if (!portal_db_table_exists($pdo, 'academic_reports')) {
    $_SESSION['flash_error'] = 'A tabela academic_reports ainda não existe. Execute o ficheiro database/2026_05_20_academic_reports.sql.';
    redirect(APP_URL . '/pages/relatorios.php');
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = trim($_POST['category'] ?? '');
$courseId = (int) ($_POST['related_course_id'] ?? 0);
$academicYearId = (int) ($_POST['academic_year_id'] ?? 0);

if ($title === '') {
    $_SESSION['flash_error'] = 'Informe o título do relatório.';
    redirect(APP_URL . '/pages/relatorios.php');
}

if ($category === '') {
    $category = $role === 'secretaria' ? 'documento_secretaria' : 'relatorio_coordenacao';
}

if (!isset($_FILES['report_file']) || $_FILES['report_file']['error'] === UPLOAD_ERR_NO_FILE) {
    $_SESSION['flash_error'] = 'Selecione o ficheiro do relatório.';
    redirect(APP_URL . '/pages/relatorios.php');
}

if ($_FILES['report_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash_error'] = 'Não foi possível carregar o ficheiro.';
    redirect(APP_URL . '/pages/relatorios.php');
}

$allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'txt', 'zip', 'rar'];
$originalName = (string) $_FILES['report_file']['name'];
$tmpName = (string) $_FILES['report_file']['tmp_name'];
$fileSize = (int) $_FILES['report_file']['size'];
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($extension, $allowedExtensions, true)) {
    $_SESSION['flash_error'] = 'Formato inválido. Use PDF, DOCX, XLSX, PPTX, imagem, TXT, ZIP ou RAR.';
    redirect(APP_URL . '/pages/relatorios.php');
}

if ($fileSize > 20 * 1024 * 1024) {
    $_SESSION['flash_error'] = 'O ficheiro ultrapassa o limite de 20MB.';
    redirect(APP_URL . '/pages/relatorios.php');
}

$uploadDir = __DIR__ . '/../assets/uploads/relatorios/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0750, true);
}

$safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
$newFileName = 'REL_' . date('Ymd_His') . '_' . uniqid('', true) . '_' . $safeName;
$destination = $uploadDir . $newFileName;

if (!move_uploaded_file($tmpName, $destination)) {
    $_SESSION['flash_error'] = 'Não foi possível guardar o ficheiro enviado.';
    redirect(APP_URL . '/pages/relatorios.php');
}

$relativePath = 'assets/uploads/relatorios/' . $newFileName;
$mimeType = $_FILES['report_file']['type'] ?? null;

try {
    $stmt = $pdo->prepare("
        INSERT INTO academic_reports (
            submitted_by,
            target_role,
            title,
            description,
            category,
            related_course_id,
            academic_year_id,
            original_name,
            file_path,
            mime_type,
            file_size,
            status,
            created_at
        ) VALUES (?, 'direcao', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted', NOW())
    ");

    $stmt->execute([
        (int) $user['id'],
        $title,
        $description !== '' ? $description : null,
        $category,
        $courseId > 0 ? $courseId : null,
        $academicYearId > 0 ? $academicYearId : null,
        $originalName,
        $relativePath,
        $mimeType,
        $fileSize
    ]);

    $reportId = (int) $pdo->lastInsertId();

    $sender = $user['full_name'] ?? 'Utilizador';
    $kind = $category === 'documento_secretaria' ? 'documento da secretaria' : 'relatório de coordenação';

    report_notify_direction(
        $pdo,
        'Novo ' . $kind . ' submetido',
        $sender . ' submeteu "' . $title . '". Abra a área de relatórios para consultar ou baixar o ficheiro.'
    );

    $_SESSION['flash_success'] = 'Relatório submetido com sucesso.';
    redirect(APP_URL . '/pages/relatorios.php#relatorio-' . $reportId);
} catch (Throwable $error) {
    $_SESSION['flash_error'] = 'Não foi possível registar o relatório. Verifique a base de dados.';
    redirect(APP_URL . '/pages/relatorios.php');
}
