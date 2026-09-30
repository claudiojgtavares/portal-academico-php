<?php

require_once __DIR__ . '/../includes/auth.php';

$courseId = (int) ($_GET['id'] ?? 0);

if ($courseId <= 0) {
    redirect(APP_URL . '/pages/ensino.php');
}

$stmt = $pdo->prepare("
    SELECT
        id,
        code,
        name,
        level,
        duration_years,
        status
    FROM courses
    WHERE id = ?
      AND status = 'active'
    LIMIT 1
");

$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    redirect(APP_URL . '/pages/ensino.php');
}

function curso_public_level_label(?string $level): string
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

function curso_public_level_image(?string $level): string
{
    $images = [
        'licenciatura' => 'licenciaturas.jpg',
        'especializacao' => 'especializacoes.jpg',
        'mestrado' => 'mestrados.jpg',
        'doutoramento' => 'doutoramento.jpg',
        'formacao_permanente' => 'formacao-permanente.jpg',
        'mestrado_integrado' => 'mestrado-integrado.jpg'
    ];

    $file = $images[$level ?? ''] ?? 'licenciaturas.jpg';

    return APP_URL . '/assets/img/courses/' . $file;
}

$levelLabel = curso_public_level_label($course['level']);
$levelImage = curso_public_level_image($course['level']);

?>
<!DOCTYPE html>
<html lang="pt-CV">
<head>
    <meta charset="UTF-8">
    <title><?= e($course['name']); ?> — <?= e(APP_NAME); ?></title>
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

    <section
        class="course-detail-hero"
        style="background-image: linear-gradient(to right, rgba(53, 23, 7, .92), rgba(143, 47, 40, .74)), url('<?= e($levelImage); ?>');"
    >
        <div class="pub-container">
            <div class="course-detail-hero-content">
                <a class="course-back-link" href="<?= e(APP_URL); ?>/pages/ensino.php?nivel=<?= e($course['level']); ?>">
                    ← Voltar para <?= e($levelLabel); ?>
                </a>

                <span class="pub-pill"><?= e($levelLabel); ?></span>

                <h1><?= e($course['name']); ?></h1>

                <p>
                    Curso <?= e($levelLabel); ?> disponível no Portal Académico.
                    Consulte os dados principais e inicie a sua candidatura online.
                </p>

                <div class="course-detail-actions">
                    <a class="pub-btn pub-btn-primary" href="<?= e(APP_URL); ?>/candidatura.php?course_id=<?= (int) $course['id']; ?>">
                        Candidatar-se a este curso
                    </a>

                    <a class="pub-btn pub-btn-soft" href="<?= e(APP_URL); ?>/pages/requisitos.php">
                        Ver requisitos
                    </a>
                </div>
            </div>
        </div>
    </section>

    <main class="pub-section">
        <div class="pub-container">
            <div class="course-detail-grid">
                <section class="pub-card">
                    <h2 class="course-detail-title">Informações do curso</h2>

                    <div class="course-info-list">
                        <div class="course-info-row">
                            <span>Código</span>
                            <strong><?= e($course['code']); ?></strong>
                        </div>

                        <div class="course-info-row">
                            <span>Nome</span>
                            <strong><?= e($course['name']); ?></strong>
                        </div>

                        <div class="course-info-row">
                            <span>Nível</span>
                            <strong><?= e($levelLabel); ?></strong>
                        </div>

                        <div class="course-info-row">
                            <span>Duração</span>
                            <strong>
                                <?php if (!empty($course['duration_years'])): ?>
                                    <?= (int) $course['duration_years']; ?> ano(s)
                                <?php else: ?>
                                    A definir
                                <?php endif; ?>
                            </strong>
                        </div>

                        <div class="course-info-row">
                            <span>Estado</span>
                            <strong>Inscrições disponíveis</strong>
                        </div>
                    </div>
                </section>

                <aside class="pub-card">
                    <h2 class="course-detail-title">Como candidatar-se</h2>

                    <ol class="pub-flow">
                        <li>Clique em “Candidatar-se a este curso”</li>
                        <li>Preencha os seus dados pessoais</li>
                        <li>Envie os documentos solicitados</li>
                        <li>Acompanhe o estado da candidatura</li>
                        <li>Após aprovação, receba as credenciais do portal</li>
                    </ol>

                    <div class="pub-actions">
                        <a class="pub-btn pub-btn-brown" href="<?= e(APP_URL); ?>/candidatura.php?course_id=<?= (int) $course['id']; ?>">
                            Iniciar candidatura
                        </a>

                        <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/estado-candidatura.php">
                            Acompanhar candidatura
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </main>

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
                        <a href="<?= e(APP_URL); ?>/candidatura.php">Inscrição online</a>
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