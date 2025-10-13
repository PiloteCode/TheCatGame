<?php
require_once 'config.php';

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);

// Validation des données
if (!isset($data['lobby_name']) || empty(trim($data['lobby_name']))) {
    echo json_encode(['success' => false, 'error' => 'Nom du lobby requis']);
    exit();
}

if (!isset($data['cat_count']) || $data['cat_count'] < 1 || $data['cat_count'] > 10) {
    echo json_encode(['success' => false, 'error' => 'Nombre de chats invalide (1-10)']);
    exit();
}

if (!isset($data['mode']) || !in_array($data['mode'], ['control', 'self'])) {
    echo json_encode(['success' => false, 'error' => 'Mode de jeu invalide']);
    exit();
}

try {
    $db = getDB();
    
    // Générer les identifiants
    $lobbyId = generateId(6);
    $lobbyName = trim($data['lobby_name']);
    $mode = $data['mode'];
    $catCount = intval($data['cat_count']);
    
    // Générer le mot de passe pour la tour de contrôle
    $controlPassword = $mode === 'control' ? generatePassword() : null;
    
    // Commencer une transaction
    $db->beginTransaction();
    
    // Insérer le lobby
    $stmt = $db->prepare("
        INSERT INTO lobbies (lobby_id, lobby_name, mode, control_password, max_players)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$lobbyId, $lobbyName, $mode, $controlPassword, $catCount]);
    
    // Couleurs disponibles
    $colors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#14b8a6'];
    
    // Noms de joueurs disponibles
    $playerNames = ['Alex', 'Marie', 'Thomas', 'Sarah', 'Lucas', 'Emma', 'Hugo', 'Léa', 'Nathan', 'Chloé', 'Antoine', 'Julie', 'Maxime', 'Laura', 'Pierre', 'Sophie'];
    shuffle($playerNames);
    
    // Centre de Paris (Tour Eiffel)
    $centerLat = 48.8584;
    $centerLng = 2.2945;
    
    // Créer les joueurs
    $players = [];
    $stmt = $db->prepare("
        INSERT INTO players (lobby_id, player_id, player_name, password, color, latitude, longitude, radius)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    for ($i = 0; $i < $catCount; $i++) {
        $password = generatePassword();
        $color = $colors[$i % count($colors)];
        $playerName = $playerNames[$i % count($playerNames)];
        
        // Générer une position aléatoire proche (rayon de 2km)
        $radiusInDegrees = 2 / 111;
        $angle = mt_rand(0, 360) * (M_PI / 180);
        $distance = sqrt(mt_rand(0, 1000) / 1000) * $radiusInDegrees;
        
        $lat = $centerLat + ($distance * cos($angle));
        $lng = $centerLng + ($distance * sin($angle) / cos($centerLat * M_PI / 180));
        $lat = round($lat, 6);
        $lng = round($lng, 6);
        
        $radius = mt_rand(200, 500);
        
        $stmt->execute([
            $lobbyId,
            $i + 1,
            $playerName,
            $password,
            $color,
            $lat,
            $lng,
            $radius
        ]);
        
        $players[] = [
            'player_id' => $i + 1,
            'player_name' => $playerName,
            'password' => $password,
            'color' => $color,
            'latitude' => $lat,
            'longitude' => $lng,
            'radius' => $radius
        ];
    }
    
    // Valider la transaction
    $db->commit();
    
    // Réponse
    echo json_encode([
        'success' => true,
        'data' => [
            'lobby_id' => $lobbyId,
            'lobby_name' => $lobbyName,
            'mode' => $mode,
            'control_password' => $controlPassword,
            'players' => $players
        ]
    ]);
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la création du lobby: ' . $e->getMessage()
    ]);
}