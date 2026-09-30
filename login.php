<?php

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    $user = current_user();

    if ($user && (int) ($user['must_change_password'] ?? 0) === 1) {
        redirect(APP_URL . '/pages/alterar_password.php?aviso=inicial');
    }

    if ($user) {
        redirect_by_role($user['roles_array']);
    }
}

$erro = $_GET['erro'] ?? null;
$sucesso = $_GET['sucesso'] ?? null;

$mensagensErro = [
    'credenciais' => 'Credenciais inválidas. Verifique o e-mail, ID institucional ou palavra-passe.',
    'sessao' => 'A sua sessão expirou ou ainda não iniciou sessão.',
    'permissao' => 'Não tem permissão para aceder a essa área.',
    'perfil' => 'O seu perfil ainda não possui painel definido.',
    'bloqueado' => 'Esta conta está bloqueada ou suspensa.',
    'tentativas' => 'Demasiadas tentativas falhadas. Aguarde 15 minutos e tente novamente.'
];

$mensagensSucesso = [
    'password_alterada' => 'Palavra-passe alterada com sucesso. Entre novamente no portal.'
];

?>
<!DOCTYPE html>
<html lang="pt-CV">
<head>
    <meta charset="UTF-8">
    <title>Entrar — <?= e(APP_NAME); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()); ?>">
    <link rel="stylesheet" href="<?= e(APP_URL); ?>/assets/css/style.css">
</head>
<body>
    <main class="auth-page">
        <div class="auth-wrap">
            <a class="auth-back" href="<?= e(APP_URL); ?>/index.php">← voltar</a>

            <section class="auth-card">
                <div class="auth-logo">
                    <div class="auth-logo-mark">PA</div>
                    <strong><?= e(INSTITUTION_SHORT_NAME); ?></strong>
                    <span><?= e(INSTITUTION_NAME); ?></span>
                </div>

                <h1 class="auth-title">Entrar no portal</h1>
                <p class="auth-subtitle">Use o seu e-mail ou ID institucional.</p>

                <?php if ($erro && isset($mensagensErro[$erro])): ?>
                    <div class="alert alert-error">
                        <?= e($mensagensErro[$erro]); ?>
                    </div>
                <?php endif; ?>

                <?php if ($sucesso && isset($mensagensSucesso[$sucesso])): ?>
                    <div class="alert alert-success">
                        <?= e($mensagensSucesso[$sucesso]); ?>
                    </div>
                <?php endif; ?>

                <form action="<?= e(APP_URL); ?>/actions/login_action.php" method="POST">
                    <?= csrf_field(); ?>
                    <div class="form-group">
                        <label for="identifier">E-mail ou ID institucional</label>
                        <input
                            class="form-control"
                            type="text"
                            id="identifier"
                            name="identifier"
                            placeholder="Ex.: ALU-2026-ESI-0001"
                            autocomplete="username"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Palavra-passe</label>
                        <input
                            class="form-control"
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Introduza a sua palavra-passe"
                            autocomplete="current-password"
                            required
                        >
                    </div>

                    <button class="btn btn-primary" type="submit">
                        Entrar
                    </button>
                </form>

                <div class="auth-links">
                    <a href="<?= e(APP_URL); ?>/candidatura.php">Criar candidatura</a>
                    <a href="<?= e(APP_URL); ?>/index.php">Página inicial</a>
                </div>
            </section>

            <div class="auth-external">
                <a href="https://m365.cloud.microsoft/" target="_blank" rel="noopener">
                    <span>Abrir e-mail institucional</span>
                    <strong>Microsoft 365</strong>
                </a>
            </div>

            <div class="auth-footer">
                © <?= date('Y'); ?> <?= e(INSTITUTION_NAME); ?>
            </div>
        </div>
    </main>
</body>
</html>
