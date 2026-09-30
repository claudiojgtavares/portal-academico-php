<?php
require_once __DIR__ . '/../includes/portal_layout.php';
require_any_role(['direcao','admin']);
$user = current_user();
$forcedRole = 'direcao';

$metrics=['courses'=>portal_count($pdo,"SELECT COUNT(*) FROM courses WHERE status='active'"),'students'=>portal_count($pdo,"SELECT COUNT(*) FROM students WHERE enrollment_status='active'"),'apps'=>portal_count($pdo,"SELECT COUNT(*) FROM applications"),'pending'=>portal_count($pdo,"SELECT COUNT(*) FROM applications WHERE status IN ('pending','in_review','missing_documents')"),'subjects'=>portal_count($pdo,"SELECT COUNT(*) FROM subjects WHERE status='active'"),'classes'=>portal_count($pdo,"SELECT COUNT(*) FROM academic_classes WHERE status='active'"),'grades'=>portal_count($pdo,"SELECT COUNT(*) FROM grades"),'value'=>portal_money(portal_count($pdo,"SELECT COALESCE(SUM(amount_cve),0) FROM payments WHERE status='confirmado'"))];
$courses=portal_rows($pdo,"SELECT courses.*, COUNT(students.id) AS total_students FROM courses LEFT JOIN students ON students.course_id=courses.id GROUP BY courses.id ORDER BY courses.name ASC LIMIT 8");

portal_layout_start('dashboard', 'Painel da Direção', 'Indicadores estratégicos e visão académica do portal.', $forcedRole);
?>
<div class="ui-kpi-grid">
    <div class="ui-kpi"><span>Cursos ativos</span><strong><?= e((string)($metrics['courses'])); ?></strong><small>Oferta em funcionamento.</small></div>
    <div class="ui-kpi"><span>Alunos ativos</span><strong><?= e((string)($metrics['students'])); ?></strong><small>Matrículas ativas.</small></div>
    <div class="ui-kpi"><span>Candidaturas</span><strong><?= e((string)($metrics['apps'])); ?></strong><small>Total recebido.</small></div>
    <div class="ui-kpi"><span>Por tratar</span><strong><?= e((string)($metrics['pending'])); ?></strong><small>Acompanhamento necessário.</small></div>
    <div class="ui-kpi"><span>Disciplinas</span><strong><?= e((string)($metrics['subjects'])); ?></strong><small>Unidades curriculares.</small></div>
    <div class="ui-kpi"><span>Turmas</span><strong><?= e((string)($metrics['classes'])); ?></strong><small>Turmas ativas.</small></div>
    <div class="ui-kpi"><span>Notas</span><strong><?= e((string)($metrics['grades'])); ?></strong><small>Registos de avaliação.</small></div>
    <div class="ui-kpi"><span>Valor confirmado</span><strong><?= e((string)($metrics['value'])); ?></strong><small>Pagamentos confirmados.</small></div>
</div>

<div class="ui-panel"><div class="ui-panel-header"><div><h2>Resumo por curso</h2><p>Indicadores principais para direção.</p></div></div><div class="ui-table-wrap"><table class="ui-table"><thead><tr><th>Curso</th><th>Nível</th><th>Estado</th><th>Alunos</th></tr></thead><tbody><?php if(empty($courses)): ?><tr><td colspan="4" class="ui-empty">Sem cursos.</td></tr><?php endif; ?><?php foreach($courses as $c): ?><tr><td><strong><?= e($c['code'] ?? '-'); ?></strong><small><?= e($c['name'] ?? '-'); ?></small></td><td><?= e($c['level'] ?? '-'); ?></td><td><span class="ui-badge <?= e(portal_badge_class($c['status'] ?? '')); ?>"><?= e(portal_label_status($c['status'] ?? '')); ?></span></td><td><?= (int)($c['total_students'] ?? 0); ?></td></tr><?php endforeach; ?></tbody></table></div></div>

<?php portal_layout_end(); ?>
