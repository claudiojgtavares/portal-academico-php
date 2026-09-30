<?php
require_once __DIR__ . '/../includes/portal_layout.php';
require_any_role(['secretaria','admin']);
$user = current_user();
$forcedRole = 'secretaria';

$metrics=['apps'=>portal_count($pdo,"SELECT COUNT(*) FROM applications"),'pending'=>portal_count($pdo,"SELECT COUNT(*) FROM applications WHERE status IN ('pending','in_review','missing_documents')"),'validated'=>portal_count($pdo,"SELECT COUNT(*) FROM applications WHERE status='documents_validated'"),'credentials'=>portal_count($pdo,"SELECT COUNT(*) FROM applications WHERE status='credentials_sent'"),'docs'=>portal_count($pdo,"SELECT COUNT(*) FROM document_requests WHERE status IN ('pending','in_review')"),'payments'=>portal_count($pdo,"SELECT COUNT(*) FROM payments WHERE status='em_analise'")];
$apps=portal_rows($pdo,"SELECT applications.*, courses.code AS course_code, courses.name AS course_name FROM applications LEFT JOIN courses ON courses.id=applications.course_id ORDER BY applications.created_at DESC LIMIT 8");

portal_layout_start('dashboard', 'Painel da Secretaria', 'Gestão académica, validações e acompanhamento operacional.', $forcedRole);
?>
<div class="ui-kpi-grid">
    <div class="ui-kpi"><span>Candidaturas</span><strong><?= e((string)($metrics['apps'])); ?></strong><small>Pedidos recebidos.</small></div>
    <div class="ui-kpi"><span>Por tratar</span><strong><?= e((string)($metrics['pending'])); ?></strong><small>Pendentes e em análise.</small></div>
    <div class="ui-kpi"><span>Documentos validados</span><strong><?= e((string)($metrics['validated'])); ?></strong><small>Prontas para credenciais.</small></div>
    <div class="ui-kpi"><span>Credenciais enviadas</span><strong><?= e((string)($metrics['credentials'])); ?></strong><small>Contas já criadas.</small></div>
    <div class="ui-kpi"><span>Pedidos de documentos</span><strong><?= e((string)($metrics['docs'])); ?></strong><small>Documentos solicitados.</small></div>
    <div class="ui-kpi"><span>Pagamentos em análise</span><strong><?= e((string)($metrics['payments'])); ?></strong><small>Comprovativos recebidos.</small></div>
</div>

<div class="ui-panel"><div class="ui-panel-header"><div><h2>Candidaturas recentes</h2><p>Últimos pedidos submetidos.</p></div><a class="ui-btn ui-btn-primary" href="<?= e(APP_URL); ?>/pages/candidaturas.php">Ver candidaturas</a></div><div class="ui-table-wrap"><table class="ui-table"><thead><tr><th>Candidato</th><th>Curso</th><th>Estado</th><th>Data</th><th>Ação</th></tr></thead><tbody><?php if(empty($apps)): ?><tr><td colspan="5" class="ui-empty">Sem candidaturas.</td></tr><?php endif; ?><?php foreach($apps as $a): ?><tr><td><strong><?= e($a['full_name']); ?></strong><small><?= e($a['application_code']); ?></small></td><td><?= e(($a['course_code'] ?? '-') . ' — ' . ($a['course_name'] ?? '-')); ?></td><td><span class="ui-badge <?= e(portal_badge_class($a['status'] ?? '')); ?>"><?= e(portal_label_status($a['status'] ?? '')); ?></span></td><td><?= e(portal_date($a['created_at'] ?? null)); ?></td><td><a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/candidatura_detalhes.php?id=<?= (int)$a['id']; ?>">Abrir</a></td></tr><?php endforeach; ?></tbody></table></div></div>

<?php portal_layout_end(); ?>
