<div wire:poll.30s class="space-y-8">
    <div class="space-y-3">
        <h1 class="font-display text-4xl font-bold leading-none">Bonjour {{ auth()->user()->nom }}</h1>

        <p class="text-lg">Mode : <strong>{{ $this->profil->statut->libelle() }}</strong></p>

        @if ($this->profil->statut === \App\Enums\StatutLivreur::Occupe)
            <p class="text-lg text-gris">Vous êtes sur une course. Terminez-la pour changer de mode.</p>
        @elseif ($this->estDisponible)
            <x-ui.bouton type="button" variante="secondaire" cible="basculer" wire:click="basculer">
                Me mettre indisponible
            </x-ui.bouton>
        @else
            <x-ui.bouton type="button" cible="basculer" wire:click="basculer">
                Me mettre disponible
            </x-ui.bouton>
        @endif
    </div>

    @if ($erreur !== '')
        <p class="border-l-4 border-alerte py-1 pl-3 text-lg text-alerte" role="alert">{{ $erreur }}</p>
    @endif

    @if ($this->courseEnCours)
        @php($course = $this->courseEnCours)

        <section class="border-t-2 border-marine pt-4">
            <p class="text-gris">{{ $course->reference }}</p>
            <h2 class="font-display text-3xl font-bold">{{ $course->statut->libelle() }}</h2>

            <dl class="mt-3 flex flex-col gap-3 text-lg">
                <div>
                    <dt class="text-gris">Retrait</dt>
                    <dd class="font-semibold">{{ $course->zoneRetrait?->nom }}, {{ $course->retrait_repere }}</dd>
                    <dd>
                        <a href="tel:{{ preg_replace('/\s+/', '', $course->retrait_contact_telephone) }}"
                           class="font-semibold text-marine underline underline-offset-4">{{ $course->retrait_contact_telephone }}</a>
                    </dd>
                </div>
                <div>
                    <dt class="text-gris">Livraison</dt>
                    <dd class="font-semibold">{{ $course->zoneLivraison?->nom }}, {{ $course->livraison_repere }}</dd>
                    <dd>
                        <a href="tel:{{ preg_replace('/\s+/', '', $course->livraison_contact_telephone) }}"
                           class="font-semibold text-marine underline underline-offset-4">{{ $course->livraison_contact_telephone }}</a>
                    </dd>
                </div>
                <div>
                    <dt class="text-gris">À livrer</dt>
                    <dd>{{ $course->description }}</dd>
                </div>
                <div>
                    <dt class="text-gris">À encaisser</dt>
                    <dd class="whitespace-nowrap font-display text-3xl font-bold">
                        {{ number_format($course->montant_total, 0, ',', ' ') }} FCFA
                    </dd>
                    @if ($course->montant_articles > 0)
                        <dd class="text-base text-gris">
                            dont {{ number_format($course->montant_articles, 0, ',', ' ') }} FCFA pour les articles, que vous gardez.
                        </dd>
                    @endif
                </div>
            </dl>

            <div x-data="suiviGps({ url: @js(route('livreur.position')), intervalle: @js(config('bls.gps.intervalle_envoi_secondes')) })"
                class="mt-4 border-l-4 py-1 pl-3 text-base"
                :class="['refuse', 'session', 'indisponible', 'erreur', 'ecartee'].includes(etat) ? 'border-alerte text-alerte' : 'border-ambre'"
                role="status">
                <span class="font-semibold">Suivi GPS :</span> <span x-text="message"></span>
            </div>

            @if ($course->statut === \App\Enums\StatutCommande::EnRouteRetrait && $course->type === \App\Enums\TypeCommande::Repas)
                <div class="mt-6">
                    <x-ui.champ nom="montantArticles" label="Montant payé pour les articles (FCFA)"
                                type="text" inputmode="numeric" placeholder="2500"
                                wire:model="montantArticles" :erreur="$errors->first('montantArticles')" />
                </div>
            @endif

            @if ($course->statut === \App\Enums\StatutCommande::EnRouteLivraison)
                <div class="mt-6">
                    <x-ui.champ nom="codeLivraison" label="Code donné par le client"
                                type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="4" placeholder="0000"
                                wire:model="codeLivraison" :erreur="$errors->first('codeLivraison')" />
                </div>
            @endif
        </section>
    @endif

    <section class="space-y-6">
        <h2 class="font-display text-3xl font-bold">Offres de course</h2>

        @forelse ($this->offres as $offre)
            @php($commande = $offre->commande)

            <article wire:key="offre-{{ $offre->id }}" class="border-t-2 border-marine pt-4">
                <p class="text-gris">{{ ucfirst($commande->type->value) }} · {{ $commande->reference }}</p>
                <p class="whitespace-nowrap font-display text-4xl font-bold">
                    {{ number_format($commande->frais_livraison, 0, ',', ' ') }} FCFA
                </p>

                <dl class="mt-3 flex flex-col gap-2 text-lg">
                    <div>
                        <dt class="text-gris">Retrait</dt>
                        <dd class="font-semibold">{{ $commande->zoneRetrait?->nom }}, {{ $commande->retrait_repere }}</dd>
                    </div>
                    <div>
                        <dt class="text-gris">Livraison</dt>
                        <dd class="font-semibold">{{ $commande->zoneLivraison?->nom }}, {{ $commande->livraison_repere }}</dd>
                    </div>
                </dl>

                @if ($offre->expire_le)
                    <p class="mt-2 text-base text-gris">Répondre avant {{ $offre->expire_le->timezone(config('app.timezone'))->format('H:i') }}</p>
                @endif

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <x-ui.bouton type="button" variante="secondaire" cible="refuser({{ $offre->id }})"
                                 wire:click="refuser({{ $offre->id }})" wire:confirm="Refuser cette course ?">
                        Refuser
                    </x-ui.bouton>
                    <x-ui.bouton type="button" cible="accepter({{ $offre->id }})" wire:click="accepter({{ $offre->id }})">
                        Accepter
                    </x-ui.bouton>
                </div>
            </article>
        @empty
            <p class="text-lg text-gris">
                @if ($this->profil->statut === \App\Enums\StatutLivreur::Occupe)
                    Terminez votre course en cours pour recevoir d'autres offres.
                @elseif ($this->estDisponible)
                    Aucune offre pour le moment. Elles apparaissent ici automatiquement.
                @else
                    Passez en mode disponible pour recevoir des courses.
                @endif
            </p>
        @endforelse
    </section>

    <section class="border-t-2 border-marine pt-4">
        <h2 class="font-display text-3xl font-bold">Mon activité</h2>

        <dl class="mt-3 flex flex-col gap-3 text-lg">
            <div>
                <dt class="text-gris">Courses livrées aujourd'hui</dt>
                <dd class="font-display text-3xl font-bold">{{ $this->bilanDuJour['livrees'] }}</dd>
            </div>
            <div>
                <dt class="text-gris">Caisse à reverser</dt>
                <dd class="whitespace-nowrap font-display text-3xl font-bold">
                    {{ number_format($this->bilanDuJour['caisse'], 0, ',', ' ') }} FCFA
                </dd>
            </div>
        </dl>
    </section>

    @if ($this->courseEnCours)
        <x-ui.barre-action class="-mx-5 -mb-6 lg:mx-0 lg:mb-0">
            @if ($this->courseEnCours->statut === \App\Enums\StatutCommande::EnRouteLivraison)
                <x-ui.bouton type="button" cible="confirmerLivraison" wire:click="confirmerLivraison">
                    Confirmer la livraison
                </x-ui.bouton>
            @elseif ($this->libelleAction)
                <x-ui.bouton type="button" cible="avancer" wire:click="avancer">
                    {{ $this->libelleAction }}
                </x-ui.bouton>
            @endif
        </x-ui.barre-action>
    @endif
</div>