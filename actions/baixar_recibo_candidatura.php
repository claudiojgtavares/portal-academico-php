<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$codigo = trim($_GET['codigo'] ?? '');
$email = trim($_GET['email'] ?? '');

if ($codigo === '') {
    redirect(APP_URL . '/estado-candidatura.php');
}

$params = [$codigo];

$whereEmail = '';

if ($email !== '') {
    $whereEmail = " AND applications.personal_email = ? ";
    $params[] = $email;
}

$stmt = $pdo->prepare("
    SELECT
        applications.*,
        courses.code AS course_code,
        courses.name AS course_name,
        courses.level AS course_level,
        academic_years.name AS academic_year_name
    FROM applications
    LEFT JOIN courses ON courses.id = applications.course_id
    LEFT JOIN academic_years ON academic_years.id = applications.academic_year_id
    WHERE applications.application_code = ?
    {$whereEmail}
    LIMIT 1
");

$stmt->execute($params);
$application = $stmt->fetch();

if (!$application) {
    redirect(APP_URL . '/estado-candidatura.php?q=' . urlencode($codigo));
}

function receipt_pdf_clean(string $text): string
{
    $text = trim($text);

    $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);

    if ($converted === false) {
        $converted = preg_replace('/[^\x20-\x7E]/', '', $text);
    }

    return $converted;
}

function receipt_pdf_escape(string $text): string
{
    $text = receipt_pdf_clean($text);
    $text = str_replace('\\', '\\\\', $text);
    $text = str_replace('(', '\\(', $text);
    $text = str_replace(')', '\\)', $text);

    return $text;
}

function receipt_status_label(?string $status): string
{
    $status = strtolower(trim((string) $status));

    $labels = [
        'pending' => 'Pendente',
        'submitted' => 'Submetida',
        'in_review' => 'Em análise',
        'em_analise' => 'Em análise',
        'documents_requested' => 'Documentos solicitados',
        'approved' => 'Aprovada',
        'aprovada' => 'Aprovada',
        'rejected' => 'Rejeitada',
        'rejeitada' => 'Rejeitada',
        'credentials_sent' => 'Credenciais enviadas',
    ];

    return $labels[$status] ?? ucfirst($status);
}

function receipt_level_label(?string $level): string
{
    $labels = [
        'licenciatura' => 'Licenciatura',
        'mestrado' => 'Mestrado',
        'doutoramento' => 'Doutoramento',
        'especializacao' => 'Especialização',
        'formacao_permanente' => 'Formação Permanente',
        'mestrado_integrado' => 'Mestrado Integrado',
    ];

    return $labels[$level ?? ''] ?? ucfirst((string) $level);
}

function receipt_date(?string $value): string
{
    if (!$value) {
        return '-';
    }

    $time = strtotime($value);

    return $time ? date('d/m/Y H:i', $time) : $value;
}

function receipt_birth_date(?string $value): string
{
    if (!$value) {
        return '-';
    }

    $time = strtotime($value);

    return $time ? date('d/m/Y', $time) : $value;
}

function receipt_text_line(float $x, float $y, int $size, string $text, string $font = 'F1'): string
{
    return "BT /{$font} {$size} Tf {$x} {$y} Td (" . receipt_pdf_escape($text) . ") Tj ET\n";
}

function receipt_wrapped_text(float $x, float $y, int $size, string $text, int $maxChars, float $lineHeight, string $font = 'F1'): string
{
    $output = '';
    $words = preg_split('/\s+/', trim($text));
    $line = '';

    foreach ($words as $word) {
        $test = trim($line . ' ' . $word);

        if (strlen(receipt_pdf_clean($test)) > $maxChars && $line !== '') {
            $output .= receipt_text_line($x, $y, $size, $line, $font);
            $y -= $lineHeight;
            $line = $word;
        } else {
            $line = $test;
        }
    }

    if ($line !== '') {
        $output .= receipt_text_line($x, $y, $size, $line, $font);
    }

    return $output;
}

