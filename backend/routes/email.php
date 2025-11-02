<?php
//test route to send email
require_once __DIR__ . '/../services/MailService.php';

$router->add('POST', '/api/mail', function () {
    $data = json_decode(file_get_contents("php://input"), true) ?? [];
    $email = trim($data['mail'] ?? '');
    $subject = trim($data['subject'] ?? '');
    $body = trim($data['body'] ?? '');
    (new MailService())->sendEmail($email,$subject,$body);
    
});


