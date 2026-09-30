<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/helpers.php';
?>

<header class="site-header">
    <div class="top-strip">
        <div class="container top-strip-inner">
            <span>🇨🇻 PT</span>
            <span>📞 +238 260 90 00</span>
            <span>✉️ info@portal.example.test</span>
        </div>
    </div>

    <div class="main-header">
        <div class="container header-inner">
            <a href="<?= APP_URL; ?>/index.php" class="site-brand" aria-label="Página inicial">
                <div class="brand-seal">PA</div>
                <div>
                    <strong>Instituto Horizonte</strong>
                    <span>Cabo Verde</span>
                </div>
            </a>

            <nav class="public-nav" aria-label="Menu principal">
                <a href="<?= APP_URL; ?>/index.php">Início</a>
                <a href="#ofertas">Ensino</a>
                <a href="#candidatura">Candidaturas</a>
                <a href="#eventos">Eventos</a>
                <a href="#noticias">Notícias</a>
                <a href="<?= APP_URL; ?>/login.php">Portal Académico</a>
            </nav>
        </div>
    </div>
</header>