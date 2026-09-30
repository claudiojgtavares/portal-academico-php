<?php

require_once __DIR__ . '/../includes/portal_layout.php';

require_login();

$user = current_user();
$role = portal_user_role($user);
$student = portal_one($pdo, "SELECT * FROM students WHERE user_id = ? LIMIT 1", [(int) $user['id']]);

if ($role === 'aluno' && $student) {
    $rows = portal_rows($pdo, "
        SELECT
            attendance_records.*,
            subjects.code AS subject_code,
            subjects.name AS subject_name,
            academic_classes.name AS class_name,
            users.full_name AS teacher_name
        FROM attendance_records
        LEFT JOIN subjects ON subjects.id = attendance_records.subject_id
        LEFT JOIN academic_classes ON academic_classes.id = attendance_records.class_id
        LEFT JOIN users ON users.id = attendance_records.teacher_user_id
        WHERE attendance_records.student_id = ?
        ORDER BY attendance_records.class_date DESC, attendance_records.id DESC
    ", [(int) $student['id']]);
} else {
    require_any_role(['admin', 'direcao', 'coordenador', 'professor']);
    $rows = portal_rows($pdo, "
        SELECT
            attendance_records.*,
            subjects.code AS subject_code,
            subjects.name AS subject_name,
            academic_classes.name AS class_name,
            users.full_name AS student_name,
            students.student_code
        FROM attendance_records
        LEFT JOIN subjects ON subjects.id = attendance_records.subject_id
        LEFT JOIN academic_classes ON academic_classes.id = attendance_records.class_id
        LEFT JOIN students ON students.id = attendance_records.student_id
        LEFT JOIN users ON users.id = students.user_id
        ORDER BY attendance_records.class_date DESC, attendance_records.id DESC
        LIMIT 200
    ");
}

portal_layout_start('faltas', 'Faltas', 'Consulta de presenças, ausências e justificações.', $role);

?>

<div class="ui-kpi-grid">
    <div class="ui-kpi"><span>Total</span><strong><?= count($rows); ?></strong><small>Registos listados.</small></div>
    <div class="ui-kpi is-good"><span>Presenças</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM attendance_records WHERE status = 'present'"); ?></strong><small>Aulas com presença.</small></div>
    <div class="ui-kpi is-danger"><span>Faltas</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM attendance_records WHERE status = 'absent'"); ?></strong><small>Ausências registadas.</small></div>
    <div class="ui-kpi is-warn"><span>Justificadas</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM attendance_records WHERE status = 'justified'"); ?></strong><small>Ausências justificadas.</small></div>
</div>

<div class="ui-panel">
    <div class="ui-panel-header"><div><h2>Registos de presença</h2><p>Histórico por disciplina, turma e data.</p></div></div>
    <div class="ui-table-wrap">
        <table class="ui-table">
            <thead><tr><th><?= $role === 'aluno' ? 'Disciplina' : 'Aluno'; ?></th><th>Turma</th><th>Data</th><th>Estado</th><th>Observação</th></tr></thead>
            <tbody>
                <?php if (empty($rows)): ?><tr><td colspan="5" class="ui-empty">Sem faltas ou presenças registadas.</td></tr><?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td>
                            <?php if ($role === 'aluno'): ?>
                                <strong><?= e($row['subject_code'] ?? '-'); ?></strong><small><?= e($row['subject_name'] ?? '-'); ?></small>
                            <?php else: ?>
                                <strong><?= e($row['student_name'] ?? '-'); ?></strong><small><?= e($row['student_code'] ?? '-'); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= e($row['class_name'] ?? '-'); ?></td>
                        <td><?= e(portal_short_date($row['class_date'] ?? null)); ?></td>
                        <td><span class="ui-badge <?= e(portal_badge_class($row['status'] ?? '')); ?>"><?= e(portal_label_status($row['status'] ?? '')); ?></span></td>
                        <td><?= e($row['notes'] ?? '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php portal_layout_end(); ?>
