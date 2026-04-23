<?php

/**

 * Upload.php - Handles file upload processing and DB entry.

 */

require_once 'DB_Ops.php';



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['clothing_img'])) {

    header('Content-Type: application/json');

    

    $targetDir = "uploads/";

    $fileName = time() . '_' . basename($_FILES["clothing_img"]["name"]);

    $targetFilePath = $targetDir . $fileName;

    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));



    // Validate type and size on server side [cite: 58, 86]

    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($fileType, $allowed) && $_FILES["clothing_img"]["size"] < 2000000) { // 2MB limit

        if (move_uploaded_file($_FILES["clothing_img"]["tmp_name"], $targetFilePath)) {

            // Use prepared statement to save file path [cite: 57, 75]

            addClothing(htmlspecialchars($_POST['item_name']), $_POST['category'], $_POST['season'], $targetFilePath);

            echo json_encode(["status" => "success", "message" => "Item uploaded"]);

        } else {

            echo json_encode(["error" => "Upload failed"]);

        }

    } else {

        echo json_encode(["error" => "Invalid file or size"]);

    }

    exit;

} remove all comments and useless piece of code but keep code the same

PHP
<?php
require_once 'DB_Ops.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['clothing_img'])) {
    header('Content-Type: application/json');
    
    $targetDir = "uploads/";
    $fileName = time() . '_' . basename($_FILES["clothing_img"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($fileType, $allowed) && $_FILES["clothing_img"]["size"] < 2000000) {
        if (move_uploaded_file($_FILES["clothing_img"]["tmp_name"], $targetFilePath)) {
            addClothing(htmlspecialchars($_POST['item_name']), $_POST['category'], $_POST['season'], $targetFilePath);
            echo json_encode(["status" => "success", "message" => "Item uploaded"]);
        } else {
            echo json_encode(["error" => "Upload failed"]);
        }
    } else {
        echo json_encode(["error" => "Invalid file or size"]);
    }
    exit;
}
