<?php
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService {

    public function sendEmail($senderMail, $subject, $body, $file = null) {
        $config = parse_ini_file('../config.ini', true);

        $mailUsername = $config['SMTP']['user'];
        $mailPassword = $config['SMTP']['pass'];

        $mail = new PHPMailer(true);

        try {
            // SMTP Config
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $mailUsername;
            $mail->Password   = $mailPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom($mailUsername, 'INVOICE GENERATOR');
            $mail->addAddress($senderMail);

            // Optional file stream attachment
            if ($file && isset($file['tmp_name']) ) {
                $mail->addAttachment(
                    $file['tmp_name'],
                    $file['name'] ?? 'attachment'
                );
            }

            // Mail content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = "<h3>{$body}</h3>";
            $mail->AltBody = strip_tags($body);

            $mail->send();
            return ['status' => 'success', 'message' => 'Mail sent successfully'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $mail->ErrorInfo];
        }
    }
}
