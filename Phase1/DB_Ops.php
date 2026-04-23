<?php

$host = 'localhost';
$db   = 'weather_outfit_db';
$user = 'root';
$pass = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    die(json_encode(["error" => "Failed"])); 
}

function addClothing($name, $category, $season, $path) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO clothing_items (item_name, category, season, image_path) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$name, $category, $season, $path]);
}

function getClothes($season = 'all') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM clothing_items WHERE season = ? OR season = 'all'");
    $stmt->execute([$season]);
    return $stmt->fetchAll();
}

function deleteClothing($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM clothing_items WHERE id = ?");
    return $stmt->execute([(int)$id]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    try {
        if ($_POST['action'] === 'fetch') {
            echo json_encode(getClothes($_POST['season'] ?? 'all'));
        } elseif ($_POST['action'] === 'delete') {
            $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
            deleteClothing($id);
            echo json_encode(["status" => "success"]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error"]);
    }
    exit;
}
