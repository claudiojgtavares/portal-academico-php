<?php
require_once __DIR__ . '/portal_layout.php';
if (!function_exists('professor_layout_start')) { function professor_layout_start(string $activeMenu, string $title, string $subtitle = ''): void { portal_layout_start($activeMenu, $title, $subtitle, 'professor'); } }
if (!function_exists('professor_layout_end')) { function professor_layout_end(): void { portal_layout_end(); } }
