<?php

require_once __DIR__ . '/../includes/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/pages/editar_perfil.php');
}

$user = current_user();

if (!$user) {
    redirect(APP_URL . '/login.php?erro=sessao');
}

$fullName = trim($_POST['full_name'] ?? '');
$personalEmail = trim($_POST['personal_email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$birthDate = trim($_POST['birth_date'] ?? '');
$birthDateVisible = trim($_POST['birth_date_visible'] ?? '');
if ($birthDate === '' && preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $birthDateVisible, $m)) {
    $birthDate = $m[3] . '-' . $m[2] . '-' . $m[1];
}
$documentType = trim($_POST['document_type'] ?? '');
$documentNumber = trim($_POST['document_number'] ?? '');
$address = trim($_POST['address'] ?? '');

function profile_upload_file(string $fieldName, array $allowedExtensions, int $maxSizeMb, string $prefix): ?string
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erro ao carregar ficheiro.');
    }

    $fileSize = (int) $_FILES[$fieldName]['size'];

    if ($fileSize > ($maxSizeMb * 1024 * 1024)) {
        throw new Exception('O ficheiro ultrapassa o tamanho máximo permitido.');
    }

    $originalName = $_FILES[$fieldName]['name'];
    $tmpName = $_FILES[$fieldName]['tmp_name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        throw new Exception('Formato de ficheiro inválido.');
    }

    $uploadDir = __DIR__ . '/../assets/uploads/perfis/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0750, true);
    }

    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
    $newFileName = $prefix . '_' . uniqid('', true) . '_' . $safeName;
    $destination = $uploadDir . $newFileName;

    if (!move_uploaded_file($tmpName, $destination)) {
        throw new Exception('Não foi possível guardar o ficheiro.');
    }

    return 'assets/uploads/perfis/' . $newFileName;
}

try {
    if ($fullName === '') {
        throw new Exception('O nome completo é obrigatório.');
    }

    if ($personalEmail !== '' && !filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('O email pessoal informado não é válido.');
    }

    if ($birthDate !== '') {
        $birthTimestamp = strtotime($birthDate);

        if (!$birthTimestamp) {
            throw new Exception('A data de nascimento informada não é válida.');
        }
    }

    $currentStmt = $pdo->prepare("
        SELECT
            profile_photo,
            identity_document_file
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $currentStmt->execute([(int) $user['id']]);
    $currentData = $currentStmt->fetch();

    if (!$currentData) {
        throw new Exception('Utilizador não encontrado.');
    }

    $profilePhoto = $currentData['profile_photo'] ?? null;
    $identityDocumentFile = $currentData['identity_document_file'] ?? null;

    $newPhoto = profile_upload_file(
        'profile_photo',
        ['jpg', 'jpeg', 'png', 'webp'],
        2,
        'FOTO_' . $user['institutional_id']
    );

    if ($newPhoto) {
        $profilePhoto = $newPhoto;
    }

    $newIdentityDocument = profile_upload_file(
        'identity_document_file',
        ['pdf', 'jpg', 'jpeg', 'png'],
        5,
        'DOC_ID_' . $user['institutional_id']
    );

    if ($newIdentityDocument) {
        $identityDocumentFile = $newIdentityDocument;
    }

    $updateStmt = $pdo->prepare("
        UPDATE users
        SET
            full_name = ?,
            personal_email = ?,
            phone = ?,
            birth_date = ?,
            document_type = ?,
            document_number = ?,
            address = ?,
            profile_photo = ?,
            identity_document_file = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    $updateStmt->execute([
        $fullName,
        $personalEmail !== '' ? $personalEmail : null,
        $phone !== '' ? $phone : null,
        $birthDate !== '' ? $birthDate : null,
        $documentType !== '' ? $documentType : null,
        $documentNumber !== '' ? $documentNumber : null,
        $address !== '' ? $address : null,
        $profilePhoto,
        $identityDocumentFile,
        (int) $user['id']
    ]);

    $logStmt = $pdo->prepare("
        INSERT INTO activity_logs (
            user_id,
            action,
            description,
            ip_address
        ) VALUES (?, 'PROFILE_UPDATED', 'Utilizador atualizou dados do perfil.', ?)
    ");

    $logStmt->execute([
        (int) $user['id'],
        current_ip()
    ]);

    $_SESSION['flash_success'] = 'Perfil atualizado com sucesso.';

    redirect(APP_URL . '/pages/perfil.php');
} catch (Throwable $error) {
    $_SESSION['flash_error'] = $error->getMessage();

    redirect(APP_URL . '/pages/editar_perfil.php');
}