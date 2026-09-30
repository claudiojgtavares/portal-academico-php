<?php

require_once __DIR__ . '/../includes/auth.php';

$nivel = trim($_GET['nivel'] ?? '');

$categories = [
    'licenciatura' => [
        'title' => 'Licenciaturas',
        'heading' => 'Licenciatura',
        'description' => 'Cursos superiores de formação inicial, orientados para a construção de competências académicas, científicas e profissionais.',
        'badge' => 'Formação inicial',
        'icon' => '🎓'
    ],
    'especializacao' => [
        'title' => 'Especializações',
        'heading' => 'Especializações',
        'description' => 'Programas curtos e focados para aprofundamento técnico, atualização profissional e valorização curricular.',
        'badge' => 'Especialização',
        'icon' => '🏅'
    ],
    'mestrado' => [
        'title' => 'Mestrados',
        'heading' => 'Mestrado',
        'description' => 'Formação avançada para aprofundamento científico, investigação aplicada e desenvolvimento profissional.',
        'badge' => 'Pós-graduação',
        'icon' => '📚'
    ],
    'doutoramento' => [
        'title' => 'Doutoramento',
        'heading' => 'Doutoramento',
        'description' => 'Percursos orientados para investigação, produção científica e inovação em áreas estratégicas.',
        'badge' => 'Investigação',
        'icon' => '🔬'
    ],
    'formacao_permanente' => [
        'title' => 'Formação Permanente',
        'heading' => 'Formação Permanente',
        'description' => 'Cursos de atualização, capacitação e aprendizagem ao longo da vida para estudantes, profissionais e comunidade.',
        'badge' => 'Formação contínua',
        'icon' => '🧭'
    ],
    'mestrado_integrado' => [
        'title' => 'Mestrado Integrado',
        'heading' => 'Mestrado Integrado',
        'description' => 'Programas integrados que articulam formação de base, especialização e competências profissionais.',
        'badge' => 'Percurso integrado',
        'icon' => '🏛️'
    ]
];

if ($nivel !== '' && !array_key_exists($nivel, $categories)) {
    $nivel = '';
}

function ensino_level_label(?string $level): string
{
    $labels = [
        'licenciatura' => 'Licenciatura',
        'mestrado' => 'Mestrado',
        'doutoramento' => 'Doutoramento',
        'especializacao' => 'Especialização',
        'formacao_permanente' => 'Formação Permanente',
        'mestrado_integrado' => 'Mestrado Integrado'
    ];

    return $labels[$level ?? ''] ?? ucfirst((string) $level);
}

function ensino_count_by_level(PDO $pdo, string $level): int
{
    $stmt = $pdo->prepare("\n        SELECT COUNT(*) AS total\n        FROM courses\n        WHERE status = 'active'\n          AND level = ?\n    ");

    $stmt->execute([$level]);

    return (int) ($stmt->fetch()['total'] ?? 0);
}

function ensino_level_summary(PDO $pdo): array
{
    $stmt = $pdo->query("\n        SELECT level, COUNT(*) AS total\n        FROM courses\n        WHERE status = 'active'\n        GROUP BY level\n    ");

    $summary = [];

    foreach ($stmt->fetchAll() as $row) {
        $summary[(string) $row['level']] = (int) $row['total'];
    }

    return $summary;
}

$summary = ensino_level_summary($pdo);
$totalCourses = array_sum($summary);
$courses = [];

if ($nivel !== '') {
    $stmt = $pdo->prepare("\n        SELECT\n            id,\n            code,\n            name,\n            level,\n            duration_years,\n            status\n        FROM courses\n        WHERE status = 'active'\n          AND level = ?\n        ORDER BY name ASC\n    ");

    $stmt->execute([$nivel]);
    $courses = $stmt->fetchAll();
}

$currentCategory = $nivel !== '' ? $categories[$nivel] : null;

?>
<!DOCTYPE html>
<html lang="pt-CV">
<head>
    <meta charset="UTF-8">
    <title>Ensino — <?= e(APP_NAME); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= e(APP_URL); ?>/assets/css/public.css">
