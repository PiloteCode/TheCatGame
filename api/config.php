<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'thecatgame');
define('DB_USER', 'thecatgame');
define('DB_PASS', '%Vevi55&LAn1heao');

// Classe de connexion à la base de données
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die(json_encode([
                'success' => false,
                'error' => 'Erreur de connexion à la base de données'
            ]));
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prévenir le clonage de l'instance
    private function __clone() {}
    
    // Prévenir la désérialisation de l'instance
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Fonction utilitaire pour obtenir la connexion
function getDB() {
    return Database::getInstance()->getConnection();
}

// Fonction pour générer un ID unique
function generateId($length = 6) {
    return strtoupper(substr(md5(uniqid(rand(), true)), 0, $length));
}

// Fonction pour générer un mot de passe
function generatePassword($length = 8) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, $length);
}

// Fonction pour valider un mot de passe
function validatePassword($lobbyId, $role, $password) {
    $db = getDB();
    
    if ($role === 'control') {
        $stmt = $db->prepare("SELECT control_password FROM lobbies WHERE lobby_id = ?");
        $stmt->execute([$lobbyId]);
        $result = $stmt->fetch();
        return $result && $result['control_password'] === $password;
    } else if ($role === 'cat') {
        $stmt = $db->prepare("SELECT password FROM players WHERE lobby_id = ? AND player_id = ?");
        $stmt->execute([$lobbyId, $_GET['id'] ?? 1]);
        $result = $stmt->fetch();
        return $result && $result['password'] === $password;
    }
    
    return false;
}

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Gérer les requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}