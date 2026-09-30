<?php

require_once __DIR__ . '/../includes/portal_layout.php';

require_any_role(['admin', 'secretaria', 'funcionario', 'coordenador', 'direcao']);

$user = current_user();
$role = portal_user_role($user);
[$success, $error] = portal_flash();

$tableReady = portal_db_table_exists($pdo, 'schedules');
$canManage = in_array($role, ['admin', 'secretaria', 'funcionario'], true);
$canValidate = in_array($role, ['admin', 'coordenador', 'direcao'], true);

$weekdays = [
    'segunda' => 'Segunda-feira',
    'terca' => 'Terça-feira',
    'quarta' => 'Quarta-feira',
    'quinta' => 'Quinta-feira',
    'sexta' => 'Sexta-feira',
    'sabado' => 'Sábado',
    'domingo' => 'Domingo',
];

$academicYears = portal_db_table_exists($pdo, 'academic_years')
    ? portal_rows($pdo, "SELECT id, name FROM academic_years ORDER BY name DESC")
    : [];

$courses = portal_db_table_exists($pdo, 'courses')
    ? portal_rows($pdo, "SELECT id, code, name FROM courses WHERE status = 'active' ORDER BY name ASC")
    : [];

$classes = portal_db_table_exists($pdo, 'academic_classes')
    ? portal_rows($pdo, "SELECT academic_classes.*, courses.code AS course_code FROM academic_classes LEFT JOIN courses ON courses.id = academic_classes.course_id ORDER BY academic_classes.name ASC")
    : [];

$subjects = portal_db_table_exists($pdo, 'subjects')
    ? portal_rows($pdo, "SELECT subjects.*, courses.code AS course_code FROM subjects LEFT JOIN courses ON courses.id = subjects.course_id WHERE subjects.status = 'active' ORDER BY subjects.name ASC")
    : [];

