<?php
require_once __DIR__ . '/../includes/portal_layout.php';
require_login();

$user = current_user();
$id = (int) ($_GET['id'] ?? 0);
$role = portal_user_role($user);

$notification = null;
if ($id > 0) {
    $notification = portal_one($pdo, "SELECT * FROM notifications WHERE id = ? AND user_id = ? LIMIT 1", [$id, (int) $user['id']]);

    if ($notification) {
        try {
            $columns = portal_db_columns($pdo, 'notifications');
            if (in_array('is_read', $columns, true)) {
                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, (int) $user['id']]);
            } elseif (in_array('read_at', $columns, true)) {
                $stmt = $pdo->prepare("UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, (int) $user['id']]);
            }
        } catch (Throwable $error) {}
    }
}

portal_layout_start('notificacoes', 'Ler notificação', 'Mensagem completa e estado de leitura.', $role);
?>

<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2><?= e($notification['title'] ?? 'Notificação não encontrada'); ?></h2>
            <p><?= $notification ? e(portal_date($notification['created_at'] ?? null)) : 'A mensagem pode ter sido removida ou não pertence à sua conta.'; ?></p>
        </div>
        <a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/notificacoes.php">Voltar às notificações</a>
    </div>

    <div class="ui-panel-body">
        <?php if (!$notification): ?>
            <div class="ui-empty">Não foi possível abrir esta notificação.</div>
        <?php else: ?>
            <div style="display:grid;gap:18px;max-width:920px;">
                <div>
                    <span class="ui-badge <?= e(portal_badge_class($notification['type'] ?? 'info')); ?>"><?= e(portal_label_status($notification['type'] ?? 'info')); ?></span>
                </div>

                <article class="ui-card-row" style="grid-template-columns:1fr;">
                    <h3 style="margin:0;color:var(--ui-heading);"><?= e($notification['title'] ?? 'Notificação'); ?></h3>
                    <p style="font-size:16px;line-height:1.75;color:var(--ui-text);margin:0;">
                        <?= nl2br(e($notification['message'] ?? '')); ?>
                    </p>
                </article>

                <div class="ui-section-note">
                    Esta notificação foi marcada como lida automaticamente ao abrir esta página.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php portal_layout_end(); ?>
