<?php

require_once __DIR__ . '/includes/auth.php';

$coursesCountStmt = $pdo->query("
    SELECT
        SUM(CASE WHEN level = 'licenciatura' AND status = 'active' THEN 1 ELSE 0 END) AS licenciaturas,
        SUM(CASE WHEN level = 'mestrado' AND status = 'active' THEN 1 ELSE 0 END) AS mestrados,
        SUM(CASE WHEN level NOT IN ('licenciatura', 'mestrado') AND status = 'active' THEN 1 ELSE 0 END) AS outros
    FROM courses
");

$courseCounts = $coursesCountStmt->fetch() ?: [];
$licCount = (int) ($courseCounts['licenciaturas'] ?? 0);
$mesCount = (int) ($courseCounts['mestrados'] ?? 0);
$outrosCount = (int) ($courseCounts['outros'] ?? 0);

?>
<!DOCTYPE html>
<html lang="pt-CV">
<head>
    <meta charset="UTF-8">
    <title><?= e(APP_NAME); ?> — Portal Académico</title>
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
                <a class="active" href="<?= e(APP_URL); ?>/index.php">Início</a>
                <a href="<?= e(APP_URL); ?>/pages/ensino.php">Ensino</a>
                <a href="<?= e(APP_URL); ?>/candidatura.php">Candidaturas</a>
                <a href="<?= e(APP_URL); ?>/pages/eventos.php">Eventos</a>
                <a href="<?= e(APP_URL); ?>/pages/noticias.php">Notícias</a>
                <a href="<?= e(APP_URL); ?>/login.php">Portal Académico</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="pub-hero pub-hero-carousel">
            <div class="pub-container">
                <div class="pub-carousel" data-carousel data-interval="5200">
                    <div class="pub-carousel-track">
                        <article class="pub-carousel-slide active image-campus">
                            <div class="pub-slide-panel">
                                <span class="pub-pill">Ano letivo 2025/2026</span>
                                <h1>Candidaturas abertas para novos estudantes</h1>
                                <p>
                                    Inicie a sua candidatura online, submeta os documentos necessários e acompanhe
                                    todo o processo de admissão sem deslocação inicial.
                                </p>

                                <div class="pub-actions">
                                    <a class="pub-btn pub-btn-primary" href="<?= e(APP_URL); ?>/candidatura.php">Solicitar inscrição</a>
                                    <a class="pub-btn pub-btn-secondary" href="<?= e(APP_URL); ?>/estado-candidatura.php">Acompanhar candidatura</a>
                                    <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/pages/requisitos.php">Consultar requisitos</a>
                                </div>

                                <div class="pub-slide-kpis">
                                    <div class="pub-slide-kpi"><strong><?= $licCount; ?></strong><span>Licenciaturas ativas</span></div>
                                    <div class="pub-slide-kpi"><strong><?= $mesCount; ?></strong><span>Mestrados disponíveis</span></div>
                                    <div class="pub-slide-kpi"><strong><?= $outrosCount; ?></strong><span>Outros programas</span></div>
                                </div>
                            </div>
                        </article>

                        <article class="pub-carousel-slide image-students">
                            <div class="pub-slide-panel">
                                <span class="pub-pill">Serviços académicos</span>
                                <h1>Um portal único para a vida académica</h1>
                                <p>
                                    Depois da aprovação, o estudante passa a acompanhar matrículas, propinas,
                                    notificações, documentos, horários, notas e faltas no mesmo sistema.
                                </p>

                                <div class="pub-actions">
                                    <a class="pub-btn pub-btn-primary" href="<?= e(APP_URL); ?>/login.php">Entrar no portal</a>
                                    <a class="pub-btn pub-btn-secondary" href="<?= e(APP_URL); ?>/pages/noticias.php">Ver notícias</a>
                                    <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/pages/contactos.php">Falar com a secretaria</a>
                                </div>

                                <div class="pub-slide-kpis">
                                    <div class="pub-slide-kpi"><strong>24h</strong><span>Acesso online</span></div>
                                    <div class="pub-slide-kpi"><strong>1 conta</strong><span>Serviços centralizados</span></div>
                                    <div class="pub-slide-kpi"><strong>Notificações</strong><span>Acompanhamento contínuo</span></div>
                                </div>
                            </div>
                        </article>

                        <article class="pub-carousel-slide image-graduation">
                            <div class="pub-slide-panel">
                                <span class="pub-pill">Comunidade académica</span>
                                <h1>Conheça a oferta formativa e os eventos institucionais</h1>
                                <p>
                                    Explore os cursos por nível de ensino, acompanhe editais, consulte atividades
                                    académicas e prepare a sua entrada na Instituto Horizonte com mais informação.
                                </p>

                                <div class="pub-actions">
                                    <a class="pub-btn pub-btn-primary" href="<?= e(APP_URL); ?>/pages/ensino.php">Ver oferta formativa</a>
                                    <a class="pub-btn pub-btn-secondary" href="<?= e(APP_URL); ?>/pages/eventos.php">Explorar eventos</a>
                                    <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/pages/noticias.php">Ler notícias</a>
                                </div>

                                <div class="pub-slide-kpis">
                                    <div class="pub-slide-kpi"><strong>Ensino</strong><span>Cursos por nível</span></div>
                                    <div class="pub-slide-kpi"><strong>Editais</strong><span>Informação institucional</span></div>
                                    <div class="pub-slide-kpi"><strong>Eventos</strong><span>Vida académica ativa</span></div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div class="pub-carousel-controls" aria-label="Controlo do carrossel">
                        <button type="button" class="pub-carousel-dot active" aria-label="Slide 1"></button>
                        <button type="button" class="pub-carousel-dot" aria-label="Slide 2"></button>
                        <button type="button" class="pub-carousel-dot" aria-label="Slide 3"></button>
                    </div>
                    <div class="pub-carousel-progress"><span></span></div>
                </div>
            </div>
        </section>

        <section class="pub-section">
            <div class="pub-container">
                <div class="pub-section-title">
                    <span class="pub-eyebrow">Ensino</span>
                    <h2>Ofertas formativas</h2>
                    <p>
                        Consulte os cursos disponíveis por nível de formação e escolha a opção mais adequada ao seu percurso académico.
                    </p>
                </div>

                <div class="pub-grid-3">
                    <a class="pub-card compact" href="<?= e(APP_URL); ?>/pages/ensino.php?nivel=licenciatura" style="text-decoration:none;">
                        <div class="pub-card-image" style="background-image:url('<?= e(APP_URL); ?>/assets/img/hero-graduation.png')"></div>
                        <div class="pub-icon">🎓</div>
                        <h3>Licenciaturas</h3>
                        <p>Formação superior de base, orientada para competências científicas, técnicas e profissionais.</p>
                        <strong><?= $licCount; ?> curso(s) disponível(is)</strong>
                    </a>

                    <a class="pub-card compact" href="<?= e(APP_URL); ?>/pages/ensino.php?nivel=mestrado" style="text-decoration:none;">
                        <div class="pub-card-image" style="background-image:url('<?= e(APP_URL); ?>/assets/img/library-students.png')"></div>
                        <div class="pub-icon">📚</div>
                        <h3>Mestrados</h3>
                        <p>Programas de aprofundamento académico e profissional para especialização e progressão na carreira.</p>
                        <strong><?= $mesCount; ?> curso(s) disponível(is)</strong>
                    </a>

                    <a class="pub-card compact" href="<?= e(APP_URL); ?>/pages/ensino.php?nivel=outros" style="text-decoration:none;">
                        <div class="pub-card-image" style="background-image:url('<?= e(APP_URL); ?>/assets/img/classroom-tech.png')"></div>
                        <div class="pub-icon">🏛️</div>
                        <h3>Doutoramento e especializações</h3>
                        <p>Programas avançados orientados para investigação, inovação, produção científica e qualificação especializada.</p>
                        <strong><?= $outrosCount; ?> programa(s) disponível(is)</strong>
                    </a>
                </div>
            </div>
        </section>

        <section class="pub-section alt">
            <div class="pub-container">
                <div class="pub-grid-2">
                    <div class="pub-card">
                        <span class="pub-pill">Candidaturas</span>
                        <h2 style="color: var(--pub-brown-900); font-size: 2.3rem; line-height: 1.05;">
                            Processo simples, orientado e totalmente acompanhado
                        </h2>

                        <p>
                            O candidato preenche o formulário, submete documentos, acompanha o estado do pedido
                            e recebe as credenciais institucionais após validação da secretaria e aprovação administrativa.
                        </p>

                        <ul class="pub-check-list">
                            <li>Preenchimento dos dados pessoais e académicos</li>
                            <li>Escolha do curso e do ano letivo pretendido</li>
                            <li>Envio de identificação e comprovativos</li>
                            <li>Análise da secretaria e validação administrativa</li>
                            <li>Geração de credenciais para acesso ao portal</li>
                        </ul>

                        <a class="pub-btn pub-btn-brown" href="<?= e(APP_URL); ?>/candidatura.php">Começar candidatura</a>
                    </div>

                    <div class="pub-card">
                        <h2 style="color: var(--pub-brown-900); font-size: 2.1rem;">Fluxo do portal</h2>

                        <ol class="pub-flow">
                            <li>Candidato submete a candidatura online</li>
                            <li>Secretaria verifica documentos e requisitos</li>
                            <li>Administração ou direção confirma a aprovação</li>
                            <li>Credenciais são geradas para o novo estudante</li>
                            <li>O estudante passa a gerir a sua vida académica no portal</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="pub-section">
            <div class="pub-container">
                <div class="pub-section-title">
                    <span class="pub-eyebrow">Eventos</span>
                    <h2>Atividades e editais</h2>
                    <p>Fique a par de iniciativas académicas, sessões informativas e publicações institucionais relevantes.</p>
                </div>

                <div class="pub-grid-2">
                    <a class="pub-card" href="<?= e(APP_URL); ?>/pages/eventos.php" style="text-decoration:none; padding:0; overflow:hidden;">
                        <div class="pub-feature pub-feature-photo" style="background-image:linear-gradient(120deg,rgba(74,36,14,.34),rgba(181,22,50,.42)),url('<?= e(APP_URL); ?>/assets/img/lecture-hall.png'); min-height:180px;"></div>
                        <div style="padding:24px;">
                            <h3>Semana académica, editais e sessões de acolhimento</h3>
                            <p>Consulte atividades de integração, informações de candidatura, palestras, seminários e avisos académicos.</p>
                        </div>
                    </a>

                    <div class="pub-card">
                        <div class="pub-card-image" style="background-image:url('<?= e(APP_URL); ?>/assets/img/documents-desk.png')"></div>
                        <h3>Documentos importantes</h3>
                        <p>Consulte regulamentos, requisitos, orientações e informações úteis para o processo de candidatura e vida académica.</p>

                        <div class="pub-actions">
                            <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/pages/requisitos.php">Ver requisitos</a>
                            <a class="pub-btn pub-btn-brown" href="<?= e(APP_URL); ?>/pages/contactos.php">Contactos</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="pub-section alt">
            <div class="pub-container">
                <div class="pub-section-title">
                    <span class="pub-eyebrow">Notícias</span>
                    <h2>Comunidade académica</h2>
                    <p>Acompanhe a vida institucional, a inovação pedagógica e as iniciativas com estudantes, docentes e parceiros.</p>
                </div>

                <div class="pub-grid-3">
                    <a class="pub-card compact" href="<?= e(APP_URL); ?>/pages/noticias.php" style="text-decoration:none;">
                        <div class="pub-card-image" style="background-image:url('<?= e(APP_URL); ?>/assets/img/hero-students.png')"></div>
                        <h3>Instituto Horizonte em ação na comunidade</h3>
                        <p>Projetos de extensão universitária e iniciativas de impacto social desenvolvidas com estudantes e docentes.</p>
                    </a>

                    <a class="pub-card compact" href="<?= e(APP_URL); ?>/pages/noticias.php" style="text-decoration:none;">
                        <div class="pub-card-image" style="background-image:url('<?= e(APP_URL); ?>/assets/img/reports-dashboard.png')"></div>
                        <h3>Portal académico integrado</h3>
                        <p>Um sistema único para candidaturas, matrículas, pagamentos, documentos, notas, faltas e comunicação institucional.</p>
                    </a>

                    <a class="pub-card compact" href="<?= e(APP_URL); ?>/pages/ensino.php?nivel=licenciatura" style="text-decoration:none;">
                        <div class="pub-card-image" style="background-image:url('<?= e(APP_URL); ?>/assets/img/classroom-tech.png')"></div>
                        <h3>Engenharia de Sistemas e Informática</h3>
                        <p>Formação orientada para software, redes, bases de dados, interação humano-máquina e transformação digital.</p>
                    </a>
                </div>
            </div>
        </section>
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

                    <p>
                        Portal académico para candidaturas, matrículas, documentos,
                        propinas, notas, horários e acompanhamento académico.
                    </p>
                </div>

                <div>
                    <h4>Ensino na Instituto Horizonte</h4>
                    <div class="pub-footer-links">
                        <a href="<?= e(APP_URL); ?>/pages/ensino.php?nivel=licenciatura">Licenciaturas</a>
                        <a href="<?= e(APP_URL); ?>/pages/ensino.php?nivel=mestrado">Mestrados</a>
                        <a href="<?= e(APP_URL); ?>/pages/ensino.php?nivel=outros">Doutoramento</a>
                        <a href="<?= e(APP_URL); ?>/pages/ensino.php">Formação Permanente</a>
                    </div>
                </div>

                <div>
                    <h4>Documentos importantes</h4>
                    <div class="pub-footer-links">
                        <a href="<?= e(APP_URL); ?>/pages/requisitos.php">Calendário académico</a>
                        <a href="<?= e(APP_URL); ?>/pages/requisitos.php">Regulamentos</a>
                        <a href="<?= e(APP_URL); ?>/pages/requisitos.php">Propinas e emolumentos</a>
                        <a href="<?= e(APP_URL); ?>/pages/requisitos.php">Critérios de candidatura</a>
                    </div>
                </div>

                <div>
                    <h4>Links úteis</h4>
                    <div class="pub-footer-links">
                        <a href="<?= e(APP_URL); ?>/pages/contactos.php">Contactos</a>
                        <a href="<?= e(APP_URL); ?>/login.php">Biblioteca digital</a>
                        <a href="<?= e(APP_URL); ?>/login.php">Secretaria académica</a>
                        <a href="<?= e(APP_URL); ?>/login.php">Suporte ao estudante</a>
                    </div>
                </div>
            </div>

            <div class="pub-copy">
                © 2026 Instituto Horizonte. Todos os direitos reservados.
            </div>
        </div>
    </footer>

    <script>
        (function () {
            document.querySelectorAll('[data-carousel]').forEach(function (carousel) {
                var slides = Array.prototype.slice.call(carousel.querySelectorAll('.pub-carousel-slide'));
                var dots = Array.prototype.slice.call(carousel.querySelectorAll('.pub-carousel-dot'));
                var progress = carousel.querySelector('.pub-carousel-progress > span');
                var interval = parseInt(carousel.getAttribute('data-interval') || '5000', 10);
                var current = 0;
                var timer = null;

                function activate(index) {
                    current = (index + slides.length) % slides.length;
                    slides.forEach(function (slide, i) {
                        slide.classList.toggle('active', i === current);
                    });
                    dots.forEach(function (dot, i) {
                        dot.classList.toggle('active', i === current);
                    });
                    if (progress) {
                        progress.style.transition = 'none';
                        progress.style.width = '0%';
                        void progress.offsetWidth;
                        progress.style.transition = 'width ' + interval + 'ms linear';
                        progress.style.width = '100%';
                    }
                }

                function start() {
                    stop();
                    activate(current);
                    timer = window.setInterval(function () {
                        activate(current + 1);
                    }, interval);
                }

                function stop() {
                    if (timer) {
                        window.clearInterval(timer);
                        timer = null;
                    }
                }

                dots.forEach(function (dot, index) {
                    dot.addEventListener('click', function () {
                        activate(index);
                        start();
                    });
                });

                carousel.addEventListener('mouseenter', stop);
                carousel.addEventListener('mouseleave', start);
                activate(0);
                start();
            });
        })();
    </script>
</body>
</html>
