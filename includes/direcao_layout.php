<?php
require_once __DIR__ . '/portal_layout.php';
if (!function_exists('direcao_layout_start')) { function direcao_layout_start(string $activeMenu, string $title, string $subtitle = ''): void { portal_layout_start($activeMenu, $title, $subtitle, 'direcao'); } }
if (!function_exists('direcao_layout_end')) { function direcao_layout_end(): void { portal_layout_end(); } }
