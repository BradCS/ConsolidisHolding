<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

function send_contact_email(array $config, array $payload): array
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        return ['success' => false, 'error' => 'PHPMailer not installed'];
    }

    require_once $autoload;

    $mail = new PHPMailer(true);

    $subject = 'New Consolidis enquiry';

    $bodyText = "New enquiry submitted via consolidis.co.uk\n\n"
        . "Name: {$payload['name']}\n"
        . "Company: {$payload['company']}\n"
        . "Phone: {$payload['phone']}\n"
        . "Email: {$payload['email']}\n"
        . "Message:\n{$payload['message']}\n\n"
        . "Submitted at: {$payload['submitted_at']}\n"
        . "Client IP: {$payload['client_ip']}\n";

    $safeName = htmlspecialchars((string)$payload['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeCompany = htmlspecialchars((string)$payload['company'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safePhone = htmlspecialchars((string)$payload['phone'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeEmail = htmlspecialchars((string)$payload['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars((string)$payload['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    $safeSubmittedAt = htmlspecialchars((string)$payload['submitted_at'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeClientIp = htmlspecialchars((string)$payload['client_ip'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $bodyHtml = "<h2>New enquiry submitted via consolidis.co.uk</h2>"
        . "<p><strong>Name:</strong> {$safeName}</p>"
        . "<p><strong>Company:</strong> {$safeCompany}</p>"
        . "<p><strong>Phone:</strong> {$safePhone}</p>"
        . "<p><strong>Email:</strong> {$safeEmail}</p>"
        . "<p><strong>Message:</strong><br>{$safeMessage}</p>"
        . "<hr>"
        . "<p><strong>Submitted at:</strong> {$safeSubmittedAt}<br>"
        . "<strong>Client IP:</strong> {$safeClientIp}</p>";

    try {
        $mail->isSMTP();
        $mail->Host = (string)$config['SMTP_HOST'];
        $mail->Port = (int)$config['SMTP_PORT'];
        $mail->SMTPAuth = true;
        $mail->Username = (string)$config['SMTP_USERNAME'];
        $mail->Password = (string)$config['SMTP_PASSWORD'];
        $mail->SMTPSecure = (string)$config['SMTP_ENCRYPTION'];

        $mail->setFrom((string)$config['MAIL_FROM_EMAIL'], (string)$config['MAIL_FROM_NAME']);
        $mail->addAddress((string)$config['CONTACT_TO_EMAIL']);
        $mail->addReplyTo((string)$payload['email'], (string)$payload['name']);

        $mail->Subject = $subject;
        $mail->Body = $bodyHtml;
        $mail->AltBody = $bodyText;
        $mail->isHTML(true);

        $mail->send();

        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
