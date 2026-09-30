<?php

if (!function_exists('notify_table_exists')) {
    function notify_table_exists(PDO $pdo, string $table): bool
    {
        try {
            $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $error) {
            return false;
        }
    }
}

if (!function_exists('notify_columns')) {
    function notify_columns(PDO $pdo, string $table): array
    {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
            return array_map(static fn ($row) => $row['Field'], $stmt->fetchAll());
        } catch (Throwable $error) {
            return [];
        }
    }
}

if (!function_exists('notify_pick')) {
    function notify_pick(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
    }
}

if (!function_exists('notify_initials')) {
    function notify_initials(string $name): string
    {
        return function_exists('portal_initials_safe') ? portal_initials_safe($name) : 'PA';
    }
}

if (!function_exists('notify_date')) {
    function notify_date(?string $value): string
    {
        if (!$value) {
            return '';
        }

        $time = strtotime($value);
        return $time ? date('d/m/Y H:i', $time) : $value;
    }
}

if (!function_exists('notify_type_badge')) {
    function notify_type_badge(?string $type): string
    {
        $type = strtolower((string) $type);

        if ($type === 'success') {
            return 'ui-badge-success';
        }
        if ($type === 'warning') {
            return 'ui-badge-warning';
        }
        if (in_array($type, ['danger', 'error'], true)) {
            return 'ui-badge-danger';
        }

        return 'ui-badge-info';
    }
}

if (!function_exists('notify_type_label')) {
    function notify_type_label(?string $type): string
    {
        return [
            'success' => 'Sucesso',
            'warning' => 'Aviso',
            'danger' => 'Alerta',
            'error' => 'Erro',
            'info' => 'Informação',
        ][strtolower((string) $type)] ?? 'Informação';
    }
}

if (!function_exists('notify_is_unread')) {
    function notify_is_unread(array $notification): bool
    {
        if (array_key_exists('is_read', $notification)) {
            return (int) ($notification['is_read'] ?? 0) === 0;
        }

        if (array_key_exists('read_at', $notification)) {
            return empty($notification['read_at']);
        }

        return false;
    }
}

