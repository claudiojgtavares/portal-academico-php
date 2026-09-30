<?php

require_once __DIR__ . '/../includes/portal_layout.php';

require_login();

$user = current_user();
$role = portal_user_role($user);
$student = portal_one($pdo, "SELECT * FROM students WHERE user_id = ? LIMIT 1", [(int) $user['id']]);

$tableReady = portal_db_table_exists($pdo, 'schedules');
$rows = [];

$weekdays = [
    'segunda' => 'Segunda',
    'terca' => 'Terça',
    'quarta' => 'Quarta',
    'quinta' => 'Quinta',
    'sexta' => 'Sexta',
    'sabado' => 'Sábado',
];

function horario_time_slots(string $start = '07:30', string $end = '21:30', int $stepMinutes = 60): array
{
    $slots = [];
    $current = strtotime($start);
    $limit = strtotime($end);

    while ($current <= $limit) {
        $slots[] = date('H:i', $current);
        $current = strtotime('+' . $stepMinutes . ' minutes', $current);
    }

    return $slots;
}

function horario_status_label(?string $status): string
{
    return [
        'draft' => 'Rascunho',
        'published' => 'Publicado',
        'validated' => 'Validado',
        'cancelled' => 'Cancelado',
    ][$status ?? ''] ?? 'Publicado';
}

if ($tableReady) {
    $where = "WHERE schedules.status IN ('published','validated')";
    $params = [];

    if ($role === 'aluno' && $student) {
        $where .= " AND EXISTS (
            SELECT 1
            FROM class_students
            WHERE class_students.class_id = schedules.class_id
              AND class_students.student_id = ?
              AND class_students.status = 'active'
        )";
        $params[] = (int) $student['id'];
    } elseif ($role === 'professor') {
        $where .= " AND schedules.teacher_user_id = ?";
        $params[] = (int) $user['id'];
    } elseif ($role === 'coordenador' && portal_db_table_exists($pdo, 'course_coordinators')) {
        $where .= " AND EXISTS (
            SELECT 1
            FROM course_coordinators
            WHERE course_coordinators.course_id = COALESCE(schedules.course_id, academic_classes.course_id)
              AND course_coordinators.user_id = ?
        )";
        $params[] = (int) $user['id'];
    } elseif (!in_array($role, ['admin', 'secretaria', 'direcao', 'funcionario'], true)) {
        $where .= " AND 1 = 0";
    }

    $rows = portal_rows($pdo, "
        SELECT
            schedules.*,
            subjects.code AS subject_code,
            subjects.name AS subject_name,
            academic_classes.name AS class_name,
            courses.code AS course_code,
            courses.name AS course_name,
            users.full_name AS teacher_name,
            users.institutional_id AS teacher_code
        FROM schedules
        LEFT JOIN subjects ON subjects.id = schedules.subject_id
        LEFT JOIN academic_classes ON academic_classes.id = schedules.class_id
        LEFT JOIN courses ON courses.id = COALESCE(schedules.course_id, academic_classes.course_id)
        LEFT JOIN users ON users.id = schedules.teacher_user_id
        {$where}
        ORDER BY FIELD(schedules.weekday, 'segunda','terca','quarta','quinta','sexta','sabado','domingo'), schedules.start_time ASC
        LIMIT 250
    ", $params);
}

$slots = horario_time_slots();
$eventsByDaySlot = [];
$subjectsLegend = [];
$teachersLegend = [];

foreach ($rows as $row) {
    $day = (string) ($row['weekday'] ?? '');
    $slot = substr((string) ($row['start_time'] ?? ''), 0, 5);

    if (!isset($eventsByDaySlot[$day])) {
        $eventsByDaySlot[$day] = [];
    }

    if (!isset($eventsByDaySlot[$day][$slot])) {
        $eventsByDaySlot[$day][$slot] = [];
    }

    $eventsByDaySlot[$day][$slot][] = $row;

    $subjectCode = (string) ($row['subject_code'] ?? '');
    if ($subjectCode !== '') {
        $subjectsLegend[$subjectCode] = (string) ($row['subject_name'] ?? '');
    }

    $teacherName = trim((string) ($row['teacher_name'] ?? ''));
    if ($teacherName !== '') {
        $initials = portal_initials_safe($teacherName);
        $teachersLegend[$initials] = $teacherName;
    }
}

