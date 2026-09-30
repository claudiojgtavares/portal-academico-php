<?php
require_once __DIR__ . '/../includes/portal_layout.php';

require_any_role(['admin']);

[$success, $error] = portal_flash();
$q = trim($_GET['q'] ?? '');
$roleFilter = trim($_GET['perfil'] ?? '');

$roleOptions = [
    'admin' => 'Administrador',
    'secretaria' => 'Secretaria Académica',
    'direcao' => 'Direção Académica',
    'coordenador' => 'Coordenador de Curso',
    'professor' => 'Professor',
    'funcionario' => 'Funcionário Académico',
    'aluno' => 'Aluno',
];

$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(users.full_name LIKE ? OR users.email LIKE ? OR users.institutional_id LIKE ?)";
    $like = '%' . $q . '%';
    $params = [$like, $like, $like];
}
if ($roleFilter !== '') {
    $where[] = "EXISTS (SELECT 1 FROM user_roles ur INNER JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = users.id AND r.code = ?)";
    $params[] = $roleFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$users = portal_rows($pdo, "
    SELECT users.*, GROUP_CONCAT(roles.code ORDER BY roles.code SEPARATOR ',') AS role_codes
    FROM users
    LEFT JOIN user_roles ON user_roles.user_id = users.id
    LEFT JOIN roles ON roles.id = user_roles.role_id
    {$whereSql}
    GROUP BY users.id
    ORDER BY users.full_name ASC
    LIMIT 250
", $params);

portal_layout_start('perfis', 'Perfis de utilizadores', 'Defina quem é aluno, professor, coordenador, secretaria, direção, funcionário ou administrador.', 'admin');
?>

<?php if ($success): ?><div class="ui-alert ui-alert-success"><?= e($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="ui-alert ui-alert-error"><?= e($error); ?></div><?php endif; ?>

<div class="ui-section-note">
    Regra de negócio: <strong>o Administrador</strong> define os perfis de acesso. A Secretaria pode gerir processos académicos,
    mas não deve transformar uma conta em administrador, direção ou outro perfil sensível.
</div>

<div class="ui-panel">
    <div class="ui-panel-header"><div><h2>Pesquisar utilizadores</h2><p>Encontre a conta antes de alterar os perfis.</p></div></div>
    <div class="ui-panel-body">
        <form method="GET" class="ui-form-grid" style="grid-template-columns:minmax(260px,1fr) 260px auto auto;align-items:end;">
            <div class="ui-field"><label>Pesquisar</label><input class="ui-control" name="q" value="<?= e($q); ?>" placeholder="Nome, email ou ID institucional"></div>
            <div class="ui-field"><label>Perfil atual</label><select class="ui-control" name="perfil"><option value="">Todos os perfis</option><?php foreach($roleOptions as $code=>$label): ?><option value="<?= e($code); ?>" <?= $roleFilter===$code?'selected':''; ?>><?= e($label); ?></option><?php endforeach; ?></select></div>
            <button class="ui-btn ui-btn-primary">Pesquisar</button>
            <a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/perfis_utilizadores.php">Limpar</a>
        </form>
    </div>
</div>

<div class="ui-panel">
    <div class="ui-panel-header"><div><h2>Utilizadores e perfis</h2><p>Uma conta pode ter mais do que um perfil, por exemplo coordenador e professor.</p></div></div>
    <div class="ui-card-list">
        <?php if(empty($users)): ?><div class="ui-empty">Nenhum utilizador encontrado.</div><?php endif; ?>
        <?php foreach($users as $u): ?>
            <?php $userRoles = array_filter(explode(',', (string)($u['role_codes'] ?? ''))); ?>
            <div class="ui-card-row" style="grid-template-columns:1.2fr 2fr;align-items:start;">
                <div class="ui-person">
                    <div class="ui-person-avatar"><?= e(portal_initials_safe($u['full_name'] ?? '')); ?></div>
                    <div>
                        <strong><?= e($u['full_name'] ?? '-'); ?></strong>
                        <small><?= e($u['institutional_id'] ?? '-'); ?></small>
                        <small><?= e($u['email'] ?? '-'); ?></small>
                    </div>
                </div>

                <form method="POST" action="<?= e(APP_URL); ?>/actions/salvar_perfil_utilizador.php" class="ui-form-grid" style="grid-template-columns:1fr 180px auto;align-items:end;">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id']; ?>">
                    <div class="ui-field">
                        <label>Perfis de acesso</label>
                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                            <?php foreach($roleOptions as $code=>$label): ?>
                                <label class="ui-badge" style="cursor:pointer;background:#f7f8fb;color:var(--ui-heading);border:1px solid var(--ui-border);">
                                    <input type="checkbox" name="roles[]" value="<?= e($code); ?>" <?= in_array($code, $userRoles, true)?'checked':''; ?>>
                                    <?= e($label); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="ui-field"><label>Estado</label><select class="ui-control" name="status"><option value="active" <?= ($u['status']??'')==='active'?'selected':''; ?>>Ativo</option><option value="inactive" <?= ($u['status']??'')==='inactive'?'selected':''; ?>>Inativo</option><option value="blocked" <?= ($u['status']??'')==='blocked'?'selected':''; ?>>Bloqueado</option></select></div>
                    <button class="ui-btn ui-btn-primary" type="submit">Guardar perfis</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php portal_layout_end(); ?>