function receipt_build_pdf(array $application): string
{
    $codigo = (string) ($application['application_code'] ?? '-');
    $nome = (string) ($application['full_name'] ?? '-');
    $email = (string) ($application['personal_email'] ?? '-');
    $telefone = (string) ($application['phone'] ?? '-');
    $documento = trim((string) ($application['document_type'] ?? 'Documento') . ' - ' . (string) ($application['document_number'] ?? '-'));
    $birthDate = receipt_birth_date($application['birth_date'] ?? null);
    $curso = trim((string) ($application['course_code'] ?? '') . ' - ' . (string) ($application['course_name'] ?? 'Curso não definido'));
    $nivel = receipt_level_label($application['course_level'] ?? '');
    $anoLetivo = (string) ($application['academic_year_name'] ?? '-');
    $estado = receipt_status_label($application['status'] ?? 'pending');
    $data = receipt_date($application['created_at'] ?? null);
    $geradoEm = date('d/m/Y H:i');

    $content = '';

    /*
        Fundo e cabeçalho.
    */
    $content .= "0.98 0.96 0.92 rg 0 0 595 842 re f\n";
    $content .= "0.23 0.09 0.03 rg 0 770 595 72 re f\n";
    $content .= "1 1 1 rg\n";
    $content .= receipt_text_line(50, 805, 22, 'Instituto Horizonte - Cabo Verde', 'F2');
    $content .= receipt_text_line(50, 785, 11, 'Portal Académico - Recibo de candidatura', 'F1');

    /*
        Título.
    */
    $content .= "0.27 0.12 0.04 rg\n";
    $content .= receipt_text_line(50, 725, 24, 'Recibo de candidatura', 'F2');

    $content .= "0.40 0.40 0.40 rg\n";
    $content .= receipt_wrapped_text(
        50,
        700,
        11,
        'Este documento confirma que a candidatura foi registada no Portal Académico. Guarde este recibo para acompanhamento do pedido.',
        86,
        15,
        'F1'
    );

    /*
        Caixa do código.
    */
    $content .= "1.00 0.96 0.86 rg 50 600 495 70 re f\n";
    $content .= "0.93 0.73 0.28 RG 50 600 495 70 re S\n";
    $content .= "0.40 0.40 0.40 rg\n";
    $content .= receipt_text_line(70, 642, 10, 'Código da candidatura', 'F2');
    $content .= "0.27 0.12 0.04 rg\n";
    $content .= receipt_text_line(70, 618, 22, $codigo, 'F2');

    /*
        Dados principais.
    */
    $content .= "1 1 1 rg 50 260 495 310 re f\n";
    $content .= "0.90 0.86 0.80 RG 50 260 495 310 re S\n";

    $content .= "0.27 0.12 0.04 rg\n";
    $content .= receipt_text_line(70, 535, 16, 'Dados da candidatura', 'F2');

    $rows = [
        ['Candidato', $nome],
        ['Data de nascimento', $birthDate],
        ['Email pessoal', $email],
        ['Telefone', $telefone],
        ['Documento', $documento],
        ['Curso', $curso],
        ['Nível', $nivel],
        ['Ano letivo', $anoLetivo],
        ['Estado atual', $estado],
        ['Data da submissão', $data],
    ];

    $y = 505;

    foreach ($rows as $row) {
        $label = $row[0];
        $value = $row[1];

        $content .= "0.88 0.84 0.78 RG 70 " . ($y - 8) . " 455 0.5 re S\n";

        $content .= "0.38 0.38 0.38 rg\n";
        $content .= receipt_text_line(70, $y, 10, $label, 'F2');

        $content .= "0.10 0.13 0.18 rg\n";
        $content .= receipt_wrapped_text(210, $y, 10, $value, 48, 12, 'F1');

        $y -= 27;
    }

    /*
        Orientação.
    */
    $content .= "1 1 1 rg 50 120 495 105 re f\n";
    $content .= "0.90 0.86 0.80 RG 50 120 495 105 re S\n";

    $content .= "0.27 0.12 0.04 rg\n";
    $content .= receipt_text_line(70, 195, 14, 'Orientação', 'F2');

    $content .= "0.40 0.40 0.40 rg\n";
    $content .= receipt_wrapped_text(
        70,
        172,
        10,
        'Acompanhe o estado da candidatura através da página Acompanhar candidatura, usando o código acima, o email pessoal ou o número do documento informado.',
        78,
        14,
        'F1'
    );

    $content .= receipt_text_line(70, 130, 9, 'Recibo gerado em: ' . $geradoEm, 'F1');

    /*
        Rodapé.
    */
    $content .= "0.27 0.12 0.04 rg\n";
    $content .= receipt_text_line(50, 70, 9, 'Instituto Horizonte - Documento gerado automaticamente pelo portal.', 'F1');

    $objects = [];

    $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>";
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";
    $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];

    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $number = $index + 1;
        $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
    }

    $xrefPosition = strlen($pdf);
    $pdf .= "xref\n";
    $pdf .= "0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";

    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }

    $pdf .= "trailer\n";
    $pdf .= "<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n";
    $pdf .= $xrefPosition . "\n";
    $pdf .= "%%EOF";

    return $pdf;
}

$fileName = 'recibo-candidatura-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $application['application_code']) . '.pdf';
$pdf = receipt_build_pdf($application);

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $pdf;
exit;