<?php

if (!function_exists('portal_db_table_exists')) {
    function portal_db_table_exists(PDO $pdo, string $table): bool
    {
        try { $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table)); return (bool) $stmt->fetchColumn(); }
        catch (Throwable $e) { return false; }
    }
}
if (!function_exists('portal_db_columns')) {
    function portal_db_columns(PDO $pdo, string $table): array
    {
        try { $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`"); return array_map(fn($r) => $r['Field'], $stmt->fetchAll()); }
        catch (Throwable $e) { return []; }
    }
}
if (!function_exists('portal_db_pick')) {
    function portal_db_pick(array $columns, array $candidates): ?string
    { foreach ($candidates as $c) { if (in_array($c, $columns, true)) return $c; } return null; }
}
if (!function_exists('portal_count')) {
    function portal_count(PDO $pdo, string $sql, array $params = []): int
    { try { $stmt=$pdo->prepare($sql); $stmt->execute($params); return (int)($stmt->fetchColumn() ?: 0); } catch(Throwable $e){ return 0; } }
}
if (!function_exists('portal_rows')) {
    function portal_rows(PDO $pdo, string $sql, array $params = []): array
    { try { $stmt=$pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(); } catch(Throwable $e){ return []; } }
}
if (!function_exists('portal_one')) {
    function portal_one(PDO $pdo, string $sql, array $params = []): ?array
    { try { $stmt=$pdo->prepare($sql); $stmt->execute($params); $row=$stmt->fetch(); return $row ?: null; } catch(Throwable $e){ return null; } }
}
if (!function_exists('portal_money')) {
    function portal_money($value): string { return number_format((float)($value ?: 0), 0, ',', '.') . ' CVE'; }
}
if (!function_exists('portal_date')) {
    function portal_date(?string $value): string { if(!$value) return '-'; $t=strtotime($value); return $t ? date('d/m/Y H:i',$t) : $value; }
}
if (!function_exists('portal_short_date')) {
    function portal_short_date(?string $value): string { if(!$value) return '-'; $t=strtotime($value); return $t ? date('d/m/Y',$t) : $value; }
}
if (!function_exists('portal_label_status')) {
    function portal_label_status(?string $status): string
    {
        $s = (string)$status;
        $map = [
            'active'=>'Ativo','inactive'=>'Inativo','blocked'=>'Bloqueado','pending'=>'Pendente','success'=>'Sucesso','info'=>'Informação','warning'=>'Aviso','danger'=>'Alerta','error'=>'Erro','frequencia'=>'Frequência','trabalho'=>'Trabalho','exame'=>'Exame','recurso'=>'Recurso','in_review'=>'Em análise','missing_documents'=>'Documentos em falta','documents_validated'=>'Documentos validados','approved'=>'Aprovada','rejected'=>'Rejeitada','credentials_sent'=>'Credenciais enviadas',
            'em_analise'=>'Em análise','confirmado'=>'Confirmado','rejeitado'=>'Rejeitado','em_atraso'=>'Em atraso','ready'=>'Pronto','delivered'=>'Entregue','draft'=>'Rascunho','submitted'=>'Submetido','reviewed'=>'Revisto','archived'=>'Arquivado','documento_secretaria'=>'Documento da secretaria','relatorio_coordenacao'=>'Relatório de coordenação','relatorio_direcao'=>'Relatório da direção','draft'=>'Rascunho','published'=>'Publicado','validated'=>'Validado','cancelled'=>'Cancelado','present'=>'Presente','absent'=>'Falta','justified'=>'Justificada','terca'=>'Terça-feira','sabado'=>'Sábado','admin'=>'Administrador','secretaria'=>'Secretaria Académica','direcao'=>'Direção Académica','coordenador'=>'Coordenador de Curso','professor'=>'Professor','funcionario'=>'Funcionário Académico','aluno'=>'Aluno','apoio'=>'Apoio académico'
        ];
        return $map[$s] ?? ($s !== '' ? ucfirst(str_replace('_',' ',$s)) : '-');
    }
}
if (!function_exists('portal_badge_class')) {
    function portal_badge_class(?string $status): string
    {
        $s=(string)$status;
        if (in_array($s,['active','approved','credentials_sent','documents_validated','confirmado','ready','delivered','present','submitted','reviewed','success','published','validated'],true)) return 'ui-badge-success';
        if (in_array($s,['pending','in_review','missing_documents','em_analise','em_atraso','draft','justified','warning'],true)) return 'ui-badge-warning';
        if (in_array($s,['rejected','rejeitado','blocked','absent','danger','error','cancelled'],true)) return 'ui-badge-danger';
        return 'ui-badge-info';
    }
}
if (!function_exists('portal_initials_safe')) {
    function portal_initials_safe(string $name): string
    {
        $parts=preg_split('/\s+/', trim($name)); if(!$parts || $parts[0]==='') return 'UP';
        $first=mb_substr($parts[0],0,1,'UTF-8'); $last=count($parts)>1?mb_substr(end($parts),0,1,'UTF-8'):'';
        return mb_strtoupper($first.$last,'UTF-8');
    }
}
if (!function_exists('portal_flash')) {
    function portal_flash(): array
    {
        $s=$_SESSION['flash_success'] ?? null; $e=$_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        return [$s,$e];
    }
}
if (!function_exists('portal_user_role')) {
    function portal_user_role(array $user): string
    {
        $roles=$user['roles_array'] ?? [];
        foreach(['admin','secretaria','direcao','coordenador','professor','funcionario','aluno'] as $r){ if(in_array($r,$roles,true)) return $r; }
        return 'aluno';
    }
}
