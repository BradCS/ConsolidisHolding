<?php

declare(strict_types=1);

function load_config(string $configPath): ?array
{
    if (!is_file($configPath)) {
        return null;
    }

    $config = require $configPath;
    if (!is_array($config)) {
        return null;
    }

    $required = [
        'CONTACT_TO_EMAIL',
        'MAIL_FROM_EMAIL',
        'MAIL_FROM_NAME',
        'SMTP_HOST',
        'SMTP_PORT',
        'SMTP_ENCRYPTION',
        'SMTP_USERNAME',
        'SMTP_PASSWORD',
        'TURNSTILE_SITE_KEY',
        'TURNSTILE_SECRET_KEY',
    ];

    foreach ($required as $key) {
        if (!array_key_exists($key, $config)) {
            return null;
        }
    }

    return $config;
}

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function verify_csrf_token(string $postedToken, string $sessionToken): bool
{
    return $postedToken !== '' && $sessionToken !== '' && hash_equals($sessionToken, $postedToken);
}

function contains_header_injection(string $value): bool
{
    return (bool)preg_match('/[\r\n]|content-type:|bcc:|cc:/i', $value);
}

function get_client_ip(): string
{
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $value = trim(explode(',', (string)$_SERVER[$key])[0]);
            if (filter_var($value, FILTER_VALIDATE_IP)) {
                return $value;
            }
        }
    }

    return 'unknown';
}

function get_rate_limit_file(): string
{
    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'consolidis_rate_limit.json';
}

function is_submission_allowed(): bool
{
    $config = load_config(__DIR__ . '/../config.php');
    $window = (int)($config['RATE_LIMIT_WINDOW_SECONDS'] ?? 300);
    $max = (int)($config['RATE_LIMIT_MAX_SUBMISSIONS'] ?? 5);

    $ip = get_client_ip();
    $now = time();
    $file = get_rate_limit_file();

    $data = [];
    if (is_file($file)) {
        $raw = file_get_contents($file);
        if ($raw !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }
    }

    $entries = $data[$ip] ?? [];
    $entries = array_values(array_filter($entries, static function ($timestamp) use ($now, $window): bool {
        return is_int($timestamp) && ($now - $timestamp) <= $window;
    }));

    return count($entries) < $max;
}

function register_submission_attempt(): void
{
    $config = load_config(__DIR__ . '/../config.php') ?? [];
    $window = (int)($config['RATE_LIMIT_WINDOW_SECONDS'] ?? 300);

    $ip = get_client_ip();
    $now = time();
    $file = get_rate_limit_file();

    $data = [];
    if (is_file($file)) {
        $raw = file_get_contents($file);
        if ($raw !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }
    }

    if (!isset($data[$ip]) || !is_array($data[$ip])) {
        $data[$ip] = [];
    }

    $data[$ip] = array_values(array_filter($data[$ip], static function ($timestamp) use ($now, $window): bool {
        return is_int($timestamp) && ($now - $timestamp) <= $window;
    }));
    $data[$ip][] = $now;

    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}
