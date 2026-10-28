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

if ($latitudeReal < -90 || $latitudeReal > 90 || $longitudeReal < -180 || $longitudeReal > 180) {
    echo json_encode(['success' => false, 'error' => 'Coordonnées invalides']);
    exit();
}

try {
    $db = getDB();

    // Vérifier le mot de passe
    $stmt = $db->prepare("
        SELECT p.*, l.mode FROM players p
        JOIN lobbies l ON l.lobby_id = p.lobby_id
        WHERE p.lobby_id = ? AND p.player_id = ? AND p.password = ? AND p.status = 'active'
    ");
    $stmt->execute([$lobbyId, $playerId, $password]);
    $player = $stmt->fetch();

    if (!$player) {
        failDelay();
        echo json_encode([
            'success' => false,
            'error' => 'Authentification échouée',
            'invalid_password' => true
        ]);
        exit();
    }

    // Un chat ne règle son rayon lui-même qu'en mode Self Control, et dans les
    // mêmes bornes que la tour de contrôle (sinon rayon 1 m = position exacte).
    if ($radius !== null && ($player['mode'] !== 'self' || $radius < 50 || $radius > 5000)) {
        $radius = null;
    }
    $currentRadius = $radius !== null ? $radius : intval($player['radius']);

    // La zone publique est un cercle centré sur latitude/longitude_public.
    // Tant que le chat reste dans ce cercle, on la garde.
    $needNewCircle = true;

    if ($player['latitude_public'] !== null && $player['longitude_public'] !== null) {
        $distance = calculateDistance(
            $latitudeReal,
            $longitudeReal,
            floatval($player['latitude_public']),
            floatval($player['longitude_public'])
        );

        if ($distance <= $currentRadius) {
            $needNewCircle = false;
        }
    }

    if ($needNewCircle) {
        // Nouveau centre tiré au hasard à au plus 75 % du rayon de la position
        // réelle : le chat est donc toujours DANS le cercle affiché. (Avant, le
        // centre pouvait être décalé jusqu'à 144 % du rayon, et le chat se
        // retrouvait hors de sa propre zone.)
        $center = randomPointInDisk($latitudeReal, $longitudeReal, $currentRadius * 0.75);
        $latitudePublic = $center['lat'];
        $longitudePublic = $center['lng'];
    } else {
        $latitudePublic = floatval($player['latitude_public']);
        $longitudePublic = floatval($player['longitude_public']);
    }
    // Conservé pour compatibilité : le centre du cercle est la position publique
    $circleCenterLat = $latitudePublic;
    $circleCenterLng = $longitudePublic;

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
        'new_circle' => $needNewCircle
    ]);

} catch (Exception $e) {
    serverError('Erreur lors de la mise à jour', $e);
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

// Point tiré uniformément dans un disque de rayon $maxMeters autour d'un point
function randomPointInDisk($lat, $lng, $maxMeters) {
    $angle = random_int(0, 359999) / 1000 * M_PI / 180;
    $distance = sqrt(random_int(0, 1000000) / 1000000) * $maxMeters;

    return [
        'lat' => $lat + ($distance / 111320) * cos($angle),
        'lng' => $lng + ($distance / (111320 * cos(deg2rad($lat)))) * sin($angle)
    ];
}
