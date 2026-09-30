<?php
require_once __DIR__ . '/portal_layout.php';
if (!function_exists('admin_layout_start')) { function admin_layout_start(string $activeMenu, string $title, string $subtitle = ''): void { portal_layout_start($activeMenu, $title, $subtitle, 'admin'); } }
if (!function_exists('admin_layout_end')) { function admin_layout_end(): void { portal_layout_end(); } }
