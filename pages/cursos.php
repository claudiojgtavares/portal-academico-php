<?php
require_once __DIR__ . '/../includes/portal_layout.php';
require_any_role(['admin','secretaria','direcao','coordenador']);

$user = current_user();
$role = portal_user_role($user);
$canManage = in_array($role, ['admin', 'secretaria'], true);
$q = trim($_GET['q'] ?? '');
$level = trim($_GET['nivel'] ?? '');
$status = trim($_GET['estado'] ?? '');
$editId = (int) ($_GET['editar'] ?? 0);

$levelLabels = [
    'licenciatura' => 'Licenciaturas',
    'mestrado' => 'Mestrados',
    'mestrado_integrado' => 'Mestrados Integrados',
    'doutoramento' => 'Doutoramentos',
    'especializacao' => 'Especializações',
    'formacao_permanente' => 'Formação Permanente',
];

$where = [];
$params = [];
if ($q !== '') { $where[] = "(courses.name LIKE ? OR courses.code LIKE ?)"; $like = '%' . $q . '%'; $params[] = $like; $params[] = $like; }
if ($level !== '') { $where[] = "courses.level = ?"; $params[] = $level; }
if ($status !== '') { $where[] = "courses.status = ?"; $params[] = $status; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$courses = portal_rows($pdo, "
    SELECT courses.*, COUNT(students.id) AS students_total
    FROM courses
    LEFT JOIN students ON students.course_id = courses.id
    {$whereSql}
    GROUP BY courses.id
    ORDER BY FIELD(courses.level,'licenciatura','mestrado','mestrado_integrado','doutoramento','especializacao','formacao_permanente'), courses.name ASC
", $params);

$grouped = [];
foreach ($courses as $c) { $grouped[$c['level'] ?? 'outros'][] = $c; }
$editing = $editId > 0 ? portal_one($pdo, "SELECT * FROM courses WHERE id = ? LIMIT 1", [$editId]) : null;

[$flashSuccess, $flashError] = portal_flash();
portal_layout_start('cursos','Cursos','Gestão e consulta da oferta formativa por nível de ensino.',$role);
?>

<?php if ($flashSuccess): ?><div class="ui-alert ui-alert-success"><?= e($flashSuccess); ?></div><?php endif; ?>
<?php if ($flashError): ?><div class="ui-alert ui-alert-danger"><?= e($flashError); ?></div><?php endif; ?>

<div class="ui-kpi-grid">
    <div class="ui-kpi"><span>Total</span><strong><?= portal_count($pdo,"SELECT COUNT(*) FROM courses"); ?></strong><small>Cursos registados.</small></div>
    <div class="ui-kpi is-good"><span>Ativos</span><strong><?= portal_count($pdo,"SELECT COUNT(*) FROM courses WHERE status='active'"); ?></strong><small>Disponíveis.</small></div>
    <div class="ui-kpi is-info"><span>Licenciaturas</span><strong><?= portal_count($pdo,"SELECT COUNT(*) FROM courses WHERE level='licenciatura'"); ?></strong><small>1.º ciclo.</small></div>
    <div class="ui-kpi is-warn"><span>Mestrados</span><strong><?= portal_count($pdo,"SELECT COUNT(*) FROM courses WHERE level='mestrado'"); ?></strong><small>2.º ciclo.</small></div>
</div>

<?php if ($canManage): ?>
<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2><?= $editing ? 'Editar curso' : 'Criar novo curso'; ?></h2>
            <p>Use esta área quando a instituição abrir uma nova oferta formativa ou precisar corrigir dados do curso.</p>
        </div>
    </div>
    <div class="ui-panel-body">
        <form method="POST" action="<?= e(APP_URL); ?>/actions/salvar_curso.php" class="ui-form-grid" style="grid-template-columns:160px minmax(260px,1fr) 220px 140px 160px;align-items:end;">
            <input type="hidden" name="course_id" value="<?= (int)($editing['id'] ?? 0); ?>">
            <div class="ui-field"><label>Código</label><input class="ui-control" name="code" required value="<?= e($editing['code'] ?? ''); ?>" placeholder="Ex.: LIC-ESI"></div>
            <div class="ui-field"><label>Nome do curso</label><input class="ui-control" name="name" required value="<?= e($editing['name'] ?? ''); ?>" placeholder="Ex.: Engenharia de Sistemas e Informática"></div>
            <div class="ui-field"><label>Nível</label><select class="ui-control" name="level"><?php foreach ($levelLabels as $key=>$label): ?><option value="<?= e($key); ?>" <?= ($editing['level'] ?? 'licenciatura') === $key ? 'selected' : ''; ?>><?= e($label); ?></option><?php endforeach; ?></select></div>
            <div class="ui-field"><label>Duração</label><input class="ui-control" type="number" min="1" max="10" name="duration_years" value="<?= e((string)($editing['duration_years'] ?? '')); ?>" placeholder="Anos"></div>
            <div class="ui-field"><label>Estado</label><select class="ui-control" name="status"><option value="active" <?= ($editing['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Ativo</option><option value="inactive" <?= ($editing['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inativo</option></select></div>
            <div class="ui-field" style="grid-column:1/-2;"><label>Descrição / observação</label><textarea class="ui-control" name="description" rows="3" placeholder="Resumo curto do curso, quando necessário."><?= e($editing['description'] ?? ''); ?></textarea></div>
            <div style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;"><button class="ui-btn ui-btn-primary"><?= $editing ? 'Guardar curso' : 'Criar curso'; ?></button><?php if ($editing): ?><a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/cursos.php">Cancelar</a><?php endif; ?></div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="ui-panel">
    <div class="ui-panel-header"><div><h2>Oferta formativa</h2><p>Filtre por nível, estado, código ou nome do curso.</p></div></div>
    <div class="ui-panel-body" style="border-bottom:1px solid var(--ui-divider);">
        <form method="GET" class="ui-form-grid" style="grid-template-columns:minmax(260px,1fr) 220px 180px auto auto;align-items:end;">
            <div class="ui-field"><label>Pesquisar</label><input class="ui-control" name="q" value="<?= e($q); ?>" placeholder="Código ou nome do curso"></div>
            <div class="ui-field"><label>Nível</label><select class="ui-control" name="nivel"><option value="">Todos os níveis</option><?php foreach($levelLabels as $key=>$label): ?><option value="<?= e($key); ?>" <?= $level===$key?'selected':''; ?>><?= e($label); ?></option><?php endforeach; ?></select></div>
            <div class="ui-field"><label>Estado</label><select class="ui-control" name="estado"><option value="">Todos</option><option value="active" <?= $status==='active'?'selected':''; ?>>Ativo</option><option value="inactive" <?= $status==='inactive'?'selected':''; ?>>Inativo</option></select></div>
            <button class="ui-btn ui-btn-primary">Pesquisar</button><a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/cursos.php">Limpar</a>
        </form>
    </div>
    <div class="ui-panel-body" style="display:grid;gap:24px;">
        <?php if(empty($courses)): ?><div class="ui-empty">Nenhum curso encontrado.</div><?php endif; ?>
        <?php foreach($grouped as $lvl=>$items): ?>
            <section>
                <h3 style="margin:0 0 12px;color:var(--ui-brown-900);"><?= e($levelLabels[$lvl] ?? 'Outros cursos'); ?> <small style="color:var(--ui-muted);font-size:14px;">· <?= count($items); ?> curso(s)</small></h3>
                <div class="ui-table-wrap"><table class="ui-table"><thead><tr><th>Código</th><th>Nome</th><th>Duração</th><th>Alunos</th><th>Estado</th><?php if($canManage): ?><th>Ações</th><?php endif; ?></tr></thead><tbody><?php foreach($items as $c): ?><tr><td><?= e($c['code']??'-'); ?></td><td><strong><?= e($c['name']??'-'); ?></strong><small><?= e($levelLabels[$c['level'] ?? ''] ?? ''); ?></small></td><td><?= e((string)($c['duration_years']??'-')); ?> ano(s)</td><td><?= (int)($c['students_total']??0); ?></td><td><span class="ui-badge <?= e(portal_badge_class($c['status']??'')); ?>"><?= e(portal_label_status($c['status']??'')); ?></span></td><?php if($canManage): ?><td><a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/cursos.php?editar=<?= (int)$c['id']; ?>">Editar</a></td><?php endif; ?></tr><?php endforeach; ?></tbody></table></div>
            </section>
        <?php endforeach; ?>
    </div>
</div>
<?php portal_layout_end(); ?>
