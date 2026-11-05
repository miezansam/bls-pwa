document.addEventListener('alpine:init', () => {
    window.Alpine.data('carteSuivi', ({ canal, position, defaut }) => ({
        carte: null,
        marqueur: null,
        L: null,
        suivre: true,
        connecte: true,
        indisponible: false,
        position: null,
        maintenant: Date.now(),
        horloge: null,
        relance: null,
        surEtat: null,

        async init() {
            this.horloge = setInterval(() => { this.maintenant = Date.now(); }, 1000);

            if (position) {
                this.position = { ...position, recu: Date.now() };
            }

            this.ecouter();
            await this.creerCarte();
        },

        destroy() {
            clearInterval(this.horloge);
            clearInterval(this.relance);

            const connexion = window.Echo?.connector?.pusher?.connection;

            if (connexion && this.surEtat) {
                connexion.unbind('state_change', this.surEtat);
            }

            // On retire seulement notre écoute : le canal sert aussi aux changements de statut
            window.Echo?.private(canal).stopListening('.position.change');
        },

        get age() {
            return this.position === null
                ? null
                : Math.round(this.position.age_s + (this.maintenant - this.position.recu) / 1000);
        },

        get perdu() {
            return this.age !== null && this.age > 90;
        },

        get texteAge() {
            const a = this.age;

            if (a === null) {
                return 'La position du livreur n\'est pas encore disponible.';
            }

            if (this.perdu) {
                return `Dernière position il y a ${Math.round(a / 60)} min. Le livreur est peut-être sans réseau.`;
            }

            if (a < 10) {
                return 'Position mise à jour à l\'instant.';
            }

            return a < 60 ? `Position mise à jour il y a ${a} s.` : `Position mise à jour il y a ${Math.round(a / 60)} min.`;
        },

        ecouter() {
            if (!window.Echo) {
                return;
            }

            window.Echo.private(canal).listen('.position.change', (e) => this.recevoir(e));

            const connexion = window.Echo.connector?.pusher?.connection;

            if (!connexion) {
                return;
            }

            const coupe = (etat) => ['unavailable', 'failed', 'disconnected'].includes(etat);

            this.surEtat = ({ current }) => {
                const etaitCoupe = !this.connecte;
                this.connecte = !coupe(current);

                if (this.connecte) {
                    clearInterval(this.relance);
                    this.relance = null;

                    if (etaitCoupe) {
                        this.rafraichir();
                    }
                } else if (this.relance === null) {
                    this.relance = setInterval(() => this.rafraichir(), 12000);
                }
            };

            connexion.bind('state_change', this.surEtat);
            this.surEtat({ current: connexion.state });
        },

        async rafraichir() {
            try {
                const p = await this.$wire.positionActuelle();

                if (p) {
                    this.recevoir(p);
                }
            } catch (e) {
                // Réseau toujours coupé : on réessaiera au prochain tour
            }
        },

        recevoir(e) {
            const recu = Date.now();
            const nouvelle = { latitude: e.latitude, longitude: e.longitude, age_s: e.age_s ?? 0, recu };

            // Une position plus ancienne que celle déjà affichée est ignorée
            if (this.position && recu - nouvelle.age_s * 1000 < this.position.recu - this.position.age_s * 1000) {
                return;
            }

            this.position = nouvelle;
            this.afficher();
        },

        async creerCarte() {
            try {
                const [{ default: L }] = await Promise.all([
                    import('leaflet'),
                    import('leaflet/dist/leaflet.css'),
                ]);
                this.L = L;

                const depart = this.position ? [this.position.latitude, this.position.longitude] : defaut;

                this.carte = L.map(this.$refs.carte).setView(depart, this.position ? 16 : 13);

                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap',
                }).addTo(this.carte);

                // Si le client déplace la carte, on arrête de la recentrer sur le livreur
                this.carte.on('dragstart', () => { this.suivre = false; });

                this.afficher();
                this.$nextTick(() => this.carte.invalidateSize());
            } catch (e) {
                this.indisponible = true;
            }
        },

        afficher() {
            if (!this.carte || !this.position) {
                return;
            }

            const point = [this.position.latitude, this.position.longitude];

            if (this.marqueur) {
                this.marqueur.setLatLng(point);
            } else {
                const icone = this.L.divIcon({
                    className: '',
                    html: '<svg viewBox="0 0 24 24" width="32" height="32" aria-hidden="true">'
                        + '<circle class="text-ambre" cx="12" cy="12" r="10" fill="currentColor"/>'
                        + '<circle class="text-marine" cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="3"/>'
                        + '<circle class="text-marine" cx="12" cy="12" r="3.5" fill="currentColor"/></svg>',
                    iconSize: [32, 32],
                    iconAnchor: [16, 16],
                });

                this.marqueur = this.L.marker(point, { icon: icone, interactive: false, keyboard: false }).addTo(this.carte);
                this.carte.setView(point, 16);

                return;
            }

            if (this.suivre) {
                this.carte.panTo(point);
            }
        },

        recentrer() {
            this.suivre = true;
            this.afficher();
        },
    }));
});