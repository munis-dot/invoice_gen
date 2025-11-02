<?php
require_once __DIR__ . '/../services/MailService.php';

$router->add('POST', '/api/mail', function () {
    $mailService = new MailService();

    if (!empty($_FILES['file'])) {
        // Handle form-data input (Postman file upload)
        $email = trim($_POST['mail'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $file = $_FILES['file'];
        $result = $mailService->sendEmail($email, $subject, $body, $file);
    } else {
        // Handle JSON input
        $data = json_decode(file_get_contents("php://input"), true) ?? [];
        $email = trim($data['mail'] ?? '');
        $subject = trim($data['subject'] ?? '');
        $body = trim($data['body'] ?? '');
        $result = $mailService->sendEmail($email, $subject, $body);
    }

    header('Content-Type: application/json');
    echo json_encode($result);
});
