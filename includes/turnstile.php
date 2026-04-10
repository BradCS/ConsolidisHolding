<?php

declare(strict_types=1);

function verify_turnstile_token(string $secretKey, string $token, string $remoteIp): array
{
    $endpoint = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $payload = http_build_query([
        'secret' => $secretKey,
        'response' => $token,
        'remoteip' => $remoteIp,
    ]);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);

    $responseBody = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($responseBody === false || $httpCode !== 200) {
        return ['success' => false];
    }

    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded) || empty($decoded['success'])) {
        return ['success' => false];
    }

    return ['success' => true];
}
