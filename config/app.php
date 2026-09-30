<?php

function env_value(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false || $value === '' ? $default : $value;
}

define('APP_NAME', env_value('APP_NAME', 'Portal Académico'));
define('APP_URL', rtrim(env_value('APP_URL', 'http://localhost/portal-academico'), '/'));
define('APP_ENV', env_value('APP_ENV', 'production'));
define('INSTITUTION_NAME', env_value('INSTITUTION_NAME', 'Instituto Horizonte'));
define('INSTITUTION_SHORT_NAME', env_value('INSTITUTION_SHORT_NAME', 'Horizonte'));
define('INSTITUTION_EMAIL_DOMAIN', env_value('INSTITUTION_EMAIL_DOMAIN', 'academico.example.test'));
define('DEFAULT_TIMEZONE', env_value('APP_TIMEZONE', 'Atlantic/Cape_Verde'));
date_default_timezone_set(DEFAULT_TIMEZONE);
