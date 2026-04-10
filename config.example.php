<?php

declare(strict_types=1);

/**
 * Copy this file to config.php and replace placeholders with real values.
 * Do not commit config.php.
 */

return [
    // Where contact submissions are delivered.
    'CONTACT_TO_EMAIL' => 'hello@consolidis.co.uk',

    // Authenticated sender identity (must match SMTP account/domain policy).
    'MAIL_FROM_EMAIL' => 'consolidis.web@stellexa.dev',
    'MAIL_FROM_NAME' => 'Consolidis Website',

    // SMTP configuration for PHPMailer.
    'SMTP_HOST' => 'smtp-relay.brevo.com',
    'SMTP_PORT' => 587,
    'SMTP_ENCRYPTION' => 'tls', // tls or ssl
    'SMTP_USERNAME' => 'your-smtp-username',
    'SMTP_PASSWORD' => 'your-smtp-password',

    // Cloudflare Turnstile keys.
    'TURNSTILE_SITE_KEY' => 'your-turnstile-site-key',
    'TURNSTILE_SECRET_KEY' => 'your-turnstile-secret-key',

    // App behavior.
    'APP_ENV' => 'production',
    'RATE_LIMIT_WINDOW_SECONDS' => 300,
    'RATE_LIMIT_MAX_SUBMISSIONS' => 5,
];
