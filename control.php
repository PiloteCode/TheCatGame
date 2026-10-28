<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour de Contrôle - Chat Géant</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        
        .status-connected {
            background-color: #10b981;
            box-shadow: 0 0 6px #10b981;
        }
        
        .status-disconnected {
            background-color: #ef4444;
        }
        
        .hunter-icon {
            background-color: #3b82f6;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.6);
        }
        
        .real-position-marker {
            width: 20px;
            height: 20px;
            background-color: #3b82f6;
            border: 4px solid white;
            border-radius: 50%;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 1000;
        }
        
        .public-position-marker {
            width: 16px;
            height: 16px;
            border: 3px solid white;
            border-radius: 50%;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
            position: relative;
            z-index: 999;
        }
    </style>
</head>
<body>
    <?php
    $lobbyId = $_GET['lobby'] ?? '';
    $password = $_GET['pwd'] ?? '';
    
    if (empty($lobbyId) || empty($password)) {
        echo '<div class="flex items-center justify-center min-h-screen bg-red-100">
                <div class="bg-white p-8 rounded-lg shadow-2xl text-center">
                    <i class="fas fa-exclamation-triangle text-red-500 text-6xl mb-4"></i>
                    <p class="text-2xl font-bold text-red-600 mb-2">Accès refusé</p>
                    <p class="text-gray-600">Paramètres manquants</p>
                </div>
              </div>';
        exit();
    }
    ?>
    
    <div class="fixed top-4 left-4 z-[1000] bg-white rounded-lg shadow-2xl p-4 max-w-sm max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-bold mb-4 flex items-center gap-2" style="color: #2c3e50;">
            <i class="fas fa-tower-broadcast" style="color: #d4a574;"></i>
            Tour de Contrôle
        </h2>
        
        <div class="mb-4 p-3 bg-green-100 rounded-lg border-2 border-green-300">
            <div class="flex items-center gap-2 text-sm mb-2">
                <i class="fas fa-crosshairs text-green-700"></i>
                <span class="font-semibold text-green-700">Mode précision activé</span>
            </div>
            <div class="text-xs text-green-600 space-y-1">
                <div class="flex items-center gap-2">
                    <div class="w-5 h-5 rounded-full bg-blue-500 border-2 border-white"></div>
                    <span>Point bleu = Position réelle</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded-full bg-red-500 border-2 border-white"></div>
                    <span>Point rouge + cercle = Position publique</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded-full" style="background: #3b82f6; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-user text-white" style="font-size: 8px;"></i>
                    </div>
                    <span>Cercle bleu = Hunter (50m)</span>
                </div>
            </div>
        </div>
        
        <div class="mb-4">
            <button onclick="forceRefresh()" 
                    class="w-full py-2 px-4 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold flex items-center justify-center gap-2">
                <i class="fas fa-sync-alt"></i>
                Actualiser
            </button>
        </div>
        
        <div id="statsPanel" class="mb-4 p-3 bg-gray-100 rounded-lg">
            <div class="text-sm space-y-1">
                <div class="flex justify-between">
                    <span class="text-gray-600">Connectés:</span>
                    <span id="connectedCount" class="font-bold text-green-600">0</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Actifs:</span>
                    <span id="activeCount" class="font-bold text-blue-600">0</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Éliminés:</span>
                    <span id="eliminatedCount" class="font-bold text-red-600">0</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Hunters:</span>
                    <span id="huntersCount" class="font-bold text-purple-600">0</span>
                </div>
            </div>
        </div>
        
        <div class="mb-4">
            <h3 class="font-bold text-sm mb-2 flex items-center gap-2" style="color: #2c3e50;">
                <i class="fas fa-users" style="color: #3b82f6;"></i>
                Chercheurs
            </h3>
            <div id="huntersList" class="space-y-2 max-h-48 overflow-y-auto"></div>
        </div>
        
        <div>
            <h3 class="font-bold text-sm mb-2" style="color: #2c3e50;">
                <i class="fas fa-cat" style="color: #d4a574;"></i>
                Chats
            </h3>
            <div id="playersList" class="space-y-2"></div>
        </div>
    </div>
    
    <div id="map"></div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        const LOBBY_ID = '<?php echo htmlspecialchars($lobbyId); ?>';
        const PASSWORD = '<?php echo htmlspecialchars($password); ?>';
        
        let map = null;
        let players = {};
        let hunters = {};
        
        function initMap() {
            map = L.map('map').setView([48.8566, 2.3522], 13);
            
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);
            
            loadPlayers();
            setInterval(loadPlayers, 5000);
        }
        
        async function loadPlayers() {
            try {
                const response = await fetch(`api/get_position.php?lobby_id=${LOBBY_ID}&control=true&control_pwd=${PASSWORD}`);
                const data = await response.json();
                
                if (data.success) {
                    updatePlayers(data.active_only, data.data_with_public);
                    updatePlayersList(data.data);
                    updateHuntersList(data.hunters || []);
                    updateStats(data.stats);
                } else if (data.invalid_password) {
                    // Mot de passe invalide
                    document.body.innerHTML = `
                        <div class="flex items-center justify-center min-h-screen" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="bg-white p-12 rounded-2xl shadow-2xl text-center max-w-md animate-pulse">
                                <i class="fas fa-lock text-red-500 text-8xl mb-6"></i>
                                <h1 class="text-3xl font-bold text-red-600 mb-4">Mot de passe incorrect</h1>
                                <p class="text-gray-600 mb-6">Le mot de passe de la tour de contrôle est invalide.</p>
                                <p class="text-sm text-gray-500">Accès refusé</p>
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }
        
        function updateStats(stats) {
            document.getElementById('connectedCount').textContent = stats.connected || 0;
            document.getElementById('activeCount').textContent = stats.active || 0;
            document.getElementById('eliminatedCount').textContent = stats.eliminated || 0;
            document.getElementById('huntersCount').textContent = stats.hunters || 0;
        }
        
        function updatePlayers(playersData, publicPositions) {
            // Nettoyer les markers obsolètes
            Object.keys(players).forEach(id => {
                if (!playersData.find(p => p.id === id)) {
                    if (players[id].circle) map.removeLayer(players[id].circle);
                    if (players[id].markerReal) map.removeLayer(players[id].markerReal);
                    if (players[id].markerPublic) map.removeLayer(players[id].markerPublic);
                    if (players[id].connectionLine) map.removeLayer(players[id].connectionLine);
                    delete players[id];
                }
            });
            
            Object.keys(hunters).forEach(id => {
                if (!playersData.find(p => p.id === id)) {
                    if (hunters[id].circle) map.removeLayer(hunters[id].circle);
                    if (hunters[id].marker) map.removeLayer(hunters[id].marker);
                    delete hunters[id];
                }
            });
            
            playersData.forEach(playerData => {
                const { id, lat, lng, radius, color, player, type } = playerData;
                
                if (lat === null || lng === null) return;
                
                if (type === 'hunter') {
                    // Hunter avec cercle de 50m
                    if (!hunters[id]) {
                        const hunterIcon = L.divIcon({
                            className: 'hunter-marker',
                            html: '<div class="hunter-icon"><i class="fas fa-user"></i></div>',
                            iconSize: [32, 32],
                            iconAnchor: [16, 16]
                        });
                        
                        hunters[id] = {
                            circle: L.circle([lat, lng], {
                                color: '#3b82f6',
                                fillColor: '#3b82f6',
                                fillOpacity: 0.1,
                                radius: 50,
                                weight: 3
                            }).addTo(map),
                            marker: L.marker([lat, lng], { icon: hunterIcon }).addTo(map)
                        };
                    } else {
                        hunters[id].circle.setLatLng([lat, lng]);
                        hunters[id].marker.setLatLng([lat, lng]);
                    }
                    
                    hunters[id].marker.bindPopup(`
                        <div class="p-3">
                            <b><i class="fas fa-user mr-1"></i>${playerData.hunter_name}</b><br>
                            Points: ${playerData.points || 0}
                        </div>
                    `);
                } else {
                    // Chat - Position réelle (point bleu) + Position publique (point rouge + cercle)
                    const publicPos = publicPositions?.find(p => p.id === id);
                    
                    if (!players[id]) {
                        // Marker bleu pour position réelle (juste un point)
                        const realIcon = L.divIcon({
                            className: 'real-marker',
                            html: `<div class="real-position-marker"></div>`,
                            iconSize: [20, 20],
                            iconAnchor: [10, 10]
                        });
                        
                        players[id] = {
                            markerReal: L.marker([lat, lng], { icon: realIcon }).addTo(map)
                        };
                        
                        players[id].markerReal.bindPopup(`
                            <div class="p-2">
                                <b>${player}</b><br>
                                <span class="text-blue-600 font-semibold">📍 Position réelle</span>
                            </div>
                        `);
                        
                        // Ajouter marker rouge + cercle pour position publique
                        if (publicPos && publicPos.lat_public !== null && publicPos.lng_public !== null) {
                            const publicIcon = L.divIcon({
                                className: 'public-marker',
                                html: `<div class="public-position-marker" style="background-color: ${color};"></div>`,
                                iconSize: [16, 16],
                                iconAnchor: [8, 8]
                            });
                            
                            players[id].circle = L.circle([publicPos.lat_public, publicPos.lng_public], {
                                color: color,
                                fillColor: color,
                                fillOpacity: 0.15,
                                radius: radius,
                                weight: 3
                            }).addTo(map);
                            
                            players[id].markerPublic = L.marker([publicPos.lat_public, publicPos.lng_public], { icon: publicIcon }).addTo(map);
                            
                            players[id].markerPublic.bindPopup(`
                                <div class="p-2">
                                    <b>${player}</b><br>
                                    <span class="font-semibold" style="color: ${color};">🎯 Position publique</span><br>
                                    <small>Visible par tous - Rayon: ${radius}m</small>
                                </div>
                            `);
                            
                            // Ligne pointillée entre position réelle et publique
                            players[id].connectionLine = L.polyline(
                                [[lat, lng], [publicPos.lat_public, publicPos.lng_public]],
                                {
                                    color: '#9ca3af',
                                    dashArray: '5, 10',
                                    weight: 2,
                                    opacity: 0.5
                                }
                            ).addTo(map);
                        }
                    } else {
                        // Mettre à jour position réelle
                        players[id].markerReal.setLatLng([lat, lng]);
                        
                        // Mettre à jour position publique
                        if (publicPos && publicPos.lat_public !== null && publicPos.lng_public !== null) {
                            if (players[id].circle) {
                                players[id].circle.setLatLng([publicPos.lat_public, publicPos.lng_public]);
                                players[id].circle.setRadius(radius);
                            }
                            
                            if (players[id].markerPublic) {
                                players[id].markerPublic.setLatLng([publicPos.lat_public, publicPos.lng_public]);
                            }
                            
                            // Mettre à jour la ligne
                            if (players[id].connectionLine) {
                                players[id].connectionLine.setLatLngs([[lat, lng], [publicPos.lat_public, publicPos.lng_public]]);
                            }
                        }
                    }
                }
            });
        }
        
        function updateHuntersList(huntersData) {
            if (huntersData.length === 0) {
                document.getElementById('huntersList').innerHTML = '<p class="text-xs text-gray-500 text-center py-2">Aucun chercheur</p>';
                return;
            }
            
            const html = huntersData.map(h => `
                <div class="p-2 bg-blue-50 border-2 border-blue-200 rounded">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-user text-blue-600"></i>
                            <span class="text-sm font-semibold">${h.hunter_name}</span>
                        </div>
                        <span class="text-xs bg-blue-600 text-white px-2 py-1 rounded">${h.points} pts</span>
                    </div>
                </div>
            `).join('');
            
            document.getElementById('huntersList').innerHTML = html;
        }
        
        function updatePlayersList(playersData) {
            const html = playersData.map(p => {
                const playerName = p.player || `Chat ${p.player_id}`;
                const statusDot = p.connected ? 'status-connected' : 'status-disconnected';
                const statusText = p.connected ? 'En ligne' : 'Hors ligne';
                
                return `
                    <div class="p-3 border-2 rounded-lg" style="border-color: ${p.color};">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="status-dot ${statusDot}"></span>
                                <input type="text" 
                                       id="name_${p.player_id}" 
                                       value="${playerName}"
                                       class="font-semibold bg-transparent border-b border-gray-300 focus:border-blue-500 outline-none text-sm"
                                       onblur="updatePlayerName(${p.player_id})">
                            </div>
                            <span class="text-xs text-gray-600">#${p.player_id}</span>
                        </div>
                        <div class="text-xs text-gray-500 mb-2">${statusText}</div>
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
                            ${p.status === 'active' ? `
                            <button onclick="eliminatePlayer(${p.player_id})" 
                                    class="w-full py-1 px-2 bg-red-500 hover:bg-red-600 text-white rounded text-sm">
                                <i class="fas fa-times-circle mr-1"></i>
                                Éliminer
                            </button>
                            ` : `
                            <div class="w-full py-1 px-2 bg-gray-400 text-white rounded text-sm text-center">
                                Éliminé
                            </div>
                            `}
                        </div>
                    </div>
                `;
            }).join('');
            
            document.getElementById('playersList').innerHTML = html;
        }
        
        async function updatePlayerName(playerId) {
            const name = document.getElementById(`name_${playerId}`).value.trim();
            
            if (!name) {
                alert('Le nom ne peut pas être vide');
                loadPlayers();
                return;
            }
            
            try {
                const response = await fetch('api/control_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        lobby_id: LOBBY_ID,
                        password: PASSWORD,
                        action: 'update_player_name',
                        player_id: playerId,
                        player_name: name
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    loadPlayers();
                } else if (data.invalid_password) {
                    document.body.innerHTML = `
                        <div class="flex items-center justify-center min-h-screen" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="bg-white p-12 rounded-2xl shadow-2xl text-center max-w-md">
                                <i class="fas fa-lock text-red-500 text-8xl mb-6"></i>
                                <h1 class="text-3xl font-bold text-red-600 mb-4">Mot de passe incorrect</h1>
                                <p class="text-gray-600 mb-6">Le mot de passe de la tour de contrôle est invalide.</p>
                                <p class="text-sm text-gray-500">Accès refusé</p>
                            </div>
                        </div>
                    `;
                } else {
                    alert('Erreur: ' + data.error);
                }
            } catch (error) {
                alert('Erreur réseau');
            }
        }
        
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
                } else if (data.invalid_password) {
                    document.body.innerHTML = `
                        <div class="flex items-center justify-center min-h-screen" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="bg-white p-12 rounded-2xl shadow-2xl text-center max-w-md">
                                <i class="fas fa-lock text-red-500 text-8xl mb-6"></i>
                                <h1 class="text-3xl font-bold text-red-600 mb-4">Mot de passe incorrect</h1>
                                <p class="text-gray-600 mb-6">Le mot de passe de la tour de contrôle est invalide.</p>
                                <p class="text-sm text-gray-500">Accès refusé</p>
                            </div>
                        </div>
                    `;
                } else {
                    alert('Erreur: ' + data.error);
                }
            } catch (error) {
                alert('Erreur réseau');
            }
        }
        
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
                } else if (data.invalid_password) {
                    document.body.innerHTML = `
                        <div class="flex items-center justify-center min-h-screen" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="bg-white p-12 rounded-2xl shadow-2xl text-center max-w-md">
                                <i class="fas fa-lock text-red-500 text-8xl mb-6"></i>
                                <h1 class="text-3xl font-bold text-red-600 mb-4">Mot de passe incorrect</h1>
                                <p class="text-gray-600 mb-6">Le mot de passe de la tour de contrôle est invalide.</p>
                                <p class="text-sm text-gray-500">Accès refusé</p>
                            </div>
                        </div>
                    `;
                } else {
                    alert('Erreur: ' + data.error);
                }
            } catch (error) {
                alert('Erreur réseau');
            }
        }
        
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
        
        window.addEventListener('DOMContentLoaded', initMap);
    </script>
</body>
</html>