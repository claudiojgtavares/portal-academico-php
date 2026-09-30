<?php

require_once __DIR__ . '/../includes/portal_layout.php';

require_any_role(['admin', 'secretaria', 'funcionario']);

$role = user_has_role('admin') ? 'admin' : (user_has_role('funcionario') ? 'funcionario' : 'secretaria');
$status = trim($_GET['status'] ?? 'todos');
$allowed = ['todos', 'pending', 'in_review', 'ready', 'rejected', 'delivered'];

if (!in_array($status, $allowed, true)) {
    $status = 'todos';
}

$where = $status === 'todos' ? '' : 'WHERE document_requests.status = ?';
$params = $status === 'todos' ? [] : [$status];

$requests = portal_rows($pdo, "
    SELECT
        document_requests.*,
        students.student_code,
        users.full_name,
        users.email,
        courses.code AS course_code,
        courses.name AS course_name
    FROM document_requests
    INNER JOIN students ON students.id = document_requests.student_id
    INNER JOIN users ON users.id = students.user_id
    LEFT JOIN courses ON courses.id = students.course_id
    {$where}
    ORDER BY document_requests.requested_at DESC
", $params);

$metrics = [
    'pending' => portal_count($pdo, "SELECT COUNT(*) FROM document_requests WHERE status = 'pending'"),
    'review' => portal_count($pdo, "SELECT COUNT(*) FROM document_requests WHERE status = 'in_review'"),
    'ready' => portal_count($pdo, "SELECT COUNT(*) FROM document_requests WHERE status = 'ready'"),
    'rejected' => portal_count($pdo, "SELECT COUNT(*) FROM document_requests WHERE status = 'rejected'"),
];

[$success, $error] = portal_flash();

portal_layout_start(
    'documentos',
    'Validação de Documentos',
    'Analisar pedidos, disponibilizar ficheiros e comunicar o estado ao aluno.',
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
    <div class="ui-kpi is-warn">
        <span>Pendentes</span>
        <strong><?= (int) $metrics['pending']; ?></strong>
        <small>Aguardam primeira análise.</small>
    </div>

    <div class="ui-kpi is-info">
        <span>Em análise</span>
        <strong><?= (int) $metrics['review']; ?></strong>
        <small>Pedidos sendo tratados.</small>
    </div>

    <div class="ui-kpi is-good">
        <span>Prontos</span>
        <strong><?= (int) $metrics['ready']; ?></strong>
        <small>Documentos emitidos para entrega.</small>
    </div>

    <div class="ui-kpi is-danger">
        <span>Rejeitados</span>
        <strong><?= (int) $metrics['rejected']; ?></strong>
        <small>Pedidos recusados ou com erro.</small>
    </div>
</div>

<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2>Pedidos recebidos</h2>
            <p>Total listado: <?= count($requests); ?> pedido(s).</p>
        </div>

        <div class="ui-actions">
            <?php foreach (['todos' => 'Todos', 'pending' => 'Pendentes', 'in_review' => 'Em análise', 'ready' => 'Prontos', 'rejected' => 'Rejeitados'] as $key => $label): ?>
                <a class="ui-btn <?= $status === $key ? 'ui-btn-primary' : 'ui-btn-ghost'; ?>" href="<?= e(APP_URL); ?>/pages/validar_documentos.php?status=<?= e($key); ?>">
                    <?= e($label); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="ui-panel-body">
        <?php if (empty($requests)): ?>
            <div class="ui-empty">Não existem pedidos para este filtro.</div>
        <?php else: ?>
            <div class="ui-card-list">
                <?php foreach ($requests as $request): ?>
                    <article class="document-card">
                        <div class="document-card-main">
                            <div class="ui-toolbar">
                                <div class="ui-person">
                                    <div class="ui-person-avatar"><?= e(portal_initials_safe($request['full_name'] ?? '')); ?></div>
                                    <div>
                                        <strong><?= e($request['full_name'] ?? '-'); ?></strong>
                                        <small><?= e($request['student_code'] ?? '-'); ?> · <?= e($request['course_code'] ?? '-'); ?></small>
                                        <small><?= e($request['email'] ?? '-'); ?></small>
                                    </div>
                                </div>

                                <span class="ui-badge <?= e(portal_badge_class($request['status'] ?? '')); ?>">
                                    <?= e(portal_label_status($request['status'] ?? '')); ?>
                                </span>
                            </div>

                            <div class="ui-soft-card">
                                <h3 class="document-title"><?= e($request['document_type'] ?? 'Documento'); ?></h3>
                                <div class="ui-stat-inline">
                                    <span>Finalidade</span>
                                    <strong><?= e($request['purpose'] ?: '-'); ?></strong>
                                </div>
                                <div class="ui-stat-inline">
                                    <span>Pedido em</span>
                                    <strong><?= e(portal_date($request['requested_at'] ?? null)); ?></strong>
                                </div>
                                <?php if (!empty($request['notes'])): ?>
                                    <div class="ui-section-note"><?= nl2br(e($request['notes'])); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="ui-actions">
                                <?php if (!empty($request['file_path'])): ?>
                                    <a class="ui-btn ui-btn-light" target="_blank" href="<?= e(APP_URL . '/' . ltrim($request['file_path'], '/')); ?>">
                                        Abrir ficheiro emitido
                                    </a>
                                <?php else: ?>
                                    <span class="ui-badge ui-badge-muted">Sem ficheiro emitido</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="decision-box">
                            <form action="<?= e(APP_URL); ?>/actions/validar_documento.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="request_id" value="<?= (int) $request['id']; ?>">

                                <div class="ui-field">
                                    <label>Estado do pedido</label>
                                    <select class="ui-control" name="status" required>
                                        <?php foreach (['pending' => 'Pendente', 'in_review' => 'Em análise', 'ready' => 'Pronto', 'rejected' => 'Rejeitado', 'delivered' => 'Entregue'] as $key => $label): ?>
                                            <option value="<?= e($key); ?>" <?= ($request['status'] ?? '') === $key ? 'selected' : ''; ?>><?= e($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="ui-field">
                                    <label>Observação para o aluno</label>
                                    <textarea class="ui-control" name="notes" placeholder="Ex.: documento pronto para levantamento ou precisa de correção..."><?= e($request['notes'] ?? ''); ?></textarea>
                                </div>

                                <div class="ui-field">
                                    <label>Ficheiro emitido</label>
                                    <input class="ui-control" type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png">
                                    <small>Opcional. Use quando o documento estiver pronto.</small>
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
