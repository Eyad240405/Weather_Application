<?php
require_once 'DB_Ops.php';

header('Content-Type: application/json');

if (!isset($_GET['season'])) {
    echo json_encode(["error" => "No season provided"]);
    exit;
}

$season = htmlspecialchars($_GET['season']);

try {
    $clothes = getClothes($season);

    echo json_encode([
        "status" => "success",
        "season" => $season,
        "data" => $clothes
    ]);

} catch (Exception $e) {
    echo json_encode(["error" => "Something went wrong"]);
}
?>