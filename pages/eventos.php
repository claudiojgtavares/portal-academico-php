<?php

require_once __DIR__ . '/../includes/auth.php';

?>
<!DOCTYPE html>
<html lang="pt-CV">
<head>
    <meta charset="UTF-8">
    <title>Eventos e editais — <?= e(APP_NAME); ?></title>
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
            <a href="<?= e(APP_URL); ?>/pages/ensino.php">Ensino</a>
            <a href="<?= e(APP_URL); ?>/candidatura.php">Candidaturas</a>
            <a class="active" href="<?= e(APP_URL); ?>/pages/eventos.php">Eventos</a>
            <a href="<?= e(APP_URL); ?>/pages/noticias.php">Notícias</a>
            <a href="<?= e(APP_URL); ?>/login.php">Portal Académico</a>
        </nav>
    </div>
</header>

<section class="pub-page-hero pub-page-carousel">
    <div class="pub-container">
        <div class="pub-carousel" data-carousel data-interval="5400">
            <div class="pub-carousel-track">
                <article class="pub-carousel-slide active image-lecture">
                    <div class="pub-slide-panel">
                        <span class="pub-pill">Agenda académica</span>
                        <h1>Atividades académicas e institucionais</h1>
                        <p>
                            Consulte eventos, editais, sessões informativas e iniciativas relevantes para estudantes,
                            docentes, coordenadores e serviços académicos.
                        </p>
                        <div class="pub-actions">
                            <a class="pub-btn pub-btn-primary" href="<?= e(APP_URL); ?>/candidatura.php">Iniciar candidatura</a>
                            <a class="pub-btn pub-btn-secondary" href="<?= e(APP_URL); ?>/pages/ensino.php">Ver ofertas formativas</a>
                            <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/index.php">Voltar ao início</a>
                        </div>
                        <div class="pub-slide-kpis">
                            <div class="pub-slide-kpi"><strong>Editais</strong><span>Informação oficial</span></div>
                            <div class="pub-slide-kpi"><strong>Eventos</strong><span>Participação académica</span></div>
                            <div class="pub-slide-kpi"><strong>Avisos</strong><span>Atualizações institucionais</span></div>
                        </div>
                    </div>
                </article>

                <article class="pub-carousel-slide image-classroom">
                    <div class="pub-slide-panel">
                        <span class="pub-pill">Semana académica</span>
                        <h1>Integração, acolhimento e vida universitária</h1>
                        <p>
                            Divulgamos atividades de receção, encontros de curso, semanas temáticas,
                            jornadas científicas e momentos de integração da comunidade académica.
                        </p>
                        <div class="pub-actions">
                            <a class="pub-btn pub-btn-primary" href="<?= e(APP_URL); ?>/pages/contactos.php">Falar com a secretaria</a>
                            <a class="pub-btn pub-btn-secondary" href="<?= e(APP_URL); ?>/pages/noticias.php">Ver notícias</a>
                        </div>
                        <div class="pub-slide-kpis">
                            <div class="pub-slide-kpi"><strong>Estudantes</strong><span>Integração e apoio</span></div>
                            <div class="pub-slide-kpi"><strong>Docentes</strong><span>Participação académica</span></div>
                            <div class="pub-slide-kpi"><strong>Comunidade</strong><span>Vida universitária ativa</span></div>
                        </div>
                    </div>
                </article>

                <article class="pub-carousel-slide image-library">
                    <div class="pub-slide-panel">
                        <span class="pub-pill">Calendário e prazos</span>
                        <h1>Editais, prazos e ações de candidatura</h1>
                        <p>
                            Acompanhe datas importantes, orientações sobre requisitos, documentos exigidos
                            e etapas essenciais do processo de candidatura.
                        </p>
                        <div class="pub-actions">
                            <a class="pub-btn pub-btn-primary" href="<?= e(APP_URL); ?>/pages/requisitos.php">Consultar requisitos</a>
                            <a class="pub-btn pub-btn-secondary" href="<?= e(APP_URL); ?>/estado-candidatura.php">Acompanhar candidatura</a>
                        </div>
                        <div class="pub-slide-kpis">
                            <div class="pub-slide-kpi"><strong>Prazos</strong><span>Calendário académico</span></div>
                            <div class="pub-slide-kpi"><strong>Critérios</strong><span>Orientação clara</span></div>
                            <div class="pub-slide-kpi"><strong>Fluxo</strong><span>Acompanhamento do processo</span></div>
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

