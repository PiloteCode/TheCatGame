<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

// Validation
if (!isset($data['lobby_id']) || !isset($data['player_id']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
    exit();
}

if (!isset($data['latitude']) || !isset($data['longitude'])) {
    echo json_encode(['success' => false, 'error' => 'Position manquante']);
    exit();
}

$lobbyId = $data['lobby_id'];
$playerId = intval($data['player_id']);
$password = $data['password'];
$latitude = floatval($data['latitude']);
$longitude = floatval($data['longitude']);
$radius = isset($data['radius']) ? intval($data['radius']) : null;

try {
    $db = getDB();
    
    // Vérifier le mot de passe du joueur
    $stmt = $db->prepare("
        SELECT * FROM players 
        WHERE lobby_id = ? AND player_id = ? AND password = ? AND status = 'active'
    ");
    $stmt->execute([$lobbyId, $playerId, $password]);
    $player = $stmt->fetch();
    
    if (!$player) {
        echo json_encode(['success' => false, 'error' => 'Authentification échouée']);
        exit();
    }
    
    // Mettre à jour la position
    if ($radius !== null) {
        $stmt = $db->prepare("
            UPDATE players 
            SET latitude = ?, longitude = ?, radius = ?, last_update = CURRENT_TIMESTAMP
            WHERE lobby_id = ? AND player_id = ?
        ");
        $stmt->execute([$latitude, $longitude, $radius, $lobbyId, $playerId]);
    } else {
        $stmt = $db->prepare("
            UPDATE players 
            SET latitude = ?, longitude = ?, last_update = CURRENT_TIMESTAMP
            WHERE lobby_id = ? AND player_id = ?
        ");
        $stmt->execute([$latitude, $longitude, $lobbyId, $playerId]);
    }
    
    // Enregistrer dans l'historique
    $currentRadius = $radius !== null ? $radius : intval($player['radius']);
    $stmt = $db->prepare("
        INSERT INTO position_history (lobby_id, player_id, latitude, longitude, radius)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$lobbyId, $playerId, $latitude, $longitude, $currentRadius]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Position mise à jour'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
    ]);
}