<?php
require_once __DIR__ . '/../includes/portal_layout.php';
require_any_role(['admin','direcao']);

$user = current_user();
$role = user_has_role('admin') ? 'admin' : 'direcao';

function log_action_label(?string $action): string
{
    $map = [
        'LOGIN' => 'Entrada no portal',
        'LOGIN_SUCCESS' => 'Entrada com sucesso',
        'LOGIN_FAILED' => 'Falha de entrada',
        'ACCESS_DENIED' => 'Acesso negado',
        'APPLICATION_SUBMITTED' => 'Candidatura submetida',
        'APPLICATION_APPROVED' => 'Candidatura aprovada',
        'APPLICATION_REJECTED' => 'Candidatura rejeitada',
        'PAYMENT_STATUS_UPDATED' => 'Pagamento atualizado',
        'DOCUMENT_STATUS_UPDATED' => 'Documento atualizado',
        'USER_CREATED' => 'Utilizador criado',
        'USER_UPDATED' => 'Utilizador atualizado',
        'PASSWORD_CHANGED' => 'Palavra-passe alterada'
    ];

    return $map[$action ?? ''] ?? str_replace('_', ' ', ucfirst(strtolower((string) $action)));
}

$logs = portal_rows($pdo, "
    SELECT activity_logs.*, users.full_name
    FROM activity_logs
    LEFT JOIN users ON users.id = activity_logs.user_id
    ORDER BY activity_logs.created_at DESC
    LIMIT 200
");

portal_layout_start('logs', 'Registos do sistema', 'Auditoria de acessos, decisões e alterações no sistema.', $role);
?>

<div class="ui-kpi-grid">
    <div class="ui-kpi is-info">
        <span>Total</span>
        <strong><?= portal_count($pdo, "SELECT COUNT(*) FROM activity_logs"); ?></strong>
        <small>Registos guardados.</small>
    </div>

    <div class="ui-kpi is-good">
        <span>Entradas</span>
        <strong><?= portal_count($pdo, "SELECT COUNT(*) FROM activity_logs WHERE action IN ('LOGIN','LOGIN_SUCCESS')"); ?></strong>
        <small>Acessos ao portal.</small>
    </div>

    <div class="ui-kpi is-danger">
        <span>Acesso negado</span>
        <strong><?= portal_count($pdo, "SELECT COUNT(*) FROM activity_logs WHERE action='ACCESS_DENIED'"); ?></strong>
        <small>Tentativas bloqueadas.</small>
    </div>

    <div class="ui-kpi is-warn">
        <span>Falhas recentes</span>
        <strong><?= portal_count($pdo, "SELECT COUNT(*) FROM login_attempts WHERE success=0 AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)"); ?></strong>
        <small>Últimos 15 minutos.</small>
    </div>
</div>

<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2>Atividade registada</h2>
            <p><?= count($logs); ?> registos recentes.</p>
        </div>
    </div>

    <div class="ui-table-wrap">
        <table class="ui-table">
            <thead>
                <tr>
                    <th>Ação</th>
                    <th>Descrição</th>
                    <th>Utilizador</th>
                    <th>IP</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" class="ui-empty">Ainda não existem registos.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($logs as $l): ?>
                    <tr>
                        <td>
                            <span class="ui-badge ui-badge-info">
                                <?= e(log_action_label($l['action'] ?? '-')); ?>
                            </span>
                        </td>
                        <td><?= e($l['description'] ?? '-'); ?></td>
                        <td><?= e($l['full_name'] ?? 'Sistema'); ?></td>
                        <td><?= e($l['ip_address'] ?? '-'); ?></td>
                        <td><?= e(portal_date($l['created_at'] ?? null)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php portal_layout_end(); ?>