$teachers = portal_db_table_exists($pdo, 'users') && portal_db_table_exists($pdo, 'roles') && portal_db_table_exists($pdo, 'user_roles')
    ? portal_rows($pdo, "
        SELECT DISTINCT users.id, users.full_name, users.institutional_id
        FROM users
        INNER JOIN user_roles ON user_roles.user_id = users.id
        INNER JOIN roles ON roles.id = user_roles.role_id
        WHERE roles.code IN ('professor','coordenador') AND users.status = 'active'
        ORDER BY users.full_name ASC
    ")
    : [];

$schedules = [];

if ($tableReady) {
    $where = '';
    $params = [];

    if ($role === 'coordenador' && portal_db_table_exists($pdo, 'course_coordinators')) {
        $where = "WHERE (course_coordinators.user_id = ? OR schedules.status IN ('published','validated'))";
        $params[] = (int) $user['id'];
    }

    $schedules = portal_rows($pdo, "
        SELECT
            schedules.*,
            academic_years.name AS academic_year_name,
            subjects.code AS subject_code,
            subjects.name AS subject_name,
            academic_classes.name AS class_name,
            courses.code AS course_code,
            courses.name AS course_name,
            teachers.full_name AS teacher_name,
            validators.full_name AS validator_name
        FROM schedules
        LEFT JOIN academic_years ON academic_years.id = schedules.academic_year_id
        LEFT JOIN subjects ON subjects.id = schedules.subject_id
        LEFT JOIN academic_classes ON academic_classes.id = schedules.class_id
        LEFT JOIN courses ON courses.id = COALESCE(schedules.course_id, academic_classes.course_id)
        LEFT JOIN course_coordinators ON course_coordinators.course_id = courses.id
        LEFT JOIN users teachers ON teachers.id = schedules.teacher_user_id
        LEFT JOIN users validators ON validators.id = schedules.validated_by
        {$where}
        ORDER BY FIELD(schedules.weekday, 'segunda','terca','quarta','quinta','sexta','sabado','domingo'), schedules.start_time ASC, schedules.id DESC
        LIMIT 250
    ", $params);
}

portal_layout_start('gerir_horarios', 'Gerir horários', 'Cadastro, publicação e validação dos horários académicos.', $role);

?>

<?php if ($success): ?><div class="ui-alert ui-alert-success"><?= e($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="ui-alert ui-alert-error"><?= e($error); ?></div><?php endif; ?>

<div class="ui-kpi-grid">
    <div class="ui-kpi"><span>Total</span><strong><?= count($schedules); ?></strong><small>Aulas listadas.</small></div>
    <div class="ui-kpi is-warn"><span>Rascunhos</span><strong><?= $tableReady ? portal_count($pdo, "SELECT COUNT(*) FROM schedules WHERE status = 'draft'") : 0; ?></strong><small>Aguardam publicação.</small></div>
    <div class="ui-kpi is-info"><span>Publicados</span><strong><?= $tableReady ? portal_count($pdo, "SELECT COUNT(*) FROM schedules WHERE status = 'published'") : 0; ?></strong><small>Aguardam validação.</small></div>
    <div class="ui-kpi is-good"><span>Validados</span><strong><?= $tableReady ? portal_count($pdo, "SELECT COUNT(*) FROM schedules WHERE status = 'validated'") : 0; ?></strong><small>Confirmados.</small></div>
</div>

<?php if (!$tableReady): ?>
    <div class="ui-panel">
        <div class="ui-panel-header">
            <div>
                <h2>Tabela de horários ainda não instalada</h2>
                <p>Execute o ficheiro SQL antes de lançar horários no sistema.</p>
            </div>
        </div>
        <div class="ui-panel-body">
            <div class="ui-section-note">
                Abra o phpMyAdmin, escolha a base de dados do portal e execute:
                <strong>database/2026_05_19_schedules.sql</strong>.
            </div>
        </div>
    </div>
<?php else: ?>

<?php if ($canManage): ?>
<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2>Novo horário</h2>
            <p>Registe a aula por turma, disciplina, professor, dia, hora e sala. O registo fica como rascunho até ser publicado.</p>
        </div>
    </div>

    <div class="ui-panel-body">
        <form action="<?= e(APP_URL); ?>/actions/salvar_horario.php" method="POST" class="ui-form-grid">
            <input type="hidden" name="acao" value="guardar">

            <div class="ui-field">
                <label>Ano letivo</label>
                <select class="ui-control" name="academic_year_id">
                    <option value="">Selecionar ano letivo</option>
                    <?php foreach ($academicYears as $year): ?>
                        <option value="<?= (int) $year['id']; ?>"><?= e($year['name'] ?? '-'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ui-field">
                <label>Curso</label>
                <select class="ui-control" name="course_id">
                    <option value="">Selecionar curso</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= (int) $course['id']; ?>"><?= e(($course['code'] ?? '-') . ' — ' . ($course['name'] ?? '-')); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ui-field">
                <label>Turma *</label>
                <select class="ui-control" name="class_id" required>
                    <option value="">Selecionar turma</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= (int) $class['id']; ?>"><?= e(($class['course_code'] ?? '-') . ' — ' . ($class['name'] ?? '-')); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ui-field">
                <label>Disciplina *</label>
                <select class="ui-control" name="subject_id" required>
                    <option value="">Selecionar disciplina</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= (int) $subject['id']; ?>"><?= e(($subject['course_code'] ?? '-') . ' — ' . ($subject['code'] ?? '-') . ' — ' . ($subject['name'] ?? '-')); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ui-field">
                <label>Professor</label>
                <select class="ui-control" name="teacher_user_id">
                    <option value="">Selecionar professor</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= (int) $teacher['id']; ?>"><?= e(($teacher['full_name'] ?? '-') . ' — ' . ($teacher['institutional_id'] ?? '')); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ui-field">
                <label>Dia *</label>
                <select class="ui-control" name="weekday" required>
                    <option value="">Selecionar dia</option>
                    <?php foreach ($weekdays as $value => $label): ?>
                        <option value="<?= e($value); ?>"><?= e($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ui-field">
                <label>Início *</label>
                <input class="ui-control" type="time" name="start_time" required>
            </div>

            <div class="ui-field">
                <label>Fim *</label>
                <input class="ui-control" type="time" name="end_time" required>
            </div>

            <div class="ui-field">
                <label>Sala</label>
                <input class="ui-control" type="text" name="room" placeholder="Ex.: Sala 12, Laboratório 2">
            </div>

            <div class="ui-field">
                <label>Observação</label>
                <input class="ui-control" type="text" name="notes" placeholder="Ex.: aula prática, aula teórica, alternância semanal">
            </div>

            <div style="grid-column:1/-1;">
                <button class="ui-btn ui-btn-primary" type="submit">Guardar rascunho</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2>Horários registados</h2>
            <p><?= count($schedules); ?> aula(s) encontrada(s). Secretaria publica; coordenação ou direção valida.</p>
        </div>
    </div>

    <div class="ui-table-wrap">
        <table class="ui-table">
            <thead>
                <tr>
                    <th>Dia</th>
                    <th>Hora</th>
                    <th>Turma / Curso</th>
                    <th>Disciplina</th>
                    <th>Professor</th>
                    <th>Sala</th>
                    <th>Estado</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($schedules)): ?>
                    <tr><td colspan="8" class="ui-empty">Ainda não existem horários registados.</td></tr>
                <?php endif; ?>

                <?php foreach ($schedules as $row): ?>
                    <tr>
                        <td><strong><?= e($weekdays[$row['weekday'] ?? ''] ?? ucfirst((string) ($row['weekday'] ?? '-'))); ?></strong></td>
                        <td><?= e(substr((string) ($row['start_time'] ?? '-'), 0, 5)); ?> - <?= e(substr((string) ($row['end_time'] ?? '-'), 0, 5)); ?></td>
                        <td><strong><?= e($row['class_name'] ?? '-'); ?></strong><small><?= e(($row['course_code'] ?? '-') . ' — ' . ($row['course_name'] ?? '-')); ?></small></td>
                        <td><strong><?= e($row['subject_code'] ?? '-'); ?></strong><small><?= e($row['subject_name'] ?? '-'); ?></small></td>
                        <td><?= e($row['teacher_name'] ?? '-'); ?></td>
                        <td><?= e($row['room'] ?? '-'); ?></td>
                        <td>
                            <span class="ui-badge <?= e(portal_badge_class($row['status'] ?? 'draft')); ?>">
                                <?= e(portal_label_status($row['status'] ?? 'draft')); ?>
                            </span>
                            <?php if (!empty($row['validator_name'])): ?>
                                <small>Validado por <?= e($row['validator_name']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <?php if ($canManage && ($row['status'] ?? '') === 'draft'): ?>
                                    <form action="<?= e(APP_URL); ?>/actions/salvar_horario.php" method="POST">
                                        <input type="hidden" name="schedule_id" value="<?= (int) $row['id']; ?>">
                                        <button class="ui-btn ui-btn-ghost" name="acao" value="publicar">Publicar</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($canValidate && in_array(($row['status'] ?? ''), ['published', 'draft'], true)): ?>
                                    <form action="<?= e(APP_URL); ?>/actions/salvar_horario.php" method="POST">
                                        <input type="hidden" name="schedule_id" value="<?= (int) $row['id']; ?>">
                                        <button class="ui-btn ui-btn-secondary" name="acao" value="validar">Validar</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($canManage && ($row['status'] ?? '') !== 'cancelled'): ?>
                                    <form action="<?= e(APP_URL); ?>/actions/salvar_horario.php" method="POST">
                                        <input type="hidden" name="schedule_id" value="<?= (int) $row['id']; ?>">
                                        <button class="ui-btn ui-btn-ghost" name="acao" value="cancelar">Cancelar</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($canManage): ?>
                                    <form action="<?= e(APP_URL); ?>/actions/salvar_horario.php" method="POST" onsubmit="return confirm('Apagar este horário?');">
                                        <input type="hidden" name="schedule_id" value="<?= (int) $row['id']; ?>">
                                        <button class="ui-btn ui-btn-danger" name="acao" value="apagar">Apagar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2>Regra de negócio aplicada</h2>
            <p>Modelo simples para uma plataforma de gestão escolar.</p>
        </div>
    </div>
    <div class="ui-panel-body">
        <ol class="pub-flow" style="margin:0;">
            <li>A coordenação define a organização pedagógica do horário do curso.</li>
            <li>A secretaria ou funcionário autorizado lança o horário no sistema.</li>
            <li>A secretaria publica o horário quando estiver pronto para revisão.</li>
            <li>Coordenação ou direção valida o horário final.</li>
            <li>Alunos e professores consultam apenas os horários publicados ou validados.</li>
        </ol>
    </div>
</div>

<?php endif; ?>

<?php portal_layout_end(); ?>
