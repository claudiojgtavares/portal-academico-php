<?php

require_once __DIR__ . '/../includes/auth.php';

$codigo = trim($_GET['codigo'] ?? '');
$email = trim($_GET['email'] ?? '');

?>
<!DOCTYPE html>
<html lang="pt-CV">
<head>
    <meta charset="UTF-8">
    <title>Candidatura recebida — <?= e(APP_NAME); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= e(APP_URL); ?>/assets/css/public.css">

    <style>
        .success-hero {
            background: linear-gradient(120deg, var(--pub-brown-950), var(--pub-red-800));
            color: #ffffff;
            padding: 84px 0 92px;
        }

        .success-hero h1 {
            margin: 22px 0 16px;
            font-size: clamp(2.5rem, 5vw, 4.5rem);
            line-height: 1;
            letter-spacing: -0.045em;
        }

        .success-hero p {
            max-width: 760px;
            color: rgba(255, 255, 255, 0.86);
            font-size: 18px;
            line-height: 1.7;
        }

        .success-section {
            padding: 70px 0;
            background: linear-gradient(180deg, #f5f7fa 0%, #ffffff 100%);
        }

        .success-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(320px, 0.9fr);
            gap: 28px;
            align-items: start;
        }

        .success-card {
            background: #ffffff;
            border: 1px solid var(--pub-border);
            border-radius: 24px;
            box-shadow: var(--pub-shadow);
            padding: 30px;
        }

        .success-check {
            width: 66px;
            height: 66px;
            border-radius: 999px;
            background: #e8f8ef;
            color: #178a54;
            display: grid;
            place-items: center;
            font-size: 34px;
            font-weight: 900;
            margin-bottom: 20px;
        }

        .success-card h2 {
            margin: 0 0 12px;
            color: var(--pub-brown-900);
            font-size: 1.8rem;
        }

        .success-card p {
            color: var(--pub-muted);
            line-height: 1.7;
            margin-top: 0;
        }

        .success-code-box {
            margin: 22px 0;
            padding: 18px;
            border-radius: 18px;
            background: var(--pub-gold-100);
            border: 1px solid #efd499;
        }

        .success-code-box span {
            display: block;
            color: var(--pub-muted);
            font-weight: 800;
            margin-bottom: 6px;
        }

        .success-code-box strong {
            display: block;
            color: var(--pub-brown-900);
            font-size: 1.6rem;
            letter-spacing: 0.02em;
            overflow-wrap: anywhere;
        }

        .success-warning {
            margin-top: 18px;
            padding: 16px;
            border-radius: 16px;
            background: #fff7e6;
            border: 1px solid #f3d18b;
            color: #7a4a00;
            line-height: 1.6;
            font-weight: 700;
        }

        .success-steps {
            display: grid;
            gap: 18px;
            margin-top: 22px;
        }

        .success-step {
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }

        .success-step span {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            background: var(--pub-gold-400);
            color: #2a1600;
            display: grid;
            place-items: center;
            font-weight: 900;
            flex-shrink: 0;
        }

        .success-step strong {
            color: var(--pub-brown-900);
            display: block;
            margin-bottom: 5px;
        }

        .success-step p {
            margin: 0;
            color: var(--pub-muted);
            line-height: 1.55;
        }

        .success-main-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 24px;
        }

        .success-main-actions .pub-btn {
            min-width: auto;
            min-height: 42px;
            padding: 0 16px;
            font-size: 0.88rem;
            border-radius: 12px;
            white-space: nowrap;
        }

        @media (max-width: 900px) {
            .success-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 620px) {
            .success-main-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .success-main-actions .pub-btn {
                width: 100%;
            }
        }
    </style>
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
            <a class="active" href="<?= e(APP_URL); ?>/candidatura.php">Candidaturas</a>
            <a href="<?= e(APP_URL); ?>/pages/eventos.php">Eventos</a>
            <a href="<?= e(APP_URL); ?>/pages/noticias.php">Notícias</a>
            <a href="<?= e(APP_URL); ?>/login.php">Portal Académico</a>
        </nav>
    </div>
