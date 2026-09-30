<?php

require_once __DIR__ . '/includes/auth.php';

$coursesStmt = $pdo->query("
    SELECT
        id,
        code,
        name,
        level,
        duration_years
    FROM courses
    WHERE status = 'active'
    ORDER BY
        FIELD(level, 'licenciatura', 'mestrado', 'doutoramento', 'especializacao', 'formacao_permanente', 'mestrado_integrado'),
        name ASC
");

$courses = $coursesStmt->fetchAll();

$yearsStmt = $pdo->query("
    SELECT
        id,
        name
    FROM academic_years
    ORDER BY id DESC
");

$academicYears = $yearsStmt->fetchAll();

$selectedCourseId = (int) ($_GET['course_id'] ?? 0);

$success = $_SESSION['flash_success'] ?? null;
$error = $_SESSION['flash_error'] ?? null;

unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if (!$success && isset($_GET['sucesso'])) {
    $success = 'Candidatura submetida com sucesso.';
}

if (!$error && isset($_GET['erro'])) {
    $error = 'Não foi possível submeter a candidatura. Verifique os dados.';
}

function candidatura_level_label(?string $level): string
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

?>
<!DOCTYPE html>
<html lang="pt-CV">
<head>
    <meta charset="UTF-8">
    <title>Solicitar inscrição — <?= e(APP_NAME); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= e(APP_URL); ?>/assets/css/public.css">

    <style>
        .application-help-text {
            display: block;
            margin-top: 7px;
            color: var(--pub-muted);
            font-size: 0.85rem;
            line-height: 1.4;
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

    <section class="application-hero image-candidatura">
        <div class="pub-container">
            <span class="pub-pill">Candidaturas</span>

            <h1>Solicitar inscrição</h1>

            <p>
                Preencha os seus dados, escolha o curso pretendido e envie os documentos necessários.
                Após a submissão, a secretaria académica analisa os documentos, valida o processo e comunica o resultado da candidatura.
            </p>

            <div class="pub-actions">
                <a class="pub-btn pub-btn-primary" href="#formulario-candidatura">
                    Preencher candidatura
                </a>

                <a class="pub-btn pub-btn-soft" href="<?= e(APP_URL); ?>/estado-candidatura.php">
                    Acompanhar candidatura
                </a>

                <a class="pub-btn pub-btn-soft" href="<?= e(APP_URL); ?>/index.php">
                    Cancelar e voltar ao início
                </a>
            </div>
        </div>
    </section>

    <main class="application-section" id="formulario-candidatura">
        <div class="pub-container">
            <?php if ($success): ?>
                <div class="application-alert success">
                    <?= e($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="application-alert error">
                    <?= e($error); ?>
                </div>
            <?php endif; ?>

            <div class="application-layout">
                <section class="application-card">
                    <div class="application-card-header">
                        <h2>Formulário de candidatura</h2>
                        <p>Campos com * são obrigatórios.</p>
                    </div>

                    <form
                        action="<?= e(APP_URL); ?>/actions/candidatura_action.php"
                        method="POST"
                        enctype="multipart/form-data"
                        class="application-form"
                        id="applicationForm"
                    >
                        <?= csrf_field(); ?>
                        <div class="application-grid">
                            <div class="application-field">
                                <label for="full_name">Nome completo <span>*</span></label>
                                <input
                                    type="text"
                                    id="full_name"
                                    name="full_name"
                                    placeholder="Ex.: João Silva"
                                    required
                                >
                            </div>

                            <div class="application-field">
                                <label for="birth_date_visible">Data de nascimento <span>*</span></label>

                                <input
                                    type="text"
                                    id="birth_date_visible"
                                    name="birth_date_visible"
                                    placeholder="dd/mm/aaaa"
                                    maxlength="10"
                                    inputmode="numeric"
                                    required
                                >

                                <input
                                    type="hidden"
                                    id="birth_date"
                                    name="birth_date"
                                    required
                                >
                            </div>

                            <div class="application-field">
                                <label for="document_type">Tipo de documento <span>*</span></label>
                                <select id="document_type" name="document_type" required>
                                    <option value="">Selecionar</option>
                                    <option value="BI">BI</option>
                                    <option value="Cartão Nacional de Identificação">Cartão Nacional de Identificação</option>
                                    <option value="Passaporte">Passaporte</option>
                                    <option value="Outro">Outro</option>
                                </select>
                            </div>

                            <div class="application-field">
                                <label for="document_number">N.º do documento <span>*</span></label>
                                <input
                                    type="text"
                                    id="document_number"
                                    name="document_number"
                                    placeholder="Ex.: 123456789"
                                    required
                                >
                            </div>

                            <div class="application-field">
                                <label for="personal_email">Email pessoal <span>*</span></label>
                                <input
                                    type="email"
                                    id="personal_email"
                                    name="personal_email"
                                    placeholder="exemplo@email.com"
                                    required
                                >
                            </div>

                            <div class="application-field">
                                <label for="phone">Telefone <span>*</span></label>
                                <input
                                    type="text"
                                    id="phone"
                                    name="phone"
                                    placeholder="+238 900 00 00"
                                    required
                                >
                            </div>

                            <div class="application-field">
                                <label for="course_id">Curso pretendido <span>*</span></label>
                                <select id="course_id" name="course_id" required>
                                    <option value="">Selecionar curso</option>

                                    <?php foreach ($courses as $course): ?>
                                        <option
                                            value="<?= (int) $course['id']; ?>"
                                            <?= $selectedCourseId === (int) $course['id'] ? 'selected' : ''; ?>
                                        >
                                            <?= e($course['code']); ?> — <?= e($course['name']); ?>
                                            (<?= e(candidatura_level_label($course['level'])); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="application-field">
                                <label for="academic_year_id">Ano letivo pretendido <span>*</span></label>
                                <select id="academic_year_id" name="academic_year_id" required>
                                    <option value="">Selecionar ano letivo</option>

                                    <?php foreach ($academicYears as $year): ?>
                                        <option value="<?= (int) $year['id']; ?>">
                                            <?= e($year['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="application-field full">
                                <label for="documents">Documentos em anexo <span>*</span></label>

                                <div class="application-upload">
                                    <label class="application-file-label" for="documents">
                                        <strong>Escolher ficheiros</strong>
                                        <span id="documentsLabel">Nenhum ficheiro selecionado</span>
                                    </label>

                                    <input
                                        type="file"
                                        id="documents"
                                        name="documents[]"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        multiple
                                        required
                                    >

                                    <small>
                                        Envie identificação, certificado escolar, documento de aprovação de matrícula
                                        ou comprovativo. Formatos aceites: PDF, JPG e PNG.
                                    </small>
                                </div>
                            </div>

                            <div class="application-field full">
                                <label for="notes">Observações</label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows="5"
                                    placeholder="Escreva alguma informação adicional, se necessário."
                                ></textarea>
                            </div>
                        </div>

                        <div class="application-actions">
                            <button class="pub-btn pub-btn-brown" type="submit">
                                Submeter candidatura
                            </button>

                            <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/index.php">
                                Cancelar e voltar ao início
                            </a>

                            <a class="pub-btn pub-btn-light" href="<?= e(APP_URL); ?>/estado-candidatura.php">
                                Já submeti, acompanhar candidatura
                            </a>
                        </div>
                    </form>
                </section>

                <aside class="application-card application-help">
                    <h2>Como funciona?</h2>

                    <div class="application-steps">
                        <div class="application-step">
                            <span>1</span>
                            <div>
                                <strong>Preencher formulário</strong>
                                <p>Indique os seus dados pessoais e curso pretendido.</p>
                            </div>
                        </div>

                        <div class="application-step">
                            <span>2</span>
                            <div>
                                <strong>Enviar documentos</strong>
                                <p>Anexe identificação, comprovativos e documentos académicos.</p>
                            </div>
                        </div>

                        <div class="application-step">
                            <span>3</span>
                            <div>
                                <strong>Análise da secretaria</strong>
                                <p>O pedido fica pendente até validação administrativa.</p>
                            </div>
                        </div>

                        <div class="application-step">
                            <span>4</span>
                            <div>
                                <strong>Receber credenciais</strong>
                                <p>Se a candidatura for aprovada, receberá o ID académico, email institucional e senha temporária.</p>
                            </div>
                        </div>
                    </div>

                    <a class="pub-btn pub-btn-light application-wide-button" href="<?= e(APP_URL); ?>/estado-candidatura.php">
                        Acompanhar candidatura
                    </a>

                    <a class="pub-btn pub-btn-light application-wide-button" href="<?= e(APP_URL); ?>/pages/ensino.php">
                        Voltar às ofertas formativas
                    </a>
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

    <script>
        const birthVisible = document.getElementById('birth_date_visible');
        const birthHidden = document.getElementById('birth_date');
        const applicationForm = document.getElementById('applicationForm');

        function isValidDate(day, month, year) {
            const dayNumber = Number(day);
            const monthNumber = Number(month);
            const yearNumber = Number(year);

            if (yearNumber < 1900 || yearNumber > new Date().getFullYear()) {
                return false;
            }

            if (monthNumber < 1 || monthNumber > 12) {
                return false;
            }

            if (dayNumber < 1 || dayNumber > 31) {
                return false;
            }

            const date = new Date(yearNumber, monthNumber - 1, dayNumber);

            return (
                date.getFullYear() === yearNumber &&
                date.getMonth() === monthNumber - 1 &&
                date.getDate() === dayNumber
            );
        }



        const documentsInput = document.getElementById('documents');
        const documentsLabel = document.getElementById('documentsLabel');

        if (documentsInput && documentsLabel) {
            documentsInput.addEventListener('change', function () {
                const files = Array.from(documentsInput.files || []);
                if (files.length === 0) {
                    documentsLabel.textContent = 'Nenhum ficheiro selecionado';
                    return;
                }
                if (files.length === 1) {
                    documentsLabel.textContent = files[0].name;
                    return;
                }
                documentsLabel.textContent = files.length + ' ficheiros selecionados';
            });
        }

        if (birthVisible && birthHidden && applicationForm) {
            birthVisible.addEventListener('input', function () {
                let value = birthVisible.value.replace(/\D/g, '');

                if (value.length > 2) {
                    value = value.slice(0, 2) + '/' + value.slice(2);
                }

                if (value.length > 5) {
                    value = value.slice(0, 5) + '/' + value.slice(5, 9);
                }

                birthVisible.value = value;

                const parts = value.split('/');

                if (
                    parts.length === 3 &&
                    parts[0].length === 2 &&
                    parts[1].length === 2 &&
                    parts[2].length === 4 &&
                    isValidDate(parts[0], parts[1], parts[2])
                ) {
                    birthHidden.value = parts[2] + '-' + parts[1] + '-' + parts[0];
                } else {
                    birthHidden.value = '';
                }
            });

            applicationForm.addEventListener('submit', function (event) {
                if (!birthHidden.value) {
                    event.preventDefault();
                    alert('Informe a data de nascimento no formato dd/mm/aaaa. Exemplo: 25/12/2000.');
                    birthVisible.focus();
                }
            });
        }
    </script>
</body>
</html>
