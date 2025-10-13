<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

// Validation
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
    
    $lobbyId = generateId(6);
    $lobbyName = trim($data['lobby_name']);
    $mode = $data['mode'];
    $catCount = intval($data['cat_count']);
    $controlPassword = $mode === 'control' ? generatePassword() : null;
    $playerNames = isset($data['player_names']) && is_array($data['player_names']) ? $data['player_names'] : [];
    
    $db->beginTransaction();
    
    // Créer le lobby
    $stmt = $db->prepare("
        INSERT INTO lobbies (lobby_id, lobby_name, mode, control_password, max_players)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$lobbyId, $lobbyName, $mode, $controlPassword, $catCount]);
    
    // Couleurs
    $colors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#14b8a6'];
    
    // Noms par défaut
    $defaultNames = ['Alex', 'Marie', 'Thomas', 'Sarah', 'Lucas', 'Emma', 'Hugo', 'Léa', 'Nathan', 'Chloé'];
    
    // Créer les joueurs
    $players = [];
    $stmt = $db->prepare("
        INSERT INTO players (lobby_id, player_id, player_name, password, color, latitude_real, longitude_real, latitude_public, longitude_public, radius)
        VALUES (?, ?, ?, ?, ?, NULL, NULL, NULL, NULL, ?)
    ");
    
    for ($i = 0; $i < $catCount; $i++) {
        $password = generatePassword();
        $color = $colors[$i % count($colors)];
        $playerName = isset($playerNames[$i]) && !empty(trim($playerNames[$i])) 
            ? trim($playerNames[$i]) 
            : null;
        $radius = 500;
        
        $stmt->execute([
            $lobbyId,
            $i + 1,
            $playerName,
            $password,
            $color,
            $radius
        ]);
        
        $players[] = [
            'player_id' => $i + 1,
            'player_name' => $playerName,
            'password' => $password,
            'color' => $color,
            'radius' => $radius
        ];
    }
    
    $db->commit();
    
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
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la création du lobby: ' . $e->getMessage()
    ]);
}