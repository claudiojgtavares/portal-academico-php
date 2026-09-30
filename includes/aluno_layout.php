<?php
require_once __DIR__ . '/portal_layout.php';
if (!function_exists('aluno_layout_start')) { function aluno_layout_start(string $activeMenu, string $title, string $subtitle = ''): void { portal_layout_start($activeMenu, $title, $subtitle, 'aluno'); } }
if (!function_exists('aluno_layout_end')) { function aluno_layout_end(): void { portal_layout_end(); } }
