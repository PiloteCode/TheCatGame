<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Géant - Mode Chat</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            overflow: hidden;
            background-color: #2c3e50;
        }
        
        #map {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 1;
        }
        
        .info-panel {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-radius: 20px 20px 0 0;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            transition: transform 0.3s ease;
            max-height: 40vh;
            overflow-y: auto;
        }
        
        .info-panel.minimized {
            transform: translateY(calc(100% - 60px));
        }
        
        .drag-handle {
            width: 40px;
            height: 4px;
            background: #cbd5e0;
            border-radius: 2px;
            margin: 12px auto;
            cursor: pointer;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        
        .status-connected {
            background-color: #10b981;
            box-shadow: 0 0 10px #10b981;
            animation: pulse 2s infinite;
        }
        
        .status-error {
            background-color: #ef4444;
        }
        
        .status-waiting {
            background-color: #f59e0b;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }
        
        .radar-wave-permanent {
            position: absolute;
            border: 3px solid;
            border-radius: 50%;
            animation: radarPulsePermanent 2s ease-out infinite;
            pointer-events: none;
        }
        
        .radar-wave-permanent:nth-child(1) {
            animation-delay: 0s;
        }
        
        .radar-wave-permanent:nth-child(2) {
            animation-delay: 0.6s;
        }
        
        .radar-wave-permanent:nth-child(3) {
            animation-delay: 1.2s;
        }
        
        @keyframes radarPulsePermanent {
            0% {
                width: 30px;
                height: 30px;
                opacity: 0.8;
            }
            50% {
                opacity: 0.4;
            }
            100% {
                width: 90px;
                height: 90px;
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <?php
    $lobbyId = $_GET['lobby'] ?? '';
    $playerId = $_GET['id'] ?? '';
    $password = $_GET['pwd'] ?? '';
    
    if (empty($lobbyId) || empty($playerId) || empty($password)) {
        echo '<div class="min-h-screen flex items-center justify-center text-white text-xl">Paramètres manquants</div>';
        exit();
    }
    ?>
    
    <!-- Carte en arrière-plan -->
    <div id="map"></div>
    
    <!-- Panneau d'informations -->
    <div id="infoPanel" class="info-panel">
        <div class="drag-handle" onclick="togglePanel()"></div>
        
        <div class="px-6 pb-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <i class="fas fa-cat text-4xl" id="catIcon" style="color: #d4a574;"></i>
                    <div>
                        <h1 class="text-2xl font-bold" style="color: #2c3e50;">Mode Chat</h1>
                        <p class="text-sm text-gray-600" id="playerName">Chat #<?php echo htmlspecialchars($playerId); ?></p>
                    </div>
                </div>
                <button onclick="togglePanel()" class="text-gray-500 hover:text-gray-700 p-2">
                    <i id="toggleIcon" class="fas fa-chevron-down text-xl"></i>
                </button>
            </div>
            
            <div id="statusCard" class="p-4 rounded-lg border-2 mb-4" style="border-color: #bdc3c7; background-color: #ecf0f1;">
                <div class="flex items-center gap-3">
                    <span id="statusIndicator" class="status-indicator status-waiting"></span>
                    <div class="flex-1">
                        <h3 id="statusTitle" class="font-bold" style="color: #2c3e50;">Initialisation...</h3>
                        <p id="statusText" class="text-sm text-gray-600">Demande d'accès GPS</p>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="p-3 bg-gray-100 rounded-lg">
                    <div class="text-xs text-gray-600">Lobby</div>
                    <div class="font-mono font-semibold text-sm"><?php echo htmlspecialchars($lobbyId); ?></div>
                </div>
                <div class="p-3 bg-gray-100 rounded-lg">
                    <div class="text-xs text-gray-600">Dernière MAJ</div>
                    <div id="lastUpdate" class="font-semibold text-sm">-</div>
                </div>
                <div class="p-3 bg-gray-100 rounded-lg col-span-2">
                    <div class="text-xs text-gray-600">Position GPS</div>
                    <div id="currentPosition" class="font-mono text-xs">-</div>
                </div>
                <div class="p-3 bg-gray-100 rounded-lg">
                    <div class="text-xs text-gray-600">Tentatives</div>
                    <div id="retryCount" class="font-semibold text-sm">0</div>
                </div>
                <div class="p-3 bg-gray-100 rounded-lg">
                    <div class="text-xs text-gray-600">Statut</div>
                    <div id="connectionStatus" class="font-semibold text-sm text-green-600">Actif</div>
                </div>
            </div>
            
            <div class="p-4 bg-blue-50 rounded-lg border-2 border-blue-200">
                <div class="flex items-start gap-2">
                    <i class="fas fa-info-circle text-blue-600 mt-1"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-1">Mode Chat Authentifié</p>
                        <ul class="text-xs space-y-1">
                            <li>• Vous voyez les positions réelles des autres chats</li>
                            <li>• Votre position est envoyée automatiquement</li>
                            <li>• Gardez le GPS et cette page actifs</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        const LOBBY_ID = '<?php echo htmlspecialchars($lobbyId); ?>';
        const PLAYER_ID = <?php echo intval($playerId); ?>;
        const PASSWORD = '<?php echo htmlspecialchars($password); ?>';
        const UPDATE_INTERVAL = 5000;
        const MAX_RETRIES = 5;
        const RETRY_DELAY = 3000;
        
        let map = null;
        let watchId = null;
        let currentPosition = null;
        let retryCount = 0;
        let isMinimized = false;
        let players = {};
        let myMarker = null;
        let myColor = '#d4a574';
        
        function initMap() {
            map = L.map('map').setView([48.8566, 2.3522], 13);
            
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
                maxZoom: 19
            }).addTo(map);
            
            startTracking();
            loadAllPlayers();
            setInterval(loadAllPlayers, 5000);
        }
        
        function togglePanel() {
            isMinimized = !isMinimized;
            const panel = document.getElementById('infoPanel');
            const icon = document.getElementById('toggleIcon');
            
            if (isMinimized) {
                panel.classList.add('minimized');
                icon.className = 'fas fa-chevron-up text-xl';
            } else {
                panel.classList.remove('minimized');
                icon.className = 'fas fa-chevron-down text-xl';
            }
        }
        
        function updateStatus(type, title, text) {
            const statusCard = document.getElementById('statusCard');
            const indicator = document.getElementById('statusIndicator');
            const statusTitle = document.getElementById('statusTitle');
            const statusText = document.getElementById('statusText');
            
            statusCard.style.borderColor = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#f59e0b';
            statusCard.style.backgroundColor = type === 'success' ? '#d1fae5' : type === 'error' ? '#fee2e2' : '#fef3c7';
            
            indicator.className = 'status-indicator ' + (type === 'success' ? 'status-connected' : type === 'error' ? 'status-error' : 'status-waiting');
            
            statusTitle.textContent = title;
            statusText.textContent = text;
        }
        
        async function loadAllPlayers() {
            try {
                const response = await fetch(`api/get_position.php?lobby_id=${LOBBY_ID}&cat=true&cat_id=${PLAYER_ID}&cat_pwd=${PASSWORD}`);
                const data = await response.json();
                
                if (data.success) {
                    updateMapPlayers(data.active_only);
                    
                    // Récupérer ma couleur
                    const me = data.data.find(p => p.player_id === PLAYER_ID);
                    if (me) {
                        myColor = me.color;
                        document.getElementById('catIcon').style.color = myColor;
                        document.getElementById('playerName').textContent = me.player || `Chat #${PLAYER_ID}`;
                        document.getElementById('connectionStatus').textContent = me.status === 'active' ? 'Actif' : 'Éliminé';
                        document.getElementById('connectionStatus').className = 'font-semibold text-sm ' + (me.status === 'active' ? 'text-green-600' : 'text-red-600');
                    }
                }
            } catch (error) {
                console.error('Erreur chargement joueurs:', error);
            }
        }
        
        function updateMapPlayers(playersData) {
            playersData.forEach(playerData => {
                const { id, lat, lng, radius, color, player } = playerData;
                
                if (lat === null || lng === null) return;
                
                // Ne pas afficher mon propre marker (je suis au centre avec mon GPS)
                if (playerData.player_id === PLAYER_ID) return;
                
                if (!players[id]) {
                    players[id] = {
                        circle: L.circle([lat, lng], {
                            color: color,
                            fillColor: color,
                            fillOpacity: 0.2,
                            radius: radius
                        }).addTo(map),
                        marker: L.marker([lat, lng], {
                            icon: L.divIcon({
                                className: 'custom-player-icon',
                                html: `
                                    <div style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; position: relative;">
                                        <div class="radar-wave-permanent" style="border-color: ${color};"></div>
                                        <div class="radar-wave-permanent" style="border-color: ${color};"></div>
                                        <div class="radar-wave-permanent" style="border-color: ${color};"></div>
                                        <div style="width: 20px; height: 20px; background-color: ${color}; border: 3px solid white; border-radius: 50%; box-shadow: 0 2px 6px rgba(0,0,0,0.4); z-index: 10;"></div>
                                    </div>
                                `,
                                iconSize: [40, 40],
                                iconAnchor: [20, 20]
                            })
                        }).addTo(map)
                    };
                    
                    players[id].marker.bindPopup(`<b>${player}</b><br>Rayon: ${radius}m`);
                } else {
                    players[id].circle.setLatLng([lat, lng]);
                    players[id].circle.setRadius(radius);
                    players[id].marker.setLatLng([lat, lng]);
                }
            });
        }
        
        async function sendPosition(latitude, longitude, isRetry = false) {
            try {
                const response = await fetch('api/update_position.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        lobby_id: LOBBY_ID,
                        player_id: PLAYER_ID,
                        password: PASSWORD,
                        latitude: latitude,
                        longitude: longitude
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    retryCount = 0;
                    document.getElementById('retryCount').textContent = '0';
                    updateStatus('success', 'Connecté', 'Position envoyée avec succès');
                    document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
                    document.getElementById('currentPosition').textContent = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
                    
                    // Mettre à jour ma position sur la carte
                    if (!myMarker) {
                        myMarker = L.marker([latitude, longitude], {
                            icon: L.divIcon({
                                className: 'custom-player-icon',
                                html: `
                                    <div style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; position: relative;">
                                        <div class="radar-wave-permanent" style="border-color: ${myColor};"></div>
                                        <div class="radar-wave-permanent" style="border-color: ${myColor};"></div>
                                        <div class="radar-wave-permanent" style="border-color: ${myColor};"></div>
                                        <div style="width: 24px; height: 24px; background-color: ${myColor}; border: 4px solid white; border-radius: 50%; box-shadow: 0 4px 8px rgba(0,0,0,0.4); z-index: 10;"></div>
                                    </div>
                                `,
                                iconSize: [50, 50],
                                iconAnchor: [25, 25]
                            })
                        }).addTo(map);
                        
                        myMarker.bindPopup(`<b>Vous</b><br>C'est vous !`);
                        map.setView([latitude, longitude], 15);
                    } else {
                        myMarker.setLatLng([latitude, longitude]);
                    }
                    
                    return true;
                } else {
                    throw new Error(data.error || 'Erreur inconnue');
                }
            } catch (error) {
                console.error('Erreur envoi:', error);
                
                if (!isRetry && retryCount < MAX_RETRIES) {
                    retryCount++;
                    document.getElementById('retryCount').textContent = retryCount;
                    updateStatus('error', 'Erreur réseau', `Nouvelle tentative ${retryCount}/${MAX_RETRIES}...`);
                    
                    setTimeout(() => {
                        sendPosition(latitude, longitude, true);
                    }, RETRY_DELAY);
                } else if (retryCount >= MAX_RETRIES) {
                    updateStatus('error', 'Échec de connexion', 'Vérifiez votre connexion internet');
                } else {
                    updateStatus('error', 'Erreur', error.message);
                }
                
                return false;
            }
        }
        
        function handleSuccess(position) {
            currentPosition = position;
            const { latitude, longitude } = position.coords;
            sendPosition(latitude, longitude);
        }
        
        function handleError(error) {
            let message = 'Erreur inconnue';
            
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    message = 'Permission refusée. Autorisez l\'accès à votre position.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = 'Position indisponible. Activez votre GPS.';
                    break;
                case error.TIMEOUT:
                    message = 'Délai d\'attente dépassé.';
                    setTimeout(() => {
                        updateStatus('waiting', 'Nouvelle tentative', 'Recherche de votre position...');
                        startTracking();
                    }, RETRY_DELAY);
                    break;
            }
            
            updateStatus('error', 'Erreur GPS', message);
        }
        
        function startTracking() {
            if (!navigator.geolocation) {
                updateStatus('error', 'Non supporté', 'Votre navigateur ne supporte pas la géolocalisation');
                return;
            }
            
            updateStatus('waiting', 'En attente', 'Recherche de votre position...');
            
            watchId = navigator.geolocation.watchPosition(
                handleSuccess,
                handleError,
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
            
            setInterval(() => {
                if (currentPosition) {
                    const { latitude, longitude } = currentPosition.coords;
                    sendPosition(latitude, longitude);
                }
            }, UPDATE_INTERVAL);
        }
        
        window.addEventListener('DOMContentLoaded', initMap);
        
        window.addEventListener('beforeunload', () => {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
            }
        });
        
        window.addEventListener('online', () => {
            updateStatus('waiting', 'Connexion rétablie', 'Reprise de l\'envoi...');
            if (currentPosition) {
                const { latitude, longitude } = currentPosition.coords;
                sendPosition(latitude, longitude);
            }
        });
        
        window.addEventListener('offline', () => {
            updateStatus('error', 'Hors ligne', 'Connexion internet perdue');
        });
    </script>
</body>
</html>