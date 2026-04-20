<?php
// check city
if(!isset($_GET['city']) || empty($_GET['city'])){
    echo json_encode(["error"=>"please provide a city name"]);
    exit;
}

// cleaning the text
$city = htmlspecialchars($_GET['city']);
$api_key = "caa146fbf1074732bc9101147262004";
$apiUrl = "http://api.weatherapi.com/v1/current.json?key={$api_key}&q={$city}&aqi=no";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if(isset($data['current'])){
    $temp = $data['current']['temp_c'];
    $suggestion = ($temp > 25) ? "Summer" : "Winter";

    header('Content-Type: application/json');
    echo json_encode([
        "city" => $data['location']['name'],
        "temp" => $temp,
        "condition" => $data['current']['condition']['text'],
        "icon" => $data['current']['condition']['icon'],
        "suggestion" => $suggestion
    ]);
}
else {
    echo json_encode(["error" => "City not found"]);
}

?>