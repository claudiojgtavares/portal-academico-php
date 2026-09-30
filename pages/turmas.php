<?php
require_once __DIR__ . '/../includes/portal_layout.php';

require_any_role(['admin', 'secretaria', 'coordenador', 'direcao']);

$user = current_user();
$role = portal_user_role($user);
$canEdit = in_array($role, ['admin', 'secretaria'], true);
[$success, $error] = portal_flash();

$q = trim($_GET['q'] ?? '');
$courseFilter = (int) ($_GET['course_id'] ?? 0);
$statusFilter = trim($_GET['estado'] ?? '');
$selectedClassId = (int) ($_GET['turma'] ?? 0);

$courses = portal_rows($pdo, "SELECT id, code, name, level FROM courses WHERE status = 'active' ORDER BY level ASC, name ASC");
$years = portal_db_table_exists($pdo, 'academic_years')
    ? portal_rows($pdo, "SELECT id, name FROM academic_years ORDER BY name DESC")
    : [];
$subjects = portal_rows($pdo, "
    SELECT subjects.id, subjects.code, subjects.name, subjects.course_id, courses.code AS course_code, courses.name AS course_name
    FROM subjects
    LEFT JOIN courses ON courses.id = subjects.course_id
    WHERE subjects.status = 'active'
    ORDER BY courses.name ASC, subjects.name ASC
");

$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(academic_classes.name LIKE ? OR academic_classes.code LIKE ? OR courses.name LIKE ? OR courses.code LIKE ?)";
    $like = '%' . $q . '%';
    $params = [$like, $like, $like, $like];
}
if ($courseFilter > 0) {
    $where[] = 'academic_classes.course_id = ?';
    $params[] = $courseFilter;
}
if ($statusFilter !== '') {
    $where[] = 'academic_classes.status = ?';
    $params[] = $statusFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$classes = portal_db_table_exists($pdo, 'academic_classes')
    ? portal_rows($pdo, "
        SELECT academic_classes.*, courses.code AS course_code, courses.name AS course_name, academic_years.name AS academic_year
        FROM academic_classes
        LEFT JOIN courses ON courses.id = academic_classes.course_id
        LEFT JOIN academic_years ON academic_years.id = academic_classes.academic_year_id
        {$whereSql}
        ORDER BY courses.name ASC, academic_classes.year_number ASC, academic_classes.name ASC
        LIMIT 300
    ", $params)
    : [];

$selectedClass = null;
if ($selectedClassId > 0) {
    $selectedClass = portal_one($pdo, "
        SELECT academic_classes.*, courses.code AS course_code, courses.name AS course_name
        FROM academic_classes
        LEFT JOIN courses ON courses.id = academic_classes.course_id
        WHERE academic_classes.id = ?
        LIMIT 1
    ", [$selectedClassId]);
}

$classSubjects = portal_db_table_exists($pdo, 'class_subjects')
    ? portal_rows($pdo, "
        SELECT class_subjects.*, academic_classes.name AS class_name, courses.code AS course_code,
               subjects.code AS subject_code, subjects.name AS subject_name, academic_years.name AS academic_year
        FROM class_subjects
        LEFT JOIN academic_classes ON academic_classes.id = class_subjects.class_id
        LEFT JOIN courses ON courses.id = academic_classes.course_id
        LEFT JOIN subjects ON subjects.id = class_subjects.subject_id
        LEFT JOIN academic_years ON academic_years.id = class_subjects.academic_year_id
        " . ($selectedClassId > 0 ? "WHERE class_subjects.class_id = ?" : "") . "
        ORDER BY academic_classes.name ASC, subjects.name ASC
        LIMIT 300
    ", $selectedClassId > 0 ? [$selectedClassId] : [])
    : [];

$activeKey = $role === 'coordenador' ? 'turmas_curso' : 'turmas';
portal_layout_start($activeKey, 'Turmas', 'Criação de turmas e associação de disciplinas por curso e ano letivo.', $role);
?>

<?php if ($success): ?><div class="ui-alert ui-alert-success"><?= e($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="ui-alert ui-alert-error"><?= e($error); ?></div><?php endif; ?>

<div class="ui-kpi-grid">
    <div class="ui-kpi"><span>Turmas</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM academic_classes"); ?></strong><small>Total registado.</small></div>
    <div class="ui-kpi is-good"><span>Ativas</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM academic_classes WHERE status='active'"); ?></strong><small>Disponíveis.</small></div>
    <div class="ui-kpi is-info"><span>Disciplinas em turmas</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM class_subjects WHERE status='active'"); ?></strong><small>Associações ativas.</small></div>
    <div class="ui-kpi is-warn"><span>Alunos em turmas</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM class_students WHERE status='active'"); ?></strong><small>Matrículas por turma.</small></div>
</div>

<?php if (!portal_db_table_exists($pdo, 'academic_classes') || !portal_db_table_exists($pdo, 'class_subjects')): ?>
    <div class="ui-section-note">Execute o SQL <strong>database/2026_05_20_academic_structure.sql</strong> para ativar a gestão de turmas.</div>
<?php endif; ?>

<?php if ($canEdit): ?>
<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2>Criar turma</h2>
            <p>A secretaria ou administração cria turmas por curso e ano letivo. Depois associa as disciplinas da turma.</p>
        </div>
    </div>
    <div class="ui-panel-body">
        <form method="POST" action="<?= e(APP_URL); ?>/actions/salvar_turma.php" class="ui-form-grid" style="grid-template-columns:1.2fr 1fr .7fr .7fr .7fr .7fr;align-items:end;">
            <input type="hidden" name="acao" value="criar_turma">
            <div class="ui-field"><label>Curso</label><select class="ui-control" name="course_id" required><option value="">Selecionar curso</option><?php foreach ($courses as $c): ?><option value="<?= (int) $c['id']; ?>"><?= e(($c['code'] ?? '-') . ' — ' . ($c['name'] ?? '-')); ?></option><?php endforeach; ?></select></div>
            <div class="ui-field"><label>Ano letivo</label><select class="ui-control" name="academic_year_id"><option value="0">Selecionar</option><?php foreach ($years as $y): ?><option value="<?= (int) $y['id']; ?>"><?= e($y['name'] ?? '-'); ?></option><?php endforeach; ?></select></div>
            <div class="ui-field"><label>Nome</label><input class="ui-control" name="name" placeholder="Ex.: ESI 1.º Ano — Turma A" required></div>
            <div class="ui-field"><label>Código</label><input class="ui-control" name="code" placeholder="Ex.: ESI-1A"></div>
            <div class="ui-field"><label>Ano</label><input class="ui-control" type="number" min="1" max="8" name="year_number" placeholder="1"></div>
            <div class="ui-field"><label>Turno</label><select class="ui-control" name="shift"><option value="">Sem turno</option><option value="manha">Manhã</option><option value="tarde">Tarde</option><option value="noite">Noite</option></select></div>
            <div class="ui-actions" style="grid-column:1/-1"><button class="ui-btn ui-btn-primary" type="submit">Criar turma</button></div>
        </form>
    </div>
</div>

<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2>Atribuir disciplina à turma</h2>
            <p>Esta etapa define quais unidades curriculares fazem parte de cada turma.</p>
        </div>
    </div>
    <div class="ui-panel-body">
        <form method="POST" action="<?= e(APP_URL); ?>/actions/salvar_turma.php" class="ui-form-grid" style="grid-template-columns:1fr 1fr .8fr .6fr .6fr auto;align-items:end;">
            <input type="hidden" name="acao" value="atribuir_disciplina">
            <div class="ui-field"><label>Turma</label><select class="ui-control" name="class_id" required><option value="">Selecionar turma</option><?php foreach ($classes as $class): ?><option value="<?= (int) $class['id']; ?>" <?= $selectedClassId===(int)$class['id']?'selected':''; ?>><?= e(($class['course_code'] ?? '-') . ' · ' . ($class['name'] ?? '-')); ?></option><?php endforeach; ?></select></div>
            <div class="ui-field"><label>Disciplina</label><select class="ui-control" name="subject_id" required><option value="">Selecionar disciplina</option><?php foreach ($subjects as $subject): ?><option value="<?= (int) $subject['id']; ?>"><?= e(($subject['course_code'] ?? '-') . ' · ' . ($subject['code'] ?? '-') . ' — ' . ($subject['name'] ?? '-')); ?></option><?php endforeach; ?></select></div>
            <div class="ui-field"><label>Ano letivo</label><select class="ui-control" name="academic_year_id"><option value="0">Selecionar</option><?php foreach ($years as $y): ?><option value="<?= (int) $y['id']; ?>"><?= e($y['name'] ?? '-'); ?></option><?php endforeach; ?></select></div>
            <div class="ui-field"><label>Semestre</label><input class="ui-control" type="number" min="1" max="12" name="semester" placeholder="1"></div>
            <div class="ui-field"><label>Estado</label><select class="ui-control" name="status"><option value="active">Ativo</option><option value="inactive">Inativo</option></select></div>
            <button class="ui-btn ui-btn-primary" type="submit">Atribuir</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="ui-panel">
    <div class="ui-panel-header"><div><h2>Turmas registadas</h2><p>Pesquise por curso, código, turma ou estado.</p></div></div>
    <div class="ui-panel-body" style="border-bottom:1px solid var(--ui-divider);">
        <form method="GET" class="ui-form-grid" style="grid-template-columns:minmax(260px,1fr) 260px 180px auto auto;align-items:end;">
            <div class="ui-field"><label>Pesquisar</label><input class="ui-control" name="q" value="<?= e($q); ?>" placeholder="Turma, código ou curso"></div>
            <div class="ui-field"><label>Curso</label><select class="ui-control" name="course_id"><option value="0">Todos os cursos</option><?php foreach ($courses as $c): ?><option value="<?= (int)$c['id']; ?>" <?= $courseFilter===(int)$c['id']?'selected':''; ?>><?= e(($c['code']??'-').' — '.($c['name']??'-')); ?></option><?php endforeach; ?></select></div>
            <div class="ui-field"><label>Estado</label><select class="ui-control" name="estado"><option value="">Todos</option><option value="active" <?= $statusFilter==='active'?'selected':''; ?>>Ativo</option><option value="inactive" <?= $statusFilter==='inactive'?'selected':''; ?>>Inativo</option></select></div>
            <button class="ui-btn ui-btn-primary">Pesquisar</button>
            <a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/turmas.php">Limpar</a>
        </form>
    </div>
    <div class="ui-table-wrap">
        <table class="ui-table">
            <thead><tr><th>Turma</th><th>Curso</th><th>Ano letivo</th><th>Ano/Semestre</th><th>Estado</th><th>Ações</th></tr></thead>
            <tbody>
                <?php if (empty($classes)): ?><tr><td colspan="6" class="ui-empty">Nenhuma turma encontrada.</td></tr><?php endif; ?>
                <?php foreach ($classes as $class): ?>
                    <tr>
                        <td><strong><?= e($class['name'] ?? '-'); ?></strong><small><?= e($class['code'] ?? ''); ?></small></td>
                        <td><strong><?= e($class['course_code'] ?? '-'); ?></strong><small><?= e($class['course_name'] ?? '-'); ?></small></td>
                        <td><?= e($class['academic_year'] ?? '-'); ?></td>
                        <td><?= e(($class['year_number'] ?? '-') . ' / ' . ($class['semester'] ?? '-')); ?></td>
                        <td><span class="ui-badge <?= e(portal_badge_class($class['status'] ?? '')); ?>"><?= e(portal_label_status($class['status'] ?? '')); ?></span></td>
                        <td><div class="ui-actions"><a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/turmas.php?turma=<?= (int)$class['id']; ?>">Ver disciplinas</a><?php if($canEdit): ?><form method="POST" action="<?= e(APP_URL); ?>/actions/salvar_turma.php"><input type="hidden" name="class_id" value="<?= (int)$class['id']; ?>"><button class="ui-btn <?= ($class['status'] ?? '')==='active'?'ui-btn-danger':'ui-btn-secondary'; ?>" name="acao" value="<?= ($class['status'] ?? '')==='active'?'inativar_turma':'ativar_turma'; ?>"><?= ($class['status'] ?? '')==='active'?'Inativar':'Ativar'; ?></button></form><?php endif; ?></div></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="ui-panel">
    <div class="ui-panel-header"><div><h2>Disciplinas por turma<?= $selectedClass ? ' — ' . e($selectedClass['name'] ?? '') : ''; ?></h2><p>Estas disciplinas alimentam a atribuição ao professor/coordenador, horários, notas e faltas.</p></div></div>
    <div class="ui-table-wrap">
        <table class="ui-table">
            <thead><tr><th>Turma</th><th>Disciplina</th><th>Ano letivo</th><th>Semestre</th><th>Estado</th><th>Ações</th></tr></thead>
            <tbody>
                <?php if (empty($classSubjects)): ?><tr><td colspan="6" class="ui-empty">Nenhuma disciplina associada à turma.</td></tr><?php endif; ?>
                <?php foreach ($classSubjects as $row): ?>
                    <tr>
                        <td><strong><?= e($row['class_name'] ?? '-'); ?></strong><small><?= e($row['course_code'] ?? '-'); ?></small></td>
                        <td><strong><?= e($row['subject_code'] ?? '-'); ?></strong><small><?= e($row['subject_name'] ?? '-'); ?></small></td>
                        <td><?= e($row['academic_year'] ?? '-'); ?></td>
                        <td><?= e((string)($row['semester'] ?? '-')); ?></td>
                        <td><span class="ui-badge <?= e(portal_badge_class($row['status'] ?? '')); ?>"><?= e(portal_label_status($row['status'] ?? '')); ?></span></td>
                        <td><?php if($canEdit): ?><form method="POST" action="<?= e(APP_URL); ?>/actions/salvar_turma.php" class="ui-actions"><input type="hidden" name="class_subject_id" value="<?= (int)$row['id']; ?>"><button class="ui-btn <?= ($row['status'] ?? '')==='active'?'ui-btn-danger':'ui-btn-secondary'; ?>" name="acao" value="<?= ($row['status'] ?? '')==='active'?'inativar_disciplina_turma':'ativar_disciplina_turma'; ?>"><?= ($row['status'] ?? '')==='active'?'Inativar':'Ativar'; ?></button></form><?php else: ?>-<?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php portal_layout_end(); ?>
