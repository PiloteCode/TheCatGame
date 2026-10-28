const CONFIG = {
    defaultLat: 48.8566,
    defaultLng: 2.3522,
    defaultZoom: 13,
    updateInterval: 5000,
    animationDuration: 2000
};

function getUrlParams() {
    const params = new URLSearchParams(window.location.search);
    return {
        lobbyId: params.get('lobby'),
        role: params.get('role'),
        playerId: params.get('id'),
        password: params.get('pwd')
    };
}

class MapApp {
    constructor() {
        this.map = null;
        this.players = {};
        this.urlParams = getUrlParams();
        this.firstLoad = true;
        
        if (!this.urlParams.lobbyId) {
            alert('ID du lobby manquant');
            window.location.href = 'index.php';
            return;
        }
        
        this.init();
    }

    init() {
        this.map = L.map('map').setView([CONFIG.defaultLat, CONFIG.defaultLng], CONFIG.defaultZoom);
        
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(this.map);
        
        this.map.on('click', (e) => this.handleMapClick(e));
        this.map.on('zoomend', () => this.handleZoom());
        
        this.updateNetworkStatus();
        window.addEventListener('online', () => this.updateNetworkStatus());
        window.addEventListener('offline', () => this.updateNetworkStatus());
        
        this.updatePositions();
        setInterval(() => this.updatePositions(), CONFIG.updateInterval);
    }

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

    handleZoom() {
        Object.keys(this.players).forEach(playerId => {
            if (this.players[playerId].marker) {
                const color = this.players[playerId].circle.options.color;
                this.players[playerId].marker.setIcon(this.createPlayerIcon(color));
            }
        });
    }

    getIconSize() {
        const zoom = this.map.getZoom();
        const baseSize = 20;
        const scale = Math.pow(1.15, zoom - 13);
        return Math.max(16, Math.min(40, baseSize * scale));
    }

    createPlayerIcon(color) {
        const size = this.getIconSize();
        return L.divIcon({
            className: 'custom-player-icon',
            html: `
                <div style="width: ${size}px; height: ${size}px; background-color: ${color}; border: 3px solid white; border-radius: 50%; box-shadow: 0 3px 8px rgba(0,0,0,0.4);"></div>
            `,
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2]
        });
    }

    animatePlayer(playerId, fromLat, fromLng, toLat, toLng, radius, color) {
        const steps = 60;
        const stepDuration = CONFIG.animationDuration / steps;
        let currentStep = 0;
        
        const animate = setInterval(() => {
            currentStep++;
            const progress = currentStep / steps;
            
            const currentLat = fromLat + (toLat - fromLat) * progress;
            const currentLng = fromLng + (toLng - fromLng) * progress;
            
            if (this.players[playerId] && this.players[playerId].circle) {
                this.players[playerId].circle.setLatLng([currentLat, currentLng]);
                this.players[playerId].circle.setRadius(radius);
            }
            
            if (this.players[playerId] && this.players[playerId].marker) {
                this.players[playerId].marker.setLatLng([currentLat, currentLng]);
            }
            
            if (currentStep >= steps) {
                clearInterval(animate);
            }
        }, stepDuration);
    }

    async updatePositions() {
        try {
            const response = await fetch(`api/get_position.php?lobby_id=${this.urlParams.lobbyId}`);
            const result = await response.json();
            
            if (result.success) {
                let firstValidPlayer = null;
                
                result.active_only.forEach(playerData => {
                    const { id, lat, lng, radius, player, color } = playerData;
                    
                    if (lat === null || lng === null) return;
                    
                    if (!firstValidPlayer) {
                        firstValidPlayer = { lat, lng };
                    }
                    
                    // Position publique : point + cercle
                    if (!this.players[id]) {
                        this.players[id] = {
                            circle: L.circle([lat, lng], {
                                color: color,
                                fillColor: color,
                                fillOpacity: 0.2,
                                radius: radius,
                                weight: 3
                            }).addTo(this.map),
                            marker: L.marker([lat, lng], {
                                icon: this.createPlayerIcon(color)
                            }).addTo(this.map)
                        };
                        
                        // Pas de popup pour les joueurs (mode hunter)
                        
                        if (this.firstLoad && firstValidPlayer && Object.keys(this.players).length === 1) {
                            this.map.setView([lat, lng], CONFIG.defaultZoom);
                        }
                    } else {
                        const oldLatLng = this.players[id].circle.getLatLng();
                        this.animatePlayer(id, oldLatLng.lat, oldLatLng.lng, lat, lng, radius, color);
                    }
                });
                
                this.firstLoad = false;
                this.updateNetworkStatus(false);
            } else {
                console.error('Erreur API:', result.error);
                this.updateNetworkStatus(true);
            }
        } catch (error) {
            console.error('Erreur lors de la récupération des positions:', error);
            this.updateNetworkStatus(true);
        }
    }

    updateNetworkStatus(error = false) {
        const networkIcon = document.getElementById('networkIcon');
        const networkText = document.getElementById('networkText');
        
        if (!networkIcon || !networkText) return;
        
        if (error) {
            networkIcon.className = 'fas fa-wifi-slash text-xl text-red-500';
            networkText.textContent = 'Erreur';
            networkText.className = 'text-sm font-medium text-red-600';
            return;
        }
        
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

    async manualRefresh() {
        const icon = document.getElementById('refreshIcon');
        if (icon) icon.classList.add('fa-spin');
        
        await this.updatePositions();
        
        if (icon) {
            setTimeout(() => {
                icon.classList.remove('fa-spin');
            }, 1000);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.mapApp = new MapApp();
});