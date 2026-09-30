<?php
require_once __DIR__ . '/../includes/portal_layout.php';
require_any_role(['coordenador','admin']);
$user = current_user();
$forcedRole = 'coordenador';

$uid=(int)$user['id'];
$courses=portal_rows($pdo,"SELECT courses.* FROM course_coordinators INNER JOIN courses ON courses.id=course_coordinators.course_id WHERE course_coordinators.user_id=? OR course_coordinators.coordinator_user_id=?",[$uid,$uid]);
$ids=array_map(fn($c)=>(int)$c['id'],$courses); if(empty($ids)){$ids=[0];} $ph=implode(',',array_fill(0,count($ids),'?'));
$metrics=['courses'=>count(array_filter($ids)),'students'=>portal_count($pdo,"SELECT COUNT(*) FROM students WHERE course_id IN ($ph)",$ids),'apps'=>portal_count($pdo,"SELECT COUNT(*) FROM applications WHERE course_id IN ($ph)",$ids),'subjects'=>portal_count($pdo,"SELECT COUNT(*) FROM subjects WHERE course_id IN ($ph)",$ids),'classes'=>portal_count($pdo,"SELECT COUNT(*) FROM academic_classes WHERE course_id IN ($ph)",$ids),'payments'=>portal_money(portal_count($pdo,"SELECT COALESCE(SUM(payments.amount_cve),0) FROM payments INNER JOIN students ON students.id=payments.student_id WHERE students.course_id IN ($ph) AND payments.status='confirmado'",$ids))];

portal_layout_start('dashboard', 'Painel do Coordenador', 'Visão académica limitada ao curso sob coordenação.', $forcedRole);
?>
<div class="ui-kpi-grid">
    <div class="ui-kpi"><span>Cursos</span><strong><?= e((string)($metrics['courses'])); ?></strong><small>Sob coordenação.</small></div>
    <div class="ui-kpi"><span>Alunos ativos</span><strong><?= e((string)($metrics['students'])); ?></strong><small>No(s) curso(s).</small></div>
    <div class="ui-kpi"><span>Candidaturas</span><strong><?= e((string)($metrics['apps'])); ?></strong><small>Associadas ao curso.</small></div>
    <div class="ui-kpi"><span>Disciplinas</span><strong><?= e((string)($metrics['subjects'])); ?></strong><small>Unidades curriculares.</small></div>
    <div class="ui-kpi"><span>Turmas</span><strong><?= e((string)($metrics['classes'])); ?></strong><small>Turmas ativas.</small></div>
    <div class="ui-kpi"><span>Valor confirmado</span><strong><?= e((string)($metrics['payments'])); ?></strong><small>Pagamentos confirmados.</small></div>
</div>

<div class="ui-panel"><div class="ui-panel-header"><div><h2>Curso sob coordenação</h2><p>O coordenador visualiza apenas dados dos cursos atribuídos.</p></div></div><div class="ui-panel-body"><div class="ui-card-list"><?php if(empty($courses)): ?><div class="ui-empty">Nenhum curso associado ao coordenador.</div><?php endif; ?><?php foreach($courses as $c): ?><div class="ui-card-row"><strong><?= e(($c['code'] ?? '-') . ' — ' . ($c['name'] ?? '-')); ?></strong><span style="color:var(--ui-muted);">Estado: <?= e(portal_label_status($c['status'] ?? '')); ?></span></div><?php endforeach; ?></div></div></div>


<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2>Ações de professor e coordenação</h2>
            <p>O coordenador também é docente: pode lançar notas, registar faltas e, adicionalmente, acompanhar o curso sob coordenação.</p>
        </div>
    </div>
    <div class="ui-panel-body">
        <div class="ui-grid-3">
            <a class="ui-card-row" style="grid-template-columns:1fr;text-decoration:none;color:inherit;" href="<?= e(APP_URL); ?>/pages/minhas_disciplinas.php"><strong>Minhas disciplinas</strong><span style="color:var(--ui-muted);">Turmas atribuídas como docente.</span></a>
            <a class="ui-card-row" style="grid-template-columns:1fr;text-decoration:none;color:inherit;" href="<?= e(APP_URL); ?>/pages/lancar_notas.php"><strong>Lançar notas</strong><span style="color:var(--ui-muted);">Avaliações das disciplinas lecionadas.</span></a>
            <a class="ui-card-row" style="grid-template-columns:1fr;text-decoration:none;color:inherit;" href="<?= e(APP_URL); ?>/pages/registar_faltas.php"><strong>Registar faltas</strong><span style="color:var(--ui-muted);">Pauta de presença por aula.</span></a>
        </div>
    </div>
</div>

<?php portal_layout_end(); ?>
