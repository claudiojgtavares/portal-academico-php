<?php
require_once __DIR__ . '/../includes/portal_layout.php';
require_any_role(['aluno','admin']);
$user = current_user();
$forcedRole = 'aluno';

$student=portal_one($pdo,"SELECT students.*, courses.code AS course_code, courses.name AS course_name, academic_years.name AS academic_year FROM students LEFT JOIN courses ON courses.id=students.course_id LEFT JOIN academic_years ON academic_years.id=students.academic_year_id WHERE students.user_id=? LIMIT 1",[(int)$user['id']]);
$studentId=(int)($student['id'] ?? 0);
$metrics=['notifs'=>portal_count($pdo,"SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0",[(int)$user['id']]),'docs'=>portal_count($pdo,"SELECT COUNT(*) FROM document_requests WHERE student_id=?",[$studentId]),'payments'=>portal_count($pdo,"SELECT COUNT(*) FROM payments WHERE student_id=? AND status='em_analise'",[$studentId]),'value'=>portal_money(portal_count($pdo,"SELECT COALESCE(SUM(amount_cve),0) FROM payments WHERE student_id=? AND status='confirmado'",[$studentId]))];
$notifs=portal_rows($pdo,"SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 5",[(int)$user['id']]);

portal_layout_start('dashboard', 'Painel do Aluno', 'Resumo académico, documentos, propinas e notificações.', $forcedRole);
?>

<div class="ui-panel"><div class="ui-panel-body"><div class="ui-split"><div><h2><?= e(($student['course_code'] ?? '-') . ' — ' . ($student['course_name'] ?? 'Curso não associado')); ?></h2><p style="color:var(--ui-muted);">Ano letivo: <?= e($student['academic_year'] ?? '-'); ?> · Ano: <?= e((string)($student['current_year'] ?? '1')); ?></p></div><div class="ui-kpi" style="box-shadow:none;"><span>Estado da matrícula</span><strong><?= e(portal_label_status($student['enrollment_status'] ?? 'active')); ?></strong><small>ID: <?= e($student['student_code'] ?? ($user['institutional_id'] ?? '')); ?></small></div></div></div></div>
<div class="ui-kpi-grid">
    <div class="ui-kpi"><span>Notificações novas</span><strong><?= e((string)($metrics['notifs'])); ?></strong><small>Mensagens por ler.</small></div>
    <div class="ui-kpi"><span>Pedidos de documentos</span><strong><?= e((string)($metrics['docs'])); ?></strong><small>Solicitações feitas.</small></div>
    <div class="ui-kpi"><span>Pagamentos em análise</span><strong><?= e((string)($metrics['payments'])); ?></strong><small>Comprovativos enviados.</small></div>
    <div class="ui-kpi"><span>Valor confirmado</span><strong><?= e((string)($metrics['value'])); ?></strong><small>Pagamentos confirmados.</small></div>
</div>

<div class="ui-panel"><div class="ui-panel-header"><div><h2>Notificações recentes</h2><p>Últimas mensagens associadas à sua conta.</p></div><a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/notificacoes.php">Ver todas</a></div><div class="ui-table-wrap"><table class="ui-table"><thead><tr><th>Título</th><th>Mensagem</th><th>Data</th></tr></thead><tbody><?php if(empty($notifs)): ?><tr><td colspan="3" class="ui-empty">Sem notificações.</td></tr><?php endif; ?><?php foreach($notifs as $n): ?><tr><td><?= e($n['title'] ?? 'Notificação'); ?></td><td><?= e($n['message'] ?? ''); ?></td><td><?= e(portal_date($n['created_at'] ?? null)); ?></td></tr><?php endforeach; ?></tbody></table></div></div>

<?php portal_layout_end(); ?>
