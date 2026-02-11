<?php
/**
 * Contact form handler — saves lead to DB first, then attempts email.
 */

require_once __DIR__ . '/database.php';

function handle_contact_form(): void {
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Invalid request.']);
        return;
    }

    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

    if (!$name || !$email || !$subject || !$message) {
        echo json_encode(['success' => false, 'error' => 'All fields are required.']);
        return;
    }

    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $ip = explode(',', $ip)[0];
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // ALWAYS save to DB first
    $pdo = db();
    $stmt = $pdo->prepare("INSERT INTO leads (name, email, subject, message, ip_address, user_agent, status)
        VALUES (:name, :email, :subject, :message, :ip, :ua, 'new')");
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':subject' => $subject,
        ':message' => $message,
        ':ip' => $ip,
        ':ua' => $userAgent,
    ]);
    $leadId = $pdo->lastInsertId();

    // Attempt email notification
    $emailSent = false;
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';

    if (file_exists($autoload)) {
        require_once $autoload;

        // Get SMTP config from DB settings
        $smtpHost = get_smtp_setting('smtp_host');
        $smtpUser = get_smtp_setting('smtp_user');
        $smtpPass = get_smtp_setting('smtp_pass');
        $smtpPort = (int)(get_smtp_setting('smtp_port') ?: 587);
        $smtpFrom = get_smtp_setting('smtp_from') ?: 'noreply@cdemsolutions.com';
        $smtpFromName = get_smtp_setting('smtp_from_name') ?: 'CDEM Solutions';
        $contactTo = get_smtp_setting('contact_to') ?: 'hello@cdemsolutions.com';

        if ($smtpHost && $smtpUser && $smtpPass) {
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $smtpHost;
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUser;
                $mail->Password = $smtpPass;
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = $smtpPort;
                $mail->CharSet = 'UTF-8';

                $mail->setFrom($smtpFrom, $smtpFromName);
                $mail->addAddress($contactTo);
                $mail->addReplyTo($email, $name);

                $mail->isHTML(false);
                $mail->Subject = "Contact Form: $subject";
                $mail->Body = "Name: $name\nEmail: $email\nSubject: $subject\n\n$message";

                $mail->send();
                $emailSent = true;
            } catch (\Exception $e) {
                // Email failed, but lead is saved — that's OK
            }
        } else {
            // Try server-side config file as fallback
            $configFile = dirname(__DIR__) . '/config.php';
            if (file_exists($configFile)) {
                $config = require $configFile;
                try {
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = $config['smtp_host'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $config['smtp_user'];
                    $mail->Password = $config['smtp_pass'];
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = $config['smtp_port'];
                    $mail->CharSet = 'UTF-8';

                    $mail->setFrom($config['smtp_from'], $config['smtp_from_name']);
                    $mail->addAddress($config['contact_to']);
                    $mail->addReplyTo($email, $name);

                    $mail->isHTML(false);
                    $mail->Subject = "Contact Form: $subject";
                    $mail->Body = "Name: $name\nEmail: $email\nSubject: $subject\n\n$message";

                    $mail->send();
                    $emailSent = true;
                } catch (\Exception $e) {
                    // fallback also failed
                }
            }
        }
    }

    // Update email_sent status
    if ($emailSent) {
        $stmt = $pdo->prepare("UPDATE leads SET email_sent = 1 WHERE id = :id");
        $stmt->execute([':id' => $leadId]);
    }

    echo json_encode(['success' => true]);
}

/**
 * Get SMTP setting from DB.
 */
function get_smtp_setting(string $key): ?string {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT setting_value FROM admin_settings WHERE setting_key = :key');
    $stmt->execute([':key' => $key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : null;
}
