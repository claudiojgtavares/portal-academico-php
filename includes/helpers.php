<?php

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header("Location: {$path}");
    exit;
}

function current_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf_or_abort(?string $token): void
{
    $expected = $_SESSION['_csrf'] ?? '';
    if (!is_string($token) || $expected === '' || !hash_equals($expected, $token)) {
        http_response_code(419);
        exit('Pedido expirado ou inválido. Atualize a página e tente novamente.');
    }
}

function slug_name(string $name): string
{
    $name = trim(mb_strtolower($name, 'UTF-8'));

    $replace = [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
        'é' => 'e', 'ê' => 'e',
        'í' => 'i',
        'ó' => 'o', 'õ' => 'o', 'ô' => 'o',
        'ú' => 'u',
        'ç' => 'c'
    ];

    $name = strtr($name, $replace);
    $name = preg_replace('/[^a-z0-9\s]/', '', $name);
    $name = preg_replace('/\s+/', '.', $name);

    return $name;
}

function generate_temp_password(string $year, string $sequence): string
{
    return 'Demo@' . $year . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
}

function generate_institutional_email(string $fullName, string $institutionalId): string
{
    $parts = explode(' ', trim($fullName));

    $firstName = $parts[0] ?? 'utilizador';
    $lastName = count($parts) > 1 ? $parts[count($parts) - 1] : 'portal';

    $base = slug_name($firstName . ' ' . $lastName);
    $cleanId = strtolower(str_replace(['-', '_'], '', $institutionalId));

    return $base . '.' . $cleanId . '@' . INSTITUTION_EMAIL_DOMAIN;
}