</header>

<section class="success-hero">
    <div class="pub-container">
        <span class="pub-pill">Candidatura submetida</span>

        <h1>Pedido recebido com sucesso</h1>

        <p>
            A sua candidatura foi registada no portal. Guarde o código da candidatura
            para acompanhar o estado do pedido.
        </p>

        <div class="pub-actions">
            <a class="pub-btn pub-btn-primary" href="<?= e(APP_URL); ?>/estado-candidatura.php<?= $codigo !== '' ? '?q=' . urlencode($codigo) : ''; ?>">
                Acompanhar candidatura
            </a>

            <a class="pub-btn pub-btn-soft" href="<?= e(APP_URL); ?>/candidatura.php">
                Nova candidatura
            </a>

            <a class="pub-btn pub-btn-soft" href="<?= e(APP_URL); ?>/index.php">
                Voltar ao início
            </a>
        </div>
    </div>
</section>

<main class="success-section">
    <div class="pub-container">
        <div class="success-layout">
            <section class="success-card">
                <div class="success-check">✓</div>

                <h2>Candidatura registada</h2>

                <p>
                    O seu pedido será analisado pela secretaria académica ou pela administração.
                    Caso falte algum documento, o sistema poderá solicitar correções.
                </p>

                <?php if ($codigo !== ''): ?>
                    <div class="success-code-box">
                        <span>Código da candidatura</span>
                        <strong><?= e($codigo); ?></strong>
                    </div>
                <?php else: ?>
                    <div class="success-warning">
                        Candidatura submetida. Se o código não aparecer nesta página,
                        use o seu email pessoal ou número do documento para acompanhar o estado.
                    </div>
                <?php endif; ?>

                <?php if ($email !== ''): ?>
                    <div class="success-code-box">
                        <span>Email usado na candidatura</span>
                        <strong><?= e($email); ?></strong>
                    </div>
                <?php endif; ?>

                <div class="success-main-actions">
                    <a class="pub-btn pub-btn-brown" href="<?= e(APP_URL); ?>/estado-candidatura.php<?= $codigo !== '' ? '?q=' . urlencode($codigo) : ''; ?>">
                        Acompanhar agora
                    </a>

                    <?php if ($codigo !== ''): ?>
                        <a
                            class="pub-btn pub-btn-light"
                            href="<?= e(APP_URL); ?>/actions/baixar_recibo_candidatura.php?codigo=<?= urlencode($codigo); ?><?= $email !== '' ? '&email=' . urlencode($email) : ''; ?>"
                        >
                            Baixar recibo PDF
                        </a>
                    <?php endif; ?>

                    <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/index.php">
                        Voltar ao início
                    </a>
                </div>
            </section>

            <aside class="success-card">
                <h2>Próximos passos</h2>

                <div class="success-steps">
                    <div class="success-step">
                        <span>1</span>
                        <div>
                            <strong>Análise inicial</strong>
                            <p>A secretaria verifica os dados pessoais, documentos e curso escolhido.</p>
                        </div>
                    </div>

                    <div class="success-step">
                        <span>2</span>
                        <div>
                            <strong>Validação documental</strong>
                            <p>Se algum documento estiver em falta, poderá ser solicitada correção.</p>
                        </div>
                    </div>

                    <div class="success-step">
                        <span>3</span>
                        <div>
                            <strong>Decisão da candidatura</strong>
                            <p>O pedido poderá ser aprovado, rejeitado ou ficar pendente de documentos.</p>
                        </div>
                    </div>

                    <div class="success-step">
                        <span>4</span>
                        <div>
                            <strong>Credenciais de acesso</strong>
                            <p>Se aprovado, receberá ID institucional, email e senha temporária.</p>
                        </div>
                    </div>
                </div>

                <div class="pub-actions" style="margin-top: 26px;">
                    <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/pages/requisitos.php">
                        Ver requisitos
                    </a>

                    <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/pages/contactos.php">
                        Contactos
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