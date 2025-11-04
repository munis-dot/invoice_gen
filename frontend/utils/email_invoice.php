<?php
// Temporarily save the uploaded PDF
if (empty($_FILES['pdf'])) {
    echo json_encode(['status' => 'error', 'message' => 'No PDF received', 'files' => $_FILES]);
    exit;
}

$uploadDir = sys_get_temp_dir();
$tempPdfPath = $uploadDir . '/temp_invoice_' . uniqid() . '.pdf';

if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $tempPdfPath)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to save temporary PDF',
        'error_code' => $_FILES['pdf']['error']
    ]);
    exit;
}

echo json_encode( $_FILES['pdf']);
// Now prepare the file array for MailService (mimics $_FILES structure)
$file = [
    'tmp_name' => $tempPdfPath,
    'name' => 'invoice.pdf'
];

// Load and use MailService
require_once './MailService.php'; // Adjust path if needed (e.g., to the class file)
$mailService = new MailService();

$recipientEmail = $_POST['email'] ?? '';
$subject = $_POST['subject'] ?? 'Invoice';
$body = $_POST['body'] ?? 'Please find the attached invoice PDF.';

if (empty($recipientEmail)) {
    echo json_encode(['status' => 'error', 'message' => 'Recipient email is required']);
    unlink($tempPdfPath); // Cleanup
    exit;
}
echo json_encode($file);
$result = $mailService->sendEmail($recipientEmail, $subject, $body, $file);

// Cleanup temp file
unlink($tempPdfPath);

echo json_encode($result);
?>