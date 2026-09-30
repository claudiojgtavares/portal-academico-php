<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_ui_helpers.php';

require_any_role(['admin', 'secretaria']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/pages/cursos.php');
}

function curso_save_dynamic(PDO $pdo, string $table, array $data, ?int $id = null): int
{
    $columns = portal_db_columns($pdo, $table);
    $data = array_filter($data, static fn ($value, $key) => in_array($key, $columns, true), ARRAY_FILTER_USE_BOTH);

    if (!$data) {
        throw new Exception('Não foi possível preparar os dados do curso.');
    }

    if ($id && $id > 0) {
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            $fields[] = '`' . $key . '` = ?';
            $params[] = $value;
        }
        if (in_array('updated_at', $columns, true)) {
            $fields[] = 'updated_at = NOW()';
        }
        $params[] = $id;
        $stmt = $pdo->prepare('UPDATE `' . $table . '` SET ' . implode(', ', $fields) . ' WHERE id = ?');
        $stmt->execute($params);
        return $id;
    }

    $fields = array_keys($data);
    $placeholders = implode(',', array_fill(0, count($fields), '?'));
    $stmt = $pdo->prepare('INSERT INTO `' . $table . '` (`' . implode('`,`', $fields) . '`) VALUES (' . $placeholders . ')');
    $stmt->execute(array_values($data));
    return (int) $pdo->lastInsertId();
}

try {
    if (!portal_db_table_exists($pdo, 'courses')) {
        throw new Exception('A tabela courses não existe. Execute a base de dados inicial do projeto.');
    }

    $id = (int) ($_POST['course_id'] ?? 0);
    $code = mb_strtoupper(trim($_POST['code'] ?? ''), 'UTF-8');
    $name = trim($_POST['name'] ?? '');
    $level = trim($_POST['level'] ?? 'licenciatura');
    $duration = (int) ($_POST['duration_years'] ?? 0);
    $status = trim($_POST['status'] ?? 'active');
    $description = trim($_POST['description'] ?? '');

    $allowedLevels = ['licenciatura', 'mestrado', 'mestrado_integrado', 'doutoramento', 'especializacao', 'formacao_permanente'];
    if (!in_array($level, $allowedLevels, true)) {
        $level = 'licenciatura';
    }

    if (!in_array($status, ['active', 'inactive'], true)) {
        $status = 'active';
    }

    if ($code === '' || $name === '') {
        throw new Exception('Indique o código e o nome do curso.');
    }

    if ($duration <= 0) {
        $duration = null;
    }

    $existing = portal_one($pdo, 'SELECT id FROM courses WHERE code = ? AND id <> ? LIMIT 1', [$code, $id]);
    if ($existing) {
        throw new Exception('Já existe um curso com este código.');
    }

    curso_save_dynamic($pdo, 'courses', [
        'code' => $code,
        'name' => $name,
        'level' => $level,
        'duration_years' => $duration,
        'status' => $status,
        'description' => $description !== '' ? $description : null,
        'created_at' => date('Y-m-d H:i:s'),
    ], $id > 0 ? $id : null);

    $_SESSION['flash_success'] = $id > 0 ? 'Curso atualizado com sucesso.' : 'Curso criado com sucesso.';
    redirect(APP_URL . '/pages/cursos.php?nivel=' . urlencode($level));
} catch (Throwable $error) {
    $_SESSION['flash_error'] = $error->getMessage();
    redirect(APP_URL . '/pages/cursos.php');
}
