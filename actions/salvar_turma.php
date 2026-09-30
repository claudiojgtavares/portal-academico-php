<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_ui_helpers.php';

require_any_role(['admin', 'secretaria']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/pages/turmas.php');
}

$user = current_user();
$adminId = (int) ($user['id'] ?? 0);
$acao = trim($_POST['acao'] ?? '');

function turma_insert_dynamic(PDO $pdo, string $table, array $data): int
{
    $columns = portal_db_columns($pdo, $table);
    $data = array_filter($data, static fn ($value, $key) => in_array($key, $columns, true), ARRAY_FILTER_USE_BOTH);

    if (!$data) {
        throw new Exception('Não foi possível preparar os dados para gravar.');
    }

    $fields = array_keys($data);
    $placeholders = implode(',', array_fill(0, count($fields), '?'));
    $stmt = $pdo->prepare('INSERT INTO `' . $table . '` (`' . implode('`,`', $fields) . '`) VALUES (' . $placeholders . ')');
    $stmt->execute(array_values($data));

    return (int) $pdo->lastInsertId();
}

try {
    if (!portal_db_table_exists($pdo, 'academic_classes') || !portal_db_table_exists($pdo, 'class_subjects')) {
        throw new Exception('Execute primeiro o SQL database/2026_05_20_academic_structure.sql.');
    }

    if ($acao === 'criar_turma') {
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $academicYearId = (int) ($_POST['academic_year_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $yearNumber = (int) ($_POST['year_number'] ?? 0);
        $semester = (int) ($_POST['semester'] ?? 0);
        $shift = trim($_POST['shift'] ?? '');
        $status = trim($_POST['status'] ?? 'active');

        if ($courseId <= 0 || $name === '') {
            throw new Exception('Selecione o curso e indique o nome da turma.');
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        turma_insert_dynamic($pdo, 'academic_classes', [
            'course_id' => $courseId,
            'academic_year_id' => $academicYearId > 0 ? $academicYearId : null,
            'name' => $name,
            'code' => $code !== '' ? $code : null,
            'year_number' => $yearNumber > 0 ? $yearNumber : null,
            'semester' => $semester > 0 ? $semester : null,
            'shift' => $shift !== '' ? $shift : null,
            'status' => $status,
            'created_by' => $adminId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $_SESSION['flash_success'] = 'Turma criada com sucesso.';
        redirect(APP_URL . '/pages/turmas.php');
    }

    if ($acao === 'atribuir_disciplina') {
        $classId = (int) ($_POST['class_id'] ?? 0);
        $subjectId = (int) ($_POST['subject_id'] ?? 0);
        $academicYearId = (int) ($_POST['academic_year_id'] ?? 0);
        $semester = (int) ($_POST['semester'] ?? 0);
        $status = trim($_POST['status'] ?? 'active');

        if ($classId <= 0 || $subjectId <= 0) {
            throw new Exception('Selecione a turma e a disciplina.');
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $existing = portal_one($pdo, "
            SELECT id FROM class_subjects
            WHERE class_id = ? AND subject_id = ? AND (academic_year_id <=> ?)
            LIMIT 1
        ", [$classId, $subjectId, $academicYearId > 0 ? $academicYearId : null]);

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE class_subjects SET semester = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$semester > 0 ? $semester : null, $status, (int) $existing['id']]);
        } else {
            turma_insert_dynamic($pdo, 'class_subjects', [
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'academic_year_id' => $academicYearId > 0 ? $academicYearId : null,
                'semester' => $semester > 0 ? $semester : null,
                'status' => $status,
                'created_by' => $adminId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $_SESSION['flash_success'] = 'Disciplina associada à turma com sucesso.';
        redirect(APP_URL . '/pages/turmas.php?turma=' . $classId);
    }

    if (in_array($acao, ['ativar_turma', 'inativar_turma'], true)) {
        $classId = (int) ($_POST['class_id'] ?? 0);
        if ($classId <= 0) {
            throw new Exception('Turma inválida.');
        }
        $status = $acao === 'ativar_turma' ? 'active' : 'inactive';
        $stmt = $pdo->prepare("UPDATE academic_classes SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $classId]);
        $_SESSION['flash_success'] = 'Estado da turma atualizado.';
        redirect(APP_URL . '/pages/turmas.php');
    }

    if (in_array($acao, ['ativar_disciplina_turma', 'inativar_disciplina_turma'], true)) {
        $id = (int) ($_POST['class_subject_id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('Associação inválida.');
        }
        $status = $acao === 'ativar_disciplina_turma' ? 'active' : 'inactive';
        $stmt = $pdo->prepare("UPDATE class_subjects SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $id]);
        $_SESSION['flash_success'] = 'Estado da disciplina na turma atualizado.';
        redirect(APP_URL . '/pages/turmas.php');
    }

    throw new Exception('Ação inválida.');
} catch (Throwable $error) {
    $_SESSION['flash_error'] = $error->getMessage();
    redirect(APP_URL . '/pages/turmas.php');
}
