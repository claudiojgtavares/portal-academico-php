<?php
require_once __DIR__ . '/../includes/portal_layout.php';

require_any_role(['admin', 'direcao', 'coordenador', 'secretaria']);

$user = current_user();
$role = portal_user_role($user);
[$success, $error] = portal_flash();

$reportsTableReady = portal_db_table_exists($pdo, 'academic_reports');

$courseFilter = (int) ($_GET['curso'] ?? 0);
$yearFilter = (int) ($_GET['ano'] ?? 0);

$courses = portal_rows($pdo, "SELECT id, code, name, level FROM courses ORDER BY FIELD(level,'licenciatura','mestrado','mestrado_integrado','doutoramento','especializacao','formacao_permanente'), name ASC");
$years = portal_rows($pdo, "SELECT id, name FROM academic_years ORDER BY name DESC");

$courseWhere = [];
$courseParams = [];

if ($courseFilter > 0) {
    $courseWhere[] = 'courses.id = ?';
    $courseParams[] = $courseFilter;
}

$courseWhereSql = $courseWhere ? 'WHERE ' . implode(' AND ', $courseWhere) : '';

$summaryRows = portal_rows($pdo, "
    SELECT
        courses.id,
        courses.code,
        courses.name,
        courses.level,
        COUNT(DISTINCT students.id) AS students_total,
        SUM(CASE WHEN students.enrollment_status = 'active' THEN 1 ELSE 0 END) AS active_students,
        COUNT(DISTINCT applications.id) AS applications_total,
        SUM(CASE WHEN applications.status IN ('pending','in_review','missing_documents') THEN 1 ELSE 0 END) AS applications_pending
    FROM courses
    LEFT JOIN students ON students.course_id = courses.id
    LEFT JOIN applications ON applications.course_id = courses.id
    {$courseWhereSql}
    GROUP BY courses.id
    ORDER BY FIELD(courses.level,'licenciatura','mestrado','mestrado_integrado','doutoramento','especializacao','formacao_permanente'), courses.name ASC
", $courseParams);

$levelLabels = [
    'licenciatura' => 'Licenciatura',
    'mestrado' => 'Mestrado',
    'mestrado_integrado' => 'Mestrado integrado',
    'doutoramento' => 'Doutoramento',
    'especializacao' => 'Especialização',
    'formacao_permanente' => 'Formação permanente',
];

$reports = [];
$coordinatorReports = [];
$secretariaDocs = [];

if ($reportsTableReady) {
    $reportWhere = [];
    $reportParams = [];

    if ($courseFilter > 0) {
        $reportWhere[] = 'academic_reports.related_course_id = ?';
        $reportParams[] = $courseFilter;
    }

    if ($yearFilter > 0) {
        $reportWhere[] = 'academic_reports.academic_year_id = ?';
        $reportParams[] = $yearFilter;
    }

    if ($role === 'coordenador') {
        $reportWhere[] = '(academic_reports.submitted_by = ? OR academic_reports.category = ?)';
        $reportParams[] = (int) $user['id'];
        $reportParams[] = 'relatorio_coordenacao';
    } elseif ($role === 'secretaria') {
        $reportWhere[] = '(academic_reports.submitted_by = ? OR academic_reports.category = ?)';
        $reportParams[] = (int) $user['id'];
        $reportParams[] = 'documento_secretaria';
    }

    $reportWhereSql = $reportWhere ? 'WHERE ' . implode(' AND ', $reportWhere) : '';

    $reports = portal_rows($pdo, "
        SELECT
            academic_reports.*,
            users.full_name AS submitted_by_name,
            users.institutional_id AS submitted_by_code,
            courses.code AS course_code,
            courses.name AS course_name,
            academic_years.name AS academic_year
        FROM academic_reports
        LEFT JOIN users ON users.id = academic_reports.submitted_by
        LEFT JOIN courses ON courses.id = academic_reports.related_course_id
        LEFT JOIN academic_years ON academic_years.id = academic_reports.academic_year_id
        {$reportWhereSql}
        ORDER BY academic_reports.created_at DESC, academic_reports.id DESC
        LIMIT 200
    ", $reportParams);

    foreach ($reports as $report) {
        if (($report['category'] ?? '') === 'documento_secretaria') {
            $secretariaDocs[] = $report;
        } else {
            $coordinatorReports[] = $report;
        }
    }
}

$activeStudents = portal_count($pdo, "SELECT COUNT(*) FROM students WHERE enrollment_status='active'");
$totalApplications = portal_count($pdo, "SELECT COUNT(*) FROM applications");
$totalCoursesWithStudents = portal_count($pdo, "SELECT COUNT(DISTINCT course_id) FROM students WHERE course_id IS NOT NULL");
$totalReports = $reportsTableReady ? portal_count($pdo, "SELECT COUNT(*) FROM academic_reports") : 0;

portal_layout_start(
    'relatorios',
    $role === 'coordenador' ? 'Relatórios do curso' : 'Relatórios',
    $role === 'direcao'
        ? 'Indicadores académicos, documentos submetidos e relatórios das coordenações.'
        : 'Submissão e consulta de relatórios académicos.',
    $role
);

function relatorio_label_categoria(?string $category): string
{
    return [
        'relatorio_coordenacao' => 'Relatório de coordenação',
        'documento_secretaria' => 'Documento da secretaria',
        'relatorio_direcao' => 'Relatório da direção',
    ][$category ?? ''] ?? 'Relatório académico';
}

function relatorio_extensao(string $name): string
{
    $ext = strtoupper(pathinfo($name, PATHINFO_EXTENSION));
    return $ext !== '' ? $ext : 'FICHEIRO';
}
?>

<?php if ($success): ?><div class="ui-alert ui-alert-success"><?= e($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="ui-alert ui-alert-error"><?= e($error); ?></div><?php endif; ?>

<?php if (!$reportsTableReady): ?>
    <div class="ui-alert ui-alert-warning">
        O módulo de relatórios ainda precisa da tabela <strong>academic_reports</strong>.
        Execute no phpMyAdmin o ficheiro <strong>database/2026_05_20_academic_reports.sql</strong>.
    </div>
<?php endif; ?>

<div class="ui-kpi-grid">
    <div class="ui-kpi is-good">
        <span>Alunos matriculados</span>
        <strong><?= (int) $activeStudents; ?></strong>
        <small>Matrículas ativas no portal.</small>
    </div>

    <div class="ui-kpi is-info">
        <span>Candidaturas</span>
        <strong><?= (int) $totalApplications; ?></strong>
        <small>Pedidos submetidos.</small>
    </div>

    <div class="ui-kpi">
        <span>Cursos com alunos</span>
        <strong><?= (int) $totalCoursesWithStudents; ?></strong>
        <small>Cursos com matrículas.</small>
    </div>

    <div class="ui-kpi is-warn">
        <span>Relatórios recebidos</span>
        <strong><?= (int) $totalReports; ?></strong>
        <small>Coordenação e secretaria.</small>
    </div>
</div>

<?php if (in_array($role, ['coordenador', 'secretaria', 'admin'], true)): ?>
    <div class="ui-panel report-upload-card">
        <div class="ui-panel-header">
            <div>
                <h2><?= $role === 'secretaria' ? 'Submeter documento para a direção' : 'Submeter relatório para a direção'; ?></h2>
                <p>
                    <?= $role === 'secretaria'
                        ? 'Envie documentos administrativos, mapas, ofícios ou relatórios para consulta da direção.'
                        : 'Envie relatórios do curso, atas, pareceres ou documentos académicos para consulta da direção.'; ?>
                </p>
            </div>
        </div>

        <div class="ui-panel-body">
            <form class="ui-form-grid" method="POST" action="<?= e(APP_URL); ?>/actions/submeter_relatorio.php" enctype="multipart/form-data">
                <div class="ui-field">
                    <label>Título</label>
                    <input class="ui-control" name="title" required placeholder="Ex.: Relatório do curso — Maio 2026">
                </div>

                <div class="ui-field">
                    <label>Categoria</label>
                    <select class="ui-control" name="category">
                        <?php if ($role === 'secretaria'): ?>
                            <option value="documento_secretaria">Documento da secretaria</option>
                        <?php else: ?>
                            <option value="relatorio_coordenacao">Relatório de coordenação</option>
                            <option value="relatorio_direcao">Relatório académico</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="ui-field">
                    <label>Curso relacionado</label>
                    <select class="ui-control" name="related_course_id">
                        <option value="">Sem curso específico</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= (int) $course['id']; ?>"><?= e($course['code'] . ' — ' . $course['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ui-field">
                    <label>Ano letivo</label>
                    <select class="ui-control" name="academic_year_id">
                        <option value="">Ano não especificado</option>
                        <?php foreach ($years as $year): ?>
                            <option value="<?= (int) $year['id']; ?>"><?= e($year['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ui-field" style="grid-column:1/-1;">
                    <label>Descrição</label>
                    <textarea class="ui-control" name="description" placeholder="Resumo do conteúdo, contexto ou observações importantes."></textarea>
                </div>

                <div class="ui-field" style="grid-column:1/-1;">
                    <label>Ficheiro</label>
                    <input class="ui-control" type="file" name="report_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.txt,.zip,.rar" required>
                    <small>Formatos aceites: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, imagens, TXT, ZIP ou RAR. Limite: 20MB.</small>
                </div>

                <div class="ui-actions" style="grid-column:1/-1;">
                    <button class="ui-btn ui-btn-primary" type="submit">Submeter para a direção</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2>Resumo académico por curso</h2>
            <p>Alunos matriculados e candidaturas recebidas, organizados por curso e nível de ensino.</p>
        </div>
    </div>

    <div class="ui-panel-body" style="border-bottom:1px solid var(--ui-divider);">
        <form method="GET" class="ui-form-grid" style="grid-template-columns:minmax(260px,1fr) 220px auto auto;align-items:end;">
            <div class="ui-field">
                <label>Curso</label>
                <select class="ui-control" name="curso">
                    <option value="">Todos os cursos</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= (int) $course['id']; ?>" <?= $courseFilter === (int) $course['id'] ? 'selected' : ''; ?>>
                            <?= e($course['code'] . ' — ' . $course['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ui-field">
                <label>Ano letivo</label>
                <select class="ui-control" name="ano">
                    <option value="">Todos</option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?= (int) $year['id']; ?>" <?= $yearFilter === (int) $year['id'] ? 'selected' : ''; ?>>
                            <?= e($year['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button class="ui-btn ui-btn-primary" type="submit">Filtrar</button>
            <a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/relatorios.php">Limpar</a>
        </form>
    </div>

    <div class="ui-table-wrap">
        <table class="ui-table">
            <thead>
                <tr>
                    <th>Curso</th>
                    <th>Nível</th>
                    <th>Alunos matriculados</th>
                    <th>Candidaturas</th>
                    <th>Pendentes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($summaryRows as $row): ?>
                    <tr>
                        <td>
                            <strong><?= e($row['code']); ?></strong>
                            <small><?= e($row['name']); ?></small>
                        </td>
                        <td><?= e($levelLabels[$row['level']] ?? portal_label_status($row['level'] ?? '')); ?></td>
                        <td><strong><?= (int) ($row['active_students'] ?? 0); ?></strong></td>
                        <td><?= (int) ($row['applications_total'] ?? 0); ?></td>
                        <td><?= (int) ($row['applications_pending'] ?? 0); ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($summaryRows)): ?>
                    <tr><td colspan="5">Sem dados para os filtros selecionados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="ui-split report-split">
    <div class="ui-panel">
        <div class="ui-panel-header">
            <div>
                <h2>Relatórios submetidos pelos coordenadores</h2>
                <p>Documentos académicos que a direção pode abrir ou baixar.</p>
            </div>
        </div>

        <div class="ui-panel-body">
            <div class="report-file-list">
                <?php foreach ($coordinatorReports as $report): ?>
                    <article class="report-file-card" id="relatorio-<?= (int) $report['id']; ?>">
                        <div class="report-file-icon"><?= e(relatorio_extensao($report['original_name'] ?? '')); ?></div>
                        <div class="report-file-main">
                            <span class="ui-badge <?= e(portal_badge_class($report['status'] ?? 'submitted')); ?>"><?= e(portal_label_status($report['status'] ?? 'submitted')); ?></span>
                            <h3><?= e($report['title']); ?></h3>
                            <p><?= e($report['description'] ?: 'Sem descrição.'); ?></p>
                            <small>
                                Por <?= e($report['submitted_by_name'] ?? 'Utilizador'); ?>
                                · <?= e($report['course_code'] ?? 'Sem curso'); ?>
                                · <?= e($report['academic_year'] ?? 'Ano não definido'); ?>
                                · <?= e(portal_date($report['created_at'] ?? null)); ?>
                            </small>
                        </div>
                        <div class="report-file-actions">
                            <a class="ui-btn ui-btn-primary" target="_blank" href="<?= e(APP_URL); ?>/actions/abrir_relatorio.php?id=<?= (int) $report['id']; ?>">Abrir</a>
                            <a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/actions/abrir_relatorio.php?id=<?= (int) $report['id']; ?>&download=1">Baixar</a>
                        </div>
                    </article>
                <?php endforeach; ?>

                <?php if (empty($coordinatorReports)): ?>
                    <div class="ui-empty">Ainda não existem relatórios de coordenação submetidos.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="ui-panel">
        <div class="ui-panel-header">
            <div>
                <h2>Documentos submetidos pela secretaria</h2>
                <p>Mapas, ofícios, relatórios administrativos ou documentos de apoio à decisão.</p>
            </div>
        </div>

        <div class="ui-panel-body">
            <div class="report-file-list">
                <?php foreach ($secretariaDocs as $report): ?>
                    <article class="report-file-card compact" id="relatorio-<?= (int) $report['id']; ?>">
                        <div class="report-file-icon"><?= e(relatorio_extensao($report['original_name'] ?? '')); ?></div>
                        <div class="report-file-main">
                            <span class="ui-badge <?= e(portal_badge_class($report['status'] ?? 'submitted')); ?>"><?= e(portal_label_status($report['status'] ?? 'submitted')); ?></span>
                            <h3><?= e($report['title']); ?></h3>
                            <p><?= e($report['description'] ?: 'Sem descrição.'); ?></p>
                            <small>
                                Por <?= e($report['submitted_by_name'] ?? 'Secretaria'); ?>
                                · <?= e(portal_date($report['created_at'] ?? null)); ?>
                            </small>
                        </div>
                        <div class="report-file-actions">
                            <a class="ui-btn ui-btn-primary" target="_blank" href="<?= e(APP_URL); ?>/actions/abrir_relatorio.php?id=<?= (int) $report['id']; ?>">Abrir</a>
                            <a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/actions/abrir_relatorio.php?id=<?= (int) $report['id']; ?>&download=1">Baixar</a>
                        </div>
                    </article>
                <?php endforeach; ?>

                <?php if (empty($secretariaDocs)): ?>
                    <div class="ui-empty">Ainda não existem documentos da secretaria submetidos para a direção.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php portal_layout_end(); ?>