</head>
<body>
    <div class="pub-top-strip">
        <div class="pub-top-strip-inner">
            <span>PT</span>
            <span>+238 260 90 00</span>
            <a href="https://m365.cloud.microsoft/" target="_blank" rel="noopener">E-mail</a>
            <a href="<?= e(APP_URL); ?>/login.php">Portal Académico</a>
        </div>
    </div>

    <header class="pub-header">
        <div class="pub-header-inner">
            <a class="pub-brand" href="<?= e(APP_URL); ?>/index.php">
                <div class="pub-logo">PA</div>
                <div>
                    <strong>Instituto Horizonte</strong>
                    <span>Cabo Verde</span>
                </div>
            </a>

            <nav class="pub-nav">
                <a href="<?= e(APP_URL); ?>/index.php">Início</a>
                <a class="active" href="<?= e(APP_URL); ?>/pages/ensino.php">Ensino</a>
                <a href="<?= e(APP_URL); ?>/candidatura.php">Candidaturas</a>
                <a href="<?= e(APP_URL); ?>/pages/eventos.php">Eventos</a>
                <a href="<?= e(APP_URL); ?>/pages/noticias.php">Notícias</a>
                <a href="<?= e(APP_URL); ?>/login.php">Portal Académico</a>
            </nav>
        </div>
    </header>

    <nav class="pub-subnav" aria-label="Navegação de ensino">
        <div class="pub-container pub-subnav-inner">
            <a href="<?= e(APP_URL); ?>/pages/ensino.php">Oferta formativa</a>
            <a href="<?= e(APP_URL); ?>/candidatura.php">Ingressos</a>
            <a href="<?= e(APP_URL); ?>/pages/requisitos.php">Requisitos</a>
            <a href="<?= e(APP_URL); ?>/estado-candidatura.php">Resultados</a>
        </div>
    </nav>

    <?php if ($nivel === ''): ?>
        <section class="teaching-hero-clean image-ensino">
            <div class="pub-container teaching-hero-grid">
                <div>
                    <span class="pub-eyebrow">Ensino na Instituto Horizonte</span>
                    <h1>Constrói aqui o teu futuro</h1>
                    <p>
                        Consulta a oferta formativa, escolhe o nível de ensino e conhece os cursos disponíveis
                        para iniciar ou continuar o teu percurso académico.
                    </p>
                    <div class="pub-actions">
                        <a class="pub-btn pub-btn-primary" href="<?= e(APP_URL); ?>/candidatura.php">Candidatar-se</a>
                        <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/pages/requisitos.php">Ver requisitos</a>
                    </div>
                </div>

                <div class="teaching-hero-stat" aria-label="Resumo da oferta formativa">
                    <strong><?= (int) $totalCourses; ?></strong>
                    <span>curso(s) ativo(s)</span>
                    <small>Distribuídos por licenciaturas, mestrados e outros programas de formação.</small>
                </div>
            </div>
        </section>

        <main class="pub-section teaching-overview">
            <div class="pub-container">
                <div class="teaching-layout-grid">
                    <section class="teaching-intro-card">
                        <span class="pub-eyebrow">Oferta formativa</span>
                        <h2>Escolha o nível de ensino</h2>
                        <p>
                            A Instituto Horizonte disponibiliza cursos de formação inicial, formação avançada e formação contínua,
                            organizados para responder às necessidades académicas, profissionais e sociais dos estudantes.
                        </p>
                        <p>
                            Selecione uma categoria para ver os cursos disponíveis, duração e acesso à candidatura online.
                        </p>
                    </section>

                    <section class="teaching-level-grid" aria-label="Níveis de ensino disponíveis">
                        <?php foreach ($categories as $key => $category): ?>
                            <?php $total = $summary[$key] ?? 0; ?>

                            <a class="teaching-level-card" href="<?= e(APP_URL); ?>/pages/ensino.php?nivel=<?= e($key); ?>">
                                <span class="teaching-level-icon"><?= e($category['icon']); ?></span>
                                <small><?= e($category['badge']); ?></small>
                                <strong><?= e($category['title']); ?></strong>
                                <em><?= (int) $total; ?> curso(s)</em>
                            </a>
                        <?php endforeach; ?>
                    </section>
                </div>
            </div>
        </main>

        <section class="pub-section alt">
            <div class="pub-container">
                <div class="pub-section-title">
                    <span class="pub-eyebrow">Candidaturas</span>
                    <h2>Processo simples e acompanhado</h2>
                    <p>Submeta os dados, envie documentos e acompanhe o estado do pedido no portal.</p>
                </div>

                <div class="pub-grid-3">
                    <article class="pub-card compact">
                        <div class="pub-icon">1</div>
                        <h3>Escolher o curso</h3>
                        <p>Consulte a oferta formativa e selecione o curso pretendido.</p>
                    </article>

                    <article class="pub-card compact">
                        <div class="pub-icon">2</div>
                        <h3>Enviar candidatura</h3>
                        <p>Preencha o formulário online e anexe os documentos solicitados.</p>
                    </article>

                    <article class="pub-card compact">
                        <div class="pub-icon">3</div>
                        <h3>Acompanhar decisão</h3>
                        <p>Receba notificações sobre análise, correções, aprovação e credenciais.</p>
                    </article>
                </div>
            </div>
        </section>

    <?php else: ?>
        <section class="teaching-category-clean image-ensino-category">
            <div class="pub-container">
                <a class="course-back-link" href="<?= e(APP_URL); ?>/pages/ensino.php">← Voltar à oferta formativa</a>
                <span class="pub-eyebrow"><?= e($currentCategory['badge']); ?></span>
                <h1><?= e($currentCategory['heading']); ?></h1>
                <p><?= e($currentCategory['description']); ?></p>
            </div>
        </section>

        <main class="pub-section teaching-courses-section">
            <div class="pub-container">
                <div class="teaching-tabs" aria-label="Categorias de ensino">
                    <?php foreach ($categories as $key => $category): ?>
                        <a class="<?= $nivel === $key ? 'active' : ''; ?>" href="<?= e(APP_URL); ?>/pages/ensino.php?nivel=<?= e($key); ?>">
                            <?= e($category['title']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="teaching-section-head">
                    <div>
                        <span class="pub-eyebrow">Cursos disponíveis</span>
                        <h2><?= e($currentCategory['heading']); ?></h2>
                    </div>
                    <span class="teaching-count"><?= count($courses); ?> curso(s)</span>
                </div>

                <?php if (empty($courses)): ?>
                    <div class="teaching-empty clean">
                        <strong>Nenhum curso ativo encontrado nesta categoria.</strong>
                        <p>Quando existirem cursos cadastrados neste nível, eles aparecerão automaticamente nesta página.</p>
                        <a class="pub-btn pub-btn-brown" href="<?= e(APP_URL); ?>/pages/ensino.php">Voltar à oferta formativa</a>
                    </div>
                <?php else: ?>
                    <div class="teaching-course-grid-clean">
                        <?php foreach ($courses as $course): ?>
                            <article class="teaching-course-card-clean">
                                <div>
                                    <span class="course-level-pill"><?= e(ensino_level_label($course['level'])); ?></span>
                                    <h3><?= e($course['name']); ?></h3>
                                    <p>
                                        Código <?= e($course['code']); ?>
                                        <?php if (!empty($course['duration_years'])): ?>
                                            · <?= (int) $course['duration_years']; ?> ano(s)
                                        <?php endif; ?>
                                    </p>
                                </div>

                                <div class="course-card-actions">
                                    <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/pages/curso.php?id=<?= (int) $course['id']; ?>">
                                        Ver detalhes
                                    </a>
                                    <a class="pub-btn pub-btn-primary" href="<?= e(APP_URL); ?>/candidatura.php?course_id=<?= (int) $course['id']; ?>">
                                        Candidatar-se
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    <?php endif; ?>

    <footer class="pub-footer">
        <div class="pub-container">
            <div class="pub-footer-grid">
                <div>
                    <a class="pub-brand" href="<?= e(APP_URL); ?>/index.php">
                        <div class="pub-logo" style="background:#fff;color:var(--pub-brown-900);">PA</div>
                        <div>
                            <strong style="color:#fff;">Instituto Horizonte</strong>
                            <span>Cabo Verde</span>
                        </div>
                    </a>

                    <p>Portal académico para candidaturas, matrículas, documentos, propinas e acompanhamento académico.</p>
                </div>

                <div>
                    <h4>Ensino</h4>
                    <div class="pub-footer-links">
                        <a href="<?= e(APP_URL); ?>/pages/ensino.php?nivel=licenciatura">Licenciaturas</a>
                        <a href="<?= e(APP_URL); ?>/pages/ensino.php?nivel=mestrado">Mestrados</a>
                        <a href="<?= e(APP_URL); ?>/pages/ensino.php?nivel=doutoramento">Doutoramento</a>
                        <a href="<?= e(APP_URL); ?>/pages/ensino.php?nivel=formacao_permanente">Formação Permanente</a>
                    </div>
                </div>

                <div>
                    <h4>Candidaturas</h4>
                    <div class="pub-footer-links">
                        <a href="<?= e(APP_URL); ?>/candidatura.php">Candidatura online</a>
                        <a href="<?= e(APP_URL); ?>/estado-candidatura.php">Acompanhar candidatura</a>
                        <a href="<?= e(APP_URL); ?>/pages/requisitos.php">Requisitos</a>
                    </div>
                </div>

                <div>
                    <h4>Portal</h4>
                    <div class="pub-footer-links">
                        <a href="<?= e(APP_URL); ?>/login.php">Entrar</a>
                        <a href="<?= e(APP_URL); ?>/pages/eventos.php">Eventos</a>
                        <a href="<?= e(APP_URL); ?>/pages/noticias.php">Notícias</a>
                    </div>
                </div>
            </div>

            <div class="pub-copy">
                © 2026 Instituto Horizonte. Todos os direitos reservados.
            </div>
        </div>
    </footer>
</body>
</html>
