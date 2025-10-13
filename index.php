<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Géant - Accueil</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Anime.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    
    <style>
        body {
            overflow: hidden;
            background-color: #2c3e50;
        }
        
        .btn-custom {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .cat-icon {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }
        
        .animate-element {
            opacity: 0;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            opacity: 0;
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .mode-option {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .mode-option.selected {
            border-color: #d4a574;
            background-color: rgba(212, 165, 116, 0.1);
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex flex-col items-center justify-center px-4">
        <!-- Contenu principal -->
        <div class="text-center">
            <!-- Icône principale -->
            <div class="mb-8 animate-element" id="catIcon">
                <i class="fas fa-cat text-8xl mb-4 cat-icon" style="color: #d4a574;"></i>
            </div>
            
            <!-- Titre -->
            <h1 id="title" class="text-5xl md:text-6xl font-bold mb-4 animate-element" style="color: #ecf0f1;">
                Bienvenue sur le Chat Géant
            </h1>
            
            <!-- Sous-titre -->
            <p id="subtitle" class="text-xl md:text-2xl mb-12 animate-element" style="color: #bdc3c7;">
                Suivez les joueurs en temps réel sur la carte
            </p>
            
            <!-- Boutons -->
            <div class="flex flex-col md:flex-row gap-4 justify-center items-center mb-8">
                <!-- Bouton Créer un lobby -->
                <button onclick="openModal()" 
                   class="btn-custom animate-element px-8 py-4 rounded-lg font-semibold text-lg flex items-center gap-3 shadow-lg"
                   style="background-color: #d4a574; color: #2c3e50;">
                    <i class="fas fa-plus-circle"></i>
                    <span>Créer un lobby</span>
                </button>
                
                <!-- Bouton Rejoindre un lobby -->
                <button onclick="joinLobby()" 
                   class="btn-custom animate-element px-8 py-4 rounded-lg font-semibold text-lg flex items-center gap-3 shadow-lg"
                   style="background-color: #34495e; color: #ecf0f1;">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Rejoindre un lobby</span>
                </button>
                
                <!-- Bouton Discord -->
                <a href="https://discord.com" 
                   target="_blank"
                   class="btn-custom animate-element px-8 py-4 rounded-lg font-semibold text-lg flex items-center gap-3 shadow-lg"
                   style="background-color: #34495e; color: #ecf0f1;">
                    <i class="fab fa-discord"></i>
                    <span>Discord</span>
                </a>
            </div>
        </div>
        
        <!-- Footer -->
        <div id="footer" class="absolute bottom-8 text-sm animate-element" style="color: #95a5a6;">
            © 2025 Pilote Code
        </div>
    </div>
    
    <!-- Modal Créer un lobby -->
    <div id="lobbyModal" class="modal">
        <div id="modalContent" class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold" style="color: #2c3e50;">Créer un lobby</h2>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <!-- Nom du lobby -->
            <div class="mb-6">
                <label class="block text-sm font-semibold mb-2" style="color: #2c3e50;">
                    Nom du lobby
                </label>
                <input type="text" 
                       id="lobbyName" 
                       placeholder="Entrez le nom du lobby"
                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition-colors"
                       style="border-color: #bdc3c7;">
            </div>
            
            <!-- Nombre de chats -->
            <div class="mb-6">
                <label class="block text-sm font-semibold mb-2" style="color: #2c3e50;">
                    Nombre de chats
                </label>
                <div class="flex items-center gap-4">
                    <button onclick="decreaseCount()" 
                            class="w-12 h-12 rounded-lg font-bold text-xl flex items-center justify-center"
                            style="background-color: #34495e; color: #ecf0f1;">
                        <i class="fas fa-minus"></i>
                    </button>
                    <span id="catCount" class="text-3xl font-bold" style="color: #2c3e50;">2</span>
                    <button onclick="increaseCount()" 
                            class="w-12 h-12 rounded-lg font-bold text-xl flex items-center justify-center"
                            style="background-color: #34495e; color: #ecf0f1;">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            
            <!-- Mode de jeu -->
            <div class="mb-6">
                <label class="block text-sm font-semibold mb-3" style="color: #2c3e50;">
                    Mode de jeu
                </label>
                
                <!-- Tour de contrôle -->
                <div onclick="selectMode('control')" 
                     id="modeControl"
                     class="mode-option p-4 mb-3 border-2 rounded-lg"
                     style="border-color: #bdc3c7;">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-tower-broadcast text-2xl" style="color: #d4a574;"></i>
                        <div class="flex-1 text-left">
                            <h3 class="font-bold mb-1" style="color: #2c3e50;">Tour de contrôle</h3>
                            <p class="text-sm" style="color: #7f8c8d;">
                                Visualisez la position des chats, joueurs, et gérez le jeu à distance
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Self control -->
                <div onclick="selectMode('self')" 
                     id="modeSelf"
                     class="mode-option p-4 border-2 rounded-lg selected"
                     style="border-color: #d4a574; background-color: rgba(212, 165, 116, 0.1);">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-user-shield text-2xl" style="color: #d4a574;"></i>
                        <div class="flex-1 text-left">
                            <h3 class="font-bold mb-1" style="color: #2c3e50;">Self Control</h3>
                            <p class="text-sm" style="color: #7f8c8d;">
                                Chaque chat contrôle son cercle (limité)
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bouton Créer -->
            <button onclick="createLobby()" 
                    class="w-full py-4 rounded-lg font-bold text-lg shadow-lg hover:shadow-xl transition-all"
                    style="background-color: #d4a574; color: #2c3e50;">
                <i class="fas fa-check-circle mr-2"></i>
                Créer le lobby
            </button>
        </div>
    </div>
    
    <!-- Modal Chargement -->
    <div id="loadingModal" class="modal">
        <div class="bg-white rounded-xl shadow-2xl p-12 max-w-md w-full mx-4 text-center">
            <div class="mb-6">
                <i class="fas fa-spinner fa-spin text-6xl" style="color: #d4a574;"></i>
            </div>
            <h2 class="text-2xl font-bold mb-2" style="color: #2c3e50;">Création du lobby...</h2>
            <p class="text-gray-600">Configuration en cours</p>
        </div>
    </div>
    
    <!-- Modal Résultats -->
    <div id="resultModal" class="modal">
        <div id="resultContent" class="bg-white rounded-xl shadow-2xl p-8 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold" style="color: #2c3e50;">
                    <i class="fas fa-check-circle mr-2" style="color: #10b981;"></i>
                    Lobby créé !
                </h2>
                <button onclick="closeResultModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <div id="resultsContent"></div>
        </div>
    </div>
    
    <script>
        let catCount = 2;
        let selectedMode = 'self';
        let lobbyId = '';
        
        // Animations au chargement
        window.addEventListener('DOMContentLoaded', () => {
            anime({
                targets: '#catIcon',
                opacity: [0, 1],
                scale: [0.5, 1.2, 1],
                duration: 1000,
                easing: 'easeOutElastic(1, .5)'
            });
            
            anime({
                targets: '#title',
                opacity: [0, 1],
                translateY: [30, 0],
                duration: 800,
                delay: 200,
                easing: 'easeOutQuad'
            });
            
            anime({
                targets: '#subtitle',
                opacity: [0, 1],
                translateY: [30, 0],
                duration: 800,
                delay: 400,
                easing: 'easeOutQuad'
            });
            
            anime({
                targets: '.btn-custom',
                opacity: [0, 1],
                translateY: [30, 0],
                duration: 600,
                delay: anime.stagger(100, {start: 600}),
                easing: 'easeOutQuad'
            });
            
            anime({
                targets: '#footer',
                opacity: [0, 1],
                duration: 1000,
                delay: 1000,
                easing: 'easeOutQuad'
            });
            
            const buttons = document.querySelectorAll('.btn-custom');
            buttons.forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    anime({
                        targets: this,
                        scale: 1.05,
                        translateY: -5,
                        duration: 300,
                        easing: 'easeOutQuad'
                    });
                });
                
                btn.addEventListener('mouseleave', function() {
                    anime({
                        targets: this,
                        scale: 1,
                        translateY: 0,
                        duration: 300,
                        easing: 'easeOutQuad'
                    });
                });
            });
        });
        
        // Gestion du modal
        function openModal() {
            const modal = document.getElementById('lobbyModal');
            const content = document.getElementById('modalContent');
            
            modal.classList.add('active');
            
            anime({
                targets: modal,
                opacity: [0, 1],
                duration: 300,
                easing: 'easeOutQuad'
            });
            
            anime({
                targets: content,
                scale: [0.8, 1],
                opacity: [0, 1],
                duration: 400,
                easing: 'easeOutBack'
            });
        }
        
        function closeModal() {
            const modal = document.getElementById('lobbyModal');
            const content = document.getElementById('modalContent');
            
            anime({
                targets: content,
                scale: [1, 0.8],
                opacity: [1, 0],
                duration: 300,
                easing: 'easeInQuad'
            });
            
            anime({
                targets: modal,
                opacity: [1, 0],
                duration: 300,
                easing: 'easeInQuad',
                complete: () => {
                    modal.classList.remove('active');
                }
            });
        }
        
        // Gestion du nombre de chats
        function increaseCount() {
            if (catCount < 10) {
                catCount++;
                updateCount();
            }
        }
        
        function decreaseCount() {
            if (catCount > 1) {
                catCount--;
                updateCount();
            }
        }
        
        function updateCount() {
            const countEl = document.getElementById('catCount');
            anime({
                targets: countEl,
                scale: [1, 1.3, 1],
                duration: 300,
                easing: 'easeOutQuad'
            });
            countEl.textContent = catCount;
        }
        
        // Sélection du mode
        function selectMode(mode) {
            selectedMode = mode;
            
            const controlEl = document.getElementById('modeControl');
            const selfEl = document.getElementById('modeSelf');
            
            if (mode === 'control') {
                controlEl.classList.add('selected');
                selfEl.classList.remove('selected');
                controlEl.style.borderColor = '#d4a574';
                controlEl.style.backgroundColor = 'rgba(212, 165, 116, 0.1)';
                selfEl.style.borderColor = '#bdc3c7';
                selfEl.style.backgroundColor = 'transparent';
            } else {
                selfEl.classList.add('selected');
                controlEl.classList.remove('selected');
                selfEl.style.borderColor = '#d4a574';
                selfEl.style.backgroundColor = 'rgba(212, 165, 116, 0.1)';
                controlEl.style.borderColor = '#bdc3c7';
                controlEl.style.backgroundColor = 'transparent';
            }
            
            anime({
                targets: mode === 'control' ? controlEl : selfEl,
                scale: [1, 1.02, 1],
                duration: 300,
                easing: 'easeOutQuad'
            });
        }
        
        // Créer le lobby
        async function createLobby() {
            const lobbyName = document.getElementById('lobbyName').value;
            
            if (!lobbyName.trim()) {
                alert('Veuillez entrer un nom de lobby');
                return;
            }
            
            // Fermer le modal de création
            closeModal();
            
            // Afficher le chargement
            setTimeout(() => {
                showLoadingModal();
                
                // Appeler l'API pour créer le lobby
                fetch('api/create_lobby.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        lobby_name: lobbyName,
                        cat_count: catCount,
                        mode: selectedMode
                    })
                })
                .then(response => response.json())
                .then(data => {
                    hideLoadingModal();
                    
                    if (data.success) {
                        showResults(data.data);
                    } else {
                        alert('Erreur: ' + (data.error || 'Impossible de créer le lobby'));
                    }
                })
                .catch(error => {
                    hideLoadingModal();
                    alert('Erreur de connexion: ' + error.message);
                });
            }, 300);
        }
        
        // Afficher le modal de chargement
        function showLoadingModal() {
            const modal = document.getElementById('loadingModal');
            modal.classList.add('active');
            
            anime({
                targets: modal,
                opacity: [0, 1],
                duration: 300,
                easing: 'easeOutQuad'
            });
        }
        
        // Cacher le modal de chargement
        function hideLoadingModal() {
            const modal = document.getElementById('loadingModal');
            
            anime({
                targets: modal,
                opacity: [1, 0],
                duration: 300,
                easing: 'easeInQuad',
                complete: () => {
                    modal.classList.remove('active');
                }
            });
        }
        
        // Afficher les résultats
        function showResults(data) {
            const baseUrl = window.location.origin + window.location.pathname.replace('index.php', '');
            const lobbyId = data.lobby_id;
            const lobbyName = data.lobby_name;
            const mode = data.mode;
            const controlPassword = data.control_password;
            const players = data.players;
            
            let html = `
                <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-info-circle text-blue-600"></i>
                        <h3 class="font-bold text-blue-900">Informations du lobby</h3>
                    </div>
                    <p class="text-blue-800"><strong>Nom :</strong> ${lobbyName}</p>
                    <p class="text-blue-800"><strong>ID :</strong> ${lobbyId}</p>
                    <p class="text-blue-800"><strong>Mode :</strong> ${mode === 'control' ? 'Tour de contrôle' : 'Self Control'}</p>
                </div>
            `;
            
            // Si mode Tour de contrôle
            if (mode === 'control' && controlPassword) {
                const controlUrl = `${baseUrl}started.php?lobby=${lobbyId}&role=control&pwd=${controlPassword}`;
                html += `
                    <div class="mb-6 p-4 border-2 rounded-lg" style="border-color: #d4a574; background-color: rgba(212, 165, 116, 0.05);">
                        <div class="flex items-center gap-2 mb-3">
                            <i class="fas fa-tower-broadcast text-2xl" style="color: #d4a574;"></i>
                            <h3 class="font-bold text-xl" style="color: #2c3e50;">Tour de contrôle</h3>
                        </div>
                        <div class="mb-3">
                            <label class="block text-sm font-semibold mb-1" style="color: #2c3e50;">Mot de passe :</label>
                            <div class="flex gap-2">
                                <input type="text" value="${controlPassword}" readonly 
                                       class="flex-1 px-3 py-2 bg-white border-2 border-gray-300 rounded font-mono text-sm">
                                <button onclick="copyToClipboard('${controlPassword}')" 
                                        class="px-4 py-2 rounded font-semibold"
                                        style="background-color: #34495e; color: #ecf0f1;">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1" style="color: #2c3e50;">Lien d'accès :</label>
                            <div class="flex gap-2">
                                <input type="text" value="${controlUrl}" readonly 
                                       class="flex-1 px-3 py-2 bg-white border-2 border-gray-300 rounded text-sm overflow-hidden text-ellipsis">
                                <button onclick="copyToClipboard('${controlUrl}')" 
                                        class="px-4 py-2 rounded font-semibold"
                                        style="background-color: #34495e; color: #ecf0f1;">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Chats
            html += `<div class="space-y-4">`;
            players.forEach((player, index) => {
                const chatUrl = `${baseUrl}player.php?lobby=${lobbyId}&id=${player.player_id}&pwd=${player.password}`;
                html += `
                    <div class="p-4 border-2 border-gray-300 rounded-lg">
                        <div class="flex items-center gap-2 mb-3">
                            <i class="fas fa-cat text-2xl" style="color: ${player.color};"></i>
                            <h3 class="font-bold text-lg" style="color: #2c3e50;">${player.player_name} (Chat ${player.player_id})</h3>
                        </div>
                        <div class="mb-3">
                            <label class="block text-sm font-semibold mb-1" style="color: #2c3e50;">Mot de passe :</label>
                            <div class="flex gap-2">
                                <input type="text" value="${player.password}" readonly 
                                       class="flex-1 px-3 py-2 bg-white border-2 border-gray-300 rounded font-mono text-sm">
                                <button onclick="copyToClipboard('${player.password}')" 
                                        class="px-4 py-2 rounded font-semibold"
                                        style="background-color: #34495e; color: #ecf0f1;">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1" style="color: #2c3e50;">Lien d'accès :</label>
                            <div class="flex gap-2">
                                <input type="text" value="${chatUrl}" readonly 
                                       class="flex-1 px-3 py-2 bg-white border-2 border-gray-300 rounded text-sm overflow-hidden text-ellipsis">
                                <button onclick="copyToClipboard('${chatUrl}')" 
                                        class="px-4 py-2 rounded font-semibold"
                                        style="background-color: #34495e; color: #ecf0f1;">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += `</div>`;
            
            // Bouton pour aller à la tour de contrôle ou au premier chat
            if (mode === 'control') {
                html += `
                    <div class="mt-6 space-y-3">
                        <a href="control.php?lobby=${lobbyId}&pwd=${controlPassword}" 
                           class="block w-full py-4 rounded-lg font-bold text-lg text-center shadow-lg hover:shadow-xl transition-all"
                           style="background-color: #d4a574; color: #2c3e50;">
                            <i class="fas fa-tower-broadcast mr-2"></i>
                            Accéder à la Tour de Contrôle
                        </a>
                        <a href="game.php?lobby=${lobbyId}" 
                           class="block w-full py-4 rounded-lg font-bold text-lg text-center shadow-lg hover:shadow-xl transition-all"
                           style="background-color: #10b981; color: white;">
                            <i class="fas fa-search mr-2"></i>
                            Mode Chercheur (avec stats)
                        </a>
                        <a href="started.php?lobby=${lobbyId}" 
                           class="block w-full py-4 rounded-lg font-bold text-lg text-center shadow-lg hover:shadow-xl transition-all"
                           style="background-color: #34495e; color: #ecf0f1;">
                            <i class="fas fa-eye mr-2"></i>
                            Carte simple (spectateur)
                        </a>
                    </div>
                `;
            } else {
                html += `
                    <div class="mt-6 space-y-3">
                        <a href="game.php?lobby=${lobbyId}" 
                           class="block w-full py-4 rounded-lg font-bold text-lg text-center shadow-lg hover:shadow-xl transition-all"
                           style="background-color: #10b981; color: white;">
                            <i class="fas fa-search mr-2"></i>
                            Mode Chercheur (avec stats)
                        </a>
                        <a href="started.php?lobby=${lobbyId}" 
                           class="block w-full py-4 rounded-lg font-bold text-lg text-center shadow-lg hover:shadow-xl transition-all"
                           style="background-color: #34495e; color: #ecf0f1;">
                            <i class="fas fa-eye mr-2"></i>
                            Carte simple (spectateur)
                        </a>
                    </div>
                `;
            }
            
            document.getElementById('resultsContent').innerHTML = html;
            
            const modal = document.getElementById('resultModal');
            const content = document.getElementById('resultContent');
            
            modal.classList.add('active');
            
            anime({
                targets: modal,
                opacity: [0, 1],
                duration: 300,
                easing: 'easeOutQuad'
            });
            
            anime({
                targets: content,
                scale: [0.8, 1],
                opacity: [0, 1],
                duration: 400,
                easing: 'easeOutBack'
            });
        }
        
        // Copier dans le presse-papier
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Animation de confirmation
                const btn = event.target.closest('button');
                const icon = btn.querySelector('i');
                icon.className = 'fas fa-check';
                
                setTimeout(() => {
                    icon.className = 'fas fa-copy';
                }, 1500);
            });
        }
        
        // Fermer le modal de résultats
        function closeResultModal() {
            const modal = document.getElementById('resultModal');
            const content = document.getElementById('resultContent');
            
            anime({
                targets: content,
                scale: [1, 0.8],
                opacity: [1, 0],
                duration: 300,
                easing: 'easeInQuad'
            });
            
            anime({
                targets: modal,
                opacity: [1, 0],
                duration: 300,
                easing: 'easeInQuad',
                complete: () => {
                    modal.classList.remove('active');
                }
            });
        }
        
        // Rejoindre un lobby
        function joinLobby() {
            const lobbyCode = prompt('Entrez le code du lobby :');
            if (lobbyCode) {
                window.location.href = `started.php?join=${encodeURIComponent(lobbyCode)}`;
            }
        }
        
        // Fermer le modal en cliquant en dehors
        document.getElementById('lobbyModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        document.getElementById('resultModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeResultModal();
            }
        });
    </script>
</body>
</html>