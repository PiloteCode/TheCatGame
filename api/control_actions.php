<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

// Validation
if (!isset($data['lobby_id']) || !isset($data['password']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
    exit();
}

$lobbyId = $data['lobby_id'];
$password = $data['password'];
$action = $data['action'];

try {
    $db = getDB();
    
    // Vérifier le mot de passe de la tour de contrôle
    $stmt = $db->prepare("
        SELECT * FROM lobbies 
        WHERE lobby_id = ? AND control_password = ? AND status = 'active'
    ");
    $stmt->execute([$lobbyId, $password]);
    $lobby = $stmt->fetch();
    
    if (!$lobby) {
        echo json_encode(['success' => false, 'error' => 'Authentification échouée']);
        exit();
    }
    
    // Actions selon le type
    switch ($action) {
        case 'update_radius':
            if (!isset($data['player_id']) || !isset($data['radius'])) {
                echo json_encode(['success' => false, 'error' => 'Paramètres manquants pour update_radius']);
                exit();
            }
            
            $playerId = intval($data['player_id']);
            $radius = intval($data['radius']);
            
            $stmt = $db->prepare("
                UPDATE players 
                SET radius = ?, last_update = CURRENT_TIMESTAMP
                WHERE lobby_id = ? AND player_id = ?
            ");
            $stmt->execute([$radius, $lobbyId, $playerId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Rayon mis à jour'
            ]);
            break;
            
        case 'update_position':
            if (!isset($data['player_id']) || !isset($data['latitude']) || !isset($data['longitude'])) {
                echo json_encode(['success' => false, 'error' => 'Paramètres manquants pour update_position']);
                exit();
            }
            
            $playerId = intval($data['player_id']);
            $latitude = floatval($data['latitude']);
            $longitude = floatval($data['longitude']);
            
            $stmt = $db->prepare("
                UPDATE players 
                SET latitude = ?, longitude = ?, last_update = CURRENT_TIMESTAMP
                WHERE lobby_id = ? AND player_id = ?
            ");
            $stmt->execute([$latitude, $longitude, $lobbyId, $playerId]);
            
            // Historique
            $stmt = $db->prepare("SELECT radius FROM players WHERE lobby_id = ? AND player_id = ?");
            $stmt->execute([$lobbyId, $playerId]);
            $player = $stmt->fetch();
            
            $stmt = $db->prepare("
                INSERT INTO position_history (lobby_id, player_id, latitude, longitude, radius)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$lobbyId, $playerId, $latitude, $longitude, $player['radius']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Position mise à jour'
            ]);
            break;
            
        case 'eliminate_player':
            if (!isset($data['player_id'])) {
                echo json_encode(['success' => false, 'error' => 'Paramètres manquants pour eliminate_player']);
                exit();
            }
            
            $playerId = intval($data['player_id']);
            
            $stmt = $db->prepare("
                UPDATE players 
                SET status = 'eliminated'
                WHERE lobby_id = ? AND player_id = ?
            ");
            $stmt->execute([$lobbyId, $playerId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Joueur éliminé'
            ]);
            break;
            
        case 'force_refresh':
            // Mettre à jour le timestamp du lobby pour forcer le refresh
            $stmt = $db->prepare("
                UPDATE lobbies 
                SET updated_at = CURRENT_TIMESTAMP
                WHERE lobby_id = ?
            ");
            $stmt->execute([$lobbyId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Actualisation forcée'
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Action inconnue']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de l\'action: ' . $e->getMessage()
    ]);
}