<?php
require_once __DIR__ . '/../includes/portal_layout.php';
require_any_role(['admin', 'secretaria', 'direcao', 'coordenador', 'professor']);

$user = current_user();
$role = portal_user_role($user);
$canManage = in_array($role, ['admin', 'secretaria'], true);
$q = trim($_GET['q'] ?? '');
$courseId = (int) ($_GET['course_id'] ?? 0);
$status = trim($_GET['estado'] ?? '');
$editId = (int) ($_GET['editar'] ?? 0);

$courses = portal_rows($pdo, "SELECT id, code, name, level FROM courses WHERE status='active' ORDER BY level ASC, name ASC");
$where = [];
$params = [];
if ($q !== '') { $where[] = "(subjects.name LIKE ? OR subjects.code LIKE ? OR courses.name LIKE ? OR courses.code LIKE ?)"; $like='%'.$q.'%'; $params = [$like,$like,$like,$like]; }
if ($courseId > 0) { $where[]='subjects.course_id=?'; $params[]=$courseId; }
if ($status !== '') { $where[]='subjects.status=?'; $params[]=$status; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$subjects = portal_rows($pdo, "
    SELECT subjects.*, courses.code AS course_code, courses.name AS course_name, courses.level AS course_level
    FROM subjects
    LEFT JOIN courses ON courses.id = subjects.course_id
    {$whereSql}
    ORDER BY courses.name ASC, subjects.semester ASC, subjects.name ASC
    LIMIT 400
", $params);
$grouped = [];
foreach ($subjects as $s) { $grouped[($s['course_code'] ?? '-') . ' — ' . ($s['course_name'] ?? 'Sem curso')][] = $s; }
$editing = $editId > 0 ? portal_one($pdo, "SELECT * FROM subjects WHERE id = ? LIMIT 1", [$editId]) : null;
[$flashSuccess, $flashError] = portal_flash();

portal_layout_start('disciplinas','Disciplinas','Unidades curriculares organizadas por curso, semestre e estado académico.',$role);
?>

<?php if ($flashSuccess): ?><div class="ui-alert ui-alert-success"><?= e($flashSuccess); ?></div><?php endif; ?>
<?php if ($flashError): ?><div class="ui-alert ui-alert-danger"><?= e($flashError); ?></div><?php endif; ?>

<div class="ui-kpi-grid">
    <div class="ui-kpi"><span>Total</span><strong><?= count($subjects); ?></strong><small>Disciplinas listadas.</small></div>
    <div class="ui-kpi is-good"><span>Ativas</span><strong><?= portal_count($pdo,"SELECT COUNT(*) FROM subjects WHERE status='active'"); ?></strong><small>Disponíveis.</small></div>
    <div class="ui-kpi is-info"><span>Cursos</span><strong><?= portal_count($pdo,"SELECT COUNT(DISTINCT course_id) FROM subjects"); ?></strong><small>Com disciplinas.</small></div>
    <div class="ui-kpi is-warn"><span>Atribuições</span><strong><?= portal_count($pdo,"SELECT COUNT(*) FROM teacher_subjects WHERE status='active'"); ?></strong><small>Professor-disciplina.</small></div>
</div>

<?php if ($canManage): ?>
<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2><?= $editing ? 'Editar disciplina' : 'Criar nova disciplina'; ?></h2>
            <p>A secretaria ou administração cria a unidade curricular e depois associa-a à turma correta na página Turmas.</p>
        </div>
    </div>
    <div class="ui-panel-body">
        <form method="POST" action="<?= e(APP_URL); ?>/actions/salvar_disciplina.php" class="ui-form-grid" style="grid-template-columns:280px 160px minmax(260px,1fr) 130px 130px 160px;align-items:end;">
            <input type="hidden" name="subject_id" value="<?= (int)($editing['id'] ?? 0); ?>">
            <div class="ui-field"><label>Curso</label><select class="ui-control" name="course_id" required><option value="">Selecionar curso</option><?php foreach($courses as $c): ?><option value="<?= (int)$c['id']; ?>" <?= (int)($editing['course_id'] ?? $courseId) === (int)$c['id'] ? 'selected' : ''; ?>><?= e(($c['code'] ?? '-') . ' — ' . ($c['name'] ?? '-')); ?></option><?php endforeach; ?></select></div>
            <div class="ui-field"><label>Código</label><input class="ui-control" name="code" required value="<?= e($editing['code'] ?? ''); ?>" placeholder="Ex.: INF020"></div>
            <div class="ui-field"><label>Nome da disciplina</label><input class="ui-control" name="name" required value="<?= e($editing['name'] ?? ''); ?>" placeholder="Ex.: Interação Homem-Máquina"></div>
            <div class="ui-field"><label>Semestre</label><input class="ui-control" type="number" min="1" max="12" name="semester" value="<?= e((string)($editing['semester'] ?? '')); ?>"></div>
            <div class="ui-field"><label>Créditos</label><input class="ui-control" type="number" min="0" max="60" name="credits" value="<?= e((string)($editing['credits'] ?? '')); ?>"></div>
            <div class="ui-field"><label>Estado</label><select class="ui-control" name="status"><option value="active" <?= ($editing['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Ativa</option><option value="inactive" <?= ($editing['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inativa</option></select></div>
            <div class="ui-field"><label>Carga horária</label><input class="ui-control" type="number" min="0" max="500" name="workload_hours" value="<?= e((string)($editing['workload_hours'] ?? '')); ?>" placeholder="Horas"></div>
            <div class="ui-field" style="grid-column:2/-2;"><label>Descrição / observação</label><textarea class="ui-control" name="description" rows="3" placeholder="Resumo, objetivos ou observação curricular."><?= e($editing['description'] ?? ''); ?></textarea></div>
            <div style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;"><button class="ui-btn ui-btn-primary"><?= $editing ? 'Guardar disciplina' : 'Criar disciplina'; ?></button><?php if ($editing): ?><a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/disciplinas.php">Cancelar</a><?php endif; ?></div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="ui-panel">
    <div class="ui-panel-header"><div><h2>Disciplinas registadas</h2><p>Filtre por curso, código, nome ou estado.</p></div></div>
    <div class="ui-panel-body" style="border-bottom:1px solid var(--ui-divider);">
        <form method="GET" class="ui-form-grid" style="grid-template-columns:minmax(240px,1fr) 280px 180px auto auto;align-items:end;">
            <div class="ui-field"><label>Pesquisar</label><input class="ui-control" name="q" value="<?= e($q); ?>" placeholder="Código ou nome da disciplina"></div>
            <div class="ui-field"><label>Curso</label><select class="ui-control" name="course_id"><option value="0">Todos os cursos</option><?php foreach($courses as $c): ?><option value="<?= (int)$c['id']; ?>" <?= $courseId===(int)$c['id']?'selected':''; ?>><?= e(($c['code']??'-').' — '.($c['name']??'-')); ?></option><?php endforeach; ?></select></div>
            <div class="ui-field"><label>Estado</label><select class="ui-control" name="estado"><option value="">Todos</option><option value="active" <?= $status==='active'?'selected':''; ?>>Ativa</option><option value="inactive" <?= $status==='inactive'?'selected':''; ?>>Inativa</option></select></div>
            <button class="ui-btn ui-btn-primary">Pesquisar</button><a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/disciplinas.php">Limpar</a>
        </form>
    </div>
    <div class="ui-panel-body" style="display:grid;gap:22px;">
        <?php if(empty($subjects)): ?><div class="ui-empty">Sem disciplinas registadas.</div><?php endif; ?>
        <?php foreach($grouped as $course=>$items): ?>
            <section>
                <h3 style="margin:0 0 12px;color:var(--ui-brown-900);"><?= e($course); ?> <small style="color:var(--ui-muted);font-size:14px;">· <?= count($items); ?> disciplina(s)</small></h3>
                <div class="ui-table-wrap"><table class="ui-table"><thead><tr><th>Código</th><th>Disciplina</th><th>Semestre</th><th>Créditos</th><th>Estado</th><?php if($canManage): ?><th>Ações</th><?php endif; ?></tr></thead><tbody><?php foreach($items as $subject): ?><tr><td><?= e($subject['code']??'-'); ?></td><td><strong><?= e($subject['name']??'-'); ?></strong><small><?= e($subject['description'] ?? ''); ?></small></td><td><?= e((string)($subject['semester']??'-')); ?></td><td><?= e((string)($subject['credits']??'-')); ?></td><td><span class="ui-badge <?= e(portal_badge_class($subject['status']??'')); ?>"><?= e(portal_label_status($subject['status']??'')); ?></span></td><?php if($canManage): ?><td><a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/disciplinas.php?editar=<?= (int)$subject['id']; ?>">Editar</a></td><?php endif; ?></tr><?php endforeach; ?></tbody></table></div>
            </section>
        <?php endforeach; ?>
    </div>
</div>
<?php portal_layout_end(); ?>
