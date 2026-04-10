# Consolidis Limited Holding Page (LAMP + PHP)

A lightweight single-page holding site for **consolidis.co.uk** using plain PHP, HTML, CSS, and vanilla JS.

## Project structure

```text
.
├── .gitignore
├── .htaccess
├── README.md
├── assets
│   ├── app.js
│   └── styles.css
├── config.example.php
├── includes
│   ├── helpers.php
│   ├── mailer.php
│   └── turnstile.php
└── index.php
```

## Deployment on a standard LAMP stack

1. Upload the project files to your web root (for example `public_html/` or `/var/www/html/`).
2. Ensure Apache serves `index.php` as the default document.
3. Keep `.htaccess` in the same directory as `index.php`.
4. Install dependencies (PHPMailer) with Composer if available.

## Configuration

1. Copy `config.example.php` to `config.php`.
2. Edit `config.php` and set real values.
3. Never commit `config.php` (already ignored via `.gitignore`).

### Required config values

- `CONTACT_TO_EMAIL`: **real monitored recipient inbox** (e.g. `hello@consolidis.co.uk`).
- `MAIL_FROM_EMAIL`: authenticated sender (`consolidis.web@stellexa.dev`).
- `MAIL_FROM_NAME`
- `SMTP_HOST`, `SMTP_PORT`, `SMTP_ENCRYPTION`, `SMTP_USERNAME`, `SMTP_PASSWORD`
- `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`

> `consolidis.web@stellexa.dev` is used as the SMTP-authenticated sender only. Delivery should go to `CONTACT_TO_EMAIL`, which must be an actively monitored mailbox.

## PHP extensions / runtime requirements

- PHP 8.0+ recommended
- `curl` extension (Turnstile verification)
- `json` extension
- `mbstring` extension
- OpenSSL support for SMTP TLS

## Install PHPMailer

### Composer (preferred)

```bash
composer require phpmailer/phpmailer
```

### Fallback when Composer is unavailable

- Download PHPMailer release files manually.
- Place them under `vendor/` in a Composer-compatible structure or update `includes/mailer.php` autoload logic.
- Composer is still strongly recommended for maintenance and updates.

## Lazy-loaded Turnstile flow

- Form panel is hidden on initial page load.
- Turnstile script is **not loaded initially**.
- Clicking **Get in touch** reveals the form and injects:
  - `https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit`
- Widget is rendered explicitly via JS only when form opens.
- On submit, token is verified server-side in `includes/turnstile.php` before attempting SMTP send.

## Safety and anti-spam controls

- POST-only handling
- Server-side validation + basic client-side validation
- CSRF token check
- Honeypot field (`website`)
- Basic IP rate-limiting via temp file storage
- Header injection checks
- Escaped output and generic failure messaging

## Testing the form safely

1. Use Turnstile test keys first.
2. Set a safe test recipient in `CONTACT_TO_EMAIL`.
3. Submit valid and invalid inputs to verify validation and error messages.
4. Temporarily use incorrect SMTP credentials to confirm graceful failure.
5. Confirm Turnstile widget appears only after opening the form.

