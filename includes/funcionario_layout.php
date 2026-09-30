<?php
require_once __DIR__ . '/portal_layout.php';
if (!function_exists('funcionario_layout_start')) { function funcionario_layout_start(string $activeMenu, string $title, string $subtitle = ''): void { portal_layout_start($activeMenu, $title, $subtitle, 'funcionario'); } }
if (!function_exists('funcionario_layout_end')) { function funcionario_layout_end(): void { portal_layout_end(); } }
