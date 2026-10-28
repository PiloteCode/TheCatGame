<?php
// Configuration de la base de données : les identifiants ne sont plus dans le
// dépôt. Ils viennent de api/config.local.php (ignoré par git, voir
// config.local.php.example) ou des variables d'environnement DB_*.
if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}
foreach (['DB_HOST' => 'localhost', 'DB_NAME' => 'thecatgame', 'DB_USER' => 'thecatgame', 'DB_PASS' => ''] as $name => $default) {
    if (!defined($name)) {
        $value = getenv($name);
        define($name, $value !== false ? $value : $default);
    }
}

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
            error_log('[thecatgame] Connexion BDD : ' . $e->getMessage());
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

// Chaîne aléatoire tirée avec un générateur cryptographique (random_int)
function randomString($length, $alphabet) {
    $result = '';
    $max = strlen($alphabet) - 1;
    for ($i = 0; $i < $length; $i++) {
        $result .= $alphabet[random_int(0, $max)];
    }
    return $result;
}

// Fonction pour générer un ID unique (sans I, L, O, 0, 1 pour éviter les confusions)
function generateId($length = 6) {
    return randomString($length, 'ABCDEFGHJKMNPQRSTUVWXYZ23456789');
}

// Fonction pour générer un mot de passe
function generatePassword($length = 8) {
    return randomString($length, 'abcdefghijklmnopqrstuvwxyz0123456789');
}

// Comparaison à temps constant (pas d'indice sur le mot de passe via le temps de réponse)
function checkSecret($expected, $given) {
    return is_string($expected) && is_string($given) && $expected !== '' && hash_equals($expected, $given);
}

// Délai après un mauvais mot de passe : freine les essais sans bloquer un worker PHP 5 secondes
function failDelay() {
    usleep(random_int(500000, 1000000));
}

// Nettoie un nom saisi par un joueur : pas de caractères de contrôle, longueur bornée
function cleanName($value, $max = 30) {
    if (!is_string($value)) {
        return '';
    }
    // preg_replace renvoie null si la chaîne n'est pas de l'UTF-8 valide
    $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '';
    return mb_substr(trim($value), 0, $max);
}

// Réponse d'erreur générique : le détail technique reste dans les logs du serveur
function serverError($context, $e) {
    error_log('[thecatgame] ' . $context . ' : ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $context]);
}

// L'API n'est appelée que par les pages du jeu (même origine) : pas d'en-tête CORS
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

// Gérer les requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
