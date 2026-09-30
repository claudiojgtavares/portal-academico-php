<?php
require_once __DIR__ . '/../includes/portal_layout.php';

require_login();

$user = current_user();
$role = portal_user_role($user);
$db = portal_one($pdo, "SELECT * FROM users WHERE id = ? LIMIT 1", [(int) $user['id']]) ?: $user;
[$success, $error] = portal_flash();

function perfil_data_pt(?string $value): string {
    if (!$value) return '-';
    $t = strtotime($value);
    return $t ? date('d/m/Y', $t) : $value;
}

portal_layout_start('perfil', 'Meu Perfil', 'Resumo da conta, fotografia e dados principais.', $role);
?>

<?php if ($success): ?><div class="ui-alert ui-alert-success"><?= e($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="ui-alert ui-alert-error"><?= e($error); ?></div><?php endif; ?>

<div class="ui-panel">
    <div class="ui-panel-body">
        <div class="profile-hero profile-hero-clean">
            <?php if (function_exists('portal_user_photo_url') && portal_user_photo_url($db) !== ''): ?>
                <img class="profile-avatar is-photo" src="<?= e(portal_user_photo_url($db)); ?>" alt="Fotografia de perfil">
            <?php else: ?>
                <div class="profile-avatar"><?= e(portal_initials_safe($db['full_name'] ?? '')); ?></div>
            <?php endif; ?>

            <div class="profile-title">
                <h2><?= e($db['full_name'] ?? 'Utilizador'); ?></h2>
                <p><?= e(portal_role_label($role)); ?></p>
                <p><?= e($db['institutional_id'] ?? '-'); ?> · <?= e($db['email'] ?? '-'); ?></p>

                <div class="ui-actions">
                    <a class="ui-btn ui-btn-primary" href="<?= e(APP_URL); ?>/pages/editar_perfil.php">Editar perfil</a>
                    <a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/alterar_password.php">Alterar senha</a>
                    <a class="ui-btn ui-btn-light" href="<?= e(APP_URL); ?>/dashboards/<?= e($role); ?>.php">Voltar ao painel</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="ui-kpi-grid">
    <div class="ui-kpi <?= ($db['status'] ?? '') === 'active' ? 'is-good' : 'is-danger'; ?>"><span>Estado da conta</span><strong><?= e(portal_label_status($db['status'] ?? '')); ?></strong><small>Acesso atual ao portal.</small></div>
    <div class="ui-kpi is-info"><span>Perfil principal</span><strong style="font-size:26px;"><?= e(portal_role_label($role)); ?></strong><small>Permissões aplicadas.</small></div>
    <div class="ui-kpi <?= (int) ($db['must_change_password'] ?? 0) === 1 ? 'is-warn' : 'is-good'; ?>"><span>Troca de senha</span><strong><?= (int) ($db['must_change_password'] ?? 0) === 1 ? 'Sim' : 'Não'; ?></strong><small><?= (int) ($db['must_change_password'] ?? 0) === 1 ? 'Obrigatória no próximo acesso.' : 'Senha regularizada.'; ?></small></div>
    <div class="ui-kpi <?= !empty($db['identity_document_file']) ? 'is-good' : 'is-warn'; ?>"><span>Documento</span><strong style="font-size:26px;"><?= !empty($db['identity_document_file']) ? 'Enviado' : 'Pendente'; ?></strong><small>Identificação do utilizador.</small></div>
</div>

<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2>Resumo do perfil</h2>
            <p>Dados principais registados no sistema.</p>
        </div>
        <a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/editar_perfil.php">Atualizar dados</a>
    </div>

    <div class="ui-panel-body">
        <div class="profile-info-grid">
            <div class="profile-info-item"><span>ID institucional</span><strong><?= e($db['institutional_id'] ?? '-'); ?></strong></div>
            <div class="profile-info-item"><span>Email institucional</span><strong><?= e($db['email'] ?? '-'); ?></strong></div>
            <div class="profile-info-item"><span>Email pessoal</span><strong><?= e(($db['personal_email'] ?? '') !== '' ? $db['personal_email'] : '-'); ?></strong></div>
            <div class="profile-info-item"><span>Telefone</span><strong><?= e(($db['phone'] ?? '') !== '' ? $db['phone'] : '-'); ?></strong></div>
            <div class="profile-info-item"><span>Data de nascimento</span><strong><?= e(perfil_data_pt($db['birth_date'] ?? null)); ?></strong></div>
            <div class="profile-info-item"><span>Documento</span><strong><?= e(trim(($db['document_type'] ?? '') . ' ' . ($db['document_number'] ?? '')) ?: '-'); ?></strong></div>
            <div class="profile-info-item profile-info-item-wide"><span>Morada</span><strong><?= e(($db['address'] ?? '') !== '' ? $db['address'] : '-'); ?></strong></div>
        </div>

        <div class="ui-section-note" style="margin-top:18px;">
            Dados institucionais como ID e email académico são geridos pela secretaria ou administração. O utilizador pode atualizar dados pessoais, fotografia e documento de identificação quando necessário.
        </div>
    </div>
</div>

<?php portal_layout_end(); ?>
