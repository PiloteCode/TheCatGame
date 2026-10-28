<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

// Vérification stricte des paramètres requis
if (!isset($data['lobby_id']) || empty($data['lobby_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID du lobby manquant', 'access_denied' => true]);
    exit();
}

if (!isset($data['password']) || empty($data['password'])) {
    echo json_encode(['success' => false, 'error' => 'Mot de passe manquant', 'access_denied' => true]);
    exit();
}

if (!isset($data['action']) || empty($data['action'])) {
    echo json_encode(['success' => false, 'error' => 'Action manquante']);
    exit();
}

$lobbyId = $data['lobby_id'];
$password = $data['password'];
$action = $data['action'];

try {
    $db = getDB();
    
    // VÉRIFICATION STRICTE du mot de passe de la tour de contrôle
    $stmt = $db->prepare("
        SELECT * FROM lobbies 
        WHERE lobby_id = ? AND status = 'active'
    ");
    $stmt->execute([$lobbyId]);
    $lobby = $stmt->fetch();
    
    if (!$lobby) {
        echo json_encode([
            'success' => false, 
            'error' => 'Lobby introuvable ou inactif',
            'access_denied' => true
        ]);
        exit();
    }
    
    // Vérifier que le mot de passe correspond
    if (!checkSecret($lobby['control_password'], $password)) {
        failDelay();
        echo json_encode([
            'success' => false, 
            'error' => 'Mot de passe de contrôle invalide',
            'access_denied' => true,
            'invalid_password' => true
        ]);
        exit();
    }
    
    // Le mot de passe est valide, exécuter l'action demandée
    switch ($action) {
        case 'update_player_name':
            if (!isset($data['player_id']) || !isset($data['player_name'])) {
                echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
                exit();
            }
            
            $playerId = intval($data['player_id']);
            $playerName = cleanName($data['player_name']);
            
            if (empty($playerName)) {
                echo json_encode(['success' => false, 'error' => 'Le nom ne peut pas être vide']);
                exit();
            }
            
            // Vérifier que le joueur existe dans ce lobby
            $stmt = $db->prepare("SELECT player_id FROM players WHERE lobby_id = ? AND player_id = ?");
            $stmt->execute([$lobbyId, $playerId]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Joueur introuvable']);
                exit();
            }
            
            $stmt = $db->prepare("
                UPDATE players 
                SET player_name = ?, last_update = CURRENT_TIMESTAMP
                WHERE lobby_id = ? AND player_id = ?
            ");
            $stmt->execute([$playerName, $lobbyId, $playerId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Nom mis à jour'
            ]);
            break;
            
        case 'update_radius':
            if (!isset($data['player_id']) || !isset($data['radius'])) {
                echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
                exit();
            }
            
            $playerId = intval($data['player_id']);
            $radius = intval($data['radius']);
            
            if ($radius < 50 || $radius > 5000) {
                echo json_encode(['success' => false, 'error' => 'Rayon invalide (50-5000m)']);
                exit();
            }
            
            // Vérifier que le joueur existe dans ce lobby
            $stmt = $db->prepare("SELECT player_id FROM players WHERE lobby_id = ? AND player_id = ?");
            $stmt->execute([$lobbyId, $playerId]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Joueur introuvable']);
                exit();
            }
            
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
                echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
                exit();
            }
            
            $playerId = intval($data['player_id']);
            $latitude = floatval($data['latitude']);
            $longitude = floatval($data['longitude']);
            
            // Validation des coordonnées
            if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                echo json_encode(['success' => false, 'error' => 'Coordonnées invalides']);
                exit();
            }
            
            // Vérifier que le joueur existe dans ce lobby
            $stmt = $db->prepare("SELECT player_id, radius FROM players WHERE lobby_id = ? AND player_id = ?");
            $stmt->execute([$lobbyId, $playerId]);
            $player = $stmt->fetch();
            
            if (!$player) {
                echo json_encode(['success' => false, 'error' => 'Joueur introuvable']);
                exit();
            }
            
            // Mise à jour de la position réelle uniquement (depuis la tour de contrôle)
            $stmt = $db->prepare("
                UPDATE players 
                SET latitude_real = ?, longitude_real = ?, last_update = CURRENT_TIMESTAMP
                WHERE lobby_id = ? AND player_id = ?
            ");
            $stmt->execute([$latitude, $longitude, $lobbyId, $playerId]);
            
            // Historique
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
                echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
                exit();
            }
            
            $playerId = intval($data['player_id']);
            
            // Vérifier que le joueur existe dans ce lobby
            $stmt = $db->prepare("SELECT player_id FROM players WHERE lobby_id = ? AND player_id = ?");
            $stmt->execute([$lobbyId, $playerId]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Joueur introuvable']);
                exit();
            }
            
            $stmt = $db->prepare("
                UPDATE players 
                SET status = 'eliminated', last_update = CURRENT_TIMESTAMP
                WHERE lobby_id = ? AND player_id = ?
            ");
            $stmt->execute([$lobbyId, $playerId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Joueur éliminé'
            ]);
            break;
            
        case 'force_refresh':
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
    serverError('Erreur lors de l\'action', $e);
}