<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['lobby_id']) || !isset($data['hunter_name'])) {
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
    exit();
}

if (!isset($data['latitude']) || !isset($data['longitude'])) {
    echo json_encode(['success' => false, 'error' => 'Position manquante']);
    exit();
}

$lobbyId = $data['lobby_id'];
$hunterName = trim($data['hunter_name']);
$latitude = floatval($data['latitude']);
$longitude = floatval($data['longitude']);
$sessionId = $data['session_id'] ?? session_id();

if (empty($hunterName)) {
    echo json_encode(['success' => false, 'error' => 'Nom du chercheur requis']);
    exit();
}

try {
    $db = getDB();
    
    // Vérifier que le lobby existe
    $stmt = $db->prepare("SELECT * FROM lobbies WHERE lobby_id = ? AND status = 'active'");
    $stmt->execute([$lobbyId]);
    $lobby = $stmt->fetch();
    
    if (!$lobby) {
        echo json_encode(['success' => false, 'error' => 'Lobby introuvable ou inactif']);
        exit();
    }
    
    // Vérifier si le hunter existe déjà
    $stmt = $db->prepare("
        SELECT * FROM hunters 
        WHERE lobby_id = ? AND session_id = ?
    ");
    $stmt->execute([$lobbyId, $sessionId]);
    $hunter = $stmt->fetch();
    
    if ($hunter) {
        // Mettre à jour la position
        $stmt = $db->prepare("
            UPDATE hunters 
            SET latitude = ?, longitude = ?, hunter_name = ?, last_update = CURRENT_TIMESTAMP
            WHERE lobby_id = ? AND session_id = ?
        ");
        $stmt->execute([$latitude, $longitude, $hunterName, $lobbyId, $sessionId]);
    } else {
        // Créer un nouveau hunter
        $stmt = $db->prepare("
            INSERT INTO hunters (lobby_id, hunter_name, session_id, latitude, longitude, points)
            VALUES (?, ?, ?, ?, ?, 0)
        ");
        $stmt->execute([$lobbyId, $hunterName, $sessionId, $latitude, $longitude]);
    }
    
    // Vérifier si le hunter est proche d'un chat
    $nearCats = [];
    $stmt = $db->prepare("
        SELECT player_id, player_name, latitude_real, longitude_real, radius, color
        FROM players
        WHERE lobby_id = ? AND status = 'active'
        AND latitude_real IS NOT NULL AND longitude_real IS NOT NULL
    ");
    $stmt->execute([$lobbyId]);
    $players = $stmt->fetchAll();
    
    foreach ($players as $player) {
        $distance = calculateDistance(
            $latitude,
            $longitude,
            floatval($player['latitude_real']),
            floatval($player['longitude_real'])
        );
        
        // Si le hunter est à moins de 50m d'un chat
        if ($distance <= 50) {
            $nearCats[] = [
                'player_id' => intval($player['player_id']),
                'player_name' => $player['player_name'] ?? 'Chat ' . $player['player_id'],
                'distance' => round($distance),
                'color' => $player['color']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Position mise à jour',
        'session_id' => $sessionId,
        'near_cats' => $nearCats
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
    ]);
}

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000;
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earthRadius * $c;
}