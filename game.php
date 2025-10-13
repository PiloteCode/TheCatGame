<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Géant - Chercheur</title>
    
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
        
        .player-marker-container {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .radar-wave-permanent {
            position: absolute;
            border: 3px solid #3b82f6;
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
        
        .player-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 10;
        }
        
        .stat-card {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.95);
        }
        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }
    </style>
</head>
<body>
    <?php
    $lobbyId = $_GET['lobby'] ?? '';
    
    if (empty($lobbyId)) {
        echo '<div class="flex items-center justify-center min-h-screen"><p class="text-xl">ID du lobby manquant</p></div>';
        exit();
    }
    ?>
    
    <!-- Modal nom chercheur -->
    <div id="hunterModal" class="modal-overlay" style="display: none;">
        <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full mx-4">
            <div class="text-center mb-6">
                <i class="fas fa-user-secret text-6xl mb-4" style="color: #3b82f6;"></i>
                <h2 class="text-3xl font-bold mb-2" style="color: #2c3e50;">Mode Chercheur</h2>
                <p class="text-gray-600">Entrez votre nom pour voir la carte</p>
            </div>
            
            <div class="mb-4 p-3 bg-blue-50 rounded-lg border-2 border-blue-200">
                <p class="text-xs text-blue-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    Votre position sera partagée avec la tour de contrôle
                </p>
            </div>
            
            <div class="mb-6">
                <input type="text" 
                       id="hunterNameInput" 
                       placeholder="Votre nom..."
                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 text-lg"
                       onkeypress="if(event.key === 'Enter') startHunting()">
            </div>
            
            <button onclick="startHunting()" 
                    class="w-full py-4 rounded-lg font-bold text-lg shadow-lg"
                    style="background-color: #3b82f6; color: white;">
                <i class="fas fa-check mr-2"></i>
                Confirmer
            </button>
        </div>
    </div>
    
    <div class="fixed top-4 right-4 z-[1000] space-y-3 max-w-xs">
        <div class="stat-card rounded-lg shadow-2xl p-4">
            <h2 class="text-xl font-bold flex items-center gap-2" style="color: #2c3e50;">
                <i class="fas fa-search" style="color: #d4a574;"></i>
                Mode Chercheur
            </h2>
            <p class="text-sm text-gray-600 mt-1">Lobby: <span class="font-mono font-semibold"><?php echo htmlspecialchars($lobbyId); ?></span></p>
            <p id="hunterNameDisplay" class="text-sm font-semibold mt-1" style="color: #3b82f6;"></p>
        </div>
        
        <div class="stat-card rounded-lg shadow-2xl p-4">
            <h3 class="font-bold mb-3 flex items-center gap-2" style="color: #2c3e50;">
                <i class="fas fa-chart-line" style="color: #d4a574;"></i>
                Statistiques
            </h3>
            
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-cat text-lg" style="color: #10b981;"></i>
                        <span class="text-sm">Chats actifs</span>
                    </div>
                    <span id="activeCats" class="font-bold text-lg" style="color: #10b981;">0</span>
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-times-circle text-lg" style="color: #ef4444;"></i>
                        <span class="text-sm">Éliminés</span>
                    </div>
                    <span id="eliminatedCats" class="font-bold text-lg" style="color: #ef4444;">0</span>
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-plug text-lg" style="color: #3b82f6;"></i>
                        <span class="text-sm">Connectés</span>
                    </div>
                    <span id="connectedCats" class="font-bold text-lg" style="color: #3b82f6;">0</span>
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-users text-lg" style="color: #8b5cf6;"></i>
                        <span class="text-sm">Chercheurs</span>
                    </div>
                    <span id="huntersCount" class="font-bold text-lg" style="color: #8b5cf6;">0</span>
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-clock text-lg" style="color: #f59e0b;"></i>
                        <span class="text-sm">Temps</span>
                    </div>
                    <span id="gameTime" class="font-bold text-lg" style="color: #f59e0b;">00:00</span>
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-sync text-lg" style="color: #6b7280;"></i>
                        <span class="text-sm">MAJ</span>
                    </div>
                    <span id="lastUpdate" class="font-bold text-sm" style="color: #6b7280;">-</span>
                </div>
            </div>
        </div>
        
        <div class="stat-card rounded-lg shadow-2xl p-4 max-h-96 overflow-y-auto">
            <h3 class="font-bold mb-3 flex items-center gap-2" style="color: #2c3e50;">
                <i class="fas fa-list" style="color: #d4a574;"></i>
                Chats en jeu
            </h3>
            <div id="catsList" class="space-y-2"></div>
        </div>
        
        <button onclick="window.mapApp.manualRefresh()" 
                class="w-full stat-card rounded-lg shadow-2xl p-3 font-semibold flex items-center justify-center gap-2 hover:bg-gray-50 transition-colors"
                style="color: #2c3e50;">
            <i id="refreshIcon" class="fas fa-sync-alt"></i>
            Actualiser
        </button>
    </div>
    
    <div id="map"></div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="js/maps.js"></script>
    
    <script>
        const LOBBY_ID = '<?php echo htmlspecialchars($lobbyId); ?>';
        const UPDATE_INTERVAL = 5000;
        
        let watchId = null;
        let currentPosition = null;
        let hunterName = '';
        let sessionId = generateSessionId();
        
        function generateSessionId() {
            return 'hunter_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }
        
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
        }
        
        function setCookie(name, value, days) {
            const expires = new Date();
            expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
            document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/`;
        }
        
        function checkHunterName() {
            const savedName = getCookie('hunter_name');
            if (savedName) {
                hunterName = savedName;
                document.getElementById('hunterNameDisplay').textContent = `👤 ${hunterName}`;
                startTracking();
            } else {
                document.getElementById('hunterModal').style.display = 'flex';
                setTimeout(() => {
                    document.getElementById('hunterNameInput').focus();
                }, 100);
            }
        }
        
        function startHunting() {
            const input = document.getElementById('hunterNameInput').value.trim();
            
            if (!input) {
                alert('Veuillez entrer votre nom');
                return;
            }
            
            hunterName = input;
            setCookie('hunter_name', hunterName, 30);
            document.getElementById('hunterModal').style.display = 'none';
            document.getElementById('hunterNameDisplay').textContent = `👤 ${hunterName}`;
            
            startTracking();
        }
        
        async function sendHunterPosition(latitude, longitude) {
            try {
                await fetch('api/hunter_position.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        lobby_id: LOBBY_ID,
                        hunter_name: hunterName,
                        session_id: sessionId,
                        latitude: latitude,
                        longitude: longitude
                    })
                });
            } catch (error) {
                console.error('Erreur envoi position hunter:', error);
            }
        }
        
        function handleSuccess(position) {
            currentPosition = position;
            const { latitude, longitude } = position.coords;
            sendHunterPosition(latitude, longitude);
        }
        
        function handleError(error) {
            console.error('Erreur GPS:', error);
        }
        
        function startTracking() {
            if (!navigator.geolocation) {
                console.error('Géolocalisation non supportée');
                return;
            }
            
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
                    sendHunterPosition(latitude, longitude);
                }
            }, UPDATE_INTERVAL);
        }
        
        class GameStatsManager {
            constructor(mapApp) {
                this.mapApp = mapApp;
                this.startTime = Date.now();
                this.updateTimer();
                setInterval(() => this.updateTimer(), 1000);
            }
            
            updateStats(stats, playersData) {
                document.getElementById('activeCats').textContent = stats.active || 0;
                document.getElementById('eliminatedCats').textContent = stats.eliminated || 0;
                document.getElementById('connectedCats').textContent = stats.connected || 0;
                document.getElementById('huntersCount').textContent = stats.hunters || 0;
                document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString().slice(0, 5);
                
                this.updateCatsList(playersData);
            }
            
            updateCatsList(playersData) {
                const html = playersData.map(p => {
                    const playerName = p.player || `Chat ${p.player_id}`;
                    let statusBg, statusText, statusColor;
                    
                    if (p.status === 'eliminated') {
                        statusBg = '#fef2f2';
                        statusText = 'Éliminé';
                        statusColor = '#ef4444';
                    } else if (p.connected) {
                        statusBg = '#f0fdf4';
                        statusText = 'En ligne';
                        statusColor = '#10b981';
                    } else {
                        statusBg = '#fef3c7';
                        statusText = 'Hors ligne';
                        statusColor = '#f59e0b';
                    }
                    
                    return `
                        <div class="flex items-center justify-between p-2 rounded" style="background-color: ${statusBg}; border-left: 3px solid ${p.color};">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-cat" style="color: ${p.color};"></i>
                                <span class="text-sm font-semibold">${playerName}</span>
                            </div>
                            <span class="text-xs px-2 py-1 rounded" style="background-color: ${statusColor}; color: white;">
                                ${statusText}
                            </span>
                        </div>
                    `;
                }).join('');
                
                document.getElementById('catsList').innerHTML = html || '<p class="text-sm text-gray-500 text-center">Aucun chat</p>';
            }
            
            updateTimer() {
                const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
                const minutes = Math.floor(elapsed / 60);
                const seconds = elapsed % 60;
                document.getElementById('gameTime').textContent = 
                    `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            checkHunterName();
            
            setTimeout(() => {
                if (window.mapApp) {
                    const statsManager = new GameStatsManager(window.mapApp);
                    
                    const originalUpdate = window.mapApp.updatePositions.bind(window.mapApp);
                    window.mapApp.updatePositions = async function() {
                        await originalUpdate();
                        
                        try {
                            const response = await fetch(`api/get_position.php?lobby_id=${window.mapApp.urlParams.lobbyId}`);
                            const result = await response.json();
                            
                            if (result.success) {
                                statsManager.updateStats(result.stats, result.data);
                            }
                        } catch (error) {
                            console.error('Erreur stats:', error);
                        }
                    };
                }
            }, 100);
        });
        
        window.addEventListener('beforeunload', () => {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
            }
        });
    </script>
</body>
</html>