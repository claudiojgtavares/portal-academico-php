<?php

require_once __DIR__ . '/../includes/auth.php';

?>
<!DOCTYPE html>
<html lang="pt-CV">
<head>
    <meta charset="UTF-8">
    <title>Contactos — <?= e(APP_NAME); ?></title>
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
            <a href="<?= e(APP_URL); ?>/pages/eventos.php">Eventos</a>
            <a href="<?= e(APP_URL); ?>/pages/noticias.php">Notícias</a>
            <a href="<?= e(APP_URL); ?>/login.php">Portal Académico</a>
        </nav>
    </div>
</header>

<section class="pub-page-hero image-support">
    <div class="pub-container">
        <span class="pub-pill">Contactos</span>
        <h1>Fale com a Instituto Horizonte</h1>
        <p>
            Consulte os canais principais de atendimento para apoio académico,
            candidaturas, documentos e acesso ao portal.
        </p>

        <div class="pub-actions">
            <a class="pub-btn pub-btn-primary" href="<?= e(APP_URL); ?>/candidatura.php">
                Iniciar candidatura
            </a>

            <a class="pub-btn pub-btn-soft" href="<?= e(APP_URL); ?>/estado-candidatura.php">
                Acompanhar candidatura
            </a>

            <a class="pub-btn pub-btn-soft" href="<?= e(APP_URL); ?>/index.php">
                Voltar ao início
            </a>
        </div>
    </div>
</section>

<main class="pub-section">
    <div class="pub-container">
        <div class="pub-grid-3">
            <article class="pub-card">
                <div class="pub-icon">📞</div>
                <h3>Telefone</h3>
                <p>Atendimento geral e apoio inicial ao candidato.</p>
                <strong>+238 260 90 00</strong>
            </article>

            <article class="pub-card">
                <div class="pub-icon">✉️</div>
                <h3>Email</h3>
                <p>Envie pedidos de informação sobre candidaturas, cursos e documentos.</p>
                <strong>info@portal.example.test</strong>
            </article>

            <article class="pub-card">
                <div class="pub-icon">🏛️</div>
                <h3>Secretaria académica</h3>
                <p>Apoio relacionado com validação de documentos, matrícula, propinas e credenciais.</p>
                <strong>Atendimento académico</strong>
            </article>
        </div>
    </div>
</main>

<section class="pub-section alt">
    <div class="pub-container">
        <div class="pub-grid-2">
            <div class="pub-card">
                <h3>Antes de contactar</h3>

                <ul class="pub-check-list">
                    <li>Consulte a página de requisitos</li>
                    <li>Confira se os documentos estão completos</li>
                    <li>Guarde o código da candidatura</li>
                    <li>Acompanhe o estado online</li>
                    <li>Verifique se recebeu notificações do portal</li>
                </ul>

                <div class="pub-actions">
                    <a class="pub-btn pub-btn-brown" href="<?= e(APP_URL); ?>/pages/requisitos.php">
                        Ver requisitos
                    </a>
                </div>
            </div>

            <div class="pub-card">
                <h3>Acesso rápido</h3>

                <ol class="pub-flow">
                    <li>Para nova inscrição, use Candidaturas</li>
                    <li>Para estado do pedido, use Acompanhar candidatura</li>
                    <li>Para estudantes aprovados, use Portal Académico</li>
                    <li>Para dúvidas gerais, contacte a secretaria</li>
                </ol>

                <div class="pub-actions">
                    <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/pages/ensino.php">
                        Ver cursos
                    </a>

                    <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/login.php">
                        Entrar no portal
                    </a>
                </div>
            </div>
        </div>
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
</body>
</html>