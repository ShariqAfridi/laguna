<?php
// app/Helpers/mail_helper.php — Centralized Mail Helper for Laguna Vibe

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\POP3;
use PHPMailer\PHPMailer\Exception;

if (!function_exists('send_mail')) {
    /**
     * Send email via configured SMTP settings with robust fallback, SSL handling, and logging.
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $htmlBody HTML content
     * @param array $debugLog Optional array reference to capture debug log
     * @return bool True if sent successfully, false otherwise
     */
    function send_mail(string $to, string $subject, string $htmlBody, array &$debugLog = []): bool {
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            if (file_exists(dirname(__DIR__, 2) . '/vendor/autoload.php')) {
                require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
            }
        }

        $mailer     = env('MAIL_MAILER', 'smtp');
        $host       = env('MAIL_HOST', 'mail.lagunavibe.com');
        $pop3Host   = env('POP3_HOST', 'mail.lagunavibe.com');
        $port       = (int) env('MAIL_PORT', 465);
        $username   = env('MAIL_USERNAME', 'noreply@lagunavibe.com');
        $password   = env('MAIL_PASSWORD', '=xQHc%KEN3!@ol96');
        $encryption = env('MAIL_ENCRYPTION', 'ssl');
        $fromEmail  = env('MAIL_FROM_ADDRESS', 'orders@lagunavibe.com');
        $fromName   = env('MAIL_FROM_NAME', 'Laguna Vibe');

        // Resend API Handler (Fastest REST API delivery)
        if (str_starts_with($password, 're_') || str_contains($host, 'resend')) {
            $senderEmail = !empty($fromEmail) ? $fromEmail : 'orders@lagunavibe.com';
            $payload = [
                'from'     => "{$fromName} <{$senderEmail}>",
                'to'       => [$to],
                'subject'  => $subject,
                'html'     => $htmlBody,
                'reply_to' => 'orders@lagunavibe.com'
            ];

            $ch = curl_init('https://api.resend.com/emails');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $password,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $debugLog[] = "Resend API HTTP {$httpCode}: {$response}";
            if ($httpCode >= 200 && $httpCode < 300) {
                return true;
            }

            // Fallback during DNS propagation
            if ($httpCode === 403 && $senderEmail !== 'onboarding@resend.dev') {
                $payload['from'] = "{$fromName} <onboarding@resend.dev>";
                $ch2 = curl_init('https://api.resend.com/emails');
                curl_setopt($ch2, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $password,
                    'Content-Type: application/json'
                ]);
                curl_setopt($ch2, CURLOPT_POST, true);
                curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                $res2 = curl_exec($ch2);
                $code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                curl_close($ch2);
                $debugLog[] = "Resend Fallback HTTP {$code2}: {$res2}";
                if ($code2 >= 200 && $code2 < 300) {
                    return true;
                }
            }
        }

        $mail = new PHPMailer(true);

        try {
            if (strtolower($mailer) === 'mail') {
                $mail->isMail();
            } else {
                $mail->isSMTP();
                $mail->Host       = $host;
                $mail->SMTPAuth   = !empty($username) && !empty($password);
                $mail->Username   = $username;
                $mail->Password   = $password;
                
                // SSL / TLS configuration
                if (strtolower($encryption) === 'ssl' || $port === 465) {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } elseif (strtolower($encryption) === 'tls' || $port === 587) {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                } else {
                    $mail->SMTPAutoTLS = false;
                }

                // Stream SSL options for maximum compatibility across environments
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true
                    ]
                ];

                $mail->Port       = $port;
                $mail->Timeout    = 8;
            }
            $mail->CharSet = 'UTF-8';

            // Debug output capture if requested
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function($str, $level) use (&$debugLog) {
                $debugLog[] = trim($str);
            };

            // SetFrom uses username for SMTP authentication, with brand name and Reply-To header
            $mail->setFrom($username, $fromName);
            if (!empty($fromEmail) && $fromEmail !== $username) {
                $mail->addReplyTo($fromEmail, $fromName);
            }
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</tr>', '</p>'], "\n", $htmlBody));

            $mail->send();
            return true;
        } catch (Exception $e) {
            $errorMsg = "Mail send error to {$to} via {$host}:{$port} — " . $e->getMessage();
            error_log($errorMsg);
            $debugLog[] = "EXCEPTION: " . $errorMsg;
            return false;
        }
    }
}

// Global legacy alias
if (!function_exists('sendMail')) {
    function sendMail(string $to, string $subject, string $htmlBody): bool {
        $dummy = [];
        return send_mail($to, $subject, $htmlBody, $dummy);
    }
}
