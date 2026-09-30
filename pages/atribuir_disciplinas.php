<?php
require_once __DIR__ . '/../includes/portal_layout.php';

require_any_role(['admin', 'secretaria']);

$user = current_user();
$role = portal_user_role($user);
[$success, $error] = portal_flash();

$q = trim($_GET['q'] ?? '');
$teacherFilter = (int) ($_GET['teacher_user_id'] ?? 0);
$courseFilter = (int) ($_GET['course_id'] ?? 0);
$statusFilter = trim($_GET['status'] ?? 'active');

$teachers = portal_rows($pdo, "
    SELECT DISTINCT users.id, users.full_name, users.institutional_id, GROUP_CONCAT(roles.code ORDER BY roles.code SEPARATOR ',') AS role_codes
    FROM users
    INNER JOIN user_roles ON user_roles.user_id = users.id
    INNER JOIN roles ON roles.id = user_roles.role_id
    WHERE roles.code IN ('professor', 'coordenador')
      AND users.status = 'active'
    GROUP BY users.id
    ORDER BY users.full_name ASC
");

$courses = portal_rows($pdo, "SELECT id, code, name, level FROM courses WHERE status = 'active' ORDER BY level ASC, name ASC");
$classes = portal_rows($pdo, "
    SELECT academic_classes.id, academic_classes.name, academic_classes.course_id, courses.code AS course_code, courses.name AS course_name
    FROM academic_classes
    LEFT JOIN courses ON courses.id = academic_classes.course_id
    WHERE academic_classes.status = 'active'
    ORDER BY courses.name ASC, academic_classes.name ASC
");
$subjects = portal_rows($pdo, "
    SELECT subjects.id, subjects.code, subjects.name, subjects.course_id, courses.code AS course_code, courses.name AS course_name
    FROM subjects
    LEFT JOIN courses ON courses.id = subjects.course_id
    WHERE subjects.status = 'active'
    ORDER BY courses.name ASC, subjects.name ASC
");
$years = portal_rows($pdo, "SELECT id, name FROM academic_years ORDER BY name DESC");

$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(users.full_name LIKE ? OR users.institutional_id LIKE ? OR subjects.code LIKE ? OR subjects.name LIKE ? OR academic_classes.name LIKE ? OR courses.code LIKE ? OR courses.name LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like, $like, $like);
}
if ($teacherFilter > 0) { $where[] = 'teacher_subjects.teacher_user_id = ?'; $params[] = $teacherFilter; }
if ($courseFilter > 0) { $where[] = 'COALESCE(subjects.course_id, academic_classes.course_id) = ?'; $params[] = $courseFilter; }
if ($statusFilter !== '') { $where[] = 'teacher_subjects.status = ?'; $params[] = $statusFilter; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$assignments = portal_rows($pdo, "
    SELECT
        teacher_subjects.*,
        users.full_name AS teacher_name,
        users.institutional_id AS teacher_code,
        subjects.code AS subject_code,
        subjects.name AS subject_name,
        academic_classes.name AS class_name,
        courses.code AS course_code,
        courses.name AS course_name,
        academic_years.name AS academic_year
    FROM teacher_subjects
    LEFT JOIN users ON users.id = teacher_subjects.teacher_user_id
    LEFT JOIN subjects ON subjects.id = teacher_subjects.subject_id
    LEFT JOIN academic_classes ON academic_classes.id = teacher_subjects.class_id
    LEFT JOIN courses ON courses.id = COALESCE(subjects.course_id, academic_classes.course_id)
    LEFT JOIN academic_years ON academic_years.id = teacher_subjects.academic_year_id
    {$whereSql}
    ORDER BY teacher_subjects.status ASC, users.full_name ASC, courses.name ASC, subjects.name ASC
    LIMIT 300
", $params);

portal_layout_start('atribuicoes', 'Atribuir disciplinas', 'Associe professores e coordenadores às disciplinas e turmas que irão lecionar.', $role);
?>

<?php if ($success): ?><div class="ui-alert ui-alert-success"><?= e($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="ui-alert ui-alert-error"><?= e($error); ?></div><?php endif; ?>

<div class="ui-kpi-grid">
    <div class="ui-kpi"><span>Atribuições ativas</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM teacher_subjects WHERE status = 'active'"); ?></strong><small>Professor/coordenador-disciplina.</small></div>
    <div class="ui-kpi is-info"><span>Docentes</span><strong><?= count($teachers); ?></strong><small>Professores e coordenadores ativos.</small></div>
    <div class="ui-kpi is-good"><span>Disciplinas</span><strong><?= count($subjects); ?></strong><small>Unidades disponíveis.</small></div>
    <div class="ui-kpi is-warn"><span>Turmas</span><strong><?= count($classes); ?></strong><small>Turmas ativas.</small></div>
</div>

<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2>Nova atribuição</h2>
            <p>Regra de negócio: a secretaria académica faz a atribuição operacional; a coordenação valida a coerência académica do curso.</p>
        </div>
    </div>

    <div class="ui-panel-body">
        <form class="ui-form-grid" method="POST" action="<?= e(APP_URL); ?>/actions/salvar_atribuicao_disciplina.php">
            <input type="hidden" name="acao" value="criar">

            <div class="ui-field">
                <label>Professor / Coordenador</label>
                <select class="ui-control" name="teacher_user_id" required>
                    <option value="">Selecionar docente</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= (int) $teacher['id']; ?>"><?= e($teacher['full_name'] . ' — ' . $teacher['institutional_id'] . ' (' . str_replace(',', ', ', $teacher['role_codes']) . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ui-field">
                <label>Curso</label>
                <select class="ui-control" name="course_id" id="course_id_assign" required>
                    <option value="">Selecionar curso</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= (int) $course['id']; ?>"><?= e($course['code'] . ' — ' . $course['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ui-field">
                <label>Turma</label>
                <select class="ui-control" name="class_id" id="class_id_assign" required>
                    <option value="">Selecionar turma</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= (int) $class['id']; ?>" data-course="<?= (int) $class['course_id']; ?>"><?= e(($class['course_code'] ?? '-') . ' — ' . ($class['name'] ?? '-')); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ui-field">
                <label>Disciplina</label>
                <select class="ui-control" name="subject_id" id="subject_id_assign" required>
                    <option value="">Selecionar disciplina</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= (int) $subject['id']; ?>" data-course="<?= (int) $subject['course_id']; ?>"><?= e(($subject['course_code'] ?? '-') . ' · ' . ($subject['code'] ?? '-') . ' — ' . ($subject['name'] ?? '-')); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ui-field">
                <label>Ano letivo</label>
                <select class="ui-control" name="academic_year_id" required>
                    <?php foreach ($years as $year): ?>
                        <option value="<?= (int) $year['id']; ?>"><?= e($year['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ui-field">
                <label>Estado</label>
                <select class="ui-control" name="status">
                    <option value="active">Ativo</option>
                    <option value="inactive">Inativo</option>
                </select>
            </div>

            <div class="ui-actions" style="grid-column:1/-1;">
                <button class="ui-btn ui-btn-primary" type="submit">Guardar atribuição</button>
            </div>
        </form>
    </div>
</div>

<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2>Atribuições registadas</h2>
            <p>Pesquise por docente, disciplina, turma, curso ou estado.</p>
        </div>
    </div>

    <div class="ui-panel-body" style="border-bottom:1px solid var(--ui-divider);">
        <form method="GET" class="ui-form-grid" style="grid-template-columns:minmax(220px,1fr) 260px 260px 180px auto auto;align-items:end;">
            <div class="ui-field"><label>Pesquisar</label><input class="ui-control" name="q" value="<?= e($q); ?>" placeholder="Docente, disciplina, turma ou curso"></div>
            <div class="ui-field"><label>Docente</label><select class="ui-control" name="teacher_user_id"><option value="0">Todos</option><?php foreach ($teachers as $teacher): ?><option value="<?= (int) $teacher['id']; ?>" <?= $teacherFilter === (int) $teacher['id'] ? 'selected' : ''; ?>><?= e($teacher['full_name']); ?></option><?php endforeach; ?></select></div>
            <div class="ui-field"><label>Curso</label><select class="ui-control" name="course_id"><option value="0">Todos</option><?php foreach ($courses as $course): ?><option value="<?= (int) $course['id']; ?>" <?= $courseFilter === (int) $course['id'] ? 'selected' : ''; ?>><?= e($course['code'] . ' — ' . $course['name']); ?></option><?php endforeach; ?></select></div>
            <div class="ui-field"><label>Estado</label><select class="ui-control" name="status"><option value="">Todos</option><option value="active" <?= $statusFilter === 'active' ? 'selected' : ''; ?>>Ativo</option><option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inativo</option></select></div>
            <button class="ui-btn ui-btn-primary" type="submit">Pesquisar</button>
            <a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/atribuir_disciplinas.php">Limpar</a>
        </form>
    </div>

    <div class="ui-table-wrap">
        <table class="ui-table">
            <thead><tr><th>Docente</th><th>Disciplina</th><th>Curso / Turma</th><th>Ano letivo</th><th>Estado</th><th>Ações</th></tr></thead>
            <tbody>
                <?php if (empty($assignments)): ?><tr><td colspan="6" class="ui-empty">Nenhuma atribuição encontrada.</td></tr><?php endif; ?>
                <?php foreach ($assignments as $assignment): ?>
                    <tr>
                        <td><strong><?= e($assignment['teacher_name'] ?? '-'); ?></strong><small><?= e($assignment['teacher_code'] ?? '-'); ?></small></td>
                        <td><strong><?= e($assignment['subject_code'] ?? '-'); ?></strong><small><?= e($assignment['subject_name'] ?? '-'); ?></small></td>
                        <td><strong><?= e($assignment['course_code'] ?? '-'); ?></strong><small><?= e(($assignment['course_name'] ?? '-') . ' · ' . ($assignment['class_name'] ?? '-')); ?></small></td>
                        <td><?= e($assignment['academic_year'] ?? '-'); ?></td>
                        <td><span class="ui-badge <?= e(portal_badge_class($assignment['status'] ?? '')); ?>"><?= e(portal_label_status($assignment['status'] ?? '')); ?></span></td>
                        <td>
                            <form method="POST" action="<?= e(APP_URL); ?>/actions/salvar_atribuicao_disciplina.php" class="ui-actions" onsubmit="return confirm('Confirmar alteração da atribuição?');">
                                <input type="hidden" name="assignment_id" value="<?= (int) $assignment['id']; ?>">
                                <input type="hidden" name="acao" value="<?= ($assignment['status'] ?? '') === 'active' ? 'inativar' : 'ativar'; ?>">
                                <button class="ui-btn ui-btn-ghost" type="submit"><?= ($assignment['status'] ?? '') === 'active' ? 'Inativar' : 'Ativar'; ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function(){
  var course = document.getElementById('course_id_assign');
  var classSelect = document.getElementById('class_id_assign');
  var subjectSelect = document.getElementById('subject_id_assign');
  function filterOptions(select){
    if(!course || !select) return;
    var value = course.value;
    Array.prototype.forEach.call(select.options, function(opt){
      if(!opt.value){ opt.hidden = false; return; }
      opt.hidden = value && opt.getAttribute('data-course') !== value;
    });
    if(select.selectedOptions[0] && select.selectedOptions[0].hidden){ select.value = ''; }
  }
  if(course){
    course.addEventListener('change', function(){ filterOptions(classSelect); filterOptions(subjectSelect); });
    filterOptions(classSelect); filterOptions(subjectSelect);
  }
})();
</script>

<?php portal_layout_end(); ?>
