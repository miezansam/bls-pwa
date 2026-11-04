document.addEventListener('alpine:init', () => {
    window.Alpine.data('suiviGps', ({ url, intervalle }) => ({
        etat: 'attente',
        file: [],
        derniere: null,
        dernierMis: 0,
        watchId: null,
        minuteur: null,
        verrou: null,
        envoiEnCours: false,
        raison: null,
        code: 0,

        init() {
            this.file = this.lireFile();

            if (!('geolocation' in navigator)) {
                this.etat = 'indisponible';

                return;
            }

            this.watchId = navigator.geolocation.watchPosition(
                (p) => this.surPosition(p),
                (e) => this.surErreur(e),
                { enableHighAccuracy: true, maximumAge: 2000, timeout: 20000 },
            );

            this.minuteur = setInterval(() => this.tick(), intervalle * 1000);

            this.surVisibilite = () => {
                if (document.visibilityState === 'visible') {
                    this.demanderVerrou();
                }
            };
            document.addEventListener('visibilitychange', this.surVisibilite);
            this.demanderVerrou();
        },

        destroy() {
            if (this.watchId !== null) {
                navigator.geolocation.clearWatch(this.watchId);
            }

            clearInterval(this.minuteur);
            document.removeEventListener('visibilitychange', this.surVisibilite);
            this.verrou?.release().catch(() => { });
        },

        get message() {
            const ecartee = {
                precision: 'GPS peu précis, la position n\'est pas partagée. Sortez à l\'extérieur.',
                region: 'vous êtes hors de la zone de livraison, la position n\'est pas partagée.',
                course: 'aucune course active pour ce suivi.',
                age: 'position trop ancienne, en attente d\'une nouvelle.',
            };

            return {
                attente: 'en attente du signal GPS. Gardez l\'application ouverte.',
                actif: 'position partagée avec le client.',
                ecartee: ecartee[this.raison] ?? 'position non retenue.',
                erreur: `le serveur refuse les positions (code ${this.code}).`,
                hors_reseau: `pas de réseau, ${this.file.length} position(s) gardée(s), envoi dès que possible.`,
                refuse: 'localisation refusée. Autorisez-la dans les réglages du navigateur.',
                session: 'session expirée. Reconnectez-vous.',
                indisponible: 'ce téléphone ne permet pas le GPS.',
            }[this.etat];
        },

        surPosition(p) {
            this.derniere = {
                latitude: p.coords.latitude,
                longitude: p.coords.longitude,
                precision_m: p.coords.accuracy,
                mesure_ms: p.timestamp,
            };

            if (this.etat === 'refuse') {
                this.etat = 'attente';
            }
        },

        surErreur(e) {
            if (e.code === 1) {
                this.etat = 'refuse';
            }
        },

        async demanderVerrou() {
            try {
                if ('wakeLock' in navigator) {
                    this.verrou = await navigator.wakeLock.request('screen');
                }
            } catch (e) {
                // Refusé (économie d'énergie ou page non sécurisée) : le suivi continue sans
            }
        },

        async tick() {
            if (this.derniere && this.derniere.mesure_ms > this.dernierMis) {
                this.file.push(this.derniere);
                this.dernierMis = this.derniere.mesure_ms;

                if (this.file.length > 60) {
                    this.file.shift();
                }

                this.sauverFile();
            }

            await this.vider();
        },

        async vider() {
            if (this.envoiEnCours || this.etat === 'session') {
                return;
            }

            this.envoiEnCours = true;

            try {
                while (this.file.length > 0) {
                    const point = this.file[0];
                    const reponse = await fetch(url, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body: JSON.stringify({
                            latitude: point.latitude,
                            longitude: point.longitude,
                            precision_m: point.precision_m,
                            age_ms: Math.max(0, Date.now() - point.mesure_ms),
                        }),
                    });

                    if (reponse.status === 401 || reponse.status === 419) {
                        this.etat = 'session';

                        return;
                    }

                    if (reponse.status === 429 || reponse.status >= 500) {
                        this.etat = 'hors_reseau';

                        return;
                    }

                    this.file.shift();
                    this.sauverFile();

                    if (!reponse.ok) {
                        this.code = reponse.status;
                        this.etat = 'erreur';

                        continue;
                    }

                    this.raison = reponse.headers.get('X-Position-Ecartee');
                    this.etat = this.raison ? 'ecartee' : 'actif';
                }
            } catch (e) {
                this.etat = 'hors_reseau';
            } finally {
                this.envoiEnCours = false;
            }
        },

        lireFile() {
            try {
                return JSON.parse(localStorage.getItem('bls.gps.file') ?? '[]');
            } catch (e) {
                return [];
            }
        },

        sauverFile() {
            try {
                localStorage.setItem('bls.gps.file', JSON.stringify(this.file));
            } catch (e) {
                // Stockage plein ou bloqué : la file reste en mémoire
            }
        },
    }));
});