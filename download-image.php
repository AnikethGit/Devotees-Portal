<?php
// download-image.php - Force-download card images

require_once 'config.php';
require_once 'functions.php';
require_once 'session.php';

// Whitelist of allowed images and their download filenames
$allowed = [
    'rules-1.png' => 'Parayanam-Card-Front.png',
    'rules-2.png' => 'Parayanam-Card-Back.png',
];

$file = $_GET['file'] ?? '';

if (!isset($allowed[$file])) {
    http_response_code(404);
    exit('File not found');
}

$filepath = __DIR__ . '/images/' . $file;
$download_name = $allowed[$file];

if (!file_exists($filepath)) {
    http_response_code(404);
    exit('File not found');
}

header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="' . $download_name . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-cache');
readfile($filepath);
exit;
?>
