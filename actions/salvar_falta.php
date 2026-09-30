<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_ui_helpers.php';
require_any_role(['admin', 'professor', 'coordenador']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(APP_URL.'/pages/registar_faltas.php');
$user=current_user(); $userId=(int)$user['id']; $assignmentId=(int)($_POST['assignment_id']??0); $classDate=$_POST['class_date']??date('Y-m-d'); $statusInput=$_POST['status']??[]; $notesInput=$_POST['notes']??[]; $allowed=['present','absent','justified'];
if($assignmentId<=0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$classDate)){ $_SESSION['flash_error']='Dados inválidos. Verifique a disciplina e a data da aula.'; redirect(APP_URL.'/pages/registar_faltas.php?assignment_id='.$assignmentId); }
try{
    $assignment=portal_one($pdo,"SELECT subject_id,class_id,academic_year_id FROM teacher_subjects WHERE id=? AND teacher_user_id=? AND status='active' LIMIT 1",[$assignmentId,$userId]); if(!$assignment) throw new Exception('Disciplina não atribuída ao professor.');
    $saved=0;
    foreach($statusInput as $studentId=>$status){ $studentId=(int)$studentId; $status=trim((string)$status); if($studentId<=0 || !in_array($status,$allowed,true)) continue; $belongs=portal_one($pdo,"SELECT id FROM class_students WHERE class_id=? AND student_id=? AND status='active' LIMIT 1",[$assignment['class_id'],$studentId]); if(!$belongs) continue; $notes=trim((string)($notesInput[$studentId]??''));
        $existing=portal_one($pdo,"SELECT id FROM attendance_records WHERE student_id=? AND subject_id=? AND class_id=? AND teacher_user_id=? AND class_date=? LIMIT 1",[$studentId,$assignment['subject_id'],$assignment['class_id'],$userId,$classDate]);
        if($existing){ $stmt=$pdo->prepare("UPDATE attendance_records SET status=?, notes=?, updated_at=NOW() WHERE id=?"); $stmt->execute([$status,$notes!==''?$notes:null,$existing['id']]); }
        else{ $stmt=$pdo->prepare("INSERT INTO attendance_records (student_id,subject_id,class_id,teacher_user_id,academic_year_id,class_date,status,notes) VALUES (?,?,?,?,?,?,?,?)"); $stmt->execute([$studentId,$assignment['subject_id'],$assignment['class_id'],$userId,$assignment['academic_year_id'],$classDate,$status,$notes!==''?$notes:null]); }
        $saved++;
    }
    $log=$pdo->prepare("INSERT INTO activity_logs (user_id,action,description,ip_address) VALUES (?,'ATTENDANCE_SAVED',?,?)"); $log->execute([$userId,'Pauta de presenças guardada em '.$classDate.'.',current_ip()]);
    $_SESSION['flash_success']=$saved.' registo(s) de presença guardado(s).';
}catch(Throwable $error){ $_SESSION['flash_error']='Erro ao guardar presenças: '.$error->getMessage(); }
redirect(APP_URL.'/pages/registar_faltas.php?assignment_id='.$assignmentId.'&class_date='.urlencode($classDate));
