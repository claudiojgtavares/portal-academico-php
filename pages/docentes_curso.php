<?php
require_once __DIR__ . '/../includes/portal_layout.php';

require_any_role(['admin', 'secretaria', 'coordenador', 'direcao']);

$user = current_user();
$role = portal_user_role($user);
$uid = (int)($user['id'] ?? 0);
$canEdit = in_array($role, ['admin', 'secretaria'], true);
[$success, $error] = portal_flash();

$q = trim($_GET['q'] ?? '');
$courseFilter = (int)($_GET['course_id'] ?? 0);
$functionFilter = trim($_GET['funcao'] ?? '');

$courses = portal_rows($pdo, "SELECT id, code, name, level FROM courses WHERE status='active' ORDER BY level ASC, name ASC");
$years = portal_db_table_exists($pdo, 'academic_years') ? portal_rows($pdo, "SELECT id, name FROM academic_years ORDER BY name DESC") : [];

$docents = portal_rows($pdo, "
    SELECT DISTINCT users.id, users.full_name, users.institutional_id, GROUP_CONCAT(roles.code ORDER BY roles.code SEPARATOR ',') AS role_codes
    FROM users
    INNER JOIN user_roles ON user_roles.user_id = users.id
    INNER JOIN roles ON roles.id = user_roles.role_id
    WHERE roles.code IN ('professor','coordenador') AND users.status = 'active'
    GROUP BY users.id
    ORDER BY users.full_name ASC
");

$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(users.full_name LIKE ? OR users.institutional_id LIKE ? OR courses.code LIKE ? OR courses.name LIKE ?)";
    $like = '%' . $q . '%';
    $params = [$like, $like, $like, $like];
}
if ($courseFilter > 0) { $where[] = 'course_staff.course_id = ?'; $params[] = $courseFilter; }
if ($functionFilter !== '') { $where[] = 'course_staff.function_role = ?'; $params[] = $functionFilter; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$rows = portal_db_table_exists($pdo, 'course_staff') ? portal_rows($pdo, "
    SELECT course_staff.*, users.full_name, users.institutional_id, courses.code AS course_code, courses.name AS course_name, academic_years.name AS academic_year
    FROM course_staff
    LEFT JOIN users ON users.id = course_staff.user_id
    LEFT JOIN courses ON courses.id = course_staff.course_id
    LEFT JOIN academic_years ON academic_years.id = course_staff.academic_year_id
    {$whereSql}
    ORDER BY courses.name ASC, course_staff.function_role ASC, users.full_name ASC
    LIMIT 300
", $params) : [];

$activeKey = $role === 'coordenador' ? 'docentes_curso' : 'docentes_curso';
portal_layout_start($activeKey, 'Docentes por curso', 'Associação de professores e coordenadores aos cursos.', $role);
?>

<?php if ($success): ?><div class="ui-alert ui-alert-success"><?= e($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="ui-alert ui-alert-error"><?= e($error); ?></div><?php endif; ?>

<div class="ui-section-note">
    Regra de negócio: primeiro o Admin define o perfil do utilizador; depois a Secretaria/Admin associa esse docente ao curso;
    por fim, a Secretaria/Admin atribui disciplina + turma para permitir notas, faltas e horários.
</div>

<?php if (!portal_db_table_exists($pdo, 'course_staff')): ?>
    <div class="ui-section-note">Execute o SQL <strong>database/2026_05_20_academic_structure.sql</strong> para ativar esta área.</div>
<?php endif; ?>

<?php if ($canEdit): ?>
<div class="ui-panel">
    <div class="ui-panel-header"><div><h2>Associar docente ao curso</h2><p>Use esta área para indicar quem pertence ao curso e quem coordena o curso.</p></div></div>
    <div class="ui-panel-body">
        <form method="POST" action="<?= e(APP_URL); ?>/actions/salvar_docente_curso.php" class="ui-form-grid" style="grid-template-columns:1fr 1fr 220px 180px 160px auto;align-items:end;">
            <input type="hidden" name="acao" value="criar">
            <div class="ui-field"><label>Docente / Coordenador</label><select class="ui-control" name="user_id" required><option value="">Selecionar</option><?php foreach($docents as $d): ?><option value="<?= (int)$d['id']; ?>"><?= e(($d['full_name']??'-').' · '.($d['institutional_id']??'-').' · '.($d['role_codes']??'')); ?></option><?php endforeach; ?></select></div>
            <div class="ui-field"><label>Curso</label><select class="ui-control" name="course_id" required><option value="">Selecionar curso</option><?php foreach($courses as $c): ?><option value="<?= (int)$c['id']; ?>"><?= e(($c['code']??'-').' — '.($c['name']??'-')); ?></option><?php endforeach; ?></select></div>
            <div class="ui-field"><label>Ano letivo</label><select class="ui-control" name="academic_year_id"><option value="0">Selecionar</option><?php foreach($years as $y): ?><option value="<?= (int)$y['id']; ?>"><?= e($y['name']??'-'); ?></option><?php endforeach; ?></select></div>
            <div class="ui-field"><label>Função no curso</label><select class="ui-control" name="function_role"><option value="professor">Professor</option><option value="coordenador">Coordenador</option><option value="apoio">Apoio académico</option></select></div>
            <div class="ui-field"><label>Estado</label><select class="ui-control" name="status"><option value="active">Ativo</option><option value="inactive">Inativo</option></select></div>
            <button class="ui-btn ui-btn-primary">Associar</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="ui-panel">
    <div class="ui-panel-header"><div><h2>Equipa docente por curso</h2><p>Filtre por curso, função ou nome.</p></div></div>
    <div class="ui-panel-body" style="border-bottom:1px solid var(--ui-divider);">
        <form method="GET" class="ui-form-grid" style="grid-template-columns:minmax(260px,1fr) 260px 220px auto auto;align-items:end;">
            <div class="ui-field"><label>Pesquisar</label><input class="ui-control" name="q" value="<?= e($q); ?>" placeholder="Nome, ID ou curso"></div>
            <div class="ui-field"><label>Curso</label><select class="ui-control" name="course_id"><option value="0">Todos os cursos</option><?php foreach($courses as $c): ?><option value="<?= (int)$c['id']; ?>" <?= $courseFilter===(int)$c['id']?'selected':''; ?>><?= e(($c['code']??'-').' — '.($c['name']??'-')); ?></option><?php endforeach; ?></select></div>
            <div class="ui-field"><label>Função</label><select class="ui-control" name="funcao"><option value="">Todas</option><option value="professor" <?= $functionFilter==='professor'?'selected':''; ?>>Professor</option><option value="coordenador" <?= $functionFilter==='coordenador'?'selected':''; ?>>Coordenador</option><option value="apoio" <?= $functionFilter==='apoio'?'selected':''; ?>>Apoio académico</option></select></div>
            <button class="ui-btn ui-btn-primary">Pesquisar</button><a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/docentes_curso.php">Limpar</a>
        </form>
    </div>
    <div class="ui-table-wrap">
        <table class="ui-table">
            <thead><tr><th>Docente</th><th>Curso</th><th>Função</th><th>Ano letivo</th><th>Estado</th><th>Ações</th></tr></thead>
            <tbody>
                <?php if(empty($rows)): ?><tr><td colspan="6" class="ui-empty">Nenhuma associação encontrada.</td></tr><?php endif; ?>
                <?php foreach($rows as $row): ?>
                    <tr>
                        <td><div class="ui-person"><div class="ui-person-avatar"><?= e(portal_initials_safe($row['full_name'] ?? '')); ?></div><div><strong><?= e($row['full_name'] ?? '-'); ?></strong><small><?= e($row['institutional_id'] ?? '-'); ?></small></div></div></td>
                        <td><strong><?= e($row['course_code'] ?? '-'); ?></strong><small><?= e($row['course_name'] ?? '-'); ?></small></td>
                        <td><?= e(portal_label_status($row['function_role'] ?? '-')); ?></td>
                        <td><?= e($row['academic_year'] ?? '-'); ?></td>
                        <td><span class="ui-badge <?= e(portal_badge_class($row['status'] ?? '')); ?>"><?= e(portal_label_status($row['status'] ?? '')); ?></span></td>
                        <td><?php if($canEdit): ?><form method="POST" action="<?= e(APP_URL); ?>/actions/salvar_docente_curso.php" class="ui-actions"><input type="hidden" name="course_staff_id" value="<?= (int)$row['id']; ?>"><button class="ui-btn <?= ($row['status'] ?? '')==='active'?'ui-btn-danger':'ui-btn-secondary'; ?>" name="acao" value="<?= ($row['status'] ?? '')==='active'?'inativar':'ativar'; ?>"><?= ($row['status'] ?? '')==='active'?'Inativar':'Ativar'; ?></button></form><?php else: ?>-<?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php portal_layout_end(); ?>
