<?php

require_once __DIR__ . '/../includes/portal_layout.php';

require_any_role(['admin', 'secretaria', 'direcao', 'funcionario']);

$user = current_user();
$role = user_has_role('admin') ? 'admin' : (user_has_role('direcao') ? 'direcao' : (user_has_role('funcionario') ? 'funcionario' : 'secretaria'));
$filter = trim($_GET['status'] ?? 'todos');
$statusMap = [
    'todos' => null,
    'em_analise' => 'em_analise',
    'confirmado' => 'confirmado',
    'rejeitado' => 'rejeitado',
    'em_atraso' => 'em_atraso',
];

if (!array_key_exists($filter, $statusMap)) {
    $filter = 'todos';
}

$where = $statusMap[$filter] ? 'WHERE payments.status = ?' : '';
$params = $statusMap[$filter] ? [$statusMap[$filter]] : [];

$payments = portal_rows($pdo, "
    SELECT
        payments.*,
        fees.title,
        fees.month_reference,
        fees.amount_cve AS fee_amount,
        users.full_name,
        students.student_code,
        courses.code AS course_code,
        courses.name AS course_name
    FROM payments
    LEFT JOIN fees ON fees.id = payments.fee_id
    LEFT JOIN students ON students.id = payments.student_id
    LEFT JOIN users ON users.id = students.user_id
    LEFT JOIN courses ON courses.id = students.course_id
    {$where}
    ORDER BY payments.created_at DESC
    LIMIT 100
", $params);

$metrics = [
    'total' => portal_count($pdo, "SELECT COUNT(*) FROM payments"),
    'review' => portal_count($pdo, "SELECT COUNT(*) FROM payments WHERE status = 'em_analise'"),
    'confirmed' => portal_count($pdo, "SELECT COUNT(*) FROM payments WHERE status = 'confirmado'"),
    'bad' => portal_count($pdo, "SELECT COUNT(*) FROM payments WHERE status IN ('rejeitado', 'em_atraso')"),
];

[$success, $error] = portal_flash();

portal_layout_start(
    'pagamentos',
    'Validar pagamentos',
    'Analise comprovativos de propina submetidos pelos alunos.',
    $role
);

?>

<?php if ($success): ?>
    <div class="ui-alert ui-alert-success"><?= e($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="ui-alert ui-alert-error"><?= e($error); ?></div>
<?php endif; ?>

<div class="ui-kpi-grid">
    <div class="ui-kpi">
        <span>Total</span>
        <strong><?= (int) $metrics['total']; ?></strong>
        <small>Comprovativos registados.</small>
    </div>

    <div class="ui-kpi is-info">
        <span>Em análise</span>
        <strong><?= (int) $metrics['review']; ?></strong>
        <small>Aguardam decisão da secretaria.</small>
    </div>

    <div class="ui-kpi is-good">
        <span>Confirmados</span>
        <strong><?= (int) $metrics['confirmed']; ?></strong>
        <small>Pagamentos validados.</small>
    </div>

    <div class="ui-kpi is-danger">
        <span>Rejeitados / atraso</span>
        <strong><?= (int) $metrics['bad']; ?></strong>
        <small>Com erro, rejeitados ou em atraso.</small>
    </div>
</div>

<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2>Comprovativos recebidos</h2>
            <p>Total listado: <?= count($payments); ?> comprovativo(s).</p>
        </div>

        <div class="ui-actions">
            <?php foreach (['todos' => 'Todos', 'em_analise' => 'Em análise', 'confirmado' => 'Confirmados', 'rejeitado' => 'Rejeitados', 'em_atraso' => 'Em atraso'] as $key => $label): ?>
                <a class="ui-btn <?= $filter === $key ? 'ui-btn-primary' : 'ui-btn-ghost'; ?>" href="<?= e(APP_URL); ?>/pages/validar_pagamentos.php?status=<?= e($key); ?>">
                    <?= e($label); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="ui-panel-body">
        <?php if (empty($payments)): ?>
            <div class="ui-empty">Nenhum comprovativo encontrado para este filtro.</div>
        <?php else: ?>
            <div class="ui-card-list">
                <?php foreach ($payments as $payment): ?>
                    <?php $amount = $payment['amount_cve'] ?? $payment['fee_amount'] ?? 0; ?>
                    <article class="payment-card">
                        <div class="payment-card-main">
                            <div class="ui-toolbar">
                                <div class="ui-person">
                                    <div class="ui-person-avatar"><?= e(portal_initials_safe($payment['full_name'] ?? '')); ?></div>
                                    <div>
                                        <strong><?= e($payment['full_name'] ?? 'Aluno'); ?></strong>
                                        <small><?= e($payment['student_code'] ?? '-'); ?></small>
                                        <small><?= e(($payment['course_code'] ?? '-') . ' - ' . ($payment['course_name'] ?? '')); ?></small>
                                    </div>
                                </div>

                                <span class="ui-badge <?= e(portal_badge_class($payment['status'] ?? '')); ?>">
                                    <?= e(portal_label_status($payment['status'] ?? '')); ?>
                                </span>
                            </div>

                            <div class="ui-soft-card">
                                <h3 class="payment-title"><?= e($payment['title'] ?? 'Propina'); ?></h3>
                                <div class="ui-stat-inline">
                                    <span>Valor declarado</span>
                                    <strong><?= e(portal_money($amount)); ?></strong>
                                </div>
                                <div class="ui-stat-inline">
                                    <span>Método</span>
                                    <strong><?= e($payment['payment_method'] ?? '-'); ?></strong>
                                </div>
                                <div class="ui-stat-inline">
                                    <span>Referência</span>
                                    <strong><?= e($payment['reference'] ?? '-'); ?></strong>
                                </div>
                                <div class="ui-stat-inline">
                                    <span>Submetido em</span>
                                    <strong><?= e(portal_date($payment['created_at'] ?? null)); ?></strong>
                                </div>
                            </div>

                            <div class="ui-actions">
                                <?php if (!empty($payment['proof_file'])): ?>
                                    <a class="ui-btn ui-btn-light" target="_blank" href="<?= e(APP_URL . '/' . ltrim($payment['proof_file'], '/')); ?>">
                                        Abrir comprovativo
                                    </a>
                                <?php else: ?>
                                    <span class="ui-badge ui-badge-muted">Sem anexo</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="decision-box">
                            <form action="<?= e(APP_URL); ?>/actions/validar_pagamento.php" method="POST">
                                <input type="hidden" name="payment_id" value="<?= (int) $payment['id']; ?>">

                                <div class="ui-field">
                                    <label>Estado do pagamento</label>
                                    <select name="status" class="ui-control" required>
                                        <?php foreach (['em_analise' => 'Em análise', 'confirmado' => 'Confirmado', 'rejeitado' => 'Rejeitado', 'em_atraso' => 'Em atraso'] as $key => $label): ?>
                                            <option value="<?= e($key); ?>" <?= ($payment['status'] ?? '') === $key ? 'selected' : ''; ?>><?= e($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="ui-field">
                                    <label>Observação para o aluno</label>
                                    <textarea name="notes" class="ui-control" placeholder="Ex.: comprovativo confirmado, valor divergente, referência ilegível..."><?= e($payment['notes'] ?? ''); ?></textarea>
                                </div>

                                <button class="ui-btn ui-btn-primary" type="submit" style="width:100%;">
                                    Guardar decisão
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php portal_layout_end(); ?>
