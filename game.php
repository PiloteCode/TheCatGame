<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Géant - Chercheur</title>
    
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
        
        .leaflet-control-attribution a {
            color: #2563eb !important;
        }
        
        .leaflet-control-attribution {
            background-color: rgba(255, 255, 255, 0.9) !important;
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
        
        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .5;
            }
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
    
    <!-- Panneau de statistiques -->
    <div class="fixed top-4 right-4 z-[1000] space-y-3 max-w-xs">
        <!-- Header -->
        <div class="stat-card rounded-lg shadow-2xl p-4">
            <h2 class="text-xl font-bold flex items-center gap-2" style="color: #2c3e50;">
                <i class="fas fa-search" style="color: #d4a574;"></i>
                Mode Chercheur
            </h2>
            <p class="text-sm text-gray-600 mt-1">Lobby: <span class="font-mono font-semibold"><?php echo htmlspecialchars($lobbyId); ?></span></p>
        </div>
        
        <!-- Stats en temps réel -->
        <div class="stat-card rounded-lg shadow-2xl p-4">
            <h3 class="font-bold mb-3 flex items-center gap-2" style="color: #2c3e50;">
                <i class="fas fa-chart-line" style="color: #d4a574;"></i>
                Statistiques
            </h3>
            
            <div class="space-y-3">
                <!-- Chats actifs -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-cat text-lg" style="color: #10b981;"></i>
                        <span class="text-sm">Chats actifs</span>
                    </div>
                    <span id="activeCats" class="font-bold text-lg" style="color: #10b981;">0</span>
                </div>
                
                <!-- Chats éliminés -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-times-circle text-lg" style="color: #ef4444;"></i>
                        <span class="text-sm">Éliminés</span>
                    </div>
                    <span id="eliminatedCats" class="font-bold text-lg" style="color: #ef4444;">0</span>
                </div>
                
                <!-- Temps de jeu -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-clock text-lg" style="color: #3b82f6;"></i>
                        <span class="text-sm">Temps de jeu</span>
                    </div>
                    <span id="gameTime" class="font-bold text-lg" style="color: #3b82f6;">00:00</span>
                </div>
                
                <!-- Dernière mise à jour -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-sync text-lg" style="color: #8b5cf6;"></i>
                        <span class="text-sm">Dernière MAJ</span>
                    </div>
                    <span id="lastUpdate" class="font-bold text-sm" style="color: #8b5cf6;">-</span>
                </div>
            </div>
        </div>
        
        <!-- Liste des chats -->
        <div class="stat-card rounded-lg shadow-2xl p-4 max-h-96 overflow-y-auto">
            <h3 class="font-bold mb-3 flex items-center gap-2" style="color: #2c3e50;">
                <i class="fas fa-list" style="color: #d4a574;"></i>
                Chats en jeu
            </h3>
            <div id="catsList" class="space-y-2"></div>
        </div>
        
        <!-- Bouton actualiser -->
        <button onclick="window.mapApp.manualRefresh()" 
                class="w-full stat-card rounded-lg shadow-2xl p-3 font-semibold flex items-center justify-center gap-2 hover:bg-gray-50 transition-colors"
                style="color: #2c3e50;">
            <i id="refreshIcon" class="fas fa-sync-alt"></i>
            Actualiser
        </button>
    </div>
    
    <div id="map"></div>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Custom Maps JS -->
    <script src="js/maps.js"></script>
    
    <script>
        // Extension pour les stats
        class GameStatsManager {
            constructor(mapApp) {
                this.mapApp = mapApp;
                this.startTime = Date.now();
                this.updateTimer();
                setInterval(() => this.updateTimer(), 1000);
            }
            
            updateStats(playersData) {
                // Compter les chats actifs et éliminés
                const activeCats = playersData.filter(p => p.status === 'active').length;
                const totalCats = playersData.length;
                const eliminatedCats = totalCats - activeCats;
                
                document.getElementById('activeCats').textContent = activeCats;
                document.getElementById('eliminatedCats').textContent = eliminatedCats;
                document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
                
                // Mettre à jour la liste des chats
                this.updateCatsList(playersData);
            }
            
            updateCatsList(playersData) {
                const html = playersData.map(p => `
                    <div class="flex items-center justify-between p-2 rounded" style="background-color: ${p.status === 'active' ? '#f0fdf4' : '#fef2f2'}; border-left: 3px solid ${p.color};">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-cat" style="color: ${p.color};"></i>
                            <span class="text-sm font-semibold">${p.player}</span>
                        </div>
                        <span class="text-xs px-2 py-1 rounded" style="background-color: ${p.status === 'active' ? '#10b981' : '#ef4444'}; color: white;">
                            ${p.status === 'active' ? 'Actif' : 'Éliminé'}
                        </span>
                    </div>
                `).join('');
                
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
        
        // Override de la méthode updatePositions pour ajouter les stats
        document.addEventListener('DOMContentLoaded', () => {
            // Attendre que mapApp soit initialisé
            setTimeout(() => {
                if (window.mapApp) {
                    const statsManager = new GameStatsManager(window.mapApp);
                    
                    // Intercepter les mises à jour de position
                    const originalUpdate = window.mapApp.updatePositions.bind(window.mapApp);
                    window.mapApp.updatePositions = async function() {
                        await originalUpdate();
                        
                        // Récupérer les données pour les stats
                        try {
                            const response = await fetch(`api/get_position.php?lobby_id=${window.mapApp.urlParams.lobbyId}`);
                            const result = await response.json();
                            
                            if (result.success) {
                                statsManager.updateStats(result.data);
                            }
                        } catch (error) {
                            console.error('Erreur stats:', error);
                        }
                    };
                }
            }, 100);
        });
    </script>
</body>
</html>