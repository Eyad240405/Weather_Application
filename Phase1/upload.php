<?php
header('Content-Type: application/json');
require_once 'config.php';

$response = ['success' => false, 'message' => 'Invalid request', 'path' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode($response);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the upload.',
    ];
    $code = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    $response['message'] = $uploadErrors[$code] ?? 'Unknown upload error.';
    echo json_encode($response);
    exit;
}

$file = $_FILES['file'];

// Validate MIME type
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, ALLOWED_MIME)) {
    $response['message'] = 'Invalid file type. Only PNG, JPG, GIF, WEBP allowed.';
    echo json_encode($response);
    exit;
}

// Validate size
if ($file['size'] > MAX_UPLOAD_SIZE) {
    $response['message'] = 'File too large. Maximum 8 MB allowed.';
    echo json_encode($response);
    exit;
}

// Validate it is a real image
if (!getimagesize($file['tmp_name'])) {
    $response['message'] = 'Uploaded file is not a valid image.';
    echo json_encode($response);
    exit;
}

// Create uploads directory if needed
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

$ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = 'clothing_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$filepath = UPLOAD_DIR . $filename;

if (move_uploaded_file($file['tmp_name'], $filepath)) {
    $response = ['success' => true, 'message' => 'Uploaded successfully!', 'path' => $filepath];
} else {
    $response['message'] = 'Failed to save file. Check server permissions.';
}

echo json_encode($response);
