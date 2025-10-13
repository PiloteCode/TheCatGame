<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Joueur en temps réel</title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            overflow: hidden;
        }
        
        #map {
            height: 100vh;
            width: 100vw;
        }
        
        /* Style personnalisé pour le popup Leaflet */
        .leaflet-popup-content-wrapper {
            border-radius: 12px;
            padding: 0;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .leaflet-popup-content {
            margin: 0;
        }
        
        .leaflet-popup-tip {
            box-shadow: 0 3px 14px rgba(0, 0, 0, 0.1);
        }
        
        /* Correction des liens dans les attributions */
        .leaflet-control-attribution a {
            color: #2563eb !important;
        }
        
        .leaflet-control-attribution {
            background-color: rgba(255, 255, 255, 0.9) !important;
        }
        
        /* Conteneur pour l'icône avec les ondes */
        .player-marker-container {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Effet d'ondes radar permanentes */
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
        
        /* Image du joueur */
        .player-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 10;
        }
    </style>
</head>
<body>
    <!-- Contrôles en haut de l'écran -->
    <div class="fixed top-4 left-4 z-[1000] flex gap-3">
        <!-- Indicateur de connectivité -->
        <div class="bg-white rounded-lg shadow-lg px-4 py-3 flex items-center gap-2">
            <i id="networkIcon" class="fas fa-wifi text-xl"></i>
            <span id="networkText" class="text-sm font-medium text-gray-700">Connecté</span>
        </div>
        
        <!-- Bouton actualiser -->
        <button onclick="window.mapApp.manualRefresh()" class="bg-blue-500 hover:bg-blue-600 text-white rounded-lg shadow-lg px-4 py-3 flex items-center gap-2 transition-all duration-200">
            <i id="refreshIcon" class="fas fa-sync-alt"></i>
            <span class="text-sm font-medium">Actualiser</span>
        </button>
    </div>
    
    <div id="map"></div>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Custom Maps JS -->
    <script src="js/maps.js"></script>
</body>
</html>