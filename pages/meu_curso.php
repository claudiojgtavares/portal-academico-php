<?php

require_once __DIR__ . '/../includes/portal_layout.php';

require_any_role(['coordenador', 'admin']);

$user = current_user();
$role = user_has_role('admin') ? 'admin' : 'coordenador';
$uid = (int) $user['id'];

$courses = portal_rows($pdo, "
    SELECT courses.*
    FROM course_coordinators
    INNER JOIN courses ON courses.id = course_coordinators.course_id
    WHERE course_coordinators.user_id = ? OR course_coordinators.coordinator_user_id = ?
    ORDER BY courses.name ASC
", [$uid, $uid]);

if (user_has_role('admin') && empty($courses)) {
    $courses = portal_rows($pdo, "SELECT * FROM courses ORDER BY name ASC LIMIT 20");
}

portal_layout_start('curso', 'Meu curso', 'Informação do curso sob coordenação.', $role);

?>

<div class="ui-kpi-grid">
    <div class="ui-kpi"><span>Cursos</span><strong><?= count($courses); ?></strong><small>Sob coordenação ou consulta.</small></div>
    <div class="ui-kpi is-good"><span>Alunos ativos</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM students WHERE enrollment_status = 'active'"); ?></strong><small>Matrículas ativas.</small></div>
    <div class="ui-kpi is-info"><span>Disciplinas</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM subjects WHERE status = 'active'"); ?></strong><small>Unidades curriculares.</small></div>
    <div class="ui-kpi is-warn"><span>Candidaturas</span><strong><?= portal_count($pdo, "SELECT COUNT(*) FROM applications WHERE status IN ('pending','in_review','missing_documents')"); ?></strong><small>Por acompanhar.</small></div>
</div>

<div class="ui-panel">
    <div class="ui-panel-header"><div><h2>Cursos associados</h2><p>O coordenador deve visualizar apenas os cursos atribuídos.</p></div></div>
    <div class="ui-panel-body">
        <div class="ui-card-list">
            <?php if (empty($courses)): ?><div class="ui-empty">Nenhum curso associado ao coordenador.</div><?php endif; ?>
            <?php foreach ($courses as $course): ?>
                <article class="ui-card-row">
                    <div class="ui-toolbar">
                        <div>
                            <h3 style="margin:0;"><?= e(($course['code'] ?? '-') . ' - ' . ($course['name'] ?? '-')); ?></h3>
                            <p style="color:var(--ui-muted);margin:6px 0 0;">Nível: <?= e($course['level'] ?? '-'); ?> · Duração: <?= e((string) ($course['duration_years'] ?? '-')); ?> ano(s)</p>
                        </div>
                        <span class="ui-badge <?= e(portal_badge_class($course['status'] ?? '')); ?>"><?= e(portal_label_status($course['status'] ?? '')); ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php portal_layout_end(); ?>
