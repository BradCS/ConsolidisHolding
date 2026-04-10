<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/includes/helpers.php';

$configError = null;
$config = load_config(__DIR__ . '/config.php');

if ($config === null) {
    $configError = 'Configuration missing: please copy config.example.php to config.php and fill in your settings.';
}

$errors = [];
$successMessage = null;
$old = [
    'name' => '',
    'company' => '',
    'phone' => '',
    'email' => '',
    'message' => '',
];

if ($config !== null) {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $old = [
            'name' => trim((string)($_POST['name'] ?? '')),
            'company' => trim((string)($_POST['company'] ?? '')),
            'phone' => trim((string)($_POST['phone'] ?? '')),
            'email' => trim((string)($_POST['email'] ?? '')),
            'message' => trim((string)($_POST['message'] ?? '')),
        ];

        if (!verify_csrf_token((string)($_POST['csrf_token'] ?? ''), $_SESSION['csrf_token'])) {
            $errors[] = 'Your session has expired. Please refresh the page and try again.';
        }

        if (!empty($_POST['website'])) {
            $errors[] = 'Submission blocked.';
        }

        if (!is_submission_allowed()) {
            $errors[] = 'Too many attempts. Please wait a few minutes and try again.';
        }

        foreach (['name', 'company', 'phone', 'email', 'message'] as $field) {
            if (contains_header_injection($old[$field])) {
                $errors[] = 'Invalid input detected. Please remove unusual line breaks and try again.';
                break;
            }
        }

        if ($old['name'] === '' || mb_strlen($old['name']) > 120) {
            $errors[] = 'Please enter your name.';
        }

        if ($old['company'] === '' || mb_strlen($old['company']) > 120) {
            $errors[] = 'Please enter your company name.';
        }

        if ($old['phone'] === '' || mb_strlen($old['phone']) > 40) {
            $errors[] = 'Please enter your phone number.';
        }

        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['email']) > 254) {
            $errors[] = 'Please enter a valid email address.';
        }

        if ($old['message'] === '' || mb_strlen($old['message']) > 3000) {
            $errors[] = 'Please enter a message (up to 3000 characters).';
        }

        $turnstileToken = (string)($_POST['cf-turnstile-response'] ?? '');
        if ($turnstileToken === '') {
            $errors[] = 'Please complete the security check.';
        }

        if (!$errors) {
            require_once __DIR__ . '/includes/turnstile.php';
            $turnstileOk = verify_turnstile_token(
                $config['TURNSTILE_SECRET_KEY'],
                $turnstileToken,
                get_client_ip()
            );

            if (!$turnstileOk['success']) {
                $errors[] = 'Security verification failed. Please try again.';
            }
        }

        if (!$errors) {
            require_once __DIR__ . '/includes/mailer.php';
            $mailResult = send_contact_email($config, [
                'name' => $old['name'],
                'company' => $old['company'],
                'phone' => $old['phone'],
                'email' => $old['email'],
                'message' => $old['message'],
                'submitted_at' => gmdate('Y-m-d H:i:s') . ' UTC',
                'client_ip' => get_client_ip(),
            ]);

            if ($mailResult['success']) {
                register_submission_attempt();
                $successMessage = 'Thank you. Your enquiry has been sent successfully.';
                $old = ['name' => '', 'company' => '', 'phone' => '', 'email' => '', 'message' => ''];
            } else {
                $errors[] = 'Sorry, we could not send your message right now. Please try again shortly.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Consolidis Limited</title>
    <meta name="description" content="Consolidis Limited website coming soon. Contact us for enquiries.">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<main class="page">
    <section class="card" aria-labelledby="site-title">
        <img src="https://consolidis.stellexa.net/consolidis.png" alt="Consolidis Limited" class="logo">

        <h1 id="site-title">Consolidis Limited website coming soon.</h1>
        <p class="lead">For enquiries, please get in touch.</p>

        <?php if ($configError !== null): ?>
            <div class="alert alert-error" role="alert"><?php echo escape_html($configError); ?></div>
        <?php endif; ?>

        <?php if ($successMessage !== null): ?>
            <div class="alert alert-success" role="status"><?php echo escape_html($successMessage); ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-error" role="alert">
                <p>Please fix the following:</p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo escape_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <button type="button" id="contactToggle" class="cta" aria-expanded="false" aria-controls="contactPanel">
            Get in touch
        </button>

        <section id="contactPanel" class="contact-panel" hidden>
            <h2>Contact us</h2>
            <form method="post" action="" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo escape_html($_SESSION['csrf_token'] ?? ''); ?>">

                <div class="hp" aria-hidden="true">
                    <label for="website">Leave this field blank</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="field-row">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" maxlength="120" required value="<?php echo escape_html($old['name']); ?>">
                </div>

                <div class="field-row">
                    <label for="company">Company</label>
                    <input type="text" id="company" name="company" maxlength="120" required value="<?php echo escape_html($old['company']); ?>">
                </div>

                <div class="field-row">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" maxlength="40" required value="<?php echo escape_html($old['phone']); ?>">
                </div>

                <div class="field-row">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" maxlength="254" required value="<?php echo escape_html($old['email']); ?>">
                </div>

                <div class="field-row">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="6" maxlength="3000" required><?php echo escape_html($old['message']); ?></textarea>
                </div>

                <div id="turnstileContainer" class="turnstile-wrap" data-sitekey="<?php echo escape_html($config['TURNSTILE_SITE_KEY'] ?? ''); ?>"></div>

                <button type="submit" class="submit-btn">Send enquiry</button>
            </form>
        </section>

        <noscript>
            <p class="noscript-note">JavaScript is required to open the contact form on this holding page.</p>
        </noscript>
    </section>
</main>

<footer class="site-footer">
    <p>Consolidis Limited - Registered in England and Wales - Company number 17146583</p>
    <p class="tech-contact">Technical contact: <a href="https://www.stellexa.it" target="_blank" rel="noopener">Stellexa Ltd</a></p>
</footer>

<script src="assets/app.js" defer></script>
</body>
</html>
