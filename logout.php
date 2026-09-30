<?php

require_once __DIR__ . '/includes/auth.php';

$user = current_user();

if ($user) {
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (
            user_id,
            action,
            description,
            ip_address
        ) VALUES (?, 'LOGOUT', 'Utilizador terminou sessão.', ?)
    ");

    $stmt->execute([
        (int) $user['id'],
        current_ip()
    ]);
}

logout_user();

redirect(APP_URL . '/login.php');