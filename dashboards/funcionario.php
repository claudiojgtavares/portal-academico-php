<?php
require_once __DIR__ . '/../includes/portal_layout.php';
require_any_role(['funcionario','admin']);
$user = current_user();
$forcedRole = 'funcionario';

$metrics=['docs'=>portal_count($pdo,"SELECT COUNT(*) FROM document_requests WHERE status IN ('pending','in_review')"),'payments'=>portal_count($pdo,"SELECT COUNT(*) FROM payments WHERE status='em_analise'"),'apps'=>portal_count($pdo,"SELECT COUNT(*) FROM applications WHERE status IN ('pending','in_review','missing_documents')"),'notifs'=>portal_count($pdo,"SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0",[(int)$user['id']])];
$logs=portal_rows($pdo,"SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 6");

portal_layout_start('dashboard', 'Painel do Funcionário', 'Tarefas operacionais e notificações internas.', $forcedRole);
?>
<div class="ui-kpi-grid">
    <div class="ui-kpi"><span>Documentos pendentes</span><strong><?= e((string)($metrics['docs'])); ?></strong><small>Pedidos para apoio.</small></div>
    <div class="ui-kpi"><span>Pagamentos em análise</span><strong><?= e((string)($metrics['payments'])); ?></strong><small>Comprovativos pendentes.</small></div>
    <div class="ui-kpi"><span>Candidaturas por tratar</span><strong><?= e((string)($metrics['apps'])); ?></strong><small>Pedidos em fluxo.</small></div>
    <div class="ui-kpi"><span>Notificações</span><strong><?= e((string)($metrics['notifs'])); ?></strong><small>Mensagens por ler.</small></div>
</div>

<div class="ui-panel"><div class="ui-panel-header"><div><h2>Atividade recente</h2><p>Registos úteis para acompanhamento.</p></div></div><div class="ui-table-wrap"><table class="ui-table"><thead><tr><th>Ação</th><th>Descrição</th><th>Data</th></tr></thead><tbody><?php if(empty($logs)): ?><tr><td colspan="3" class="ui-empty">Sem atividade.</td></tr><?php endif; ?><?php foreach($logs as $l): ?><tr><td><?= e($l['action'] ?? '-'); ?></td><td><?= e($l['description'] ?? '-'); ?></td><td><?= e(portal_date($l['created_at'] ?? null)); ?></td></tr><?php endforeach; ?></tbody></table></div></div>

<?php portal_layout_end(); ?>
