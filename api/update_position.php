<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

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
$latitudeReal = floatval($data['latitude']);
$longitudeReal = floatval($data['longitude']);
$radius = isset($data['radius']) ? intval($data['radius']) : null;

try {
    $db = getDB();
    
    // Vérifier le mot de passe
    $stmt = $db->prepare("
        SELECT * FROM players 
        WHERE lobby_id = ? AND player_id = ? AND password = ? AND status = 'active'
    ");
    $stmt->execute([$lobbyId, $playerId, $password]);
    $player = $stmt->fetch();
    
    if (!$player) {
        // Délai anti-bruteforce de 5 secondes
        sleep(5);
        echo json_encode([
            'success' => false, 
            'error' => 'Authentification échouée',
            'invalid_password' => true
        ]);
        exit();
    }
    
    $currentRadius = $radius !== null ? $radius : intval($player['radius']);
    
    // Vérifier si le joueur est toujours dans son cercle aléatoire actuel
    $needNewCircle = true;
    
    if ($player['circle_center_lat'] !== null && $player['circle_center_lng'] !== null) {
        $distance = calculateDistance(
            $latitudeReal,
            $longitudeReal,
            floatval($player['circle_center_lat']),
            floatval($player['circle_center_lng'])
        );
        
        // Si le joueur est toujours dans son cercle, on garde la position publique
        if ($distance <= $currentRadius) {
            $needNewCircle = false;
        }
    }
    
    // Générer nouvelle position publique si nécessaire
    if ($needNewCircle) {
        // MARGE DE SÉCURITÉ : utiliser 90% du rayon pour le centre du cercle
        $safeRadius = $currentRadius * 0.9;
        
        // Générer un nouveau cercle aléatoire autour de la position réelle
        // Le centre du cercle n'est pas forcément la position réelle
        $offsetAngle = deg2rad(mt_rand(0, 360));
        $offsetDistance = mt_rand(0, $safeRadius * 0.7); // Le centre peut être décalé jusqu'à 63% du rayon total (70% de 90%)
        
        $circleCenterLat = $latitudeReal + ($offsetDistance / 111320) * cos($offsetAngle);
        $circleCenterLng = $longitudeReal + ($offsetDistance / (111320 * cos(deg2rad($latitudeReal)))) * sin($offsetAngle);
        
        // Générer une position publique aléatoire dans ce cercle avec marge de sécurité
        $publicPosition = generateRandomPositionInCircle($circleCenterLat, $circleCenterLng, $safeRadius);
        $latitudePublic = $publicPosition['lat'];
        $longitudePublic = $publicPosition['lng'];
    } else {
        // Garder la position publique actuelle
        $latitudePublic = floatval($player['latitude_public']);
        $longitudePublic = floatval($player['longitude_public']);
        $circleCenterLat = floatval($player['circle_center_lat']);
        $circleCenterLng = floatval($player['circle_center_lng']);
    }
    
    // Mettre à jour la position
    if ($radius !== null) {
        $stmt = $db->prepare("
            UPDATE players 
            SET latitude_real = ?, longitude_real = ?, 
                latitude_public = ?, longitude_public = ?,
                circle_center_lat = ?, circle_center_lng = ?,
                radius = ?, 
                connected_at = CURRENT_TIMESTAMP, 
                last_update = CURRENT_TIMESTAMP
            WHERE lobby_id = ? AND player_id = ?
        ");
        $stmt->execute([
            $latitudeReal, $longitudeReal,
            $latitudePublic, $longitudePublic,
            $circleCenterLat, $circleCenterLng,
            $radius,
            $lobbyId, $playerId
        ]);
    } else {
        $stmt = $db->prepare("
            UPDATE players 
            SET latitude_real = ?, longitude_real = ?,
                latitude_public = ?, longitude_public = ?,
                circle_center_lat = ?, circle_center_lng = ?,
                connected_at = CURRENT_TIMESTAMP, 
                last_update = CURRENT_TIMESTAMP
            WHERE lobby_id = ? AND player_id = ?
        ");
        $stmt->execute([
            $latitudeReal, $longitudeReal,
            $latitudePublic, $longitudePublic,
            $circleCenterLat, $circleCenterLng,
            $lobbyId, $playerId
        ]);
    }
    
    // Historique avec position réelle
    $stmt = $db->prepare("
        INSERT INTO position_history (lobby_id, player_id, latitude, longitude, radius)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$lobbyId, $playerId, $latitudeReal, $longitudeReal, $currentRadius]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Position mise à jour',
        'new_circle' => $needNewCircle,
        'debug' => [
            'radius_full' => $currentRadius,
            'radius_safe' => round($currentRadius * 0.9),
            'margin' => '10%'
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
    ]);
}

// Fonction pour calculer la distance entre deux points en mètres
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // Rayon de la Terre en mètres
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earthRadius * $c;
}

// Fonction pour générer une position aléatoire dans un cercle
function generateRandomPositionInCircle($centerLat, $centerLng, $radiusMeters) {
    // Générer un angle aléatoire
    $angle = deg2rad(mt_rand(0, 360));
    
    // Générer une distance aléatoire (distribution uniforme dans le cercle)
    // MARGE DE SÉCURITÉ : utiliser 90% du rayon maximum
    $maxDistance = $radiusMeters * 0.9;
    $distance = sqrt(mt_rand(0, 1000) / 1000) * $maxDistance;
    
    // Convertir en degrés
    $latOffset = ($distance / 111320) * cos($angle);
    $lngOffset = ($distance / (111320 * cos(deg2rad($centerLat)))) * sin($angle);
    
    return [
        'lat' => $centerLat + $latOffset,
        'lng' => $centerLng + $lngOffset
    ];
}