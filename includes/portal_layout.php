<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/portal_ui_helpers.php';

if (file_exists(__DIR__ . '/notification_widget.php')) {
    require_once __DIR__ . '/notification_widget.php';
}

if (!function_exists('portal_initials')) {
    function portal_initials(string $name): string
    {
        return portal_initials_safe($name);
    }
}

if (!function_exists('portal_roles')) {
    function portal_roles(array $user): array
    {
        if (!empty($user['roles_array']) && is_array($user['roles_array'])) {
            return $user['roles_array'];
        }

        if (!empty($user['roles'])) {
            $json = json_decode((string) $user['roles'], true);
            if (is_array($json)) {
                return $json;
            }

            return array_values(array_filter(array_map('trim', explode(',', (string) $user['roles']))));
        }

        if (!empty($user['role'])) {
            return [(string) $user['role']];
        }

        return [];
    }
}

if (!function_exists('portal_detect_role')) {
    function portal_detect_role(array $user): string
    {
        foreach (['admin', 'secretaria', 'direcao', 'coordenador', 'professor', 'funcionario', 'aluno'] as $role) {
            if (in_array($role, portal_roles($user), true)) {
                return $role;
            }
        }

        return 'aluno';
    }
}

if (!function_exists('portal_role_label')) {
    function portal_role_label(string $role): string
    {
        return [
            'admin' => 'Administrador',
            'secretaria' => 'Secretaria Académica',
            'direcao' => 'Direção Académica',
            'coordenador' => 'Coordenação de Curso',
            'professor' => 'Área do Professor',
            'funcionario' => 'Área do Funcionário',
            'aluno' => 'Área do Aluno',
        ][$role] ?? 'Portal Académico';
    }
}

if (!function_exists('portal_section_label')) {
    function portal_section_label(string $section): string
    {
        return [
            'principal' => 'Principal',
            'academico' => 'Académico',
            'processos' => 'Processos',
            'sistema' => 'Sistema',
            'conta' => 'Conta',
        ][$section] ?? ucfirst($section);
    }
}

if (!function_exists('portal_active')) {
    function portal_active(string $active, string $key): string
    {
        return $active === $key ? 'active' : '';
    }
}

if (!function_exists('portal_user_photo_url')) {
    function portal_user_photo_url(array $user): string
    {
        $photo = trim((string) ($user['profile_photo'] ?? ''));
        if ($photo === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $photo)) {
            return $photo;
        }

        return APP_URL . '/' . ltrim($photo, '/');
    }
}

if (!function_exists('portal_avatar_html')) {
    function portal_avatar_html(array $user, string $name, string $class = 'ui-avatar-sm'): string
    {
        $photoUrl = portal_user_photo_url($user);
        if ($photoUrl !== '') {
            return '<img class="' . e($class) . ' is-photo" src="' . e($photoUrl) . '" alt="Fotografia de perfil">';
        }

        return '<div class="' . e($class) . '">' . e(portal_initials($name)) . '</div>';
    }
}

