<?php

require_once __DIR__ . '/../includes/auth.php';

?>
<!DOCTYPE html>
<html lang="pt-CV">
<head>
    <meta charset="UTF-8">
    <title>Requisitos — <?= e(APP_NAME); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= e(APP_URL); ?>/assets/css/public.css">
</head>
<body>
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
            <a href="<?= e(APP_URL); ?>/candidatura.php">Inscrições Online</a>
            <a href="<?= e(APP_URL); ?>/pages/eventos.php">Eventos</a>
            <a href="<?= e(APP_URL); ?>/pages/noticias.php">Notícias</a>
            <a href="<?= e(APP_URL); ?>/login.php">Portal Académico</a>
        </nav>
    </div>
</header>

<section class="pub-page-hero image-documents">
    <div class="pub-container">
        <span class="pub-pill">Candidaturas</span>
        <h1>Requisitos e documentos</h1>
        <p>Consulte os documentos necessários para iniciar a candidatura online.</p>

        <div class="pub-actions">
            <a class="pub-btn pub-btn-primary" href="<?= e(APP_URL); ?>/candidatura.php">
                Iniciar candidatura
            </a>

            <a class="pub-btn pub-btn-soft" href="<?= e(APP_URL); ?>/pages/ensino.php">
                Voltar às ofertas
            </a>

            <a class="pub-btn pub-btn-soft" href="<?= e(APP_URL); ?>/index.php">
                Voltar ao início
            </a>
        </div>
    </div>
</section>

<main class="pub-section">
    <div class="pub-container">
        <div class="pub-grid-2">
            <div class="pub-card">
                <h3>Documentos necessários</h3>

                <ul class="pub-check-list">
                    <li>Documento de identificação válido</li>
                    <li>Certificado de habilitações</li>
                    <li>Fotografia tipo passe</li>
                    <li>Comprovativo de pagamento, quando aplicável</li>
                    <li>Outros documentos solicitados pela secretaria</li>
                </ul>

                <div class="pub-actions">
                    <a class="pub-btn pub-btn-brown" href="<?= e(APP_URL); ?>/candidatura.php">
                        Iniciar candidatura
                    </a>

                    <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/pages/ensino.php">
                        Voltar às ofertas
                    </a>
                </div>
            </div>

            <div class="pub-card">
                <h3>Depois da submissão</h3>

                <ol class="pub-flow">
                    <li>A secretaria analisa os documentos</li>
                    <li>O candidato acompanha o estado online</li>
                    <li>Se faltar documento, será solicitada correção</li>
                    <li>Após aprovação, são geradas credenciais de acesso</li>
                    <li>O estudante entra no Portal Académico</li>
                </ol>

                <div class="pub-actions">
                    <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/estado-candidatura.php">
                        Acompanhar candidatura
                    </a>

                    <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/index.php">
                        Voltar ao início
                    </a>
                </div>
            </div>
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
                    <a href="<?= e(APP_URL); ?>/pages/requisitos.php">Contactos</a>
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