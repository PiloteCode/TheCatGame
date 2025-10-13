// Configuration
const CONFIG = {
    defaultLat: 48.8566,
    defaultLng: 2.3522,
    defaultZoom: 13,
    updateInterval: 10000, // 10 secondes
    animationDuration: 2000, // 2 secondes
    apiEndpoint: 'api/fakeposition.php'
};

// Récupérer les paramètres URL
function getUrlParams() {
    const params = new URLSearchParams(window.location.search);
    return {
        lobbyId: params.get('lobby'),
        role: params.get('role'),
        playerId: params.get('id'),
        password: params.get('pwd')
    };
}

// Application principale
class MapApp {
    constructor() {
        this.map = null;
        this.players = {};
        this.urlParams = getUrlParams();
        
        // Vérifier les paramètres requis
        if (!this.urlParams.lobbyId) {
            alert('ID du lobby manquant');
            window.location.href = 'index.php';
            return;
        }
        
        this.init();
    }

    init() {
        // Initialisation de la carte
        this.map = L.map('map').setView([CONFIG.defaultLat, CONFIG.defaultLng], CONFIG.defaultZoom);
        
        // Ajout de la couche CartoDB Voyager
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            maxZoom: 19
        }).addTo(this.map);
        
        // Gestion du clic sur la carte
        this.map.on('click', (e) => this.handleMapClick(e));
        
        // Gestion du zoom
        this.map.on('zoomend', () => this.handleZoom());
        
        // Initialisation du statut réseau
        this.updateNetworkStatus();
        window.addEventListener('online', () => this.updateNetworkStatus());
        window.addEventListener('offline', () => this.updateNetworkStatus());
        
        // Première mise à jour
        this.updatePositions();
        
        // Mise à jour automatique
        setInterval(() => this.updatePositions(), CONFIG.updateInterval);
    }

    // Gestion du clic sur la carte
    handleMapClick(e) {
        const lat = e.latlng.lat.toFixed(6);
        const lng = e.latlng.lng.toFixed(6);
        
        const popupContent = `
            <div class="w-64 p-4">
                <div class="mb-4 pb-3 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">📍 Position</h3>
                    <p class="text-sm text-gray-500 font-mono">${lat}, ${lng}</p>
                </div>
                <div class="space-y-2">
                    <a href="https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}" target="_blank"
                       class="flex items-center gap-3 px-4 py-3 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-all duration-200 shadow-sm hover:shadow-md">
                        <i class="fas fa-directions text-lg"></i>
                        <span class="font-medium">Se diriger vers</span>
                    </a>
                </div>
            </div>
        `;
        
        L.popup({
            maxWidth: 300,
            className: 'custom-popup'
        })
            .setLatLng(e.latlng)
            .setContent(popupContent)
            .openOn(this.map);
    }

    // Gestion du zoom
    handleZoom() {
        Object.keys(this.players).forEach(playerId => {
            if (this.players[playerId].label) {
                const color = this.players[playerId].circle.options.color;
                this.players[playerId].label.setIcon(this.createPlayerIcon(color));
            }
        });
    }

    // Calcul de la taille de l'icône en fonction du zoom
    getIconSize() {
        const zoom = this.map.getZoom();
        const baseSize = 30;
        const scale = Math.pow(1.2, zoom - 13);
        return Math.max(20, Math.min(60, baseSize * scale));
    }

    // Création de l'icône du joueur avec ondes radar
    createPlayerIcon(color) {
        const size = this.getIconSize();
        return L.divIcon({
            className: 'custom-player-icon',
            html: `
                <div class="player-marker-container" style="width: ${size}px; height: ${size}px;">
                    <div class="radar-wave-permanent" style="border-color: ${color};"></div>
                    <div class="radar-wave-permanent" style="border-color: ${color};"></div>
                    <div class="radar-wave-permanent" style="border-color: ${color};"></div>
                    <img src="https://img.freepik.com/vecteurs-libre/vecteur-conception-degrade-colore-oiseau_343694-2506.jpg?semt=ais_hybrid&w=740&q=80" 
                         class="player-avatar" 
                         style="width: ${size}px; height: ${size}px;">
                </div>
            `,
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2]
        });
    }

    // Animation de déplacement d'un joueur
    animatePlayer(playerId, fromLat, fromLng, toLat, toLng, radius, color) {
        const steps = 60;
        const stepDuration = CONFIG.animationDuration / steps;
        let currentStep = 0;
        
        const animate = setInterval(() => {
            currentStep++;
            const progress = currentStep / steps;
            
            // Interpolation linéaire
            const currentLat = fromLat + (toLat - fromLat) * progress;
            const currentLng = fromLng + (toLng - fromLng) * progress;
            
            // Mise à jour du cercle
            if (this.players[playerId] && this.players[playerId].circle) {
                this.players[playerId].circle.setLatLng([currentLat, currentLng]);
                this.players[playerId].circle.setRadius(radius);
            }
            
            // Mise à jour du label
            if (this.players[playerId] && this.players[playerId].label) {
                this.players[playerId].label.setLatLng([currentLat, currentLng]);
            }
            
            // Fin de l'animation
            if (currentStep >= steps) {
                clearInterval(animate);
            }
        }, stepDuration);
    }

    // Mise à jour des positions depuis l'API
    async updatePositions() {
        try {
            const response = await fetch(`${CONFIG.apiEndpoint}?lobby_id=${this.urlParams.lobbyId}`);
            const result = await response.json();
            
            if (result.success) {
                let firstPlayer = true;
                
                result.data.forEach((playerData, index) => {
                    const { id, lat, lng, radius, player, color } = playerData;
                    
                    // Première apparition du joueur
                    if (!this.players[id]) {
                        this.players[id] = {
                            circle: L.circle([lat, lng], {
                                color: color,
                                fillColor: color,
                                fillOpacity: 0.2,
                                radius: radius
                            }).addTo(this.map),
                            label: L.marker([lat, lng], {
                                icon: this.createPlayerIcon(color)
                            }).addTo(this.map)
                        };
                        
                        // Centrer la carte sur le premier joueur uniquement au chargement initial
                        if (firstPlayer && Object.keys(this.players).length === 1) {
                            this.map.setView([lat, lng], CONFIG.defaultZoom);
                            firstPlayer = false;
                        }
                    } else {
                        // Animation vers la nouvelle position
                        const oldLatLng = this.players[id].circle.getLatLng();
                        this.animatePlayer(id, oldLatLng.lat, oldLatLng.lng, lat, lng, radius, color);
                    }
                });
            } else {
                console.error('Erreur API:', result.error);
                this.updateNetworkStatus(true);
            }
        } catch (error) {
            console.error('Erreur lors de la récupération des positions:', error);
            this.updateNetworkStatus(true);
        }
    }

    // Mise à jour du statut réseau
    updateNetworkStatus(error = false) {
        const networkIcon = document.getElementById('networkIcon');
        const networkText = document.getElementById('networkText');
        
        if (error) {
            networkIcon.className = 'fas fa-wifi-slash text-xl text-red-500';
            networkText.textContent = 'Erreur';
            networkText.className = 'text-sm font-medium text-red-600';
            return;
        }
        
        // Vérifier si l'API Network Information est disponible
        if ('connection' in navigator) {
            const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
            const effectiveType = connection.effectiveType;
            
            if (effectiveType === '4g' || effectiveType === '5g') {
                networkIcon.className = 'fas fa-wifi text-xl text-green-500';
                networkText.textContent = 'Excellent';
                networkText.className = 'text-sm font-medium text-green-600';
            } else if (effectiveType === '3g') {
                networkIcon.className = 'fas fa-wifi text-xl text-orange-500';
                networkText.textContent = 'Moyen';
                networkText.className = 'text-sm font-medium text-orange-600';
            } else if (effectiveType === '2g' || effectiveType === 'slow-2g') {
                networkIcon.className = 'fas fa-wifi text-xl text-red-500';
                networkText.textContent = 'Lent';
                networkText.className = 'text-sm font-medium text-red-600';
            } else {
                networkIcon.className = 'fas fa-wifi text-xl text-blue-500';
                networkText.textContent = 'Connecté';
                networkText.className = 'text-sm font-medium text-blue-600';
            }
        } else {
            // Si l'API n'est pas disponible, vérifier simplement si on est en ligne
            if (navigator.onLine) {
                networkIcon.className = 'fas fa-wifi text-xl text-green-500';
                networkText.textContent = 'En ligne';
                networkText.className = 'text-sm font-medium text-green-600';
            } else {
                networkIcon.className = 'fas fa-wifi-slash text-xl text-red-500';
                networkText.textContent = 'Hors ligne';
                networkText.className = 'text-sm font-medium text-red-600';
            }
        }
    }

    // Actualisation manuelle
    async manualRefresh() {
        const icon = document.getElementById('refreshIcon');
        
        // Animation de rotation
        icon.classList.add('fa-spin');
        
        // Appeler la mise à jour
        await this.updatePositions();
        
        setTimeout(() => {
            icon.classList.remove('fa-spin');
        }, 1000);
    }
}

// Initialisation de l'application au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    window.mapApp = new MapApp();
});