<?php

require_once __DIR__ . '/../includes/auth.php';

require_any_role(['admin', 'secretaria']);

$user = current_user();

$applicationId = (int) ($_POST['application_id'] ?? ($_POST['id'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $applicationId <= 0) {
    $_SESSION['flash_error'] = 'Pedido inválido.';
    redirect(APP_URL . '/pages/candidaturas.php');
}

function approve_table_exists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $error) {
        return false;
    }
}

function approve_columns(PDO $pdo, string $table): array
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

function approve_insert(PDO $pdo, string $table, array $data): int
{
    $columns = approve_columns($pdo, $table);
    $filtered = [];

    foreach ($data as $key => $value) {
        if (in_array($key, $columns, true)) {
            $filtered[$key] = $value;
        }
    }

    if (empty($filtered)) {
        throw new Exception('Não existem colunas válidas para inserir em ' . $table . '.');
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

function approve_update(PDO $pdo, string $table, array $data, string $where, array $whereParams): void
{
    $columns = approve_columns($pdo, $table);
    $filtered = [];

    foreach ($data as $key => $value) {
        if (in_array($key, $columns, true)) {
            $filtered[$key] = $value;
        }
    }

    if (empty($filtered)) {
        return;
    }

    $sets = [];

    foreach (array_keys($filtered) as $column) {
        $sets[] = "`{$column}` = ?";
    }

    $sql = "
        UPDATE `{$table}`
        SET " . implode(', ', $sets) . "
        WHERE {$where}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge(array_values($filtered), $whereParams));
}

function approve_generate_password(int $length = 10): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#$%';
    $password = '';

    for ($i = 0; $i < $length; $i++) {
        $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    return $password;
}

function approve_generate_student_code(PDO $pdo): string
{
    $year = date('Y');
    $prefix = 'ALU-' . $year . '-';

    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM students
        WHERE student_code LIKE ?
    ");

    $stmt->execute([$prefix . '%']);
    $sequence = ((int) ($stmt->fetch()['total'] ?? 0)) + 1;

    do {
        $code = $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM students
            WHERE student_code = ?
        ");

        $checkStmt->execute([$code]);
        $exists = (int) ($checkStmt->fetch()['total'] ?? 0) > 0;

        $sequence++;
    } while ($exists);

    return $code;
}

function approve_generate_institutional_email(PDO $pdo, string $studentCode): string
{
    $base = strtolower(str_replace('-', '.', $studentCode));
    $email = $base . '@aluno.portal.example.test';
    $sequence = 1;

    do {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM users
            WHERE email = ?
        ");

        $stmt->execute([$email]);
        $exists = (int) ($stmt->fetch()['total'] ?? 0) > 0;

        if ($exists) {
            $sequence++;
            $email = $base . '.' . $sequence . '@aluno.portal.example.test';
        }
    } while ($exists);

    return $email;
}

function approve_assign_student_role(PDO $pdo, int $userId): void
{
    $userColumns = approve_columns($pdo, 'users');

    if (in_array('role', $userColumns, true)) {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute(['aluno', $userId]);
    }

    if (in_array('roles', $userColumns, true)) {
        $stmt = $pdo->prepare("UPDATE users SET roles = ? WHERE id = ?");
        $stmt->execute([json_encode(['aluno']), $userId]);
    }

    if (!approve_table_exists($pdo, 'user_roles')) {
        return;
    }

    $userRoleColumns = approve_columns($pdo, 'user_roles');

    if (in_array('role', $userRoleColumns, true)) {
        approve_insert($pdo, 'user_roles', [
            'user_id' => $userId,
            'role' => 'aluno',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return;
    }

    if (!in_array('role_id', $userRoleColumns, true) || !approve_table_exists($pdo, 'roles')) {
        return;
    }

    $roleStmt = $pdo->prepare("
        SELECT id
        FROM roles
        WHERE name = ? OR slug = ? OR code = ?
        LIMIT 1
    ");

    $roleStmt->execute(['aluno', 'aluno', 'aluno']);
    $role = $roleStmt->fetch();

    if (!$role) {
        return;
    }

    approve_insert($pdo, 'user_roles', [
        'user_id' => $userId,
        'role_id' => (int) $role['id'],
        'created_at' => date('Y-m-d H:i:s')
    ]);
}

function approve_create_notification(PDO $pdo, int $userId, string $title, string $message): void
{
    if (!approve_table_exists($pdo, 'notifications')) {
        return;
    }

    approve_insert($pdo, 'notifications', [
        'user_id' => $userId,
        'title' => $title,
        'message' => $message,
        'type' => 'success',
        'is_read' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ]);
}

try {
    if (!approve_table_exists($pdo, 'applications')) {
        throw new Exception('Tabela applications não encontrada.');
    }

    if (!approve_table_exists($pdo, 'users')) {
        throw new Exception('Tabela users não encontrada.');
    }

    if (!approve_table_exists($pdo, 'students')) {
        throw new Exception('Tabela students não encontrada.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT
            applications.*,
            courses.code AS course_code,
            courses.name AS course_name,
            academic_years.name AS academic_year_name
        FROM applications
        INNER JOIN courses ON courses.id = applications.course_id
        LEFT JOIN academic_years ON academic_years.id = applications.academic_year_id
        WHERE applications.id = ?
        LIMIT 1
    ");

    $stmt->execute([$applicationId]);
    $application = $stmt->fetch();

    if (!$application) {
        throw new Exception('Candidatura não encontrada.');
    }

    if ($application['status'] !== 'documents_validated') {
        throw new Exception('A candidatura só pode gerar credenciais depois de a secretaria validar os documentos.');
    }

    $studentCode = approve_generate_student_code($pdo);
    $institutionalEmail = approve_generate_institutional_email($pdo, $studentCode);
    $temporaryPassword = approve_generate_password(10);
    $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
    $now = date('Y-m-d H:i:s');

    $userId = approve_insert($pdo, 'users', [
        'full_name' => $application['full_name'],
        'email' => $institutionalEmail,
        'personal_email' => $application['personal_email'] ?? null,
        'phone' => $application['phone'] ?? null,
        'document_type' => $application['document_type'] ?? null,
        'document_number' => $application['document_number'] ?? null,
        'institutional_id' => $studentCode,
        'password_hash' => $passwordHash,
        'role' => 'aluno',
        'roles' => json_encode(['aluno']),
        'status' => 'active',
        'is_active' => 1,
        'must_change_password' => 1,
        'created_at' => $now,
        'updated_at' => $now
    ]);

    approve_assign_student_role($pdo, $userId);

    $studentId = approve_insert($pdo, 'students', [
        'user_id' => $userId,
        'student_code' => $studentCode,
        'course_id' => (int) $application['course_id'],
        'academic_year_id' => (int) $application['academic_year_id'],
        'current_year' => 1,
        'enrollment_status' => 'active',
        'created_at' => $now,
        'updated_at' => $now
    ]);

    approve_update($pdo, 'applications', [
        'status' => 'credentials_sent',
        'user_id' => $userId,
        'student_id' => $studentId,
        'rejection_reason' => null,
        'updated_at' => $now
    ], 'id = ?', [$applicationId]);

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
        'documents_validated',
        'credentials_sent',
        'APPLICATION_CREDENTIALS_CREATED',
        'Conta de aluno criada. ID: ' . $studentCode . '. Email: ' . $institutionalEmail,
        current_ip()
    ]);

    approve_create_notification(
        $pdo,
        $userId,
        'Candidatura aprovada',
        'A sua candidatura foi aprovada. Aceda ao portal com o ID ' . $studentCode . ' e altere a palavra-passe no primeiro acesso.'
    );

    $logStmt = $pdo->prepare("
        INSERT INTO activity_logs (
            user_id,
            action,
            description,
            ip_address
        ) VALUES (?, 'APPLICATION_CREDENTIALS_CREATED', ?, ?)
    ");

    $logStmt->execute([
        (int) $user['id'],
        'Credenciais criadas para candidatura ' . $application['application_code'] . ' — ' . $application['full_name'] . '. Conta criada: ' . $studentCode,
        current_ip()
    ]);

    $pdo->commit();

    $_SESSION['flash_success'] =
        'Credenciais geradas com sucesso. ' .
        'ID: ' . $studentCode .
        ' | Email institucional: ' . $institutionalEmail .
        ' | Senha temporária: ' . $temporaryPassword .
        ' | O aluno deve alterar a senha no primeiro acesso.';

    redirect(APP_URL . '/pages/candidatura_detalhes.php?id=' . $applicationId);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['flash_error'] = $error->getMessage();

    redirect(APP_URL . '/pages/candidatura_detalhes.php?id=' . $applicationId);
}