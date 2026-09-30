<?php
require_once __DIR__ . '/portal_layout.php';
if (!function_exists('secretaria_layout_start')) { function secretaria_layout_start(string $activeMenu, string $title, string $subtitle = ''): void { portal_layout_start($activeMenu, $title, $subtitle, 'secretaria'); } }
if (!function_exists('secretaria_layout_end')) { function secretaria_layout_end(): void { portal_layout_end(); } }