<main class="pub-section">
    <div class="pub-container">
        <div class="pub-section-title">
            <span class="pub-eyebrow">Agenda</span>
            <h2>Eventos em destaque</h2>
            <p>
                Esta área já está preparada para receber eventos reais do portal.
                Enquanto isso, apresentamos os blocos institucionais principais com linguagem mais clara e organizada.
            </p>
        </div>

        <div class="pub-event-grid">
            <article class="pub-event-highlight">
                <div class="pub-card-image tall" style="background-image:url('<?= e(APP_URL); ?>/assets/img/hero-students.png')"></div>
                <div class="pub-icon">📅</div>
                <h3>Semana académica</h3>
                <p>
                    Atividades de acolhimento, integração e orientação para novos estudantes,
                    com apresentação dos serviços académicos e da vida universitária.
                </p>
                <div class="pub-highlight-meta">
                    <span class="pub-meta-pill">Integração académica</span>
                    <span class="pub-meta-pill">Novos estudantes</span>
                </div>
                <div class="pub-actions">
                    <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/pages/requisitos.php">Ver requisitos</a>
                </div>
            </article>

            <article class="pub-event-highlight">
                <div class="pub-card-image tall" style="background-image:url('<?= e(APP_URL); ?>/assets/img/documents-desk.png')"></div>
                <div class="pub-icon">📌</div>
                <h3>Editais de candidatura</h3>
                <p>
                    Informações sobre prazos, critérios, documentação necessária e etapas do processo de candidatura.
                </p>
                <div class="pub-highlight-meta">
                    <span class="pub-meta-pill">Candidaturas 2025/2026</span>
                    <span class="pub-meta-pill">Secretaria académica</span>
                </div>
                <div class="pub-actions">
                    <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/candidatura.php">Candidatar-se</a>
                </div>
            </article>

            <article class="pub-event-highlight">
                <div class="pub-card-image tall" style="background-image:url('<?= e(APP_URL); ?>/assets/img/lecture-hall.png')"></div>
                <div class="pub-icon">🎤</div>
                <h3>Palestras e seminários</h3>
                <p>
                    Sessões académicas, científicas e profissionais para estudantes, docentes e convidados institucionais.
                </p>
                <div class="pub-highlight-meta">
                    <span class="pub-meta-pill">Programação académica</span>
                    <span class="pub-meta-pill">Participação aberta</span>
                </div>
                <div class="pub-actions">
                    <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/pages/contactos.php">Contactar secretaria</a>
                </div>
            </article>
        </div>
    </div>
</main>

<section class="pub-section alt">
    <div class="pub-container">
        <div class="pub-grid-2">
            <div class="pub-card">
                <h3>Precisa de informação sobre um edital?</h3>
                <p>
                    Contacte a secretaria académica ou acompanhe a página de notícias para receber novas atualizações,
                    orientações de candidatura e avisos institucionais.
                </p>

                <div class="pub-actions">
                    <a class="pub-btn pub-btn-brown" href="<?= e(APP_URL); ?>/pages/contactos.php">Contactos</a>
                    <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/pages/noticias.php">Ver notícias</a>
                </div>
            </div>

            <div class="pub-card">
                <h3>Fluxo de candidatura</h3>

                <ol class="pub-flow">
                    <li>Consultar requisitos e documentos necessários</li>
                    <li>Escolher o curso pretendido</li>
                    <li>Submeter candidatura online</li>
                    <li>Acompanhar o estado do pedido</li>
                    <li>Aguardar validação e receção das credenciais</li>
                </ol>

                <div class="pub-actions">
                    <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/estado-candidatura.php">Acompanhar candidatura</a>
                </div>
            </div>
        </div>
        <p class="pub-soft-note">Todo o conteúdo desta página encontra-se em português e pronto para ser ligado a uma base real de eventos, editais e calendário académico.</p>
    </div>
</section>

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
                slides.forEach(function (slide, i) { slide.classList.toggle('active', i === current); });
                dots.forEach(function (dot, i) { dot.classList.toggle('active', i === current); });
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
                timer = window.setInterval(function () { activate(current + 1); }, interval);
            }
            function stop() { if (timer) { window.clearInterval(timer); timer = null; } }
            dots.forEach(function (dot, index) {
                dot.addEventListener('click', function () { activate(index); start(); });
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
