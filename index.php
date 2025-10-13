<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Géant - Accueil</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>
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
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
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
        <div class="text-center">
            <div class="mb-8" id="catIcon">
                <i class="fas fa-cat text-8xl mb-4 cat-icon" style="color: #d4a574;"></i>
            </div>
            
            <h1 id="title" class="text-5xl md:text-6xl font-bold mb-4" style="color: #ecf0f1;">
                Bienvenue sur le Chat Géant
            </h1>
            
            <p id="subtitle" class="text-xl md:text-2xl mb-12" style="color: #bdc3c7;">
                Suivez les joueurs en temps réel sur la carte
            </p>
            
            <div class="flex flex-col md:flex-row gap-4 justify-center items-center mb-8">
                <button onclick="openModal()" 
                   class="btn-custom px-8 py-4 rounded-lg font-semibold text-lg flex items-center gap-3 shadow-lg"
                   style="background-color: #d4a574; color: #2c3e50;">
                    <i class="fas fa-plus-circle"></i>
                    <span>Créer un lobby</span>
                </button>
                
                <button onclick="joinLobby()" 
                   class="btn-custom px-8 py-4 rounded-lg font-semibold text-lg flex items-center gap-3 shadow-lg"
                   style="background-color: #34495e; color: #ecf0f1;">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Rejoindre un lobby</span>
                </button>
            </div>
        </div>
        
        <div id="footer" class="absolute bottom-8 text-sm" style="color: #95a5a6;">
            © 2025 Pilote Code
        </div>
    </div>
    
    <div id="lobbyModal" class="modal">
        <div id="modalContent" class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold" style="color: #2c3e50;">Créer un lobby</h2>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-semibold mb-2" style="color: #2c3e50;">
                    Nom du lobby
                </label>
                <input type="text" 
                       id="lobbyName" 
                       placeholder="Entrez le nom du lobby"
                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-semibold mb-2" style="color: #2c3e50;">
                    Nombre de chats
                </label>
                <div class="flex items-center gap-4">
                    <button onclick="decreaseCount()" 
                            class="w-12 h-12 rounded-lg font-bold text-xl"
                            style="background-color: #34495e; color: #ecf0f1;">
                        <i class="fas fa-minus"></i>
                    </button>
                    <span id="catCount" class="text-3xl font-bold" style="color: #2c3e50;">2</span>
                    <button onclick="increaseCount()" 
                            class="w-12 h-12 rounded-lg font-bold text-xl"
                            style="background-color: #34495e; color: #ecf0f1;">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            
            <div class="mb-6">
                <label class="flex items-center gap-2 mb-3">
                    <input type="checkbox" id="defineNames" onchange="toggleNameInputs()" class="w-5 h-5">
                    <span class="text-sm font-semibold" style="color: #2c3e50;">Définir les pseudos maintenant</span>
                </label>
                
                <div id="nameInputs" class="space-y-2 hidden"></div>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-semibold mb-3" style="color: #2c3e50;">
                    Mode de jeu
                </label>
                
                <div onclick="selectMode('control')" 
                     id="modeControl"
                     class="mode-option p-4 mb-3 border-2 rounded-lg"
                     style="border-color: #bdc3c7;">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-tower-broadcast text-2xl" style="color: #d4a574;"></i>
                        <div class="flex-1 text-left">
                            <h3 class="font-bold mb-1" style="color: #2c3e50;">Tour de contrôle</h3>
                            <p class="text-sm" style="color: #7f8c8d;">
                                Contrôlez et gérez tous les joueurs à distance
                            </p>
                        </div>
                    </div>
                </div>
                
                <div onclick="selectMode('self')" 
                     id="modeSelf"
                     class="mode-option p-4 border-2 rounded-lg selected"
                     style="border-color: #d4a574; background-color: rgba(212, 165, 116, 0.1);">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-user-shield text-2xl" style="color: #d4a574;"></i>
                        <div class="flex-1 text-left">
                            <h3 class="font-bold mb-1" style="color: #2c3e50;">Self Control</h3>
                            <p class="text-sm" style="color: #7f8c8d;">
                                Chaque chat contrôle son propre cercle
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <button onclick="createLobby()" 
                    class="w-full py-4 rounded-lg font-bold text-lg shadow-lg hover:shadow-xl transition-all"
                    style="background-color: #d4a574; color: #2c3e50;">
                <i class="fas fa-check-circle mr-2"></i>
                Créer le lobby
            </button>
        </div>
    </div>
    
    <div id="loadingModal" class="modal">
        <div class="bg-white rounded-xl shadow-2xl p-12 max-w-md w-full mx-4 text-center">
            <div class="mb-6">
                <i class="fas fa-spinner fa-spin text-6xl" style="color: #d4a574;"></i>
            </div>
            <h2 class="text-2xl font-bold mb-2" style="color: #2c3e50;">Création du lobby...</h2>
            <p class="text-gray-600">Configuration en cours</p>
        </div>
    </div>
    
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
        });
        
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
        
        function increaseCount() {
            if (catCount < 10) {
                catCount++;
                updateCount();
                updateNameInputs();
            }
        }
        
        function decreaseCount() {
            if (catCount > 1) {
                catCount--;
                updateCount();
                updateNameInputs();
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
        
        function toggleNameInputs() {
            const checkbox = document.getElementById('defineNames');
            const container = document.getElementById('nameInputs');
            
            if (checkbox.checked) {
                container.classList.remove('hidden');
                updateNameInputs();
            } else {
                container.classList.add('hidden');
            }
        }
        
        function updateNameInputs() {
            const checkbox = document.getElementById('defineNames');
            if (!checkbox.checked) return;
            
            const container = document.getElementById('nameInputs');
            let html = '';
            
            for (let i = 1; i <= catCount; i++) {
                html += `
                    <input type="text" 
                           id="playerName${i}" 
                           placeholder="Chat ${i}"
                           class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 text-sm">
                `;
            }
            
            container.innerHTML = html;
        }
        
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
        }
        
        async function createLobby() {
            const lobbyName = document.getElementById('lobbyName').value;
            
            if (!lobbyName.trim()) {
                alert('Veuillez entrer un nom de lobby');
                return;
            }
            
            const defineNames = document.getElementById('defineNames').checked;
            let playerNames = [];
            
            if (defineNames) {
                for (let i = 1; i <= catCount; i++) {
                    const name = document.getElementById(`playerName${i}`).value.trim();
                    playerNames.push(name || null);
                }
            }
            
            closeModal();
            
            setTimeout(() => {
                showLoadingModal();
                
                fetch('api/create_lobby.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        lobby_name: lobbyName,
                        cat_count: catCount,
                        mode: selectedMode,
                        player_names: playerNames
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
            
            if (mode === 'control' && controlPassword) {
                const controlUrl = `${baseUrl}control.php?lobby=${lobbyId}&pwd=${controlPassword}`;
                html += `
                    <div class="mb-6 p-4 border-2 rounded-lg" style="border-color: #d4a574; background-color: rgba(212, 165, 116, 0.05);">
                        <div class="flex items-center gap-2 mb-3">
                            <i class="fas fa-tower-broadcast text-2xl" style="color: #d4a574;"></i>
                            <h3 class="font-bold text-xl" style="color: #2c3e50;">Tour de contrôle</h3>
                        </div>
                        <div class="mb-3">
                            <label class="block text-sm font-semibold mb-1">Mot de passe :</label>
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
                            <label class="block text-sm font-semibold mb-1">Lien d'accès :</label>
                            <div class="flex gap-2">
                                <input type="text" value="${controlUrl}" readonly 
                                       class="flex-1 px-3 py-2 bg-white border-2 border-gray-300 rounded text-sm">
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
            
            html += `<div class="space-y-4">`;
            players.forEach(player => {
                const chatUrl = `${baseUrl}player.php?lobby=${lobbyId}&id=${player.player_id}&pwd=${player.password}`;
                const playerName = player.player_name || `Chat ${player.player_id}`;
                
                html += `
                    <div class="p-4 border-2 border-gray-300 rounded-lg">
                        <div class="flex items-center gap-2 mb-3">
                            <i class="fas fa-cat text-2xl" style="color: ${player.color};"></i>
                            <h3 class="font-bold text-lg">${playerName}</h3>
                        </div>
                        ${!player.player_name ? '<p class="text-sm text-gray-600 mb-3">Le pseudo peut être défini via la tour de contrôle</p>' : ''}
                        <div class="mb-3">
                            <label class="block text-sm font-semibold mb-1">Mot de passe :</label>
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
                            <label class="block text-sm font-semibold mb-1">Lien d'accès :</label>
                            <div class="flex gap-2">
                                <input type="text" value="${chatUrl}" readonly 
                                       class="flex-1 px-3 py-2 bg-white border-2 border-gray-300 rounded text-sm">
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
            
            if (mode === 'control') {
                html += `
                    <div class="mt-6 space-y-3">
                        <a href="control.php?lobby=${lobbyId}&pwd=${controlPassword}" 
                           class="block w-full py-4 rounded-lg font-bold text-lg text-center shadow-lg"
                           style="background-color: #d4a574; color: #2c3e50;">
                            <i class="fas fa-tower-broadcast mr-2"></i>
                            Accéder à la Tour de Contrôle
                        </a>
                        <a href="game.php?lobby=${lobbyId}" 
                           class="block w-full py-4 rounded-lg font-bold text-lg text-center shadow-lg"
                           style="background-color: #10b981; color: white;">
                            <i class="fas fa-search mr-2"></i>
                            Rejoindre en tant que Chercheur
                        </a>
                    </div>
                `;
            } else {
                html += `
                    <div class="mt-6">
                        <a href="game.php?lobby=${lobbyId}" 
                           class="block w-full py-4 rounded-lg font-bold text-lg text-center shadow-lg"
                           style="background-color: #10b981; color: white;">
                            <i class="fas fa-search mr-2"></i>
                            Rejoindre en tant que Chercheur
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
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                const btn = event.target.closest('button');
                const icon = btn.querySelector('i');
                icon.className = 'fas fa-check';
                
                setTimeout(() => {
                    icon.className = 'fas fa-copy';
                }, 1500);
            });
        }
        
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
        
        function joinLobby() {
            const lobbyCode = prompt('Entrez le code du lobby :');
            if (lobbyCode) {
                window.location.href = `game.php?lobby=${encodeURIComponent(lobbyCode)}`;
            }
        }
        
        document.getElementById('lobbyModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        
        document.getElementById('resultModal').addEventListener('click', function(e) {
            if (e.target === this) closeResultModal();
        });
    </script>
</body>
</html>