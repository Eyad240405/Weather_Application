<?php
// DB_Ops.php - Database Operations with Validation

require_once 'config.php';

class Database {
    private $host     = DB_HOST;
    private $db_name  = DB_NAME;
    private $user     = DB_USER;
    private $password = DB_PASSWORD;
    private $conn;

    public function connect() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4',
                $this->user,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database Connection Error: ' . $e->getMessage()
            ];
        }
        return $this->conn;
    }

    // ── Validation ──────────────────────────────────────────────────────────

    private function validateClothingInput($name, $category, $season, $description = '') {
        $errors = [];

        if (empty(trim($name))) {
            $errors[] = 'Item name is required.';
        } elseif (strlen($name) > 255) {
            $errors[] = 'Name too long. Maximum 255 characters.';
        }

        if (!in_array($category, CLOTHING_CATEGORIES)) {
            $errors[] = 'Invalid category.';
        }

        if (!in_array($season, CLOTHING_SEASONS)) {
            $errors[] = 'Invalid season.';
        }

        if (strlen($description) > 255) {
            $errors[] = 'Description too long. Maximum 255 characters.';
        }

        return $errors;
    }

    // ── Clothing CRUD ────────────────────────────────────────────────────────

    public function addClothing($name, $category, $season, $imagePath = null, $description = '', $issuggested = 0) {
        $errors = $this->validateClothingInput($name, $category, $season, $description);
        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        $conn = $this->connect();
        if (!is_object($conn)) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }

        // Derive emoji and gradients from category
        $categoryEmojis  = CATEGORY_EMOJIS;
        $emoji = $categoryEmojis[$category] ?? '👕';

        // Gradient presets per category
        $gradientMap = [
            'Tops'        => ['from-sky-100',    'to-blue-200'],
            'Bottoms'     => ['from-amber-100',  'to-orange-200'],
            'Dresses'     => ['from-violet-100', 'to-purple-200'],
            'Outerwear'   => ['from-slate-100',  'to-zinc-200'],
            'Footwear'    => ['from-rose-100',   'to-pink-200'],
            'Accessories' => ['from-teal-100',   'to-cyan-200'],
        ];
        $gradient_from = $gradientMap[$category][0] ?? 'from-sky-100';
        $gradient_to   = $gradientMap[$category][1] ?? 'to-blue-200';

        try {
            $stmt = $conn->prepare(
                "INSERT INTO clothing (name, category, season, emoji, gradient_from, gradient_to, image_path, description, is_suggested, created_at)
                 VALUES (:name, :category, :season, :emoji, :gradient_from, :gradient_to, :image_path, :description, :is_suggested, NOW())"
            );
            $stmt->execute([
                ':name'          => trim($name),
                ':category'      => $category,
                ':season'        => $season,
                ':emoji'         => $emoji,
                ':gradient_from' => $gradient_from,
                ':gradient_to'   => $gradient_to,
                ':image_path'    => $imagePath,
                ':description'   => $description,
                ':is_suggested'  => (int) $issuggested,
            ]);
            return [
                'success' => true,
                'message' => 'Clothing item added successfully!',
                'id'      => $conn->lastInsertId()
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error adding clothing: ' . $e->getMessage()];
        }
    }

    public function getClothing($filter = null) {
        $conn = $this->connect();
        if (!is_object($conn)) {
            return ['success' => false, 'message' => 'Database connection failed', 'items' => []];
        }

        try {
            $query = "SELECT * FROM clothing WHERE 1=1";
            $params = [];

            // $filter can be 'all', a season name, or null (= all)
            if ($filter && $filter !== 'all' && in_array($filter, CLOTHING_SEASONS)) {
                $query .= " AND season = :season";
                $params[':season'] = $filter;
            }

            $query .= " ORDER BY created_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'items' => $items];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error fetching clothing: ' . $e->getMessage(), 'items' => []];
        }
    }

    public function getSuggestedOutfits() {
        $conn = $this->connect();
        if (!is_object($conn)) {
            return ['success' => false, 'message' => 'Database connection failed', 'items' => []];
        }

        try {
            $stmt = $conn->prepare("SELECT * FROM clothing WHERE is_suggested = 1 ORDER BY created_at ASC");
            $stmt->execute();
            return ['success' => true, 'items' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error fetching suggestions: ' . $e->getMessage(), 'items' => []];
        }
    }

    public function updateClothing($id, $name, $category, $season, $description = '') {
        if (!is_numeric($id) || $id <= 0) {
            return ['success' => false, 'message' => 'Invalid clothing ID'];
        }

        $errors = $this->validateClothingInput($name, $category, $season, $description);
        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        $conn = $this->connect();
        if (!is_object($conn)) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }

        // Refresh emoji and gradients on update
        $categoryEmojis = CATEGORY_EMOJIS;
        $emoji = $categoryEmojis[$category] ?? '👕';
        $gradientMap = [
            'Tops'        => ['from-sky-100',    'to-blue-200'],
            'Bottoms'     => ['from-amber-100',  'to-orange-200'],
            'Dresses'     => ['from-violet-100', 'to-purple-200'],
            'Outerwear'   => ['from-slate-100',  'to-zinc-200'],
            'Footwear'    => ['from-rose-100',   'to-pink-200'],
            'Accessories' => ['from-teal-100',   'to-cyan-200'],
        ];
        $gradient_from = $gradientMap[$category][0] ?? 'from-sky-100';
        $gradient_to   = $gradientMap[$category][1] ?? 'to-blue-200';

        try {
            $stmt = $conn->prepare(
                "UPDATE clothing SET name=:name, category=:category, season=:season,
                 emoji=:emoji, gradient_from=:gradient_from, gradient_to=:gradient_to,
                 description=:description WHERE id=:id"
            );
            $stmt->execute([
                ':name'          => trim($name),
                ':category'      => $category,
                ':season'        => $season,
                ':emoji'         => $emoji,
                ':gradient_from' => $gradient_from,
                ':gradient_to'   => $gradient_to,
                ':description'   => $description,
                ':id'            => (int) $id,
            ]);
            return ['success' => true, 'message' => 'Clothing item updated successfully!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error updating clothing: ' . $e->getMessage()];
        }
    }

    public function deleteClothing($id) {
        if (!is_numeric($id) || $id <= 0) {
            return ['success' => false, 'message' => 'Invalid clothing ID'];
        }

        $conn = $this->connect();
        if (!is_object($conn)) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }

        try {
            // Fetch image_path so we can clean up the file
            $stmt = $conn->prepare("SELECT image_path FROM clothing WHERE id = :id");
            $stmt->execute([':id' => (int) $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && $result['image_path'] && file_exists($result['image_path'])) {
                unlink($result['image_path']);
            }

            $stmt = $conn->prepare("DELETE FROM clothing WHERE id = :id");
            $stmt->execute([':id' => (int) $id]);
            return ['success' => true, 'message' => 'Clothing item deleted successfully!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error deleting clothing: ' . $e->getMessage()];
        }
    }

    // ── Weather ──────────────────────────────────────────────────────────────

    public function getWeatherData() {
        $conn = $this->connect();
        if (!is_object($conn)) {
            return ['success' => false, 'message' => 'Database connection failed', 'data' => DEFAULT_WEATHER];
        }

        try {
            $stmt = $conn->prepare("SELECT * FROM weather ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                return ['success' => true, 'data' => $row];
            }
            // Table empty — return default seed
            return ['success' => true, 'data' => DEFAULT_WEATHER];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error fetching weather: ' . $e->getMessage(), 'data' => DEFAULT_WEATHER];
        }
    }

    public function updateWeatherData($city, $country, $temperature, $condition_text, $emoji, $humidity, $wind_speed, $uv_index) {
        $conn = $this->connect();
        if (!is_object($conn)) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }

        try {
            // Upsert: update row 1 if exists, else insert
            $stmt = $conn->prepare("SELECT id FROM weather LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $stmt = $conn->prepare(
                    "UPDATE weather SET city=:city, country=:country, temperature=:temperature,
                     condition_text=:condition_text, emoji=:emoji, humidity=:humidity,
                     wind_speed=:wind_speed, uv_index=:uv_index WHERE id=:id"
                );
                $stmt->execute([
                    ':city'           => $city,
                    ':country'        => $country,
                    ':temperature'    => (int) $temperature,
                    ':condition_text' => $condition_text,
                    ':emoji'          => $emoji,
                    ':humidity'       => (int) $humidity,
                    ':wind_speed'     => (int) $wind_speed,
                    ':uv_index'       => $uv_index,
                    ':id'             => (int) $row['id'],
                ]);
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO weather (city, country, temperature, condition_text, emoji, humidity, wind_speed, uv_index)
                     VALUES (:city, :country, :temperature, :condition_text, :emoji, :humidity, :wind_speed, :uv_index)"
                );
                $stmt->execute([
                    ':city'           => $city,
                    ':country'        => $country,
                    ':temperature'    => (int) $temperature,
                    ':condition_text' => $condition_text,
                    ':emoji'          => $emoji,
                    ':humidity'       => (int) $humidity,
                    ':wind_speed'     => (int) $wind_speed,
                    ':uv_index'       => $uv_index,
                ]);
            }

            return ['success' => true, 'message' => 'Weather updated successfully!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error updating weather: ' . $e->getMessage()];
        }
    }
}

