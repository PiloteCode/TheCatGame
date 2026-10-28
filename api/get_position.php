<?php
require_once 'config.php';

// Les mots de passe arrivent dans le corps d'une requête POST, jamais dans
// l'URL (qui finit dans les logs du serveur et l'historique). Le GET reste
// accepté pour l'accès public des chercheurs et les pages déjà ouvertes.
$input = $_GET;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = [];
    }
}

if (empty($input['lobby_id']) || !is_string($input['lobby_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID du lobby requis']);
    exit();
}

$lobbyId = $input['lobby_id'];
$controlMode = in_array($input['control'] ?? null, ['true', true], true);
$controlPassword = (string) ($input['control_pwd'] ?? '');
$catMode = in_array($input['cat'] ?? null, ['true', true], true);
$catPassword = (string) ($input['cat_pwd'] ?? '');
$catId = isset($input['cat_id']) ? intval($input['cat_id']) : null;

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
    
    // Variable pour déterminer le type d'accès
    $isControlAccess = false;
    $isCatAccess = false;
    $accessType = 'public'; // public, control, cat
    
    // VÉRIFICATION STRICTE - Tour de contrôle
    if ($controlMode) {
        if (empty($controlPassword)) {
            echo json_encode([
                'success' => false, 
                'error' => 'Mot de passe de contrôle manquant',
                'access_denied' => true
            ]);
            exit();
        }
        
        if (!checkSecret($lobby['control_password'], $controlPassword)) {
            failDelay();
            echo json_encode([
                'success' => false, 
                'error' => 'Mot de passe de contrôle invalide',
                'access_denied' => true,
                'invalid_password' => true
            ]);
            exit();
        }
        
        // Authentification réussie pour la tour de contrôle
        $isControlAccess = true;
        $accessType = 'control';
    }
    // VÉRIFICATION STRICTE - Chat authentifié
    else if ($catMode) {
        if ($catId === null) {
            echo json_encode([
                'success' => false, 
                'error' => 'ID du chat manquant',
                'access_denied' => true
            ]);
            exit();
        }
        
        if (empty($catPassword)) {
            echo json_encode([
                'success' => false, 
                'error' => 'Mot de passe du chat manquant',
                'access_denied' => true
            ]);
            exit();
        }
        
        // Vérifier que le chat existe et que le mot de passe correspond
        $stmt = $db->prepare("
            SELECT * FROM players 
            WHERE lobby_id = ? AND player_id = ? AND password = ?
        ");
        $stmt->execute([$lobbyId, $catId, $catPassword]);
        $catPlayer = $stmt->fetch();
        
        if (!$catPlayer) {
            failDelay();
            echo json_encode([
                'success' => false, 
                'error' => 'Chat introuvable ou mot de passe invalide',
                'access_denied' => true,
                'invalid_password' => true
            ]);
            exit();
        }
        
        // Authentification réussie pour le chat
        $isCatAccess = true;
        $accessType = 'cat';
    }
    
    // Récupérer tous les joueurs selon le type d'accès
    if ($isControlAccess || $isCatAccess) {
        // Accès authentifié : positions réelles
        $stmt = $db->prepare("
            SELECT 
                player_id, 
                player_name, 
                color, 
                latitude_real as latitude, 
                longitude_real as longitude,
                latitude_public,
                longitude_public,
                radius, 
                status,
                connected_at,
                last_update,
                TIMESTAMPDIFF(SECOND, connected_at, NOW()) as seconds_since_connection
            FROM players
            WHERE lobby_id = ?
            ORDER BY player_id
        ");
    } else {
        // Accès public (hunter) : positions publiques uniquement
        $stmt = $db->prepare("
            SELECT 
                player_id, 
                player_name, 
                color, 
                latitude_public as latitude, 
                longitude_public as longitude, 
                radius, 
                status,
                connected_at,
                last_update,
                TIMESTAMPDIFF(SECOND, connected_at, NOW()) as seconds_since_connection
            FROM players
            WHERE lobby_id = ?
            ORDER BY player_id
        ");
    }
    
    $stmt->execute([$lobbyId]);
    $allPlayers = $stmt->fetchAll();
    
    // Récupérer les hunters (seulement pour tour de contrôle)
    $hunters = [];
    if ($isControlAccess) {
        $stmt = $db->prepare("
            SELECT 
                hunter_name,
                latitude,
                longitude,
                points,
                last_update,
                TIMESTAMPDIFF(SECOND, last_update, NOW()) as seconds_since_update
            FROM hunters
            WHERE lobby_id = ?
            ORDER BY points DESC
        ");
        $stmt->execute([$lobbyId]);
        $hunters = $stmt->fetchAll();
    }
    
    $activePlayers = [];
    $result = [];
    $publicPositions = [];
    
    foreach ($allPlayers as $player) {
        $isConnected = $player['connected_at'] !== null && $player['seconds_since_connection'] <= 30;
        
        $playerData = [
            'id' => 'player' . $player['player_id'],
            'player_id' => intval($player['player_id']),
            'player' => $player['player_name'] ?? 'Chat ' . $player['player_id'],
            'color' => $player['color'],
            'lat' => $player['latitude'] !== null ? floatval($player['latitude']) : null,
            'lng' => $player['longitude'] !== null ? floatval($player['longitude']) : null,
            'radius' => intval($player['radius']),
            'status' => $player['status'],
            'connected' => $isConnected,
            'last_update' => $player['last_update'] ? strtotime($player['last_update']) : null,
            'type' => 'cat'
        ];
        
        // Pour la tour de contrôle, stocker aussi les positions publiques
        if ($isControlAccess) {
            $publicPositions[] = [
                'id' => 'player' . $player['player_id'],
                'lat_public' => isset($player['latitude_public']) ? floatval($player['latitude_public']) : null,
                'lng_public' => isset($player['longitude_public']) ? floatval($player['longitude_public']) : null
            ];
        }
        
        $result[] = $playerData;
        
        if ($player['status'] === 'active' && $player['latitude'] !== null && $player['longitude'] !== null) {
            $activePlayers[] = $playerData;
        }
    }
    
    // Ajouter les hunters (seulement pour tour de contrôle)
    $huntersData = [];
    if ($isControlAccess) {
        foreach ($hunters as $hunter) {
            $isActive = $hunter['seconds_since_update'] <= 60;
            
            if ($hunter['latitude'] !== null && $hunter['longitude'] !== null && $isActive) {
                $hunterData = [
                    'id' => 'hunter_' . md5($hunter['hunter_name']),
                    'hunter_name' => $hunter['hunter_name'],
                    'lat' => floatval($hunter['latitude']),
                    'lng' => floatval($hunter['longitude']),
                    'points' => intval($hunter['points']),
                    'last_update' => strtotime($hunter['last_update']),
                    'type' => 'hunter'
                ];
                
                $huntersData[] = $hunterData;
                $activePlayers[] = $hunterData;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result,
        'active_only' => $activePlayers,
        'data_with_public' => ($isControlAccess || $isCatAccess) ? $publicPositions : null,
        'hunters' => $huntersData,
        'lobby' => [
            'name' => $lobby['lobby_name'],
            'mode' => $lobby['mode']
        ],
        'stats' => [
            'total' => count($result),
            'active' => count(array_filter($result, fn($p) => $p['status'] === 'active')),
            'eliminated' => count($result) - count(array_filter($result, fn($p) => $p['status'] === 'active')),
            'connected' => count(array_filter($result, fn($p) => $p['connected'])),
            'hunters' => count($huntersData)
        ],
        'access_type' => $accessType,
        'authenticated' => ($isControlAccess || $isCatAccess)
    ]);
    
} catch (Exception $e) {
    serverError('Erreur lors de la récupération des positions', $e);
}