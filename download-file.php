<?php
// download-file.php - Force-download user-accessible files

require_once 'config.php';
require_once 'functions.php';
require_once 'session.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    http_response_code(400);
    exit('Invalid request');
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM downloads WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $id);
$stmt->execute();
$file = $stmt->get_result()->fetch_assoc();

if (!$file) {
    http_response_code(404);
    exit('File not found');
}

$filepath = $file['file_path'];

if (!file_exists($filepath)) {
    http_response_code(404);
    exit('File not found on server');
}

$mime_types = [
    'pdf'  => 'application/pdf',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];

$mime = $mime_types[strtolower($file['file_type'])] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $file['original_filename'] . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-cache');
readfile($filepath);
exit;
?>
