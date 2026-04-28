<?php
require_once 'config.php';

class Database {

    private $conn;

    private function connect() {
        if ($this->conn) return $this->conn;


        if (!in_array('mysql', PDO::getAvailableDrivers())) {
            throw new RuntimeException(
                'PDO MySQL driver is not installed or enabled. ' .
                'On Windows: open php.ini and uncomment "extension=pdo_mysql". ' .
                'On Linux: run "sudo apt install php-mysql" and restart Apache/PHP.'
            );
        }

        try {
            $this->conn = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER, DB_PASSWORD,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage());
        }
        return $this->conn;
    }

    // ── Validation ────────────────────────────────────────────────────────────

    private function validateClothing($name, $category, $season, $description = '') {
        $errors = [];
        if (empty(trim($name)))           $errors[] = 'Item name is required.';
        elseif (strlen($name) > 255)      $errors[] = 'Name too long (max 255 chars).';
        if (!in_array($category, CLOTHING_CATEGORIES)) $errors[] = 'Invalid category.';
        if (!in_array($season,   CLOTHING_SEASONS))    $errors[] = 'Invalid season.';
        if (strlen($description) > 255)   $errors[] = 'Description too long (max 255 chars).';
        return $errors;
    }

    // ── Clothing CRUD ─────────────────────────────────────────────────────────

    public function addClothing($name, $category, $season, $imagePath = null, $description = '', $isSuggested = 0) {
        $errors = $this->validateClothing($name, $category, $season, $description);
        if ($errors) return ['success' => false, 'message' => implode(' ', $errors)];

        $emojis    = CATEGORY_EMOJIS;
        $gradients = CATEGORY_GRADIENTS;
        $emoji         = $emojis[$category]       ?? '👕';
        $gradient_from = $gradients[$category][0] ?? 'from-sky-100';
        $gradient_to   = $gradients[$category][1] ?? 'to-blue-200';

        try {
            $conn = $this->connect();
            $stmt = $conn->prepare(
                "INSERT INTO clothing
                    (name, category, season, emoji, gradient_from, gradient_to, image_path, description, is_suggested)
                 VALUES
                    (:name, :category, :season, :emoji, :gradient_from, :gradient_to, :image_path, :description, :is_suggested)"
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
                ':is_suggested'  => (int) $isSuggested,
            ]);
            return ['success' => true, 'message' => 'Item added!', 'id' => $conn->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error adding item: ' . $e->getMessage()];
        }
    }

    public function getClothing($filter = null) {
        try {
            $conn   = $this->connect();
            $query  = "SELECT * FROM clothing WHERE 1=1";
            $params = [];
            if ($filter && $filter !== 'all' && in_array($filter, CLOTHING_SEASONS)) {
                $query          .= " AND season = :season";
                $params[':season'] = $filter;
            }
            $query .= " ORDER BY created_at DESC";
            $stmt   = $conn->prepare($query);
            $stmt->execute($params);
            return ['success' => true, 'items' => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error fetching items: ' . $e->getMessage(), 'items' => []];
        }
    }

    public function getSuggestedOutfits() {
        try {
            $stmt = $this->connect()->prepare(
                "SELECT * FROM clothing WHERE is_suggested = 1 ORDER BY created_at ASC"
            );
            $stmt->execute();
            return ['success' => true, 'items' => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'items' => []];
        }
    }

    /**
     * Update which items are flagged as suggested based on the current weather/season.
     * Called by API_Ops.php after a weather fetch so the PHP-bootstrapped page stays in sync.
     */
    public function updateSuggestedOutfits($seasons) {
        try {
            $conn = $this->connect();
            // Reset all suggested flags
            $conn->exec("UPDATE clothing SET is_suggested = 0");

            if (empty($seasons)) return ['success' => true];

            // Mark up to 4 items from the matching seasons as suggested (one per category where possible)
            $placeholders = implode(',', array_fill(0, count($seasons), '?'));
            $stmt = $conn->prepare(
                "SELECT id FROM clothing
                 WHERE season IN ($placeholders)
                 ORDER BY RAND()
                 LIMIT 4"
            );
            $stmt->execute(array_values($seasons));
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if ($ids) {
                $ph = implode(',', array_fill(0, count($ids), '?'));
                $conn->prepare("UPDATE clothing SET is_suggested = 1 WHERE id IN ($ph)")
                     ->execute($ids);
            }

            return ['success' => true, 'updated' => count($ids)];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateClothing($id, $name, $category, $season, $description = '') {
        if (!is_numeric($id) || $id <= 0) return ['success' => false, 'message' => 'Invalid ID'];
        $errors = $this->validateClothing($name, $category, $season, $description);
        if ($errors) return ['success' => false, 'message' => implode(' ', $errors)];

        $emojis    = CATEGORY_EMOJIS;
        $gradients = CATEGORY_GRADIENTS;
        $emoji         = $emojis[$category]       ?? '👕';
        $gradient_from = $gradients[$category][0] ?? 'from-sky-100';
        $gradient_to   = $gradients[$category][1] ?? 'to-blue-200';

        try {
            $stmt = $this->connect()->prepare(
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
            return ['success' => true, 'message' => 'Item updated!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error updating: ' . $e->getMessage()];
        }
    }

    public function deleteClothing($id) {
        if (!is_numeric($id) || $id <= 0) return ['success' => false, 'message' => 'Invalid ID'];
        try {
            $conn = $this->connect();
            $stmt = $conn->prepare("SELECT image_path FROM clothing WHERE id = :id");
            $stmt->execute([':id' => (int) $id]);
            $row = $stmt->fetch();
            if ($row && $row['image_path'] && file_exists($row['image_path'])) {
                unlink($row['image_path']);
            }
            $conn->prepare("DELETE FROM clothing WHERE id = :id")->execute([':id' => (int) $id]);
            return ['success' => true, 'message' => 'Item deleted!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error deleting: ' . $e->getMessage()];
        }
    }

    // ── Weather ───────────────────────────────────────────────────────────────

    public function getWeatherData() {
        try {
            $stmt = $this->connect()->prepare("SELECT * FROM weather ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch();
            return ['success' => true, 'data' => $row ?: DEFAULT_WEATHER];
        } catch (PDOException $e) {
            return ['success' => false, 'data' => DEFAULT_WEATHER];
        }
    }

    public function updateWeatherData($city, $country, $temperature, $condition_text, $emoji, $humidity, $wind_speed, $uv_index) {
        try {
            $conn = $this->connect();
            $existing = $conn->query("SELECT id FROM weather LIMIT 1")->fetch();
            $params = [
                ':city'           => $city,
                ':country'        => $country,
                ':temperature'    => (int) $temperature,
                ':condition_text' => $condition_text,
                ':emoji'          => $emoji,
                ':humidity'       => (int) $humidity,
                ':wind_speed'     => (int) $wind_speed,
                ':uv_index'       => $uv_index,
            ];
            if ($existing) {
                $stmt = $conn->prepare(
                    "UPDATE weather SET city=:city, country=:country, temperature=:temperature,
                     condition_text=:condition_text, emoji=:emoji, humidity=:humidity,
                     wind_speed=:wind_speed, uv_index=:uv_index WHERE id=:id"
                );
                $params[':id'] = (int) $existing['id'];
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO weather (city,country,temperature,condition_text,emoji,humidity,wind_speed,uv_index)
                     VALUES (:city,:country,:temperature,:condition_text,:emoji,:humidity,:wind_speed,:uv_index)"
                );
            }
            $stmt->execute($params);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

// ── HTTP handler ──────────────────────────────────────────────────────────────
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
        header('Content-Type: application/json');

        $db     = new Database();
        $action = $_POST['action'] ?? $_GET['action'] ?? '';

        switch ($action) {
            case 'add':
                $name        = trim($_POST['name'] ?? '');
                $category    = $_POST['category'] ?? '';
                $season      = $_POST['season'] ?? '';
                $imagePath   = $_POST['image_path'] ?? null;
                $description = trim($_POST['description'] ?? '');

                echo json_encode($db->addClothing($name, $category, $season, $imagePath, $description));
                break;

            case 'get':
                $filter = $_GET['filter'] ?? null;
                echo json_encode($db->getClothing($filter));
                break;

            case 'getSuggested':
                echo json_encode($db->getSuggestedOutfits());
                break;

            case 'update':
                $id          = $_POST['id'] ?? 0;
                $name        = trim($_POST['name'] ?? '');
                $category    = $_POST['category'] ?? '';
                $season      = $_POST['season'] ?? '';
                $description = trim($_POST['description'] ?? '');

                echo json_encode($db->updateClothing($id, $name, $category, $season, $description));
                break;

            case 'delete':
                $id = $_POST['id'] ?? 0;
                echo json_encode($db->deleteClothing($id));
                break;

            case 'getWeather':
                echo json_encode($db->getWeatherData());
                break;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
        exit;
    }
}
