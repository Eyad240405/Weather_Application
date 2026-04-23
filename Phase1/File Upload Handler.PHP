<?php
header('Content-Type: application/json');

$response = [
    "success" => false,
    "message" => "",
    "image_path" => ""
];

$uploadDir = __DIR__ . '/uploads/';
$relativeUploadDir = 'uploads/';
$maxFileSize = 2 * 1024 * 1024; // 2MB
$allowedExtensions = ['jpg', 'jpeg', 'png'];
$allowedMimeTypes = ['image/jpeg', 'image/png'];

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        $response["message"] = "Failed to create upload directory.";
        echo json_encode($response);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response["message"] = "Invalid request method. Use POST.";
    echo json_encode($response);
    exit;
}

if (!isset($_FILES['image'])) {
    $response["message"] = "No file uploaded.";
    echo json_encode($response);
    exit;
}

$file = $_FILES['image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => "The uploaded file exceeds the server upload limit.",
        UPLOAD_ERR_FORM_SIZE  => "The uploaded file exceeds the form upload limit.",
        UPLOAD_ERR_PARTIAL    => "The file was only partially uploaded.",
        UPLOAD_ERR_NO_FILE    => "No file was uploaded.",
        UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder on server.",
        UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
        UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the file upload."
    ];

    $response["message"] = $uploadErrors[$file['error']] ?? "Unknown upload error.";
    echo json_encode($response);
    exit;
}

if ($file['size'] <= 0) {
    $response["message"] = "Uploaded file is empty.";
    echo json_encode($response);
    exit;
}

if ($file['size'] > $maxFileSize) {
    $response["message"] = "File is too large. Maximum allowed size is 2MB.";
    echo json_encode($response);
    exit;
}

$originalName = $file['name'];
$fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($fileExtension, $allowedExtensions)) {
    $response["message"] = "Invalid file extension. Only JPG, JPEG, and PNG are allowed.";
    echo json_encode($response);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedMimeTypes)) {
    $response["message"] = "Invalid file type. Only real image files are allowed.";
    echo json_encode($response);
    exit;
}

$imageInfo = getimagesize($file['tmp_name']);
if ($imageInfo === false) {
    $response["message"] = "Uploaded file is not a valid image.";
    echo json_encode($response);
    exit;
}

$uniqueName = uniqid('img_', true) . '.' . $fileExtension;
$destination = $uploadDir . $uniqueName;
$relativePath = $relativeUploadDir . $uniqueName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    $response["message"] = "Failed to save the uploaded image.";
    echo json_encode($response);
    exit;
}

$response["success"] = true;
$response["message"] = "Image uploaded successfully.";
$response["image_path"] = $relativePath;

echo json_encode($response);
exit;
?>
