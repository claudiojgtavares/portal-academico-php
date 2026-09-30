<?php
require_once __DIR__ . '/../includes/portal_layout.php';
require_any_role(['admin']);

$user = current_user();
$q = trim($_GET['q'] ?? '');
$roleFilter = trim($_GET['perfil'] ?? '');
$statusFilter = trim($_GET['estado'] ?? '');

$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(full_name LIKE ? OR email LIKE ? OR institutional_id LIKE ? OR personal_email LIKE ?)";
    $like = '%' . $q . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
}
if ($roleFilter !== '') {
    $where[] = "(role = ? OR roles LIKE ?)";
    $params[] = $roleFilter;
    $params[] = '%' . $roleFilter . '%';
}
if ($statusFilter !== '') {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$users = portal_rows($pdo, "SELECT * FROM users {$whereSql} ORDER BY full_name ASC LIMIT 250", $params);
$roles = [
    '' => 'Todos os perfis',
    'admin' => 'Administrador',
    'secretaria' => 'Secretaria',
    'funcionario' => 'Funcionário',
    'direcao' => 'Direção',
    'coordenador' => 'Coordenador',
    'professor' => 'Professor',
    'aluno' => 'Aluno',
];

portal_layout_start('usuarios', 'Gestão de Utilizadores', 'Pesquise, filtre e aplique ações administrativas por conta.', 'admin');
?>

<div class="ui-kpi-grid">
    <div class="ui-kpi"><span>Total geral</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM users"); ?></strong><small>Contas registadas.</small></div>
    <div class="ui-kpi is-good"><span>Ativas</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM users WHERE status='active'"); ?></strong><small>Podem aceder.</small></div>
    <div class="ui-kpi is-danger"><span>Bloqueadas</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM users WHERE status='blocked'"); ?></strong><small>Acesso bloqueado.</small></div>
    <div class="ui-kpi is-warn"><span>Troca de senha</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM users WHERE must_change_password=1"); ?></strong><small>Obrigatória.</small></div>
</div>

<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2>Utilizadores</h2>
            <p><?= count($users); ?> resultado(s). Use filtros para encontrar contas por perfil, estado, nome, email ou ID.</p>
        </div>
    </div>

    <div class="ui-panel-body" style="border-bottom:1px solid var(--ui-divider);">
        <form method="GET" class="ui-form-grid" style="grid-template-columns:minmax(260px,1fr) 220px 200px auto auto;align-items:end;">
            <div class="ui-field"><label>Pesquisar</label><input class="ui-control" type="search" name="q" value="<?= e($q); ?>" placeholder="Nome, email ou ID institucional"></div>
            <div class="ui-field"><label>Perfil</label><select class="ui-control" name="perfil"><?php foreach($roles as $key=>$label): ?><option value="<?= e($key); ?>" <?= $roleFilter===$key?'selected':''; ?>><?= e($label); ?></option><?php endforeach; ?></select></div>
            <div class="ui-field"><label>Estado</label><select class="ui-control" name="estado"><option value="">Todos</option><option value="active" <?= $statusFilter==='active'?'selected':''; ?>>Ativo</option><option value="blocked" <?= $statusFilter==='blocked'?'selected':''; ?>>Bloqueado</option><option value="inactive" <?= $statusFilter==='inactive'?'selected':''; ?>>Inativo</option></select></div>
            <button class="ui-btn ui-btn-primary" type="submit">Pesquisar</button>
            <a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/usuarios.php">Limpar</a>
        </form>
    </div>

    <div class="ui-table-wrap">
        <table class="ui-table">
            <thead><tr><th>Utilizador</th><th>Perfil</th><th>Estado</th><th>Troca de senha</th><th>Ações</th></tr></thead>
            <tbody>
                <?php if(empty($users)): ?><tr><td colspan="5" class="ui-empty">Nenhum utilizador encontrado.</td></tr><?php endif; ?>
                <?php foreach($users as $u): ?>
                    <tr>
                        <td><div class="ui-person"><div class="ui-person-avatar"><?= e(portal_initials_safe($u['full_name'] ?? '')); ?></div><div><strong><?= e($u['full_name'] ?? '-'); ?></strong><small><?= e($u['institutional_id'] ?? '-'); ?></small><small><?= e($u['email'] ?? '-'); ?></small></div></div></td>
                        <td><strong><?= e(portal_role_label((string)($u['role'] ?? portal_user_role($u)))); ?></strong></td>
                        <td><span class="ui-badge <?= e(portal_badge_class($u['status'] ?? '')); ?>"><?= e(portal_label_status($u['status'] ?? '')); ?></span></td>
                        <td><?= ((int)($u['must_change_password'] ?? 0) === 1) ? 'Sim' : 'Não'; ?></td>
                        <td>
                            <form method="POST" action="<?= e(APP_URL); ?>/actions/gerir_usuario.php" class="ui-actions" style="gap:8px;">
                                <input type="hidden" name="user_id" value="<?= (int)$u['id']; ?>">
                                <button class="ui-btn ui-btn-ghost" name="acao" value="reset_password">Repor senha</button>
                                <button class="ui-btn ui-btn-ghost" name="acao" value="clear_attempts">Limpar tentativas</button>
                                <?php if (($u['status'] ?? '') === 'blocked'): ?>
                                    <button class="ui-btn ui-btn-secondary" name="acao" value="activate">Ativar</button>
                                <?php else: ?>
                                    <button class="ui-btn ui-btn-danger" name="acao" value="block">Bloquear</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php portal_layout_end(); ?>
