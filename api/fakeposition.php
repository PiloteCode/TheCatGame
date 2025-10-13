<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Centre de Paris (Tour Eiffel)
$centerLat = 48.8584;
$centerLng = 2.2945;

// Fonction pour générer une position aléatoire proche
function generatePosition($centerLat, $centerLng, $maxDistanceKm) {
    $radiusInDegrees = $maxDistanceKm / 111; // Approximation : 1 degré ≈ 111 km
    
    $angle = mt_rand(0, 360) * (M_PI / 180);
    $distance = sqrt(mt_rand(0, 1000) / 1000) * $radiusInDegrees;
    
    $lat = $centerLat + ($distance * cos($angle));
    $lng = $centerLng + ($distance * sin($angle) / cos($centerLat * M_PI / 180));
    
    return [round($lat, 6), round($lng, 6)];
}

// Générer les positions pour 2 joueurs
$player1Position = generatePosition($centerLat, $centerLng, 2); // Dans un rayon de 2km
$player2Position = generatePosition($centerLat, $centerLng, 1.5); // Dans un rayon de 1.5km

// Rayons aléatoires entre 200 et 500 mètres
$player1Radius = mt_rand(200, 500);
$player2Radius = mt_rand(200, 500);

// Noms des joueurs
$playerNames = ['Alex', 'Marie', 'Thomas', 'Sarah', 'Lucas', 'Emma', 'Hugo', 'Léa'];
shuffle($playerNames);

// Réponse JSON avec 2 joueurs
echo json_encode([
    'success' => true,
    'data' => [
        [
            'id' => 'player1',
            'lat' => $player1Position[0],
            'lng' => $player1Position[1],
            'radius' => $player1Radius,
            'player' => $playerNames[0],
            'color' => '#3b82f6', // Bleu
            'timestamp' => time()
        ],
        [
            'id' => 'player2',
            'lat' => $player2Position[0],
            'lng' => $player2Position[1],
            'radius' => $player2Radius,
            'player' => $playerNames[1],
            'color' => '#ef4444', // Rouge
            'timestamp' => time()
        ]
    ]
]);