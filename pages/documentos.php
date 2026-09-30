<?php
require_once __DIR__ . '/../includes/portal_layout.php';
require_login();

$user = current_user();
$role = portal_user_role($user);
$studentId = 0;
if ($role === 'aluno') {
    $studentId = (int) portal_count($pdo, "SELECT id FROM students WHERE user_id = ? LIMIT 1", [(int) $user['id']]);
}

[$success, $error] = portal_flash();

if ($role === 'aluno') {
    $rows = $studentId ? portal_rows($pdo, "SELECT * FROM document_requests WHERE student_id = ? ORDER BY requested_at DESC, id DESC", [$studentId]) : [];
} else {
    $rows = portal_rows($pdo, "
        SELECT document_requests.*, users.full_name, students.student_code
        FROM document_requests
        LEFT JOIN students ON students.id = document_requests.student_id
        LEFT JOIN users ON users.id = students.user_id
        ORDER BY document_requests.requested_at DESC, document_requests.id DESC
        LIMIT 80
    ");
}

portal_layout_start('documentos', 'Documentos', $role === 'aluno' ? 'Solicite documentos académicos e acompanhe o estado do pedido.' : 'Consulta operacional de pedidos de documentos.', $role);
?>

<?php if ($success): ?><div class="ui-alert ui-alert-success"><?= e($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="ui-alert ui-alert-error"><?= e($error); ?></div><?php endif; ?>

<div class="ui-kpi-grid">
    <div class="ui-kpi"><span>Total</span><strong><?= count($rows); ?></strong><small>Pedidos registados.</small></div>
    <div class="ui-kpi is-warn"><span>Em análise</span><strong><?= $studentId ? portal_count($pdo, "SELECT COUNT(*) FROM document_requests WHERE student_id=? AND status IN ('pending','in_review')", [$studentId]) : portal_count($pdo, "SELECT COUNT(*) FROM document_requests WHERE status IN ('pending','in_review')"); ?></strong><small>Em tratamento.</small></div>
    <div class="ui-kpi is-good"><span>Prontos</span><strong><?= $studentId ? portal_count($pdo, "SELECT COUNT(*) FROM document_requests WHERE student_id=? AND status='ready'", [$studentId]) : portal_count($pdo, "SELECT COUNT(*) FROM document_requests WHERE status='ready'"); ?></strong><small>Disponíveis.</small></div>
    <div class="ui-kpi is-danger"><span>Rejeitados</span><strong><?= $studentId ? portal_count($pdo, "SELECT COUNT(*) FROM document_requests WHERE student_id=? AND status='rejected'", [$studentId]) : portal_count($pdo, "SELECT COUNT(*) FROM document_requests WHERE status='rejected'"); ?></strong><small>Com observação.</small></div>
</div>

<?php if ($role === 'aluno'): ?>
    <div class="ui-split">
        <div class="ui-panel">
            <div class="ui-panel-header">
                <div>
                    <h2>Solicitar documento</h2>
                    <p>Escolha o documento, informe a finalidade e acompanhe o estado no portal.</p>
                </div>
            </div>
            <div class="ui-panel-body">
                <form method="POST" action="<?= e(APP_URL); ?>/actions/solicitar_documento.php" class="ui-form-grid">
                    <div class="ui-field">
                        <label>Tipo de documento</label>
                        <select class="ui-control" name="document_type" required>
                            <option value="">Selecionar documento</option>
                            <option value="Declaração de matrícula">Declaração de matrícula</option>
                            <option value="Declaração de frequência">Declaração de frequência</option>
                            <option value="Histórico académico">Histórico académico</option>
                            <option value="Certidão de notas">Certidão de notas</option>
                            <option value="Comprovativo de inscrição">Comprovativo de inscrição</option>
                            <option value="Outro documento académico">Outro documento académico</option>
                        </select>
                    </div>
                    <div class="ui-field">
                        <label>Finalidade</label>
                        <input class="ui-control" name="purpose" placeholder="Ex.: matrícula, bolsa, trabalho, visto..." required>
                    </div>
                    <div class="ui-field" style="grid-column:1/-1;">
                        <label>Observação</label>
                        <textarea class="ui-control" name="notes" placeholder="Escreva detalhes adicionais, se necessário."></textarea>
                    </div>
                    <div class="ui-actions" style="grid-column:1/-1;">
                        <button class="ui-btn ui-btn-primary" type="submit">Enviar pedido</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="ui-panel">
            <div class="ui-panel-header"><div><h2>Como funciona?</h2><p>Fluxo simples do pedido.</p></div></div>
            <div class="ui-panel-body">
                <ol class="pub-flow" style="margin:0;">
                    <li>Aluno solicita o documento no portal.</li>
                    <li>Secretaria recebe e coloca em análise.</li>
                    <li>Secretaria emite o ficheiro ou solicita correção.</li>
                    <li>Aluno recebe notificação e descarrega o documento, quando estiver pronto.</li>
                </ol>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="ui-section-note">
        Para validar, emitir ou rejeitar pedidos, use a área <strong>Validar documentos</strong>. Esta página é a visão de consulta dos pedidos.
    </div>
<?php endif; ?>

<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2><?= $role === 'aluno' ? 'Meus pedidos' : 'Pedidos recentes'; ?></h2>
            <p>Acompanhe estado, observações e ficheiros emitidos.</p>
        </div>
        <?php if ($role !== 'aluno'): ?>
            <a class="ui-btn ui-btn-primary" href="<?= e(APP_URL); ?>/pages/validar_documentos.php">Validar documentos</a>
        <?php endif; ?>
    </div>

    <div class="ui-panel-body">
        <div class="ui-card-list">
            <?php if (empty($rows)): ?>
                <div class="ui-empty">Ainda não existem pedidos de documentos.</div>
            <?php endif; ?>

            <?php foreach ($rows as $row): ?>
                <div class="ui-card-row" style="grid-template-columns:1fr auto;align-items:center;">
                    <div>
                        <?php if ($role !== 'aluno'): ?>
                            <small style="color:var(--ui-muted);font-weight:900;"><?= e(($row['full_name'] ?? '-') . ' · ' . ($row['student_code'] ?? '-')); ?></small>
                        <?php endif; ?>
                        <h3><?= e($row['document_type'] ?? 'Documento académico'); ?></h3>
                        <p style="color:var(--ui-muted);margin:6px 0 0;">
                            <?= e($row['purpose'] ?? 'Sem finalidade informada.'); ?> · Pedido em <?= e(portal_date($row['requested_at'] ?? null)); ?>
                        </p>
                        <?php if (!empty($row['notes'])): ?>
                            <p style="color:var(--ui-muted);margin:6px 0 0;"><?= e($row['notes']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($row['file_path'])): ?>
                            <a class="ui-btn ui-btn-ghost" style="margin-top:10px;" target="_blank" href="<?= e(APP_URL . '/' . $row['file_path']); ?>">Abrir documento emitido</a>
                        <?php endif; ?>
                    </div>
                    <span class="ui-badge <?= e(portal_badge_class($row['status'] ?? 'pending')); ?>"><?= e(portal_label_status($row['status'] ?? 'pending')); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php portal_layout_end(); ?>