portal_layout_start('horarios', 'Horários', 'Grelha semanal de aulas por dia, hora, disciplina, sala e docente.', $role);

?>

<style>
    .schedule-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .schedule-print-card {
        background: #fff;
        border: 1px solid var(--ui-border);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: var(--ui-shadow);
    }

    .schedule-table-wrap {
        overflow: auto;
        padding: 18px;
    }

    .schedule-grid-table {
        width: 100%;
        min-width: 920px;
        border-collapse: collapse;
        table-layout: fixed;
        background: #fff;
    }

    .schedule-grid-table th,
    .schedule-grid-table td {
        border: 1px solid #1f2937;
        vertical-align: top;
    }

    .schedule-grid-table th {
        height: 34px;
        background: #f8fafc;
        color: #111827;
        font-size: 13px;
        text-align: center;
        font-weight: 950;
    }

    .schedule-time {
        width: 74px;
        background: #fff;
        color: #111827;
        font-weight: 950;
        text-align: right;
        padding: 8px 9px;
        font-size: 13px;
    }

    .schedule-cell {
        height: 54px;
        padding: 6px;
        background: #fff;
    }

    .schedule-event {
        display: grid;
        gap: 3px;
        border-radius: 10px;
        background: #fff7e5;
        border: 1px solid #e0b64f;
        color: #301706;
        padding: 8px;
        font-size: 12px;
        line-height: 1.25;
        text-align: center;
        min-height: 42px;
    }

    .schedule-event + .schedule-event {
        margin-top: 5px;
    }

    .schedule-event strong {
        color: #111827;
        font-size: 12px;
        font-weight: 950;
    }

    .schedule-event span {
        color: #4b5563;
        font-weight: 850;
    }

    .schedule-legends {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        padding: 18px;
        border-top: 1px solid var(--ui-divider);
        background: #fafafa;
    }

    .schedule-legend h3 {
        margin: 0 0 10px;
        color: var(--ui-heading);
        font-size: 1rem;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .schedule-legend table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
    }

    .schedule-legend th,
    .schedule-legend td {
        border: 1px solid #1f2937;
        padding: 7px 9px;
        font-size: 13px;
        text-align: left;
    }

    .schedule-empty-explain {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(260px, 360px);
        gap: 18px;
        padding: 22px;
    }

    @media (max-width: 900px) {
        .schedule-legends,
        .schedule-empty-explain {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="ui-kpi-grid">
    <div class="ui-kpi is-info"><span>Aulas visíveis</span><strong><?= count($rows); ?></strong><small>Publicadas ou validadas.</small></div>
    <div class="ui-kpi"><span>Turmas</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM academic_classes WHERE status = 'active'"); ?></strong><small>Turmas ativas.</small></div>
    <div class="ui-kpi"><span>Disciplinas</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM subjects WHERE status = 'active'"); ?></strong><small>Unidades curriculares.</small></div>
    <div class="ui-kpi is-warn"><span>Professores</span><strong><?= portal_count($pdo, "SELECT COUNT(DISTINCT teacher_user_id) FROM teacher_subjects WHERE status = 'active'"); ?></strong><small>Com atribuição.</small></div>
</div>

<div class="schedule-toolbar">
    <div>
        <h2 style="margin:0;color:var(--ui-heading);">Horário semanal</h2>
        <p style="margin:5px 0 0;color:var(--ui-muted);">Modelo visual semelhante ao horário impresso: dias na horizontal, horas na vertical e legenda no fim.</p>
    </div>

    <?php if (in_array($role, ['admin', 'secretaria', 'funcionario', 'coordenador', 'direcao'], true)): ?>
        <a class="ui-btn ui-btn-primary" href="<?= e(APP_URL); ?>/pages/gerir_horarios.php">Gerir horários</a>
    <?php endif; ?>
</div>

<?php if (!$tableReady): ?>
    <div class="ui-panel">
        <div class="ui-panel-header">
            <div>
                <h2>Módulo de horários por ativar</h2>
                <p>Execute o SQL para criar o cadastro de horários e começar a preencher a grelha.</p>
            </div>
        </div>
        <div class="schedule-empty-explain">
            <div>
                <div class="ui-section-note">
                    Execute no phpMyAdmin: <strong>database/2026_05_19_schedules.sql</strong>.
                </div>
                <h3>Como deve funcionar</h3>
                <ol class="pub-flow" style="margin:0;">
                    <li>Secretaria ou funcionário autorizado regista as aulas.</li>
                    <li>Coordenador confirma se o horário respeita o curso e os docentes.</li>
                    <li>Secretaria publica o horário para estudantes e professores.</li>
                    <li>Alunos e professores consultam a grelha final no portal.</li>
                </ol>
            </div>
            <div class="ui-card-row" style="grid-template-columns:1fr;">
                <h3 style="margin:0;">Campos do cadastro</h3>
                <p style="color:var(--ui-muted);margin:0;">Curso, turma, disciplina, professor, dia da semana, hora de início, hora de fim, sala e observação.</p>
            </div>
        </div>
    </div>
<?php elseif (empty($rows)): ?>
    <div class="ui-panel">
        <div class="ui-panel-header">
            <div>
                <h2>Ainda não há horários publicados</h2>
                <p>A grelha fica preenchida automaticamente quando a secretaria publicar aulas.</p>
            </div>
        </div>
        <div class="schedule-empty-explain">
            <div>
                <div class="ui-section-note">
                    O cadastro já existe, mas este perfil ainda não tem aulas publicadas ou validadas.
                </div>
                <ol class="pub-flow" style="margin:0;">
                    <li>Registar aula em <strong>Gerir horários</strong>.</li>
                    <li>Guardar como rascunho.</li>
                    <li>Publicar para revisão.</li>
                    <li>Validar pela coordenação ou direção.</li>
                </ol>
            </div>
            <?php if (in_array($role, ['admin', 'secretaria', 'funcionario'], true)): ?>
                <div class="ui-card-row" style="grid-template-columns:1fr;">
                    <h3 style="margin:0;">Próximo passo</h3>
                    <p style="color:var(--ui-muted);margin:0 0 12px;">Comece por lançar uma aula com curso, turma, disciplina, dia, hora e sala.</p>
                    <a class="ui-btn ui-btn-primary" href="<?= e(APP_URL); ?>/pages/gerir_horarios.php">Preencher horário</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="schedule-print-card">
        <div class="schedule-table-wrap">
            <table class="schedule-grid-table">
                <thead>
                    <tr>
                        <th style="width:74px;">Hora</th>
                        <?php foreach ($weekdays as $dayLabel): ?>
                            <th><?= e($dayLabel); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($slots as $slot): ?>
                        <tr>
                            <td class="schedule-time"><?= e($slot); ?></td>
                            <?php foreach ($weekdays as $dayKey => $dayLabel): ?>
                                <td class="schedule-cell">
                                    <?php foreach (($eventsByDaySlot[$dayKey][$slot] ?? []) as $event): ?>
                                        <?php $teacherInitials = !empty($event['teacher_name']) ? portal_initials_safe((string) $event['teacher_name']) : ''; ?>
                                        <div class="schedule-event">
                                            <strong><?= e(($event['subject_code'] ?? 'Aula') . ' ' . substr((string) ($event['start_time'] ?? ''), 0, 5) . '-' . substr((string) ($event['end_time'] ?? ''), 0, 5)); ?></strong>
                                            <span><?= e($event['room'] ?: 'Sala não definida'); ?></span>
                                            <?php if ($teacherInitials !== ''): ?><span>[<?= e($teacherInitials); ?>]</span><?php endif; ?>
                                            <small><?= e(horario_status_label($event['status'] ?? 'published')); ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="schedule-legends">
            <div class="schedule-legend">
                <h3>Disciplina</h3>
                <table>
                    <thead><tr><th>Código</th><th>Descrição</th></tr></thead>
                    <tbody>
                        <?php foreach ($subjectsLegend as $code => $name): ?>
                            <tr><td><?= e($code); ?></td><td><?= e($name); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="schedule-legend">
                <h3>Docente</h3>
                <table>
                    <thead><tr><th>Sigla</th><th>Nome</th></tr></thead>
                    <tbody>
                        <?php foreach ($teachersLegend as $sigla => $name): ?>
                            <tr><td><?= e($sigla); ?></td><td><?= e($name); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php portal_layout_end(); ?>
