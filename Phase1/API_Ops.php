<?php
// API_Ops.php - Third-party API Calls using CURL

header('Content-Type: application/json');

require_once 'config.php';

class WeatherAPI {
    private $api_key  = '';
    private $base_url = 'https://api.openweathermap.org/data/2.5/weather';
    private $city     = 'Cairo';   // Default matches HTML
    private $units    = 'metric';

    public function __construct($city = 'Cairo') {
        $this->city    = $city;
        $this->api_key = defined('WEATHER_API_KEY') ? WEATHER_API_KEY : (getenv('WEATHER_API_KEY') ?: '');
    }

    /**
     * Fetch weather data using CURL.
     * Falls back to the HTML-matching default when no API key is configured.
     */
    public function getWeather() {
        if (empty($this->api_key)) {
            return [
                'success'  => false,
                'message'  => 'Weather API not configured. Showing default data.',
                'fallback' => true,
                'data'     => $this->getFallbackWeather()
            ];
        }

        try {
            $url = $this->base_url
                 . '?q='     . urlencode($this->city)
                 . '&units=' . $this->units
                 . '&appid=' . $this->api_key;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,            $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT,        10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response  = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code !== 200) {
                return [
                    'success'  => false,
                    'message'  => 'Failed to fetch weather data (HTTP ' . $http_code . ')',
                    'fallback' => true,
                    'data'     => $this->getFallbackWeather()
                ];
            }

            $data = json_decode($response, true);

            // Build condition text and emoji
            $condition = $data['weather'][0]['main'];
            $desc      = ucfirst($data['weather'][0]['description']);
            $emoji     = $this->conditionToEmoji($condition, $data['weather'][0]['icon']);
            $uvIndex   = $this->tempToUvIndex($data['main']['temp']);

            return [
                'success' => true,
                'data' => [
                    'city'           => $data['name'],
                    'country'        => $data['sys']['country'] ?? '',
                    'temperature'    => (int) round($data['main']['temp']),
                    'condition_text' => $condition . ' & ' . $desc,
                    'emoji'          => $emoji,
                    'humidity'       => (int) $data['main']['humidity'],
                    'wind_speed'     => (int) round($data['wind']['speed'] * 3.6), // m/s → km/h
                    'uv_index'       => $uvIndex,
                    'icon'           => $data['weather'][0]['icon'],
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
     * Fallback weather — matches the HTML exactly:
     * 28°, Sunny & Clear, Cairo Egypt, 42% humidity, 14 km/h wind, UV High
     */
    private function getFallbackWeather() {
        return DEFAULT_WEATHER;
    }

    private function conditionToEmoji($condition, $icon = '') {
        $map = [
            'Clear'       => '☀️',
            'Clouds'      => '⛅',
            'Rain'        => '🌧️',
            'Drizzle'     => '🌦️',
            'Thunderstorm'=> '⛈️',
            'Snow'        => '❄️',
            'Mist'        => '🌫️',
            'Fog'         => '🌫️',
            'Haze'        => '🌫️',
            'Dust'        => '🌪️',
            'Sand'        => '🌪️',
            'Tornado'     => '🌪️',
        ];
        // Night icons
        if (str_ends_with($icon, 'n') && $condition === 'Clear') return '🌙';
        return $map[$condition] ?? '🌡️';
    }

    private function tempToUvIndex($temp) {
        if ($temp >= 30) return 'Very High';
        if ($temp >= 25) return 'High';
        if ($temp >= 18) return 'Moderate';
        return 'Low';
    }

    /**
     * Suggest outfit based on weather and available clothing.
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
                'temperature'    => $temp,
                'condition'      => $weather_data['condition_text'] ?? '',
                'suggested_seasons' => $suggested_seasons,
                'message'        => $message,
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
            $d['city'], $d['country'], $d['temperature'], $d['condition_text'],
            $d['emoji'], $d['humidity'], $d['wind_speed'], $d['uv_index']
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
