<?php
// Upload.php - File Upload Handling

header('Content-Type: application/json');

require_once 'config.php';

$response = ['success' => false, 'message' => 'Invalid request', 'path' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode($response);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'File upload failed. Please try again.';
    echo json_encode($response);
    exit;
}

$file = $_FILES['file'];

// Validate type
if (!in_array($file['type'], ALLOWED_FILE_TYPES)) {
    $response['message'] = 'Invalid file type. Only PNG, JPG, WEBP images allowed.';
    echo json_encode($response);
    exit;
}

// Validate size (8MB — matches HTML "up to 8MB")
if ($file['size'] > MAX_UPLOAD_SIZE) {
    $response['message'] = 'File too large. Maximum 8MB allowed.';
    echo json_encode($response);
    exit;
}

// Create uploads directory if needed
$upload_dir = UPLOAD_DIR;
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Unique filename
$file_ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$file_name = 'clothing_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $file_ext;
$file_path = $upload_dir . $file_name;

if (move_uploaded_file($file['tmp_name'], $file_path)) {
    $response['success'] = true;
    $response['message'] = 'File uploaded successfully!';
    $response['path']    = $file_path;
} else {
    $response['message'] = 'Failed to save file. Please check server permissions.';
}

echo json_encode($response);
?>