if (!function_exists('portal_menu_items')) {
    function portal_menu_items(string $role): array
    {
        $menus = [
            'admin' => [
                'principal' => [
                    ['key' => 'dashboard', 'label' => 'Painel', 'url' => '/dashboards/admin.php'],
                    ['key' => 'usuarios', 'label' => 'Utilizadores', 'url' => '/pages/usuarios.php'],
                    ['key' => 'perfis', 'label' => 'Perfis de utilizadores', 'url' => '/pages/perfis_utilizadores.php'],
                    ['key' => 'candidaturas', 'label' => 'Candidaturas', 'url' => '/pages/candidaturas.php'],
                    ['key' => 'cursos', 'label' => 'Cursos', 'url' => '/pages/cursos.php'],
                ],
                'academico' => [
                    ['key' => 'alunos', 'label' => 'Alunos', 'url' => '/pages/alunos.php'],
                    ['key' => 'turmas', 'label' => 'Turmas', 'url' => '/pages/turmas.php'],
                    ['key' => 'disciplinas', 'label' => 'Disciplinas', 'url' => '/pages/disciplinas.php'],
                    ['key' => 'docentes_curso', 'label' => 'Docentes por curso', 'url' => '/pages/docentes_curso.php'],
                    ['key' => 'atribuicoes', 'label' => 'Atribuir disciplinas', 'url' => '/pages/atribuir_disciplinas.php'],
                    ['key' => 'horarios', 'label' => 'Horários', 'url' => '/pages/horarios.php'],
                    ['key' => 'gerir_horarios', 'label' => 'Gerir horários', 'url' => '/pages/gerir_horarios.php'],
                ],
                'sistema' => [
                    ['key' => 'relatorios', 'label' => 'Relatórios', 'url' => '/pages/relatorios.php'],
                    ['key' => 'logs', 'label' => 'Logs', 'url' => '/pages/logs.php'],
                    ['key' => 'notificacoes', 'label' => 'Notificações', 'url' => '/pages/notificacoes.php'],
                ],
                'conta' => [
                    ['key' => 'perfil', 'label' => 'Meu Perfil', 'url' => '/pages/perfil.php'],
                    ['key' => 'senha', 'label' => 'Alterar senha', 'url' => '/pages/alterar_password.php'],
                ],
            ],

            'secretaria' => [
                'principal' => [
                    ['key' => 'dashboard', 'label' => 'Painel', 'url' => '/dashboards/secretaria.php'],
                    ['key' => 'candidaturas', 'label' => 'Candidaturas', 'url' => '/pages/candidaturas.php'],
                    ['key' => 'alunos', 'label' => 'Alunos', 'url' => '/pages/alunos.php'],
                    ['key' => 'notificacoes', 'label' => 'Notificações', 'url' => '/pages/notificacoes.php'],
                ],
                'academico' => [
                    ['key' => 'turmas', 'label' => 'Turmas', 'url' => '/pages/turmas.php'],
                    ['key' => 'disciplinas', 'label' => 'Disciplinas', 'url' => '/pages/disciplinas.php'],
                    ['key' => 'docentes_curso', 'label' => 'Docentes por curso', 'url' => '/pages/docentes_curso.php'],
                    ['key' => 'atribuicoes', 'label' => 'Atribuir disciplinas', 'url' => '/pages/atribuir_disciplinas.php'],
                    ['key' => 'horarios', 'label' => 'Horários', 'url' => '/pages/horarios.php'],
                    ['key' => 'gerir_horarios', 'label' => 'Gerir horários', 'url' => '/pages/gerir_horarios.php'],
                ],
                'processos' => [
                    ['key' => 'documentos', 'label' => 'Validar documentos', 'url' => '/pages/validar_documentos.php'],
                    ['key' => 'pagamentos', 'label' => 'Validar pagamentos', 'url' => '/pages/validar_pagamentos.php'],
                    ['key' => 'relatorios', 'label' => 'Relatórios para direção', 'url' => '/pages/relatorios.php'],
                ],
                'conta' => [
                    ['key' => 'perfil', 'label' => 'Meu Perfil', 'url' => '/pages/perfil.php'],
                    ['key' => 'senha', 'label' => 'Alterar senha', 'url' => '/pages/alterar_password.php'],
                ],
            ],

            'aluno' => [
                'principal' => [
                    ['key' => 'dashboard', 'label' => 'Painel', 'url' => '/dashboards/aluno.php'],
                    ['key' => 'perfil', 'label' => 'Meu Perfil', 'url' => '/pages/perfil.php'],
                    ['key' => 'notificacoes', 'label' => 'Notificações', 'url' => '/pages/notificacoes.php'],
                ],
                'academico' => [
                    ['key' => 'horarios', 'label' => 'Horários', 'url' => '/pages/horarios.php'],
                    ['key' => 'notas', 'label' => 'Notas', 'url' => '/pages/notas.php'],
                    ['key' => 'faltas', 'label' => 'Faltas', 'url' => '/pages/faltas.php'],
                    ['key' => 'pagamentos', 'label' => 'Propinas', 'url' => '/pages/pagamentos.php'],
                    ['key' => 'documentos', 'label' => 'Documentos', 'url' => '/pages/documentos.php'],
                ],
                'conta' => [
                    ['key' => 'senha', 'label' => 'Alterar senha', 'url' => '/pages/alterar_password.php'],
                ],
            ],

            'professor' => [
                'principal' => [
                    ['key' => 'dashboard', 'label' => 'Painel', 'url' => '/dashboards/professor.php'],
                    ['key' => 'disciplinas', 'label' => 'Minhas disciplinas', 'url' => '/pages/minhas_disciplinas.php'],
                    ['key' => 'horarios', 'label' => 'Horários', 'url' => '/pages/horarios.php'],
                    ['key' => 'notificacoes', 'label' => 'Notificações', 'url' => '/pages/notificacoes.php'],
                ],
                'academico' => [
                    ['key' => 'notas', 'label' => 'Lançar notas', 'url' => '/pages/lancar_notas.php'],
                    ['key' => 'faltas', 'label' => 'Registar faltas', 'url' => '/pages/registar_faltas.php'],
                ],
                'conta' => [
                    ['key' => 'perfil', 'label' => 'Meu Perfil', 'url' => '/pages/perfil.php'],
                    ['key' => 'senha', 'label' => 'Alterar senha', 'url' => '/pages/alterar_password.php'],
                ],
            ],

            'coordenador' => [
                'principal' => [
                    ['key' => 'dashboard', 'label' => 'Painel', 'url' => '/dashboards/coordenador.php'],
                    ['key' => 'curso', 'label' => 'Meu curso', 'url' => '/pages/meu_curso.php'],
                    ['key' => 'disciplinas_professor', 'label' => 'Minhas disciplinas', 'url' => '/pages/minhas_disciplinas.php'],
                    ['key' => 'notificacoes', 'label' => 'Notificações', 'url' => '/pages/notificacoes.php'],
                ],
                'academico' => [
                    ['key' => 'alunos', 'label' => 'Alunos do curso', 'url' => '/pages/alunos.php'],
                    ['key' => 'turmas_curso', 'label' => 'Turmas do curso', 'url' => '/pages/turmas.php'],
                    ['key' => 'disciplinas', 'label' => 'Disciplinas do curso', 'url' => '/pages/disciplinas.php'],
                    ['key' => 'docentes_curso', 'label' => 'Docentes do curso', 'url' => '/pages/docentes_curso.php'],
                    ['key' => 'horarios', 'label' => 'Horários', 'url' => '/pages/horarios.php'],
                    ['key' => 'gerir_horarios', 'label' => 'Validar horários', 'url' => '/pages/gerir_horarios.php'],
                    ['key' => 'notas', 'label' => 'Lançar notas', 'url' => '/pages/lancar_notas.php'],
                    ['key' => 'faltas', 'label' => 'Registar faltas', 'url' => '/pages/registar_faltas.php'],
                    ['key' => 'notas_consulta', 'label' => 'Notas do curso', 'url' => '/pages/notas.php'],
                    ['key' => 'faltas_consulta', 'label' => 'Faltas do curso', 'url' => '/pages/faltas.php'],
                    ['key' => 'candidaturas', 'label' => 'Candidaturas do curso', 'url' => '/pages/candidaturas.php'],
                    ['key' => 'relatorios', 'label' => 'Relatórios do curso', 'url' => '/pages/relatorios.php'],
                ],
                'conta' => [
                    ['key' => 'perfil', 'label' => 'Meu Perfil', 'url' => '/pages/perfil.php'],
                    ['key' => 'senha', 'label' => 'Alterar senha', 'url' => '/pages/alterar_password.php'],
                ],
            ],

            'direcao' => [
                'principal' => [
                    ['key' => 'dashboard', 'label' => 'Painel', 'url' => '/dashboards/direcao.php'],
                    ['key' => 'candidaturas', 'label' => 'Candidaturas', 'url' => '/pages/candidaturas.php'],
                    ['key' => 'cursos', 'label' => 'Cursos', 'url' => '/pages/cursos.php'],
                    ['key' => 'alunos', 'label' => 'Alunos', 'url' => '/pages/alunos.php'],
                    ['key' => 'turmas', 'label' => 'Turmas', 'url' => '/pages/turmas.php'],
                    ['key' => 'docentes_curso', 'label' => 'Docentes por curso', 'url' => '/pages/docentes_curso.php'],
                    ['key' => 'notificacoes', 'label' => 'Notificações', 'url' => '/pages/notificacoes.php'],
                ],
                'sistema' => [
                    ['key' => 'pagamentos', 'label' => 'Pagamentos', 'url' => '/pages/validar_pagamentos.php'],
                    ['key' => 'horarios', 'label' => 'Horários', 'url' => '/pages/horarios.php'],
                    ['key' => 'gerir_horarios', 'label' => 'Validar horários', 'url' => '/pages/gerir_horarios.php'],
                    ['key' => 'relatorios', 'label' => 'Relatórios', 'url' => '/pages/relatorios.php'],
                ],
                'conta' => [
                    ['key' => 'perfil', 'label' => 'Meu Perfil', 'url' => '/pages/perfil.php'],
                    ['key' => 'senha', 'label' => 'Alterar senha', 'url' => '/pages/alterar_password.php'],
                ],
            ],

            'funcionario' => [
                'principal' => [
                    ['key' => 'dashboard', 'label' => 'Painel', 'url' => '/dashboards/funcionario.php'],
                    ['key' => 'tarefas', 'label' => 'Tarefas', 'url' => '/pages/tarefas.php'],
                    ['key' => 'pagamentos', 'label' => 'Pagamentos', 'url' => '/pages/validar_pagamentos.php'],
                    ['key' => 'horarios', 'label' => 'Horários', 'url' => '/pages/horarios.php'],
                    ['key' => 'gerir_horarios', 'label' => 'Gerir horários', 'url' => '/pages/gerir_horarios.php'],
                    ['key' => 'notificacoes', 'label' => 'Notificações', 'url' => '/pages/notificacoes.php'],
                ],
                'conta' => [
                    ['key' => 'perfil', 'label' => 'Meu Perfil', 'url' => '/pages/perfil.php'],
                    ['key' => 'senha', 'label' => 'Alterar senha', 'url' => '/pages/alterar_password.php'],
                ],
            ],
        ];

        return $menus[$role] ?? $menus['aluno'];
    }
}

