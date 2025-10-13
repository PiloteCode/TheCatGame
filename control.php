<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour de Contrôle - Chat Géant</title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body {
            overflow: hidden;
        }
        
        #map {
            height: 100vh;
            width: 100vw;
        }
        
        .leaflet-popup-content-wrapper {
            border-radius: 12px;
            padding: 0;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .leaflet-popup-content {
            margin: 0;
        }
    </style>
</head>
<body>
    <?php
    $lobbyId = $_GET['lobby'] ?? '';
    $password = $_GET['pwd'] ?? '';
    
    if (empty($lobbyId) || empty($password)) {
        echo '<div class="flex items-center justify-center min-h-screen"><p class="text-xl">Paramètres manquants</p></div>';
        exit();
    }
    ?>
    
    <!-- Panneau de contrôle -->
    <div class="fixed top-4 left-4 z-[1000] bg-white rounded-lg shadow-2xl p-4 max-w-sm">
        <h2 class="text-xl font-bold mb-4 flex items-center gap-2" style="color: #2c3e50;">
            <i class="fas fa-tower-broadcast" style="color: #d4a574;"></i>
            Tour de Contrôle
        </h2>
        
        <div class="mb-4">
            <button onclick="forceRefresh()" 
                    class="w-full py-2 px-4 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold flex items-center justify-center gap-2">
                <i class="fas fa-sync-alt"></i>
                Actualiser
            </button>
        </div>
        
        <div id="playersList" class="space-y-2"></div>
    </div>
    
    <div id="map"></div>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        const LOBBY_ID = '<?php echo htmlspecialchars($lobbyId); ?>';
        const PASSWORD = '<?php echo htmlspecialchars($password); ?>';
        
        let map = null;
        let players = {};
        
        // Initialiser la carte
        function initMap() {
            map = L.map('map').setView([48.8566, 2.3522], 13);
            
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
                maxZoom: 19
            }).addTo(map);
            
            loadPlayers();
            setInterval(loadPlayers, 5000);
        }
        
        // Charger les joueurs
        async function loadPlayers() {
            try {
                const response = await fetch(`api/get_position.php?lobby_id=${LOBBY_ID}`);
                const data = await response.json();
                
                if (data.success) {
                    updatePlayers(data.data);
                    updatePlayersList(data.data);
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }
        
        // Mettre à jour les joueurs sur la carte
        function updatePlayers(playersData) {
            playersData.forEach(playerData => {
                const { id, lat, lng, radius, color, player } = playerData;
                
                if (!players[id]) {
                    players[id] = {
                        circle: L.circle([lat, lng], {
                            color: color,
                            fillColor: color,
                            fillOpacity: 0.2,
                            radius: radius
                        }).addTo(map),
                        marker: L.marker([lat, lng]).addTo(map)
                    };
                } else {
                    players[id].circle.setLatLng([lat, lng]);
                    players[id].circle.setRadius(radius);
                    players[id].marker.setLatLng([lat, lng]);
                }
                
                players[id].marker.bindPopup(`<b>${player}</b><br>Rayon: ${radius}m`);
            });
        }
        
        // Mettre à jour la liste des joueurs
        function updatePlayersList(playersData) {
            const html = playersData.map(p => `
                <div class="p-3 border-2 rounded-lg" style="border-color: ${p.color};">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-semibold">${p.player}</span>
                        <span class="text-sm text-gray-600">Chat #${p.player_id}</span>
                    </div>
                    <div class="space-y-2">
                        <div>
                            <label class="text-xs text-gray-600">Rayon (m)</label>
                            <div class="flex gap-2">
                                <input type="number" 
                                       id="radius_${p.player_id}" 
                                       value="${p.radius}"
                                       class="flex-1 px-2 py-1 border rounded text-sm">
                                <button onclick="updateRadius(${p.player_id})" 
                                        class="px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded text-sm">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>
                        <button onclick="eliminatePlayer(${p.player_id})" 
                                class="w-full py-1 px-2 bg-red-500 hover:bg-red-600 text-white rounded text-sm">
                            <i class="fas fa-times-circle mr-1"></i>
                            Éliminer
                        </button>
                    </div>
                </div>
            `).join('');
            
            document.getElementById('playersList').innerHTML = html;
        }
        
        // Mettre à jour le rayon d'un joueur
        async function updateRadius(playerId) {
            const radius = parseInt(document.getElementById(`radius_${playerId}`).value);
            
            try {
                const response = await fetch('api/control_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        lobby_id: LOBBY_ID,
                        password: PASSWORD,
                        action: 'update_radius',
                        player_id: playerId,
                        radius: radius
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    loadPlayers();
                } else {
                    alert('Erreur: ' + data.error);
                }
            } catch (error) {
                alert('Erreur réseau');
            }
        }
        
        // Éliminer un joueur
        async function eliminatePlayer(playerId) {
            if (!confirm(`Êtes-vous sûr de vouloir éliminer le Chat #${playerId} ?`)) return;
            
            try {
                const response = await fetch('api/control_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        lobby_id: LOBBY_ID,
                        password: PASSWORD,
                        action: 'eliminate_player',
                        player_id: playerId
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    loadPlayers();
                } else {
                    alert('Erreur: ' + data.error);
                }
            } catch (error) {
                alert('Erreur réseau');
            }
        }
        
        // Forcer l'actualisation
        async function forceRefresh() {
            try {
                const response = await fetch('api/control_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        lobby_id: LOBBY_ID,
                        password: PASSWORD,
                        action: 'force_refresh'
                    })
                });
                
                loadPlayers();
            } catch (error) {
                alert('Erreur réseau');
            }
        }
        
        // Initialiser
        window.addEventListener('DOMContentLoaded', initMap);
    </script>
</body>
</html>