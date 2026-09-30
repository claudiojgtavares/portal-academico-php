<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_ui_helpers.php';

require_any_role(['admin', 'secretaria']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/pages/docentes_curso.php');
}

$current = current_user();
$currentId = (int) ($current['id'] ?? 0);
$acao = trim($_POST['acao'] ?? '');

function notify_course_staff_user(PDO $pdo, int $userId, string $title, string $message): void
{
    if ($userId <= 0 || !portal_db_table_exists($pdo, 'notifications')) {
        return;
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, 'info', 0, NOW())");
        $stmt->execute([$userId, $title, $message]);
    } catch (Throwable $error) {
        // A notificação não deve impedir a associação.
    }
}

try {
    if (!portal_db_table_exists($pdo, 'course_staff')) {
        throw new Exception('Execute primeiro o SQL database/2026_05_20_academic_structure.sql.');
    }

    if ($acao === 'criar') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $academicYearId = (int) ($_POST['academic_year_id'] ?? 0);
        $functionRole = trim($_POST['function_role'] ?? 'professor');
        $status = trim($_POST['status'] ?? 'active');

        if ($userId <= 0 || $courseId <= 0) {
            throw new Exception('Selecione o docente/coordenador e o curso.');
        }

        if (!in_array($functionRole, ['professor', 'coordenador', 'apoio'], true)) {
            $functionRole = 'professor';
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $roleRequired = $functionRole === 'coordenador' ? 'coordenador' : 'professor';
        $hasRole = portal_one($pdo, "
            SELECT users.id
            FROM users
            INNER JOIN user_roles ON user_roles.user_id = users.id
            INNER JOIN roles ON roles.id = user_roles.role_id
            WHERE users.id = ? AND roles.code = ?
            LIMIT 1
        ", [$userId, $roleRequired]);

        if (!$hasRole) {
            throw new Exception('Antes de associar ao curso, atribua ao utilizador o perfil ' . portal_label_status($roleRequired) . ' em Perfis de utilizadores.');
        }

        $existing = portal_one($pdo, "
            SELECT id FROM course_staff
            WHERE course_id = ? AND user_id = ? AND function_role = ? AND (academic_year_id <=> ?)
            LIMIT 1
        ", [$courseId, $userId, $functionRole, $academicYearId > 0 ? $academicYearId : null]);

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE course_staff SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, (int) $existing['id']]);
        } else {
            $columns = portal_db_columns($pdo, 'course_staff');
            $data = [
                'course_id' => $courseId,
                'user_id' => $userId,
                'academic_year_id' => $academicYearId > 0 ? $academicYearId : null,
                'function_role' => $functionRole,
                'status' => $status,
                'created_by' => $currentId,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            $data = array_filter($data, static fn ($value, $key) => in_array($key, $columns, true), ARRAY_FILTER_USE_BOTH);
            $fields = array_keys($data);
            $stmt = $pdo->prepare('INSERT INTO course_staff (`' . implode('`,`', $fields) . '`) VALUES (' . implode(',', array_fill(0, count($fields), '?')) . ')');
            $stmt->execute(array_values($data));
        }

        if ($functionRole === 'coordenador' && portal_db_table_exists($pdo, 'course_coordinators')) {
            $existingCoord = portal_one($pdo, "
                SELECT id FROM course_coordinators
                WHERE course_id = ? AND user_id = ? AND (academic_year_id <=> ?)
                LIMIT 1
            ", [$courseId, $userId, $academicYearId > 0 ? $academicYearId : null]);

            if ($existingCoord) {
                $stmt = $pdo->prepare("UPDATE course_coordinators SET status = ?, coordinator_user_id = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$status, $userId, (int) $existingCoord['id']]);
            } else {
                $columns = portal_db_columns($pdo, 'course_coordinators');
                $data = [
                    'course_id' => $courseId,
                    'user_id' => $userId,
                    'coordinator_user_id' => $userId,
                    'academic_year_id' => $academicYearId > 0 ? $academicYearId : null,
                    'status' => $status,
                    'created_by' => $currentId,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $data = array_filter($data, static fn ($value, $key) => in_array($key, $columns, true), ARRAY_FILTER_USE_BOTH);
                $fields = array_keys($data);
                $stmt = $pdo->prepare('INSERT INTO course_coordinators (`' . implode('`,`', $fields) . '`) VALUES (' . implode(',', array_fill(0, count($fields), '?')) . ')');
                $stmt->execute(array_values($data));
            }
        }

        $course = portal_one($pdo, "SELECT code, name FROM courses WHERE id = ? LIMIT 1", [$courseId]);
        notify_course_staff_user(
            $pdo,
            $userId,
            'Associação ao curso',
            'Foi associado ao curso ' . (($course['code'] ?? '') ?: '') . ' — ' . (($course['name'] ?? '') ?: 'curso') . ' como ' . portal_label_status($functionRole) . '.'
        );

        $_SESSION['flash_success'] = 'Docente/coordenador associado ao curso com sucesso.';
        redirect(APP_URL . '/pages/docentes_curso.php');
    }

    if (in_array($acao, ['ativar', 'inativar'], true)) {
        $id = (int) ($_POST['course_staff_id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('Associação inválida.');
        }
        $status = $acao === 'ativar' ? 'active' : 'inactive';
        $stmt = $pdo->prepare("UPDATE course_staff SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $id]);
        $_SESSION['flash_success'] = 'Estado da associação atualizado.';
        redirect(APP_URL . '/pages/docentes_curso.php');
    }

    throw new Exception('Ação inválida.');
} catch (Throwable $error) {
    $_SESSION['flash_error'] = $error->getMessage();
    redirect(APP_URL . '/pages/docentes_curso.php');
}
