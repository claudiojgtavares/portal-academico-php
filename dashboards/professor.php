<?php
require_once __DIR__ . '/../includes/portal_layout.php';
require_any_role(['professor','admin']);
$user = current_user();
$forcedRole = 'professor';

$uid=(int)$user['id'];
$metrics=['subjects'=>portal_count($pdo,"SELECT COUNT(*) FROM teacher_subjects WHERE teacher_user_id=? AND status='active'",[$uid]),'classes'=>portal_count($pdo,"SELECT COUNT(DISTINCT class_id) FROM teacher_subjects WHERE teacher_user_id=? AND status='active'",[$uid]),'grades'=>portal_count($pdo,"SELECT COUNT(*) FROM grades WHERE teacher_user_id=?",[$uid]),'absences'=>portal_count($pdo,"SELECT COUNT(*) FROM attendance_records WHERE teacher_user_id=? AND status IN ('absent','justified')",[$uid])];
$subjects=portal_rows($pdo,"SELECT teacher_subjects.id, subjects.code AS subject_code, subjects.name AS subject_name, academic_classes.name AS class_name FROM teacher_subjects LEFT JOIN subjects ON subjects.id=teacher_subjects.subject_id LEFT JOIN academic_classes ON academic_classes.id=teacher_subjects.class_id WHERE teacher_subjects.teacher_user_id=? AND teacher_subjects.status='active' LIMIT 8",[$uid]);

portal_layout_start('dashboard', 'Painel do Professor', 'Disciplinas, turmas, notas, faltas e notificações.', $forcedRole);
?>
<div class="ui-kpi-grid">
    <div class="ui-kpi"><span>Disciplinas</span><strong><?= e((string)($metrics['subjects'])); ?></strong><small>Atribuições ativas.</small></div>
    <div class="ui-kpi"><span>Turmas</span><strong><?= e((string)($metrics['classes'])); ?></strong><small>Turmas associadas.</small></div>
    <div class="ui-kpi"><span>Notas lançadas</span><strong><?= e((string)($metrics['grades'])); ?></strong><small>Registos de avaliação.</small></div>
    <div class="ui-kpi"><span>Faltas registadas</span><strong><?= e((string)($metrics['absences'])); ?></strong><small>Ausências e justificações.</small></div>
</div>

<div class="ui-panel"><div class="ui-panel-header"><div><h2>Minhas disciplinas</h2><p>Acesso rápido às turmas atribuídas.</p></div><a class="ui-btn ui-btn-primary" href="<?= e(APP_URL); ?>/pages/minhas_disciplinas.php">Ver disciplinas</a></div><div class="ui-table-wrap"><table class="ui-table"><thead><tr><th>Disciplina</th><th>Turma</th><th>Ações</th></tr></thead><tbody><?php if(empty($subjects)): ?><tr><td colspan="3" class="ui-empty">Sem disciplinas atribuídas.</td></tr><?php endif; ?><?php foreach($subjects as $s): ?><tr><td><strong><?= e($s['subject_code'] ?? '-'); ?></strong><small><?= e($s['subject_name'] ?? '-'); ?></small></td><td><?= e($s['class_name'] ?? '-'); ?></td><td><a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/lancar_notas.php?assignment_id=<?= (int)$s['id']; ?>">Notas</a> <a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/registar_faltas.php?assignment_id=<?= (int)$s['id']; ?>">Faltas</a></td></tr><?php endforeach; ?></tbody></table></div></div>

<?php portal_layout_end(); ?>
