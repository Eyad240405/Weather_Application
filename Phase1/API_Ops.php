<?php
// API_Ops.php - Third-party API Calls using CURL

header('Content-Type: application/json');

require_once 'config.php';

class WeatherAPI {
    private $api_key  = '';
    private $base_url = 'http://api.weatherapi.com/v1/current.json';
    private $city     = 'Cairo';

    public function __construct($city = 'Cairo') {
        $this->city    = $city;
        $this->api_key = defined('WEATHER_API_KEY') ? WEATHER_API_KEY : (getenv('WEATHER_API_KEY') ?: '');
    }

    /**
     * Fetch weather data using CURL from WeatherAPI.com
     * Falls back to the HTML-matching default when no API key is configured.
     */
    public function getWeather() {
        if (empty($this->api_key)) {
            return [
                'success'  => false,
                'message'  => 'No API key configured.',
                'fallback' => true,
                'data'     => $this->getFallbackWeather()
            ];
        }

        try {
            $url = $this->base_url
                 . '?key=' . urlencode($this->api_key)
                 . '&q='   . urlencode($this->city)
                 . '&aqi=no';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);

            if ($response === false) {
                $curlError = curl_error($ch);
                curl_close($ch);

                return [
                    'success'  => false,
                    'message'  => 'cURL error: ' . $curlError,
                    'fallback' => true,
                    'data'     => $this->getFallbackWeather()
                ];
            }

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode($response, true);

            if ($http_code !== 200 || !isset($data['current']) || !isset($data['location'])) {
                $apiMessage = $data['error']['message'] ?? ('Failed to fetch weather data (HTTP ' . $http_code . ')');

                return [
                    'success'  => false,
                    'message'  => $apiMessage,
                    'fallback' => true,
                    'data'     => $this->getFallbackWeather()
                ];
            }

            $conditionText = $data['current']['condition']['text'] ?? 'Clear';
            $emoji         = $this->conditionToEmoji($conditionText);
            $uvIndex       = $this->uvNumberToText($data['current']['uv'] ?? 0);

            return [
                'success' => true,
                'data' => [
                    'city'           => $data['location']['name'] ?? $this->city,
                    'country'        => $data['location']['country'] ?? '',
                    'temperature'    => (int) round($data['current']['temp_c'] ?? 0),
                    'condition_text' => $conditionText,
                    'emoji'          => $emoji,
                    'humidity'       => (int) ($data['current']['humidity'] ?? 0),
                    'wind_speed'     => (int) round($data['current']['wind_kph'] ?? 0),
                    'uv_index'       => $uvIndex,
                    'icon'           => $data['current']['condition']['icon'] ?? '',
                ]
            ];
        } catch (Exception $e) {
            return [
                'success'  => false,
                'message'  => 'Error: ' . $e->getMessage(),
                'fallback' => true,
                'data'     => $this->getFallbackWeather()
            ];
        }
    }

    /**
     * Fallback weather — matches the HTML exactly
     */
    private function getFallbackWeather() {
        return DEFAULT_WEATHER;
    }

    /**
     * Convert WeatherAPI text condition into emoji
     */
    private function conditionToEmoji($conditionText) {
        $condition = strtolower($conditionText);

        if (str_contains($condition, 'sun') || str_contains($condition, 'clear')) return '☀️';
        if (str_contains($condition, 'cloud') || str_contains($condition, 'overcast')) return '⛅';
        if (str_contains($condition, 'rain')) return '🌧️';
        if (str_contains($condition, 'drizzle')) return '🌦️';
        if (str_contains($condition, 'thunder')) return '⛈️';
        if (str_contains($condition, 'snow') || str_contains($condition, 'sleet') || str_contains($condition, 'ice')) return '❄️';
        if (str_contains($condition, 'mist') || str_contains($condition, 'fog') || str_contains($condition, 'haze')) return '🌫️';
        if (str_contains($condition, 'wind')) return '🌬️';

        return '🌡️';
    }

    /**
     * Convert numeric UV to text
     */
    private function uvNumberToText($uv) {
        $uv = (float) $uv;

        if ($uv >= 11) return 'Extreme';
        if ($uv >= 8)  return 'Very High';
        if ($uv >= 6)  return 'High';
        if ($uv >= 3)  return 'Moderate';
        return 'Low';
    }

    /**
     * Suggest outfit based on weather and available clothing
     */
    public function suggestOutfit($weather_data, $available_clothing) {
        $temp      = $weather_data['temperature'];
        $condition = strtolower($weather_data['condition_text'] ?? $weather_data['condition'] ?? '');

        $suggested_seasons = [];
        $message = '';

        if ($temp >= 25) {
            $suggested_seasons[] = 'Summer';
            $message = '☀️ Hot weather! Light summer clothes recommended.';
        } elseif ($temp >= 15) {
            $suggested_seasons[] = 'Spring';
            $suggested_seasons[] = 'Autumn';
            $message = '🌸 Mild weather — spring or autumn layers work great.';
        } else {
            $suggested_seasons[] = 'Winter';
            $message = '❄️ Cold out there — bundle up with winter clothing!';
        }

        // Add "All Seasons" items always
        $suggested_seasons[] = 'All Seasons';

        if (str_contains($condition, 'rain'))  $message .= " Don't forget an umbrella! 🌧️";
        if (str_contains($condition, 'snow'))  $message .= ' Stay warm and safe! ⛄';
        if (str_contains($condition, 'cloud')) $message .= ' Dress in layers. 🌥️';

        return [
            'success' => true,
            'recommendation' => [
                'temperature'       => $temp,
                'condition'         => $weather_data['condition_text'] ?? '',
                'suggested_seasons' => $suggested_seasons,
                'message'           => $message,
            ]
        ];
    }
}

// ── HTTP handler ──────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

if ($action === 'getWeather') {
    $city = trim($_GET['city'] ?? 'Cairo');

    if (strlen($city) > 50 || !preg_match('/^[\p{L}\s\-\.]+$/u', $city)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid city name']);
        exit;
    }

    $api    = new WeatherAPI($city);
    $result = $api->getWeather();

    // If live fetch succeeded, also persist to DB
    if ($result['success'] && !($result['fallback'] ?? false)) {
        require_once 'DB_Ops.php';
        $db = new Database();
        $d  = $result['data'];
        $db->updateWeatherData(
            $d['city'],
            $d['country'],
            $d['temperature'],
            $d['condition_text'],
            $d['emoji'],
            $d['humidity'],
            $d['wind_speed'],
            $d['uv_index']
        );
    }

    echo json_encode($result);

} elseif ($action === 'suggestOutfit') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['weather_data']) || !isset($input['clothing'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    $api = new WeatherAPI();
    echo json_encode($api->suggestOutfit($input['weather_data'], $input['clothing']));

} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>