// ── HTTP handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
    header('Content-Type: application/json');

    $db     = new Database();
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'add':
            $name        = $_POST['name']        ?? '';
            $category    = $_POST['category']    ?? '';
            $season      = $_POST['season']      ?? '';
            $image_path  = $_POST['image_path']  ?? null;
            $description = $_POST['description'] ?? '';
            echo json_encode($db->addClothing($name, $category, $season, $image_path, $description));
            break;

        case 'get':
            $filter = $_GET['filter'] ?? $_POST['filter'] ?? null;
            echo json_encode($db->getClothing($filter));
            break;

        case 'getSuggested':
            echo json_encode($db->getSuggestedOutfits());
            break;

        case 'update':
            $id          = $_POST['id']          ?? 0;
            $name        = $_POST['name']        ?? '';
            $category    = $_POST['category']    ?? '';
            $season      = $_POST['season']      ?? '';
            $description = $_POST['description'] ?? '';
            echo json_encode($db->updateClothing($id, $name, $category, $season, $description));
            break;

        case 'delete':
            $id = $_POST['id'] ?? 0;
            echo json_encode($db->deleteClothing($id));
            break;

        case 'getWeather':
            echo json_encode($db->getWeatherData());
            break;

        case 'updateWeather':
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            echo json_encode($db->updateWeatherData(
                $input['city']           ?? 'Cairo',
                $input['country']        ?? 'Egypt',
                $input['temperature']    ?? 28,
                $input['condition_text'] ?? 'Sunny & Clear',
                $input['emoji']          ?? '☀️',
                $input['humidity']       ?? 42,
                $input['wind_speed']     ?? 14,
                $input['uv_index']       ?? 'High'
            ));
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
?>
