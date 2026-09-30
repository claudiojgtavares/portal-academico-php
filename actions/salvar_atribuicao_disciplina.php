<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_ui_helpers.php';

require_any_role(['admin', 'secretaria']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/pages/atribuir_disciplinas.php');
}

$user = current_user();
$userId = (int) ($user['id'] ?? 0);
$acao = trim($_POST['acao'] ?? '');

function atrib_notify_user(PDO $pdo, int $userId, string $title, string $message): void
{
    if ($userId <= 0 || !portal_db_table_exists($pdo, 'notifications')) {
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, 'info', 0, NOW())");
        $stmt->execute([$userId, $title, $message]);
    } catch (Throwable $error) {
        // notificação não deve impedir a atribuição
    }
}

try {
    if (!portal_db_table_exists($pdo, 'teacher_subjects')) {
        throw new Exception('A tabela teacher_subjects ainda não existe. Execute o SQL database/2026_05_20_teacher_subjects.sql.');
    }

    if ($acao === 'criar') {
        $teacherUserId = (int) ($_POST['teacher_user_id'] ?? 0);
        $subjectId = (int) ($_POST['subject_id'] ?? 0);
        $classId = (int) ($_POST['class_id'] ?? 0);
        $academicYearId = (int) ($_POST['academic_year_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'active');

        if ($teacherUserId <= 0 || $subjectId <= 0 || $classId <= 0 || $academicYearId <= 0) {
            throw new Exception('Selecione docente, disciplina, turma e ano letivo.');
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $teacher = portal_one($pdo, "
            SELECT users.id, users.full_name
            FROM users
            INNER JOIN user_roles ON user_roles.user_id = users.id
            INNER JOIN roles ON roles.id = user_roles.role_id
            WHERE users.id = ? AND roles.code IN ('professor','coordenador')
            LIMIT 1
        ", [$teacherUserId]);

        if (!$teacher) {
            throw new Exception('O utilizador selecionado não é professor nem coordenador.');
        }

        $existing = portal_one($pdo, "
            SELECT id
            FROM teacher_subjects
            WHERE teacher_user_id = ? AND subject_id = ? AND class_id = ? AND academic_year_id = ?
            LIMIT 1
        ", [$teacherUserId, $subjectId, $classId, $academicYearId]);

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE teacher_subjects SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, (int) $existing['id']]);
            $assignmentId = (int) $existing['id'];
        } else {
            $columns = portal_db_columns($pdo, 'teacher_subjects');
            $data = [
                'teacher_user_id' => $teacherUserId,
                'subject_id' => $subjectId,
                'class_id' => $classId,
                'academic_year_id' => $academicYearId,
                'status' => $status,
            ];
            if (in_array('created_by', $columns, true)) { $data['created_by'] = $userId; }
            if (in_array('created_at', $columns, true)) { $data['created_at'] = date('Y-m-d H:i:s'); }

            $insertColumns = array_keys($data);
            $placeholders = implode(',', array_fill(0, count($insertColumns), '?'));
            $stmt = $pdo->prepare("INSERT INTO teacher_subjects (`" . implode('`,`', $insertColumns) . "`) VALUES ({$placeholders})");
            $stmt->execute(array_values($data));
            $assignmentId = (int) $pdo->lastInsertId();
        }

        $info = portal_one($pdo, "
            SELECT subjects.code AS subject_code, subjects.name AS subject_name, academic_classes.name AS class_name
            FROM teacher_subjects
            LEFT JOIN subjects ON subjects.id = teacher_subjects.subject_id
            LEFT JOIN academic_classes ON academic_classes.id = teacher_subjects.class_id
            WHERE teacher_subjects.id = ?
            LIMIT 1
        ", [$assignmentId]);

        atrib_notify_user(
            $pdo,
            $teacherUserId,
            'Disciplina atribuída',
            'Foi atribuída a disciplina ' . (($info['subject_code'] ?? '') ?: '') . ' — ' . (($info['subject_name'] ?? '') ?: 'disciplina') . ' na turma ' . (($info['class_name'] ?? '') ?: '-') . '.'
        );

        $_SESSION['flash_success'] = 'Disciplina atribuída com sucesso.';
        redirect(APP_URL . '/pages/atribuir_disciplinas.php');
    }

    if (in_array($acao, ['ativar', 'inativar'], true)) {
        $assignmentId = (int) ($_POST['assignment_id'] ?? 0);
        if ($assignmentId <= 0) {
            throw new Exception('Atribuição inválida.');
        }

        $status = $acao === 'ativar' ? 'active' : 'inactive';
        $stmt = $pdo->prepare("UPDATE teacher_subjects SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $assignmentId]);
        $_SESSION['flash_success'] = $acao === 'ativar' ? 'Atribuição ativada.' : 'Atribuição inativada.';
        redirect(APP_URL . '/pages/atribuir_disciplinas.php');
    }

    throw new Exception('Ação inválida.');
} catch (Throwable $error) {
    $_SESSION['flash_error'] = $error->getMessage();
    redirect(APP_URL . '/pages/atribuir_disciplinas.php');
}
