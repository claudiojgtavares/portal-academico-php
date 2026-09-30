<?php
require_once __DIR__ . '/../includes/portal_layout.php';

require_any_role(['professor', 'coordenador', 'admin']);

$user = current_user();
$uid = (int) $user['id'];
$role = portal_user_role($user);
$layoutRole = $role === 'coordenador' ? 'coordenador' : ($role === 'admin' ? 'admin' : 'professor');
$activeKey = $role === 'coordenador' ? 'disciplinas_professor' : 'disciplinas';

$rows = portal_rows($pdo, "
    SELECT
        teacher_subjects.id,
        teacher_subjects.status,
        subjects.code AS subject_code,
        subjects.name AS subject_name,
        academic_classes.name AS class_name,
        courses.code AS course_code,
        courses.name AS course_name,
        academic_years.name AS academic_year
    FROM teacher_subjects
    LEFT JOIN subjects ON subjects.id = teacher_subjects.subject_id
    LEFT JOIN academic_classes ON academic_classes.id = teacher_subjects.class_id
    LEFT JOIN courses ON courses.id = COALESCE(subjects.course_id, academic_classes.course_id)
    LEFT JOIN academic_years ON academic_years.id = teacher_subjects.academic_year_id
    WHERE teacher_subjects.teacher_user_id = ?
      AND teacher_subjects.status = 'active'
    ORDER BY courses.name ASC, subjects.name ASC, academic_classes.name ASC
", [$uid]);

portal_layout_start($activeKey, 'Minhas disciplinas', 'Disciplinas e turmas atribuídas para aulas, notas e faltas.', $layoutRole);
?>

<div class="ui-kpi-grid">
    <div class="ui-kpi"><span>Disciplinas</span><strong><?= count($rows); ?></strong><small>Atribuições ativas.</small></div>
    <div class="ui-kpi is-info"><span>Turmas</span><strong><?= count(array_unique(array_filter(array_column($rows, 'class_name')))); ?></strong><small>Turmas associadas.</small></div>
    <div class="ui-kpi is-good"><span>Notas lançadas</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM grades WHERE teacher_user_id = ?", [$uid]); ?></strong><small>Avaliações registadas.</small></div>
    <div class="ui-kpi is-warn"><span>Faltas</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM attendance_records WHERE teacher_user_id = ? AND status IN ('absent','justified')", [$uid]); ?></strong><small>Ausências registadas.</small></div>
</div>

<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2>Disciplinas atribuídas</h2>
            <p>A secretaria académica atribui disciplinas/turmas a professores e coordenadores. Depois disso, ficam disponíveis para lançar notas e registar faltas.</p>
        </div>
    </div>

    <div class="ui-table-wrap">
        <table class="ui-table">
            <thead>
                <tr><th>Disciplina</th><th>Curso</th><th>Turma</th><th>Ano letivo</th><th>Ações</th></tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="5" class="ui-empty">
                            Nenhuma disciplina atribuída. Solicite à secretaria académica a atribuição da disciplina/turma correta.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><strong><?= e($r['subject_code'] ?? '-'); ?></strong><small><?= e($r['subject_name'] ?? '-'); ?></small></td>
                        <td><strong><?= e($r['course_code'] ?? '-'); ?></strong><small><?= e($r['course_name'] ?? '-'); ?></small></td>
                        <td><?= e($r['class_name'] ?? '-'); ?></td>
                        <td><?= e($r['academic_year'] ?? '-'); ?></td>
                        <td>
                            <div class="ui-actions">
                                <a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/lancar_notas.php?assignment_id=<?= (int) $r['id']; ?>">Lançar notas</a>
                                <a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/registar_faltas.php?assignment_id=<?= (int) $r['id']; ?>">Registar faltas</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php portal_layout_end(); ?>
