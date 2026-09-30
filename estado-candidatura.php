<?php

require_once __DIR__ . '/includes/auth.php';

$search = trim($_GET['q'] ?? '');
$application = null;
$course = null;
$academicYear = null;
$documents = [];
$errorMessage = null;

function estado_table_exists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function estado_columns(PDO $pdo, string $table): array
{
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
        $rows = $stmt->fetchAll();
        return array_map(static fn ($row) => $row['Field'], $rows);
    } catch (Throwable $e) {
        return [];
    }
}

function estado_pick_column(array $columns, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            return $candidate;
        }
    }

    return null;
}

function estado_value(?array $row, ?string $column, string $fallback = '-'): string
{
    if (!$row || !$column || !array_key_exists($column, $row) || $row[$column] === null || $row[$column] === '') {
        return $fallback;
    }

    return (string) $row[$column];
}

function estado_date(?string $value): string
{
    if (!$value) {
        return '-';
    }

    $time = strtotime($value);

    return $time ? date('d/m/Y H:i', $time) : $value;
}

function estado_normalize_status(?string $status): string
{
    $status = strtolower(trim((string) $status));
    $status = str_replace([' ', '-'], '_', $status);

    return $status;
}

function estado_status_label(?string $status): string
{
    $status = estado_normalize_status($status);

    $labels = [
        'submitted' => 'Submetida',
        'pendente' => 'Pendente',
        'pending' => 'Pendente',
        'in_review' => 'Em análise',
        'em_analise' => 'Em análise',
        'documents_requested' => 'Documentos solicitados',
        'documentos_solicitados' => 'Documentos solicitados',
        'pending_documents' => 'Documentos pendentes',
        'approved' => 'Aprovada',
        'aprovada' => 'Aprovada',
        'rejected' => 'Rejeitada',
        'rejeitada' => 'Rejeitada',
        'credentials_sent' => 'Credenciais enviadas',
        'credenciais_enviadas' => 'Credenciais enviadas',
    ];

    return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function estado_status_class(?string $status): string
{
    $status = estado_normalize_status($status);

    if (in_array($status, ['approved', 'aprovada', 'credentials_sent', 'credenciais_enviadas'], true)) {
        return 'success';
    }

    if (in_array($status, ['rejected', 'rejeitada'], true)) {
        return 'danger';
    }

    if (in_array($status, ['documents_requested', 'documentos_solicitados', 'pending_documents'], true)) {
        return 'warning';
    }

    return 'info';
}

function estado_step(?string $status): int
{
    $status = estado_normalize_status($status);

    if (in_array($status, ['submitted', 'pendente', 'pending'], true)) {
        return 1;
    }

    if (in_array($status, ['in_review', 'em_analise', 'documents_requested', 'documentos_solicitados', 'pending_documents'], true)) {
        return 2;
    }

    if (in_array($status, ['rejected', 'rejeitada'], true)) {
        return 3;
    }

    if (in_array($status, ['approved', 'aprovada'], true)) {
        return 3;
    }

    if (in_array($status, ['credentials_sent', 'credenciais_enviadas'], true)) {
        return 4;
    }

    return 1;
}

function estado_level_label(?string $level): string
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

if ($search !== '') {
    if (!estado_table_exists($pdo, 'applications')) {
        $errorMessage = 'Tabela de candidaturas não encontrada.';
    } else {
        $appColumns = estado_columns($pdo, 'applications');

        $idColumn = estado_pick_column($appColumns, ['id']);
        $codeColumn = estado_pick_column($appColumns, ['application_code', 'code', 'candidate_code']);
        $emailColumn = estado_pick_column($appColumns, ['personal_email', 'email']);
        $documentNumberColumn = estado_pick_column($appColumns, ['document_number', 'id_number']);
        $courseIdColumn = estado_pick_column($appColumns, ['course_id']);
        $academicYearIdColumn = estado_pick_column($appColumns, ['academic_year_id']);
        $createdAtColumn = estado_pick_column($appColumns, ['created_at', 'submitted_at']);
        $statusColumn = estado_pick_column($appColumns, ['status']);

        $conditions = [];
        $params = [];

        foreach ([$codeColumn, $emailColumn, $documentNumberColumn] as $column) {
            if ($column) {
                $conditions[] = "`{$column}` = ?";
                $params[] = $search;
            }
        }

        if ($codeColumn) {
            $conditions[] = "`{$codeColumn}` LIKE ?";
            $params[] = '%' . $search . '%';
        }

        if (empty($conditions)) {
            $errorMessage = 'Não foi possível pesquisar porque os campos de pesquisa não foram encontrados.';
        } else {
            $orderSql = $idColumn ? "ORDER BY `{$idColumn}` DESC" : "";

            $stmt = $pdo->prepare("
                SELECT *
                FROM applications
                WHERE " . implode(' OR ', $conditions) . "
                {$orderSql}
                LIMIT 1
            ");

            $stmt->execute($params);
            $application = $stmt->fetch() ?: null;

            if ($application) {
                if ($courseIdColumn && !empty($application[$courseIdColumn])) {
                    $courseStmt = $pdo->prepare("
                        SELECT *
                        FROM courses
                        WHERE id = ?
                        LIMIT 1
                    ");

                    $courseStmt->execute([(int) $application[$courseIdColumn]]);
                    $course = $courseStmt->fetch() ?: null;
                }

                if ($academicYearIdColumn && !empty($application[$academicYearIdColumn])) {
                    $yearStmt = $pdo->prepare("
                        SELECT *
                        FROM academic_years
                        WHERE id = ?
                        LIMIT 1
                    ");

                    $yearStmt->execute([(int) $application[$academicYearIdColumn]]);
                    $academicYear = $yearStmt->fetch() ?: null;
                }

                if ($idColumn && estado_table_exists($pdo, 'application_documents')) {
                    $docColumns = estado_columns($pdo, 'application_documents');
                    $applicationIdColumn = estado_pick_column($docColumns, ['application_id']);

                    if ($applicationIdColumn) {
                        $docOrderColumn = estado_pick_column($docColumns, ['id', 'created_at', 'uploaded_at']);
                        $docOrderSql = $docOrderColumn ? "ORDER BY `{$docOrderColumn}` ASC" : "";

                        $docStmt = $pdo->prepare("
                            SELECT *
                            FROM application_documents
                            WHERE `{$applicationIdColumn}` = ?
                            {$docOrderSql}
                        ");

                        $docStmt->execute([(int) $application[$idColumn]]);
                        $documents = $docStmt->fetchAll();
                    }
                }
            }
        }
    }
}

$appColumns = $application ? array_keys($application) : [];

$appCodeColumn = estado_pick_column($appColumns, ['application_code', 'code', 'candidate_code']);
$appNameColumn = estado_pick_column($appColumns, ['full_name', 'applicant_name', 'name']);
$appEmailColumn = estado_pick_column($appColumns, ['personal_email', 'email']);
$appPhoneColumn = estado_pick_column($appColumns, ['phone', 'telephone']);
$appDocTypeColumn = estado_pick_column($appColumns, ['document_type']);
$appDocNumberColumn = estado_pick_column($appColumns, ['document_number', 'id_number']);
$appStatusColumn = estado_pick_column($appColumns, ['status']);
$appCreatedAtColumn = estado_pick_column($appColumns, ['created_at', 'submitted_at']);
$appNotesColumn = estado_pick_column($appColumns, ['notes', 'observations']);

$status = $application ? estado_value($application, $appStatusColumn, 'submitted') : '';
$statusLabel = estado_status_label($status);
$statusClass = estado_status_class($status);
$currentStep = estado_step($status);

?>
<!DOCTYPE html>
<html lang="pt-CV">
<head>
    <meta charset="UTF-8">
    <title>Acompanhar candidatura — <?= e(APP_NAME); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= e(APP_URL); ?>/assets/css/public.css">

    <style>
        .tracking-hero {
            background: linear-gradient(120deg, #351707 0%, #8f1427 55%, #b51632 100%);
            color: #fff;
            padding: 84px 0 92px;
        }

        .tracking-hero h1 {
            margin: 22px 0 16px;
            font-size: clamp(2.5rem, 5vw, 4.4rem);
            line-height: 1;
            letter-spacing: -0.045em;
        }

        .tracking-hero p {
            max-width: 760px;
            color: rgba(255,255,255,.86);
            font-size: 18px;
            line-height: 1.7;
        }

        .tracking-section {
            padding: 70px 0;
            background: linear-gradient(180deg, #f5f7fa 0%, #fff 100%);
        }

        .tracking-search-card,
        .tracking-card {
            background: #fff;
            border: 1px solid var(--pub-border);
            border-radius: 24px;
            box-shadow: var(--pub-shadow);
            padding: 28px;
        }

        .tracking-search-card {
            margin-bottom: 28px;
        }

        .tracking-search-card h2,
        .tracking-card h2 {
            margin: 0 0 10px;
            color: #4a240e;
        }

        .tracking-search-card p {
            margin: 0 0 22px;
            color: var(--pub-muted);
            line-height: 1.6;
        }

        .tracking-search-form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto auto;
            gap: 12px;
            align-items: end;
        }

        .tracking-field label {
            display: block;
            margin-bottom: 8px;
            color: #344054;
            font-weight: 900;
        }

        .tracking-field input {
            width: 100%;
            min-height: 50px;
            border: 1px solid #d9e0ea;
            border-radius: 14px;
            padding: 0 15px;
            font: inherit;
        }

        .tracking-field input:focus {
            outline: 3px solid rgba(214, 167, 58, .25);
            border-color: #d9a62e;
        }

        .tracking-alert {
            padding: 16px 18px;
            border-radius: 16px;
            margin-bottom: 24px;
            font-weight: 800;
        }

        .tracking-alert.warning {
            background: #fff7e6;
            color: #7a4a00;
            border: 1px solid #f3d18b;
        }

        .tracking-alert.danger {
            background: #fff0ed;
            color: #b42318;
            border: 1px solid #f3c1ba;
        }

        .tracking-result-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(320px, .8fr);
            gap: 28px;
            align-items: start;
        }

        .tracking-status-header {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-start;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .tracking-code {
            color: var(--pub-muted);
            font-weight: 900;
        }

        .tracking-status-badge {
            display: inline-flex;
            padding: 8px 12px;
            border-radius: 999px;
            font-weight: 900;
            font-size: .88rem;
        }

        .tracking-status-badge.info {
            background: #eef4ff;
            color: #2563eb;
        }

        .tracking-status-badge.success {
            background: #e8f8ef;
            color: #178a54;
        }

        .tracking-status-badge.warning {
            background: #fff7e6;
            color: #8a5a00;
        }

        .tracking-status-badge.danger {
            background: #fff0ed;
            color: #b42318;
        }

        .tracking-steps {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin: 24px 0;
        }

        .tracking-step {
            border: 1px solid var(--pub-border);
            border-radius: 16px;
            padding: 16px;
            background: #fff;
        }

        .tracking-step span {
            width: 30px;
            height: 30px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: #edf0f4;
            color: var(--pub-muted);
            font-weight: 900;
            margin-bottom: 10px;
        }

        .tracking-step strong {
            color: #4a240e;
            display: block;
            font-size: .94rem;
        }

        .tracking-step.active {
            border-color: #d9a62e;
            background: #fff7e5;
        }

        .tracking-step.done span,
        .tracking-step.active span {
            background: #e0b64f;
            color: #2a1600;
        }

        .tracking-info-list {
            display: grid;
            gap: 0;
            margin-top: 10px;
        }

        .tracking-info-row {
            display: grid;
            grid-template-columns: 190px minmax(0, 1fr);
            gap: 18px;
            padding: 15px 0;
            border-bottom: 1px solid var(--pub-border);
        }

        .tracking-info-row:last-child {
            border-bottom: 0;
        }

        .tracking-info-row span {
            color: var(--pub-muted);
            font-weight: 900;
        }

        .tracking-info-row strong {
            color: var(--pub-text);
            overflow-wrap: anywhere;
        }

        .tracking-doc-list {
            display: grid;
            gap: 10px;
            margin-top: 16px;
        }

        .tracking-doc-item {
            border: 1px solid var(--pub-border);
            border-radius: 14px;
            padding: 14px;
            background: #fff;
        }

        .tracking-doc-item strong {
            display: block;
            color: #4a240e;
        }

        .tracking-doc-item small {
            color: var(--pub-muted);
        }

        .tracking-empty {
            color: var(--pub-muted);
            line-height: 1.6;
        }

        @media (max-width: 980px) {
            .tracking-search-form,
            .tracking-result-grid,
            .tracking-steps {
                grid-template-columns: 1fr;
            }

            .tracking-info-row {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            .tracking-search-form .pub-btn {
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
                <a href="<?= e(APP_URL); ?>/candidatura.php">Candidaturas</a>
                <a href="<?= e(APP_URL); ?>/pages/eventos.php">Eventos</a>
                <a href="<?= e(APP_URL); ?>/pages/noticias.php">Notícias</a>
                <a href="<?= e(APP_URL); ?>/login.php">Portal Académico</a>
            </nav>
        </div>
    </header>

    <section class="tracking-hero">
        <div class="pub-container">
            <span class="pub-pill">Candidaturas</span>

            <h1>Acompanhar candidatura</h1>

            <p>
                Consulte o estado do seu pedido usando o código da candidatura,
                o email pessoal ou o número do documento usado na inscrição.
            </p>

            <div class="pub-actions">
                <a class="pub-btn pub-btn-primary" href="#pesquisa-candidatura">
                    Pesquisar candidatura
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

    <main class="tracking-section" id="pesquisa-candidatura">
        <div class="pub-container">
            <section class="tracking-search-card">
                <h2>Consultar estado</h2>
                <p>
                    Digite o código recebido após a submissão, o email pessoal
                    ou o número do documento utilizado no processo de candidatura.
                </p>

                <form class="tracking-search-form" method="GET" action="<?= e(APP_URL); ?>/estado-candidatura.php">
                    <div class="tracking-field">
                        <label for="q">Código, email ou documento</label>
                        <input
                            type="text"
                            id="q"
                            name="q"
                            value="<?= e($search); ?>"
                            placeholder="Ex.: CAND-2026-0001 ou exemplo@email.com"
                            required
                        >
                    </div>

                    <button class="pub-btn pub-btn-brown" type="submit">
                        Pesquisar
                    </button>

                    <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/estado-candidatura.php">
                        Limpar
                    </a>
                </form>
            </section>

            <?php if ($errorMessage): ?>
                <div class="tracking-alert danger">
                    <?= e($errorMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($search !== '' && !$application && !$errorMessage): ?>
                <div class="tracking-alert warning">
                    Nenhuma candidatura encontrada com os dados informados.
                </div>
            <?php endif; ?>

            <?php if ($application): ?>
                <div class="tracking-result-grid">
                    <section class="tracking-card">
                        <div class="tracking-status-header">
                            <div>
                                <div class="tracking-code">
                                    <?= e(estado_value($application, $appCodeColumn, 'Candidatura')); ?>
                                </div>

                                <h2><?= e(estado_value($application, $appNameColumn, 'Candidato')); ?></h2>
                            </div>

                            <span class="tracking-status-badge <?= e($statusClass); ?>">
                                <?= e($statusLabel); ?>
                            </span>
                        </div>

                        <div class="tracking-steps">
                            <div class="tracking-step <?= $currentStep > 1 ? 'done' : ($currentStep === 1 ? 'active' : ''); ?>">
                                <span>1</span>
                                <strong>Submetida</strong>
                            </div>

                            <div class="tracking-step <?= $currentStep > 2 ? 'done' : ($currentStep === 2 ? 'active' : ''); ?>">
                                <span>2</span>
                                <strong>Em análise</strong>
                            </div>

                            <div class="tracking-step <?= $currentStep > 3 ? 'done' : ($currentStep === 3 ? 'active' : ''); ?>">
                                <span>3</span>
                                <strong>Decisão</strong>
                            </div>

                            <div class="tracking-step <?= $currentStep === 4 ? 'active' : ''; ?>">
                                <span>4</span>
                                <strong>Credenciais</strong>
                            </div>
                        </div>

                        <div class="tracking-info-list">
                            <div class="tracking-info-row">
                                <span>Estado atual</span>
                                <strong><?= e($statusLabel); ?></strong>
                            </div>

                            <div class="tracking-info-row">
                                <span>Data de submissão</span>
                                <strong><?= e(estado_date(estado_value($application, $appCreatedAtColumn, ''))); ?></strong>
                            </div>

                            <div class="tracking-info-row">
                                <span>Email</span>
                                <strong><?= e(estado_value($application, $appEmailColumn)); ?></strong>
                            </div>

                            <div class="tracking-info-row">
                                <span>Telefone</span>
                                <strong><?= e(estado_value($application, $appPhoneColumn)); ?></strong>
                            </div>

                            <div class="tracking-info-row">
                                <span>Documento</span>
                                <strong>
                                    <?= e(estado_value($application, $appDocTypeColumn, 'Documento')); ?>
                                    —
                                    <?= e(estado_value($application, $appDocNumberColumn)); ?>
                                </strong>
                            </div>

                            <div class="tracking-info-row">
                                <span>Curso</span>
                                <strong>
                                    <?php if ($course): ?>
                                        <?= e(($course['code'] ?? '') . ' — ' . ($course['name'] ?? 'Curso')); ?>
                                    <?php else: ?>
                                        Curso não encontrado
                                    <?php endif; ?>
                                </strong>
                            </div>

                            <div class="tracking-info-row">
                                <span>Nível</span>
                                <strong>
                                    <?= $course ? e(estado_level_label($course['level'] ?? '')) : '-'; ?>
                                </strong>
                            </div>

                            <div class="tracking-info-row">
                                <span>Ano letivo</span>
                                <strong>
                                    <?= $academicYear ? e($academicYear['name'] ?? '-') : '-'; ?>
                                </strong>
                            </div>

                            <?php if ($appNotesColumn && !empty($application[$appNotesColumn])): ?>
                                <div class="tracking-info-row">
                                    <span>Observações</span>
                                    <strong><?= nl2br(e($application[$appNotesColumn])); ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="pub-actions" style="margin-top: 24px;">
                            <?php if (in_array(estado_normalize_status($status), ['approved', 'aprovada', 'credentials_sent', 'credenciais_enviadas'], true)): ?>
                                <a class="pub-btn pub-btn-brown" href="<?= e(APP_URL); ?>/login.php">
                                    Entrar no portal
                                </a>
                            <?php endif; ?>

                            <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/candidatura.php">
                                Nova candidatura
                            </a>

                            <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/index.php">
                                Voltar ao início
                            </a>
                        </div>
                    </section>

                    <aside class="tracking-card">
                        <h2>Documentos anexados</h2>

                        <?php if (empty($documents)): ?>
                            <p class="tracking-empty">
                                Nenhum documento anexado encontrado para esta candidatura.
                            </p>
                        <?php else: ?>
                            <div class="tracking-doc-list">
                                <?php foreach ($documents as $document): ?>
                                    <?php
                                        $docName = $document['original_name']
                                            ?? $document['file_name']
                                            ?? $document['filename']
                                            ?? $document['document_name']
                                            ?? $document['type']
                                            ?? 'Documento anexado';

                                        $docStatus = $document['status']
                                            ?? $document['validation_status']
                                            ?? 'Recebido';

                                        $docDate = $document['created_at']
                                            ?? $document['uploaded_at']
                                            ?? null;
                                    ?>

                                    <div class="tracking-doc-item">
                                        <strong><?= e((string) $docName); ?></strong>
                                        <small>
                                            Estado: <?= e(estado_status_label((string) $docStatus)); ?>
                                            <?php if ($docDate): ?>
                                                · <?= e(estado_date((string) $docDate)); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top: 24px;">
                            <h2>Orientação</h2>

                            <ol class="pub-flow">
                                <li>Se estiver em análise, aguarde validação da secretaria.</li>
                                <li>Se faltar documento, acompanhe as instruções recebidas.</li>
                                <li>Se aprovada, receberá credenciais para entrar no portal.</li>
                            </ol>
                        </div>
                    </aside>
                </div>
            <?php endif; ?>
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