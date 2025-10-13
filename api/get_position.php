<?php
require_once 'config.php';

if (!isset($_GET['lobby_id']) || empty($_GET['lobby_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID du lobby requis']);
    exit();
}

$lobbyId = $_GET['lobby_id'];
$controlMode = isset($_GET['control']) && $_GET['control'] === 'true';
$controlPassword = $_GET['control_pwd'] ?? '';
$catMode = isset($_GET['cat']) && $_GET['cat'] === 'true';
$catPassword = $_GET['cat_pwd'] ?? '';
$catId = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : null;

try {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM lobbies WHERE lobby_id = ? AND status = 'active'");
    $stmt->execute([$lobbyId]);
    $lobby = $stmt->fetch();
    
    if (!$lobby) {
        echo json_encode(['success' => false, 'error' => 'Lobby introuvable ou inactif']);
        exit();
    }
    
    // Vérifier si c'est un accès tour de contrôle
    $isControlAccess = false;
    if ($controlMode && $lobby['control_password'] === $controlPassword) {
        $isControlAccess = true;
    }
    
    // Vérifier si c'est un accès chat authentifié
    $isCatAccess = false;
    if ($catMode && $catId !== null) {
        $stmt = $db->prepare("SELECT * FROM players WHERE lobby_id = ? AND player_id = ? AND password = ?");
        $stmt->execute([$lobbyId, $catId, $catPassword]);
        if ($stmt->fetch()) {
            $isCatAccess = true;
        }
    }
    
    // Récupérer tous les joueurs
    if ($isControlAccess || $isCatAccess) {
        // Tour de contrôle OU chat authentifié : positions réelles
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
        // Vue normale : positions publiques
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
    
    // Récupérer les hunters
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
    
    // Ajouter les hunters
    $huntersData = [];
    foreach ($hunters as $hunter) {
        $isActive = $hunter['seconds_since_update'] <= 60; // Actif si mis à jour dans la dernière minute
        
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
            
            // Ajouter aux actifs pour l'affichage sur la carte
            if ($isControlAccess) {
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
        'control_mode' => $isControlAccess,
        'cat_mode' => $isCatAccess
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des positions: ' . $e->getMessage()
    ]);
}