if (!function_exists('render_notification_user_box')) {
    function render_notification_user_box(array $user, string $subtitle = '', string $theme = 'ui'): void
    {
        global $pdo;

        $userId = (int) ($user['id'] ?? 0);
        $name = (string) ($user['full_name'] ?? 'Utilizador');
        $code = $subtitle !== '' ? $subtitle : (string) ($user['institutional_id'] ?? '');
        $photoUrl = function_exists('portal_user_photo_url') ? portal_user_photo_url($user) : '';
        $items = [];
        $unread = 0;

        if (isset($pdo) && $pdo instanceof PDO && $userId > 0 && notify_table_exists($pdo, 'notifications')) {
            $columns = notify_columns($pdo, 'notifications');
            $userColumn = notify_pick($columns, ['user_id']);
            $idColumn = notify_pick($columns, ['id']);
            $readColumn = notify_pick($columns, ['is_read', 'read_at']);
            $createdColumn = notify_pick($columns, ['created_at', 'date_created', 'created_on']) ?: ($idColumn ?: 'id');

            if ($userColumn && $idColumn) {
                try {
                    if ($readColumn === 'is_read') {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE `{$userColumn}` = ? AND (`{$readColumn}` = 0 OR `{$readColumn}` IS NULL)");
                        $stmt->execute([$userId]);
                        $unread = (int) $stmt->fetchColumn();
                    } elseif ($readColumn === 'read_at') {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE `{$userColumn}` = ? AND `{$readColumn}` IS NULL");
                        $stmt->execute([$userId]);
                        $unread = (int) $stmt->fetchColumn();
                    }

                    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE `{$userColumn}` = ? ORDER BY `{$createdColumn}` DESC, `{$idColumn}` DESC LIMIT 6");
                    $stmt->execute([$userId]);
                    $items = $stmt->fetchAll();
                } catch (Throwable $error) {
                    $items = [];
                }
            }
        }
        ?>
        <div class="notification-user-area">
            <details class="notify-menu">
                <summary class="notify-trigger" aria-label="Abrir notificações" title="Notificações">
                    <span class="notify-icon" aria-hidden="true">🔔</span>
                    <?php if ($unread > 0): ?>
                        <span class="notify-badge-count"><?= (int) $unread; ?></span>
                    <?php endif; ?>
                </summary>

                <div class="notify-dropdown">
                    <div class="notify-head">
                        <div>
                            <strong>Notificações</strong>
                            <small><?= (int) $unread; ?> não lida(s)</small>
                        </div>
                        <a href="<?= e(APP_URL); ?>/pages/notificacoes.php">Ver todas</a>
                    </div>

                    <?php if (empty($items)): ?>
                        <div class="notify-empty">Ainda não existem notificações para esta conta.</div>
                    <?php else: ?>
                        <div class="notify-list">
                            <?php foreach ($items as $notification): ?>
                                <?php
                                    $columns = array_keys($notification);
                                    $idKey = notify_pick($columns, ['id']);
                                    $titleKey = notify_pick($columns, ['title', 'subject']);
                                    $messageKey = notify_pick($columns, ['message', 'body', 'description']);
                                    $typeKey = notify_pick($columns, ['type', 'category']);
                                    $createdKey = notify_pick($columns, ['created_at', 'date_created', 'created_on']);
                                    $notificationId = $idKey ? (int) $notification[$idKey] : 0;
                                    $title = $titleKey ? (string) ($notification[$titleKey] ?: 'Notificação') : 'Notificação';
                                    $message = $messageKey ? (string) ($notification[$messageKey] ?? '') : '';
                                    $type = $typeKey ? (string) ($notification[$typeKey] ?? 'info') : 'info';
                                    $created = $createdKey ? (string) ($notification[$createdKey] ?? '') : '';
                                    $isUnread = notify_is_unread($notification);
                                    $url = $notificationId > 0
                                        ? APP_URL . '/actions/ler_notificacao.php?id=' . $notificationId . '&redirect=' . urlencode('/pages/notificacao.php?id=' . $notificationId)
                                        : APP_URL . '/pages/notificacoes.php';
                                ?>
                                <a class="notify-item<?= $isUnread ? ' unread' : ''; ?>" href="<?= e($url); ?>">
                                    <div class="notify-item-top">
                                        <div class="notify-item-title-wrap">
                                            <?php if ($isUnread): ?>
                                                <span class="notify-unread-dot" aria-hidden="true"></span>
                                            <?php endif; ?>
                                            <strong><?= e($title); ?></strong>
                                        </div>
                                        <span class="ui-badge <?= e(notify_type_badge($type)); ?>"><?= e(notify_type_label($type)); ?></span>
                                    </div>

                                    <?php if ($message !== ''): ?>
                                        <p class="notify-item-text"><?= e(mb_strimwidth($message, 0, 140, '...', 'UTF-8')); ?></p>
                                    <?php endif; ?>

                                    <div class="notify-item-meta">
                                        <span><?= $isUnread ? 'Não lida' : 'Lida'; ?></span>
                                        <?php if ($created !== ''): ?>
                                            <span><?= e(notify_date($created)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </details>

            <div class="ui-user-box">
                <?php if ($photoUrl !== ''): ?>
                    <img class="ui-avatar-sm is-photo" src="<?= e($photoUrl); ?>" alt="Fotografia de perfil">
                <?php else: ?>
                    <div class="ui-avatar-sm"><?= e(notify_initials($name)); ?></div>
                <?php endif; ?>
                <div>
                    <strong><?= e($name); ?></strong>
                    <span><?= e($code); ?></span>
                </div>
            </div>
        </div>
        <?php
    }
}
