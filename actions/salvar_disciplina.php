<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_ui_helpers.php';

require_any_role(['admin', 'secretaria']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/pages/disciplinas.php');
}

function disciplina_save_dynamic(PDO $pdo, string $table, array $data, ?int $id = null): int
{
    $columns = portal_db_columns($pdo, $table);
    $data = array_filter($data, static fn ($value, $key) => in_array($key, $columns, true), ARRAY_FILTER_USE_BOTH);

    if (!$data) {
        throw new Exception('Não foi possível preparar os dados da disciplina.');
    }

    if ($id && $id > 0) {
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            $fields[] = '`' . $key . '` = ?';
            $params[] = $value;
        }
        if (in_array('updated_at', portal_db_columns($pdo, $table), true)) {
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
    if (!portal_db_table_exists($pdo, 'subjects')) {
        throw new Exception('A tabela subjects não existe. Execute a base de dados inicial do projeto.');
    }

    $id = (int) ($_POST['subject_id'] ?? 0);
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $code = mb_strtoupper(trim($_POST['code'] ?? ''), 'UTF-8');
    $name = trim($_POST['name'] ?? '');
    $semester = (int) ($_POST['semester'] ?? 0);
    $credits = (int) ($_POST['credits'] ?? 0);
    $workload = (int) ($_POST['workload_hours'] ?? 0);
    $status = trim($_POST['status'] ?? 'active');
    $description = trim($_POST['description'] ?? '');

    if ($courseId <= 0 || $code === '' || $name === '') {
        throw new Exception('Selecione o curso e indique o código e nome da disciplina.');
    }

    if (!in_array($status, ['active', 'inactive'], true)) {
        $status = 'active';
    }

    $course = portal_one($pdo, 'SELECT id FROM courses WHERE id = ? LIMIT 1', [$courseId]);
    if (!$course) {
        throw new Exception('Curso selecionado não encontrado.');
    }

    $existing = portal_one($pdo, 'SELECT id FROM subjects WHERE code = ? AND course_id = ? AND id <> ? LIMIT 1', [$code, $courseId, $id]);
    if ($existing) {
        throw new Exception('Já existe uma disciplina com este código neste curso.');
    }

    disciplina_save_dynamic($pdo, 'subjects', [
        'course_id' => $courseId,
        'code' => $code,
        'name' => $name,
        'semester' => $semester > 0 ? $semester : null,
        'credits' => $credits > 0 ? $credits : null,
        'workload_hours' => $workload > 0 ? $workload : null,
        'status' => $status,
        'description' => $description !== '' ? $description : null,
        'created_at' => date('Y-m-d H:i:s'),
    ], $id > 0 ? $id : null);

    $_SESSION['flash_success'] = $id > 0 ? 'Disciplina atualizada com sucesso.' : 'Disciplina criada com sucesso.';
    redirect(APP_URL . '/pages/disciplinas.php?course_id=' . $courseId);
} catch (Throwable $error) {
    $_SESSION['flash_error'] = $error->getMessage();
    redirect(APP_URL . '/pages/disciplinas.php');
}
