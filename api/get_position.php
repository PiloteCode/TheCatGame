<?php
require_once 'config.php';

// Vérifier que lobby_id est fourni
if (!isset($_GET['lobby_id']) || empty($_GET['lobby_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID du lobby requis']);
    exit();
}

$lobbyId = $_GET['lobby_id'];

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
    
    // Récupérer tous les joueurs du lobby (actifs ET éliminés pour les stats)
    $stmt = $db->prepare("
        SELECT 
            player_id, 
            player_name, 
            color, 
            latitude, 
            longitude, 
            radius, 
            status,
            last_update
        FROM players
        WHERE lobby_id = ?
        ORDER BY player_id
    ");
    $stmt->execute([$lobbyId]);
    $allPlayers = $stmt->fetchAll();
    
    // Séparer les joueurs actifs pour l'affichage sur la carte
    $activePlayers = [];
    $result = [];
    
    foreach ($allPlayers as $player) {
        $playerData = [
            'id' => 'player' . $player['player_id'],
            'player_id' => intval($player['player_id']),
            'player' => $player['player_name'],
            'color' => $player['color'],
            'lat' => floatval($player['latitude']),
            'lng' => floatval($player['longitude']),
            'radius' => intval($player['radius']),
            'status' => $player['status'],
            'last_update' => strtotime($player['last_update'])
        ];
        
        $result[] = $playerData;
        
        // Ne garder que les actifs pour l'affichage sur la carte
        if ($player['status'] === 'active') {
            $activePlayers[] = $playerData;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result, // Tous les joueurs avec leur statut
        'active_only' => $activePlayers, // Seulement les actifs (pour compatibilité)
        'lobby' => [
            'name' => $lobby['lobby_name'],
            'mode' => $lobby['mode']
        ],
        'stats' => [
            'total' => count($result),
            'active' => count($activePlayers),
            'eliminated' => count($result) - count($activePlayers)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des positions: ' . $e->getMessage()
    ]);
}