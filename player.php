<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Géant - Joueur</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body {
            background-color: #2c3e50;
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
    // Récupérer les paramètres
    $lobbyId = $_GET['lobby'] ?? '';
    $playerId = $_GET['id'] ?? '';
    $password = $_GET['pwd'] ?? '';
    
    if (empty($lobbyId) || empty($playerId) || empty($password)) {
        echo '<div class="min-h-screen flex items-center justify-center text-white text-xl">Paramètres manquants</div>';
        exit();
    }
    ?>
    
    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        <!-- En-tête -->
        <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full mb-6">
            <div class="text-center mb-6">
                <i class="fas fa-cat text-6xl mb-4" style="color: #d4a574;"></i>
                <h1 class="text-3xl font-bold" style="color: #2c3e50;">Mode Joueur</h1>
                <p class="text-gray-600 mt-2">Chat #<?php echo htmlspecialchars($playerId); ?></p>
            </div>
            
            <!-- Statut GPS -->
            <div id="statusCard" class="p-4 rounded-lg border-2 mb-4" style="border-color: #bdc3c7; background-color: #ecf0f1;">
                <div class="flex items-center gap-3">
                    <i id="statusIcon" class="fas fa-spinner fa-spin text-2xl" style="color: #d4a574;"></i>
                    <div class="flex-1">
                        <h3 id="statusTitle" class="font-bold" style="color: #2c3e50;">Initialisation...</h3>
                        <p id="statusText" class="text-sm text-gray-600">Demande d'accès à votre position</p>
                    </div>
                </div>
            </div>
            
            <!-- Informations -->
            <div class="space-y-2 text-sm text-gray-600">
                <div class="flex justify-between">
                    <span>Lobby:</span>
                    <span class="font-mono font-semibold"><?php echo htmlspecialchars($lobbyId); ?></span>
                </div>
                <div class="flex justify-between">
                    <span>Dernière mise à jour:</span>
                    <span id="lastUpdate" class="font-semibold">-</span>
                </div>
                <div class="flex justify-between">
                    <span>Position:</span>
                    <span id="currentPosition" class="font-mono text-xs">-</span>
                </div>
            </div>
        </div>
        
        <!-- Instructions -->
        <div class="bg-white/10 rounded-lg p-6 max-w-md w-full text-white">
            <h3 class="font-bold mb-2 flex items-center gap-2">
                <i class="fas fa-info-circle"></i>
                Instructions
            </h3>
            <ul class="text-sm space-y-1 opacity-90">
                <li>• Gardez cette page ouverte</li>
                <li>• Votre position sera envoyée automatiquement</li>
                <li>• Assurez-vous que le GPS est activé</li>
            </ul>
        </div>
    </div>
    
    <script>
        const LOBBY_ID = '<?php echo htmlspecialchars($lobbyId); ?>';
        const PLAYER_ID = <?php echo intval($playerId); ?>;
        const PASSWORD = '<?php echo htmlspecialchars($password); ?>';
        const UPDATE_INTERVAL = 5000; // 5 secondes
        
        let watchId = null;
        let currentPosition = null;
        
        // Éléments du DOM
        const statusCard = document.getElementById('statusCard');
        const statusIcon = document.getElementById('statusIcon');
        const statusTitle = document.getElementById('statusTitle');
        const statusText = document.getElementById('statusText');
        const lastUpdate = document.getElementById('lastUpdate');
        const currentPositionEl = document.getElementById('currentPosition');
        
        // Mettre à jour le statut
        function updateStatus(type, title, text) {
            statusCard.style.borderColor = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#bdc3c7';
            statusCard.style.backgroundColor = type === 'success' ? '#d1fae5' : type === 'error' ? '#fee2e2' : '#ecf0f1';
            
            statusIcon.className = type === 'success' 
                ? 'fas fa-check-circle text-2xl text-green-600'
                : type === 'error'
                ? 'fas fa-exclamation-circle text-2xl text-red-600'
                : 'fas fa-spinner fa-spin text-2xl';
            statusIcon.style.color = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#d4a574';
            
            statusTitle.textContent = title;
            statusText.textContent = text;
        }
        
        // Envoyer la position au serveur
        async function sendPosition(latitude, longitude) {
            try {
                const response = await fetch('api/update_position.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
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
                    updateStatus('success', 'Position envoyée', 'Votre position est à jour');
                    lastUpdate.textContent = new Date().toLocaleTimeString();
                    currentPositionEl.textContent = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
                } else {
                    updateStatus('error', 'Erreur', data.error || 'Impossible d\'envoyer la position');
                }
            } catch (error) {
                updateStatus('error', 'Erreur réseau', error.message);
            }
        }
        
        // Gérer le succès de la géolocalisation
        function handleSuccess(position) {
            currentPosition = position;
            const { latitude, longitude } = position.coords;
            
            // Envoyer immédiatement
            sendPosition(latitude, longitude);
        }
        
        // Gérer l'erreur de géolocalisation
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
                    break;
            }
            
            updateStatus('error', 'Erreur GPS', message);
        }
        
        // Démarrer le suivi de position
        function startTracking() {
            if (!navigator.geolocation) {
                updateStatus('error', 'Non supporté', 'Votre navigateur ne supporte pas la géolocalisation');
                return;
            }
            
            updateStatus('loading', 'En attente', 'Recherche de votre position...');
            
            // Suivi continu de la position
            watchId = navigator.geolocation.watchPosition(
                handleSuccess,
                handleError,
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
            
            // Envoyer la position régulièrement
            setInterval(() => {
                if (currentPosition) {
                    const { latitude, longitude } = currentPosition.coords;
                    sendPosition(latitude, longitude);
                }
            }, UPDATE_INTERVAL);
        }
        
        // Démarrer au chargement de la page
        window.addEventListener('DOMContentLoaded', startTracking);
        
        // Arrêter le suivi lors de la fermeture
        window.addEventListener('beforeunload', () => {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
            }
        });
    </script>
</body>
</html>