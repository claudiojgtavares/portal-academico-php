<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/candidatura.php');
}

verify_csrf_or_abort($_POST['_csrf'] ?? null);

$fullName = trim($_POST['full_name'] ?? '');
$birthDate = trim($_POST['birth_date'] ?? '');
$documentType = trim($_POST['document_type'] ?? '');
$documentNumber = trim($_POST['document_number'] ?? '');
$personalEmail = trim($_POST['personal_email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$courseId = (int) ($_POST['course_id'] ?? 0);
$academicYearId = (int) ($_POST['academic_year_id'] ?? 0);
$observations = trim($_POST['notes'] ?? ($_POST['observations'] ?? ''));

if (
    $fullName === '' ||
    $birthDate === '' ||
    $documentType === '' ||
    $documentNumber === '' ||
    $personalEmail === '' ||
    $phone === '' ||
    $courseId <= 0 ||
    $academicYearId <= 0
) {
    redirect(APP_URL . '/candidatura.php?erro=campos');
}

if (!filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
    redirect(APP_URL . '/candidatura.php?erro=email');
}

$birthTimestamp = strtotime($birthDate);

if (!$birthTimestamp) {
    redirect(APP_URL . '/candidatura.php?erro=data');
}

function candidatura_table_exists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $error) {
        return false;
    }
}

function candidatura_columns(PDO $pdo, string $table): array
{
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
        $rows = $stmt->fetchAll();

        return array_map(static function ($row) {
            return $row['Field'];
        }, $rows);
    } catch (Throwable $error) {
        return [];
    }
}

function candidatura_insert_flexible(PDO $pdo, string $table, array $data): int
{
    $columns = candidatura_columns($pdo, $table);
    $filtered = [];

    foreach ($data as $key => $value) {
        if (in_array($key, $columns, true)) {
            $filtered[$key] = $value;
        }
    }

    if (empty($filtered)) {
        return 0;
    }

    $names = array_keys($filtered);
    $placeholders = array_fill(0, count($names), '?');

    $sql = "
        INSERT INTO `{$table}` (`" . implode('`, `', $names) . "`)
        VALUES (" . implode(', ', $placeholders) . ")
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($filtered));

    return (int) $pdo->lastInsertId();
}

function candidatura_user_has_role(array $user, string $role): bool
{
    if (($user['role'] ?? '') === $role) {
        return true;
    }

    if (!empty($user['roles'])) {
        $decoded = json_decode((string) $user['roles'], true);

        if (is_array($decoded) && in_array($role, $decoded, true)) {
            return true;
        }
    }

    return false;
}

