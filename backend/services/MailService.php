<?php
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService{
//put all funtianllit inside function and get sender mail, subject,body from input and return success or error
function sendEmail($senderMail, $subject, $body) {
$config = parse_ini_file('../config.ini', true);

//get from env file
$mailUsername = $config['SMTP']['user'];
$mailPassword = $config['SMTP']['pass'];
$mail = new PHPMailer(true);
try {
    // Server settings
    $mail->isSMTP();                                     
    $mail->Host       = 'smtp.gmail.com';     // SMTP server
    $mail->SMTPAuth   = true;                                
    $mail->Username   = $mailUsername; // SMTP username
    $mail->Password   = $mailPassword;   // Use App Password if Gmail
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Encryption (TLS)
    $mail->Port       = 587;                              
    echo "user name". $mailUsername;
    echo "Password : ". $mailPassword;
    // Recipients
    $mail->setFrom($mailUsername, 'INOVICE GENERATOR');
    $mail->addAddress($senderMail, 'Recipient');  

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = '<h3>'.$body.'</h3>';
    $mail->AltBody = 'This is a plain text version of the email body.';

    $mail->send();
    echo '✅ Message sent successfully!';
} catch (Exception $e) {
    echo "❌ Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}

}
}