<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_ui_helpers.php';

require_any_role(['admin', 'secretaria', 'funcionario', 'coordenador', 'direcao']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/pages/gerir_horarios.php');
}

$user = current_user();
$role = portal_user_role($user);
$userId = (int) ($user['id'] ?? 0);

function schedule_can_manage(string $role): bool
{
    return in_array($role, ['admin', 'secretaria', 'funcionario'], true);
}

function schedule_can_validate(string $role): bool
{
    return in_array($role, ['admin', 'coordenador', 'direcao'], true);
}

if (!portal_db_table_exists($pdo, 'schedules')) {
    $_SESSION['flash_error'] = 'A tabela de horários ainda não existe. Execute o ficheiro database/2026_05_19_schedules.sql.';
    redirect(APP_URL . '/pages/gerir_horarios.php');
}

$acao = $_POST['acao'] ?? 'guardar';
$scheduleId = (int) ($_POST['schedule_id'] ?? 0);

try {
    if ($acao === 'apagar') {
        if (!schedule_can_manage($role)) {
            throw new Exception('Apenas secretaria, funcionário autorizado ou administrador podem apagar horários.');
        }

        if ($scheduleId <= 0) {
            throw new Exception('Horário inválido.');
        }

        $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ?");
        $stmt->execute([$scheduleId]);

        $_SESSION['flash_success'] = 'Horário apagado com sucesso.';
        redirect(APP_URL . '/pages/gerir_horarios.php');
    }

    if (in_array($acao, ['validar', 'publicar', 'cancelar'], true)) {
        if ($scheduleId <= 0) {
            throw new Exception('Horário inválido.');
        }

        if ($acao === 'validar') {
            if (!schedule_can_validate($role)) {
                throw new Exception('Apenas coordenação, direção ou administração podem validar horários.');
            }

            $stmt = $pdo->prepare("
                UPDATE schedules
                SET status = 'validated', validated_by = ?, validated_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userId, $scheduleId]);
            $_SESSION['flash_success'] = 'Horário validado com sucesso.';
            redirect(APP_URL . '/pages/gerir_horarios.php');
        }

        if (!schedule_can_manage($role)) {
            throw new Exception('Apenas secretaria, funcionário autorizado ou administrador podem publicar ou cancelar horários.');
        }

        $newStatus = $acao === 'publicar' ? 'published' : 'cancelled';
        $stmt = $pdo->prepare("UPDATE schedules SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $scheduleId]);

        $_SESSION['flash_success'] = $acao === 'publicar'
            ? 'Horário publicado com sucesso.'
            : 'Horário cancelado com sucesso.';
        redirect(APP_URL . '/pages/gerir_horarios.php');
    }

    if (!schedule_can_manage($role)) {
        throw new Exception('Apenas secretaria, funcionário autorizado ou administrador podem registar horários.');
    }

    $academicYearId = (int) ($_POST['academic_year_id'] ?? 0);
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $classId = (int) ($_POST['class_id'] ?? 0);
    $subjectId = (int) ($_POST['subject_id'] ?? 0);
    $teacherUserId = (int) ($_POST['teacher_user_id'] ?? 0);
    $weekday = trim($_POST['weekday'] ?? '');
    $startTime = trim($_POST['start_time'] ?? '');
    $endTime = trim($_POST['end_time'] ?? '');
    $room = trim($_POST['room'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    $allowedWeekdays = ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'];

    if ($classId <= 0 || $subjectId <= 0 || !in_array($weekday, $allowedWeekdays, true) || $startTime === '' || $endTime === '') {
        throw new Exception('Preencha turma, disciplina, dia e horário da aula.');
    }

    if ($startTime >= $endTime) {
        throw new Exception('A hora de início deve ser anterior à hora de fim.');
    }

    $conflictSql = "
        SELECT id
        FROM schedules
        WHERE weekday = ?
          AND status <> 'cancelled'
          AND id <> ?
          AND (
                (class_id = ?)
                OR (? > 0 AND teacher_user_id = ?)
                OR (room <> '' AND room = ?)
          )
          AND start_time < ?
          AND end_time > ?
        LIMIT 1
    ";

    $conflictStmt = $pdo->prepare($conflictSql);
    $conflictStmt->execute([
        $weekday,
        $scheduleId,
        $classId,
        $teacherUserId,
        $teacherUserId,
        $room,
        $endTime,
        $startTime
    ]);

    if ($conflictStmt->fetch()) {
        throw new Exception('Existe conflito de horário para a mesma turma, professor ou sala nesse período.');
    }

    if ($scheduleId > 0) {
        $stmt = $pdo->prepare("
            UPDATE schedules
            SET
                academic_year_id = ?,
                course_id = ?,
                class_id = ?,
                subject_id = ?,
                teacher_user_id = ?,
                weekday = ?,
                start_time = ?,
                end_time = ?,
                room = ?,
                notes = ?,
                status = 'draft',
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $academicYearId > 0 ? $academicYearId : null,
            $courseId > 0 ? $courseId : null,
            $classId,
            $subjectId,
            $teacherUserId > 0 ? $teacherUserId : null,
            $weekday,
            $startTime,
            $endTime,
            $room !== '' ? $room : null,
            $notes !== '' ? $notes : null,
            $scheduleId
        ]);

        $_SESSION['flash_success'] = 'Horário atualizado em rascunho. Publique depois de conferir os dados.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO schedules (
                academic_year_id, course_id, class_id, subject_id, teacher_user_id,
                weekday, start_time, end_time, room, notes, status, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, NOW())
        ");

        $stmt->execute([
            $academicYearId > 0 ? $academicYearId : null,
            $courseId > 0 ? $courseId : null,
            $classId,
            $subjectId,
            $teacherUserId > 0 ? $teacherUserId : null,
            $weekday,
            $startTime,
            $endTime,
            $room !== '' ? $room : null,
            $notes !== '' ? $notes : null,
            $userId
        ]);

        $_SESSION['flash_success'] = 'Horário registado em rascunho. Publique depois de conferir os dados.';
    }
} catch (Throwable $e) {
    $_SESSION['flash_error'] = $e->getMessage();
}

redirect(APP_URL . '/pages/gerir_horarios.php');
