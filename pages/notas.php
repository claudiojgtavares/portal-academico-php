<?php

require_once __DIR__ . '/../includes/portal_layout.php';

require_login();

$user = current_user();
$role = portal_user_role($user);
$student = portal_one($pdo, "SELECT * FROM students WHERE user_id = ? LIMIT 1", [(int) $user['id']]);

if ($role === 'aluno' && $student) {
    $rows = portal_rows($pdo, "
        SELECT
            grades.*,
            subjects.code AS subject_code,
            subjects.name AS subject_name,
            academic_classes.name AS class_name,
            users.full_name AS teacher_name
        FROM grades
        LEFT JOIN subjects ON subjects.id = grades.subject_id
        LEFT JOIN academic_classes ON academic_classes.id = grades.class_id
        LEFT JOIN users ON users.id = grades.teacher_user_id
        WHERE grades.student_id = ?
        ORDER BY grades.updated_at DESC, grades.id DESC
    ", [(int) $student['id']]);
} else {
    require_any_role(['admin', 'direcao', 'coordenador', 'professor']);
    $rows = portal_rows($pdo, "
        SELECT
            grades.*,
            subjects.code AS subject_code,
            subjects.name AS subject_name,
            academic_classes.name AS class_name,
            users.full_name AS student_name,
            students.student_code
        FROM grades
        LEFT JOIN subjects ON subjects.id = grades.subject_id
        LEFT JOIN academic_classes ON academic_classes.id = grades.class_id
        LEFT JOIN students ON students.id = grades.student_id
        LEFT JOIN users ON users.id = students.user_id
        ORDER BY grades.updated_at DESC, grades.id DESC
        LIMIT 200
    ");
}

portal_layout_start('notas', 'Notas', 'Consulta de notas e avaliações registadas.', $role);

?>

<div class="ui-kpi-grid">
    <div class="ui-kpi"><span>Total</span><strong><?= count($rows); ?></strong><small>Notas listadas.</small></div>
    <div class="ui-kpi is-good"><span>Submetidas</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM grades WHERE status = 'submitted'"); ?></strong><small>Notas confirmadas.</small></div>
    <div class="ui-kpi is-warn"><span>Rascunho</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM grades WHERE status = 'draft'"); ?></strong><small>Aguardam submissão.</small></div>
    <div class="ui-kpi is-info"><span>Disciplinas</span><strong><?= portal_count($pdo, "SELECT COUNT(DISTINCT subject_id) FROM grades"); ?></strong><small>Com avaliação.</small></div>
</div>

<div class="ui-panel">
    <div class="ui-panel-header"><div><h2>Registos de avaliação</h2><p>Notas organizadas por disciplina, turma e estado.</p></div></div>
    <div class="ui-table-wrap">
        <table class="ui-table">
            <thead><tr><th><?= $role === 'aluno' ? 'Disciplina' : 'Aluno'; ?></th><th>Turma</th><th>Tipo</th><th>Nota</th><th>Estado</th><th>Atualização</th></tr></thead>
            <tbody>
                <?php if (empty($rows)): ?><tr><td colspan="6" class="ui-empty">Sem notas registadas.</td></tr><?php endif; ?>
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
                        <td><?= e(portal_label_status($row['assessment_type'] ?? '-')); ?></td>
                        <td><strong><?= e((string) ($row['grade_value'] ?? '-')); ?></strong></td>
                        <td><span class="ui-badge <?= e(portal_badge_class($row['status'] ?? '')); ?>"><?= e(portal_label_status($row['status'] ?? '')); ?></span></td>
                        <td><?= e(portal_date($row['updated_at'] ?? $row['created_at'] ?? null)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php portal_layout_end(); ?>