function candidatura_notify_admins_and_secretaria(PDO $pdo, string $applicationCode, string $fullName, string $courseCode, string $courseName): void
{
    if (!candidatura_table_exists($pdo, 'notifications') || !candidatura_table_exists($pdo, 'users')) {
        return;
    }

    $userColumns = candidatura_columns($pdo, 'users');

    $whereParts = [];

    if (in_array('status', $userColumns, true)) {
        $whereParts[] = "status = 'active'";
    }

    if (in_array('is_active', $userColumns, true)) {
        $whereParts[] = "is_active = 1";
    }

    $whereSql = '';

    if (!empty($whereParts)) {
        $whereSql = 'WHERE ' . implode(' OR ', $whereParts);
    }

    $usersStmt = $pdo->query("
        SELECT *
        FROM users
        {$whereSql}
    ");

    $users = $usersStmt->fetchAll();

    $title = 'Nova candidatura submetida';
    $message = 'Nova candidatura recebida: ' . $applicationCode . ' — ' . $fullName . '. Curso: ' . $courseCode . ' — ' . $courseName . '.';

    foreach ($users as $targetUser) {
        $isTarget =
            candidatura_user_has_role($targetUser, 'admin') ||
            candidatura_user_has_role($targetUser, 'secretaria');

        if (!$isTarget) {
            continue;
        }

        candidatura_insert_flexible($pdo, 'notifications', [
            'user_id' => (int) $targetUser['id'],
            'title' => $title,
            'message' => $message,
            'type' => 'info',
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}

try {
    $pdo->beginTransaction();

    $courseColumns = candidatura_columns($pdo, 'courses');

    if (in_array('status', $courseColumns, true)) {
        $courseStmt = $pdo->prepare("
            SELECT id, code, name
            FROM courses
            WHERE id = ?
              AND status = 'active'
            LIMIT 1
        ");
    } else {
        $courseStmt = $pdo->prepare("
            SELECT id, code, name
            FROM courses
            WHERE id = ?
            LIMIT 1
        ");
    }

    $courseStmt->execute([$courseId]);
    $course = $courseStmt->fetch();

    if (!$course) {
        throw new Exception('Curso inválido ou inativo.');
    }

    $yearStmt = $pdo->prepare("
        SELECT id
        FROM academic_years
        WHERE id = ?
        LIMIT 1
    ");

    $yearStmt->execute([$academicYearId]);

    if (!$yearStmt->fetch()) {
        throw new Exception('Ano letivo inválido.');
    }

    $year = date('Y');

    $countStmt = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM applications
        WHERE YEAR(created_at) = ?
    ");

    $countStmt->execute([$year]);
    $sequence = ((int) ($countStmt->fetch()['total'] ?? 0)) + 1;

    $applicationCode = 'CAND-' . $year . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

    $insertApplication = $pdo->prepare("
        INSERT INTO applications (
            application_code,
            full_name,
            birth_date,
            document_type,
            document_number,
            personal_email,
            phone,
            course_id,
            academic_year_id,
            status,
            rejection_reason
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
    ");

    $insertApplication->execute([
        $applicationCode,
        $fullName,
        $birthDate,
        $documentType,
        $documentNumber,
        $personalEmail,
        $phone,
        $courseId,
        $academicYearId,
        $observations !== '' ? $observations : null
    ]);

    $applicationId = (int) $pdo->lastInsertId();

    $fileField = null;

    if (isset($_FILES['documents']) && !empty($_FILES['documents']['name'][0])) {
        $fileField = 'documents';
    } elseif (isset($_FILES['documentos']) && !empty($_FILES['documentos']['name'][0])) {
        $fileField = 'documentos';
    }

    if (!$fileField) {
        throw new Exception('Nenhum documento enviado.');
    }

    $uploadDir = __DIR__ . '/../assets/uploads/documentos/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0750, true);
    }

    $allowedTypes = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    foreach ($_FILES[$fileField]['name'] as $index => $originalName) {
        if ($_FILES[$fileField]['error'][$index] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($_FILES[$fileField]['error'][$index] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro ao carregar um dos documentos.');
        }

        $tmpName = $_FILES[$fileField]['tmp_name'][$index];
        $fileSize = (int) $_FILES[$fileField]['size'][$index];

        if ($fileSize > 5 * 1024 * 1024) {
            throw new Exception('Um dos ficheiros ultrapassa 5MB.');
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpName) ?: '';

        if (!isset($allowedTypes[$mime]) || !in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            throw new Exception('Formato de ficheiro inválido. Use PDF, JPG ou PNG.');
        }

        $newFileName = bin2hex(random_bytes(20)) . '.' . $allowedTypes[$mime];
        $destination = $uploadDir . $newFileName;

        if (!move_uploaded_file($tmpName, $destination)) {
            throw new Exception('Erro ao guardar documento.');
        }

        $relativePath = 'assets/uploads/documentos/' . $newFileName;

        $insertDocument = $pdo->prepare("
            INSERT INTO application_documents (
                application_id,
                document_name,
                file_path,
                status
            ) VALUES (?, ?, ?, 'pending')
        ");

        $insertDocument->execute([
            $applicationId,
            $originalName,
            $relativePath
        ]);
    }

    $logStmt = $pdo->prepare("
        INSERT INTO activity_logs (
            user_id,
            action,
            description,
            ip_address
        ) VALUES (NULL, 'APPLICATION_SUBMITTED', ?, ?)
    ");

    $logStmt->execute([
        'Nova candidatura submetida: ' . $applicationCode . ' — ' . $fullName,
        current_ip()
    ]);

    candidatura_notify_admins_and_secretaria(
        $pdo,
        $applicationCode,
        $fullName,
        $course['code'] ?? '',
        $course['name'] ?? ''
    );

    $pdo->commit();

    redirect(
        APP_URL .
        '/pages/candidatura-sucesso.php?codigo=' .
        urlencode($applicationCode) .
        '&email=' .
        urlencode($personalEmail)
    );
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    redirect(APP_URL . '/candidatura.php?erro=1');
}
