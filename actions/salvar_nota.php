<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_ui_helpers.php';
require_any_role(['admin', 'professor', 'coordenador']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/pages/lancar_notas.php');
}
$user = current_user(); $userId=(int)$user['id']; $assignmentId=(int)($_POST['assignment_id']??0);
$assessmentType=trim($_POST['assessment_type']??'frequencia'); $assessmentLabel=trim($_POST['assessment_label']??''); $status=trim($_POST['status']??'draft'); $dateVisible=trim($_POST['assessment_date_visible']??'');
$gradeValues=$_POST['grade_value']??[]; $notesInput=$_POST['notes']??[];
$allowedTypes=['teste1','teste2','trabalho','frequencia','exame','recurso','outro']; $allowedStatus=['draft','submitted'];
if($assignmentId<=0 || !in_array($assessmentType,$allowedTypes,true) || !in_array($status,$allowedStatus,true)){ $_SESSION['flash_error']='Dados inválidos. Verifique a disciplina, tipo de avaliação e estado.'; redirect(APP_URL.'/pages/lancar_notas.php?assignment_id='.$assignmentId); }
try{
    $assignment=portal_one($pdo,"SELECT subject_id,class_id,academic_year_id FROM teacher_subjects WHERE id=? AND teacher_user_id=? AND status='active' LIMIT 1",[$assignmentId,$userId]);
    if(!$assignment) throw new Exception('Disciplina não atribuída ao professor.');
    $saved=0;
    foreach($gradeValues as $studentId=>$gradeValueRaw){
        $studentId=(int)$studentId; $raw=trim((string)$gradeValueRaw); if($studentId<=0 || $raw==='') continue; $gradeValue=(float)$raw; if($gradeValue<0 || $gradeValue>20) continue;
        $belongs=portal_one($pdo,"SELECT id FROM class_students WHERE class_id=? AND student_id=? AND status='active' LIMIT 1",[$assignment['class_id'],$studentId]); if(!$belongs) continue;
        $obs=trim((string)($notesInput[$studentId]??''));
        $label=$assessmentLabel!==''?$assessmentLabel:portal_label_status($assessmentType);
        $noteText='Avaliação: '.$label; if($dateVisible!=='') $noteText.=' · Data: '.$dateVisible; if($obs!=='') $noteText.=' · Observação: '.$obs;
        $existing=portal_one($pdo,"SELECT id FROM grades WHERE student_id=? AND subject_id=? AND class_id=? AND teacher_user_id=? AND assessment_type=? AND notes LIKE ? LIMIT 1",[$studentId,$assignment['subject_id'],$assignment['class_id'],$userId,$assessmentType,'Avaliação: '.$label.'%']);
        if($existing){
            $stmt=$pdo->prepare("UPDATE grades SET grade_value=?, status=?, notes=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$gradeValue,$status,$noteText,$existing['id']]);
        }else{
            $stmt=$pdo->prepare("INSERT INTO grades (student_id,subject_id,class_id,teacher_user_id,academic_year_id,assessment_type,grade_value,status,notes) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$studentId,$assignment['subject_id'],$assignment['class_id'],$userId,$assignment['academic_year_id'],$assessmentType,$gradeValue,$status,$noteText]);
        }
        $saved++;
    }
    $log=$pdo->prepare("INSERT INTO activity_logs (user_id,action,description,ip_address) VALUES (?,'GRADE_SAVED',?,?)");
    $log->execute([$userId,'Notas guardadas para a avaliação '.($assessmentLabel!==''?$assessmentLabel:portal_label_status($assessmentType)).'.',current_ip()]);
    $_SESSION['flash_success']=$saved.' nota(s) guardada(s) com sucesso.';
}catch(Throwable $error){ $_SESSION['flash_error']='Erro ao guardar notas: '.$error->getMessage(); }
redirect(APP_URL.'/pages/lancar_notas.php?assignment_id='.$assignmentId);
