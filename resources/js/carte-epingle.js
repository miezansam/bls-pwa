document.addEventListener('alpine:init', () => {
    window.Alpine.data('carteEpingle', ({ lieu, centres, defaut }) => ({
        ouverte: false,
        chargement: false,
        indisponible: false,
        placee: false,
        messagePosition: '',
        carte: null,
        marqueur: null,
        L: null,

        init() {
            this.placee = this.$wire[`${lieu}Latitude`] != null;
        },

        centre() {
            const zone = centres[this.$wire[`${lieu}ZoneId`]];

            return zone && zone[0] !== null ? zone : defaut;
        },

        async basculer() {
            this.ouverte = !this.ouverte;

            if (!this.ouverte) {
                return;
            }

            if (this.carte) {
                this.$nextTick(() => this.carte.invalidateSize());

                return;
            }

            await this.creer();
        },

        async creer() {
            this.chargement = true;

            try {
                const [{ default: L }] = await Promise.all([
                    import('leaflet'),
                    import('leaflet/dist/leaflet.css'),
                ]);
                this.L = L;

                const lat = this.$wire[`${lieu}Latitude`];
                const lng = this.$wire[`${lieu}Longitude`];
                const depart = lat != null ? [lat, lng] : this.centre();

                this.carte = L.map(this.$refs.carte).setView(depart, lat != null ? 16 : 14);

                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap',
                }).addTo(this.carte);

                this.carte.on('click', (e) => this.placer(e.latlng.lat, e.latlng.lng));

                if (lat != null) {
                    this.poser(lat, lng);
                }

                this.$nextTick(() => this.carte.invalidateSize());
            } catch (e) {
                this.indisponible = true;
            } finally {
                this.chargement = false;
            }
        },

        poser(lat, lng) {
            if (this.marqueur) {
                this.marqueur.setLatLng([lat, lng]);

                return;
            }

            const icone = this.L.divIcon({
                className: '',
                html: '<svg class="text-marine" viewBox="0 0 24 32" width="36" height="48" aria-hidden="true">'
                    + '<path fill="currentColor" d="M12 0C5.4 0 0 5.2 0 11.6 0 20.4 12 32 12 32s12-11.6 12-20.4C24 5.2 18.6 0 12 0z"/>'
                    + '<circle class="text-ambre" cx="12" cy="11.5" r="4.5" fill="currentColor"/></svg>',
                iconSize: [36, 48],
                iconAnchor: [18, 48],
            });

            this.marqueur = this.L.marker([lat, lng], { icon: icone, draggable: true }).addTo(this.carte);
            this.marqueur.on('dragend', () => {
                const p = this.marqueur.getLatLng();
                this.placer(p.lat, p.lng);
            });
        },

        placer(lat, lng) {
            lat = Math.round(lat * 1e6) / 1e6;
            lng = Math.round(lng * 1e6) / 1e6;

            this.$wire[`${lieu}Latitude`] = lat;
            this.$wire[`${lieu}Longitude`] = lng;
            this.placee = true;
            this.messagePosition = '';
            this.poser(lat, lng);
        },

        retirer() {
            if (this.marqueur) {
                this.marqueur.remove();
                this.marqueur = null;
            }

            this.$wire[`${lieu}Latitude`] = null;
            this.$wire[`${lieu}Longitude`] = null;
            this.placee = false;
        },

        async utiliserMaPosition() {
            this.messagePosition = '';

            if (!navigator.geolocation) {
                this.messagePosition = 'Votre téléphone ne permet pas de trouver votre position.';

                return;
            }

            this.ouverte = true;

            if (!this.carte) {
                await this.creer();
            }

            if (!this.carte) {
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (p) => {
                    this.placer(p.coords.latitude, p.coords.longitude);
                    this.carte.setView([p.coords.latitude, p.coords.longitude], 17);
                    this.$nextTick(() => this.carte.invalidateSize());
                },
                () => {
                    this.messagePosition = 'Position introuvable. Touchez la carte pour placer l\'épingle.';
                },
                { enableHighAccuracy: true, timeout: 10000 },
            );
        },
    }));
});