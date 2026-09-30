<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = current_user();
$id = (int) ($_GET['id'] ?? 0);
$redirect = trim($_GET['redirect'] ?? '');

try {
    if ($id > 0) {
        $cols = [];
        $st = $pdo->query("SHOW COLUMNS FROM notifications");
        foreach ($st->fetchAll() as $r) {
            $cols[] = $r['Field'];
        }

        if (in_array('is_read', $cols, true)) {
            $q = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $q->execute([$id, (int) $user['id']]);
        } elseif (in_array('read_at', $cols, true)) {
            $q = $pdo->prepare("UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?");
            $q->execute([$id, (int) $user['id']]);
        }
    }
} catch (Throwable $e) {
    // Não interrompe a navegação se a marcação de leitura falhar.
}

if ($redirect === '') {
    $redirect = '/pages/notificacao.php?id=' . $id;
}

if (!str_starts_with($redirect, '/')) {
    $redirect = '/pages/notificacoes.php';
}

redirect(APP_URL . $redirect);
