<?php
require_once __DIR__ . '/../includes/portal_layout.php';

require_login();

$user = current_user();
$uid = (int) $user['id'];
$items = portal_rows($pdo, "SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC, id DESC LIMIT 100", [$uid]);
$total = count($items);
$unread = portal_count($pdo, "SELECT COUNT(*) FROM notifications WHERE user_id=? AND (is_read=0 OR is_read IS NULL)", [$uid]);
$read = max(0, $total - $unread);

portal_layout_start('notificacoes', 'Notificações', 'Acompanhe mensagens, avisos e atualizações do portal.');
?>

<div class="ui-kpi-grid">
    <div class="ui-kpi"><span>Total</span><strong><?= (int) $total; ?></strong><small>Mensagens da conta.</small></div>
    <div class="ui-kpi"><span>Não lidas</span><strong><?= (int) $unread; ?></strong><small>Requerem atenção.</small></div>
    <div class="ui-kpi"><span>Lidas</span><strong><?= (int) $read; ?></strong><small>Já abertas.</small></div>
    <div class="ui-kpi"><span>Estado</span><strong style="font-size:26px"><?= $unread > 0 ? 'Com avisos' : 'Em dia'; ?></strong><small><?= $unread > 0 ? 'Existem notificações novas.' : 'Sem pendências novas.'; ?></small></div>
</div>

<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2>Minhas notificações</h2>
            <p>Clique numa mensagem para marcá-la como lida.</p>
        </div>
    </div>

    <div class="ui-panel-body">
        <div class="ui-card-list">
            <?php if (empty($items)): ?>
                <div class="ui-empty">Ainda não existem notificações.</div>
            <?php endif; ?>

            <?php foreach ($items as $n): ?>
                <?php $isUnread = (int) ($n['is_read'] ?? 0) === 0; ?>
                <a
                    class="ui-card-row"
                    style="text-decoration:none;color:inherit;background:<?= $isUnread ? '#fff8e8' : '#fff'; ?>;"
                    href="<?= e(APP_URL); ?>/actions/ler_notificacao.php?id=<?= (int) $n['id']; ?>&redirect=<?= urlencode('/pages/notificacao.php?id=' . (int) $n['id']); ?>"
                >
                    <div style="display:flex;justify-content:space-between;gap:18px;flex-wrap:wrap;">
                        <div style="min-width:0;">
                            <span class="ui-badge <?= e(portal_badge_class($n['type'] ?? 'info')); ?>"><?= e(portal_label_status($n['type'] ?? 'info')); ?></span>
                            <h3><?= e($n['title'] ?? 'Notificação'); ?></h3>
                            <p style="color:var(--ui-muted)"><?= nl2br(e($n['message'] ?? '')); ?></p>
                        </div>
                        <div style="text-align:right;color:var(--ui-muted);font-weight:900;min-width:120px;">
                            <?= $isUnread ? 'Não lida' : 'Lida'; ?><br>
                            <small><?= e(portal_date($n['created_at'] ?? null)); ?></small>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php portal_layout_end(); ?>
