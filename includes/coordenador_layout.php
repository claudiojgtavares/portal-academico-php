<?php
require_once __DIR__ . '/portal_layout.php';
if (!function_exists('coordenador_layout_start')) { function coordenador_layout_start(string $activeMenu, string $title, string $subtitle = ''): void { portal_layout_start($activeMenu, $title, $subtitle, 'coordenador'); } }
if (!function_exists('coordenador_layout_end')) { function coordenador_layout_end(): void { portal_layout_end(); } }