if (!function_exists('portal_layout_start')) {
    function portal_layout_start(string $activeMenu, string $title, string $subtitle = '', ?string $forcedRole = null): void
    {
        require_login();

        $user = current_user();
        global $pdo;

        if (isset($pdo) && $pdo instanceof PDO && function_exists('portal_one')) {
            $freshUser = portal_one($pdo, "SELECT * FROM users WHERE id = ? LIMIT 1", [(int) ($user['id'] ?? 0)]);
            if ($freshUser) {
                $user = array_merge($user, $freshUser);
            }
        }

        if (!$user) {
            redirect(APP_URL . '/login.php?erro=sessao');
        }

        $role = $forcedRole ?: portal_detect_role($user);
        $menus = portal_menu_items($role);
        $userName = (string) ($user['full_name'] ?? 'Utilizador');
        $userCode = (string) ($user['institutional_id'] ?? '');
        $roleLabel = portal_role_label($role);
        ?>
<!DOCTYPE html>
<html lang="pt-CV">
<head>
    <meta charset="UTF-8">
    <title><?= e($title); ?> - <?= e(APP_NAME); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()); ?>">
    <link rel="stylesheet" href="<?= e(APP_URL); ?>/assets/css/portal-modern.css">
</head>
<body class="ui-body">
<div class="ui-shell">
    <aside class="ui-sidebar">
        <div class="ui-brand">
            <div class="ui-brand-mark">PA</div>
            <div>
                <strong>Instituto Horizonte</strong>
                <span><?= e($roleLabel); ?></span>
            </div>
        </div>

        <nav class="ui-nav" aria-label="Menu principal">
            <?php foreach ($menus as $section => $items): ?>
                <span class="ui-nav-title"><?= e(portal_section_label($section)); ?></span>
                <?php foreach ($items as $item): ?>
                    <a class="<?= e(portal_active($activeMenu, $item['key'])); ?>" href="<?= e(APP_URL . $item['url']); ?>"><?= e($item['label']); ?></a>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <a class="is-exit" href="<?= e(APP_URL); ?>/logout.php">Sair</a>
        </nav>

        <div class="ui-sidebar-footer">
            <div class="ui-mini-user">
                <?= portal_avatar_html($user, $userName, 'ui-avatar-sm'); ?>
                <div>
                    <strong><?= e($userName); ?></strong>
                    <span><?= e($userCode); ?></span>
                </div>
            </div>
        </div>
    </aside>

    <main class="ui-main">
        <header class="ui-topbar">
            <div>
                <h1><?= e($title); ?></h1>
                <?php if ($subtitle !== ''): ?><p><?= e($subtitle); ?></p><?php endif; ?>
            </div>

            <div class="ui-topbar-actions">
                <nav class="ui-utility-links" aria-label="Ligações rápidas">
                    <a href="https://m365.cloud.microsoft/" target="_blank" rel="noopener">E-mail</a>
                    <a href="#" aria-disabled="true">Ambiente virtual</a>
                    <a href="<?= e(APP_URL); ?>/index.php">Site</a>
                </nav>

                <?php if (function_exists('render_notification_user_box')): ?>
                    <?php render_notification_user_box($user, $userCode, 'ui'); ?>
                <?php else: ?>
                    <div class="ui-user-box">
                        <?= portal_avatar_html($user, $userName, 'ui-avatar-sm'); ?>
                        <div><strong><?= e($userName); ?></strong><span><?= e($userCode); ?></span></div>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <section class="ui-content">
        <?php
    }
}

if (!function_exists('portal_layout_end')) {
    function portal_layout_end(): void
    {
        ?>
        </section>
    </main>
</div>

<script>
    document.querySelectorAll('form[method="POST"], form[method="post"]').forEach(function (form) {
        if (!form.querySelector('input[name="_csrf"]')) {
            const field = document.createElement('input');
            field.type = 'hidden';
            field.name = '_csrf';
            field.value = document.querySelector('meta[name="csrf-token"]').content;
            form.appendChild(field);
        }
    });

    document.addEventListener('click', function (event) {
        document.querySelectorAll('details.notify-menu[open]').forEach(function (menu) {
            if (!menu.contains(event.target)) {
                menu.removeAttribute('open');
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('details.notify-menu[open]').forEach(function (menu) {
                menu.removeAttribute('open');
            });
        }
    });
</script>
</body>
</html>
        <?php
    }
}
