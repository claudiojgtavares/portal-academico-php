<?php
require_once __DIR__ . '/../includes/portal_layout.php';
require_any_role(['admin']);
$user = current_user();
$forcedRole = 'admin';

$metrics = [
 'users'=>portal_count($pdo,"SELECT COUNT(*) FROM users"),
 'courses'=>portal_count($pdo,"SELECT COUNT(*) FROM courses WHERE status='active'"),
 'apps'=>portal_count($pdo,"SELECT COUNT(*) FROM applications"),
 'students'=>portal_count($pdo,"SELECT COUNT(*) FROM students WHERE enrollment_status='active'"),
 'logs'=>portal_count($pdo,"SELECT COUNT(*) FROM activity_logs"),
 'payments'=>portal_count($pdo,"SELECT COUNT(*) FROM payments WHERE status='em_analise'"),
 'value'=>portal_money(portal_count($pdo,"SELECT COALESCE(SUM(amount_cve),0) FROM payments WHERE status='confirmado'")),
 'failed'=>portal_count($pdo,"SELECT COUNT(*) FROM login_attempts WHERE success=0 AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)")
];
$logs = portal_rows($pdo,"SELECT activity_logs.*, users.full_name FROM activity_logs LEFT JOIN users ON users.id=activity_logs.user_id ORDER BY activity_logs.created_at DESC LIMIT 8");

portal_layout_start('dashboard', 'Painel do Administrador', 'Controlo geral do Portal Académico.', $forcedRole);
?>
<div class="ui-kpi-grid">
    <div class="ui-kpi"><span>Utilizadores</span><strong><?= e((string)($metrics['users'])); ?></strong><small>Total de contas no sistema.</small></div>
    <div class="ui-kpi"><span>Cursos ativos</span><strong><?= e((string)($metrics['courses'])); ?></strong><small>Cursos disponíveis.</small></div>
    <div class="ui-kpi"><span>Candidaturas</span><strong><?= e((string)($metrics['apps'])); ?></strong><small>Pedidos registados.</small></div>
    <div class="ui-kpi"><span>Alunos ativos</span><strong><?= e((string)($metrics['students'])); ?></strong><small>Matrículas ativas.</small></div>
    <div class="ui-kpi"><span>Pagamentos em análise</span><strong><?= e((string)($metrics['payments'])); ?></strong><small>Aguardam validação.</small></div>
    <div class="ui-kpi"><span>Valor confirmado</span><strong><?= e((string)($metrics['value'])); ?></strong><small>Total confirmado.</small></div>
    <div class="ui-kpi"><span>Falhas recentes</span><strong><?= e((string)($metrics['failed'])); ?></strong><small>Tentativas falhadas recentes.</small></div>
    <div class="ui-kpi"><span>Logs</span><strong><?= e((string)($metrics['logs'])); ?></strong><small>Registos do sistema.</small></div>
</div>

<div class="ui-panel"><div class="ui-panel-header"><div><h2>Atividade recente</h2><p>Últimos registos do sistema.</p></div><a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/logs.php">Ver logs</a></div><div class="ui-table-wrap"><table class="ui-table"><thead><tr><th>Ação</th><th>Descrição</th><th>Utilizador</th><th>Data</th></tr></thead><tbody><?php if(empty($logs)): ?><tr><td colspan="4" class="ui-empty">Sem atividade recente.</td></tr><?php endif; ?><?php foreach($logs as $log): ?><tr><td><span class="ui-badge ui-badge-info"><?= e($log['action'] ?? '-'); ?></span></td><td><?= e($log['description'] ?? '-'); ?></td><td><?= e($log['full_name'] ?? 'Sistema'); ?></td><td><?= e(portal_date($log['created_at'] ?? null)); ?></td></tr><?php endforeach; ?></tbody></table></div></div>

<?php portal_layout_end(); ?>
