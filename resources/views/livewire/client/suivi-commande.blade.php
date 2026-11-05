<div wire:poll.60s>
    <p class="text-gris">Commande {{ $commande->reference }}</p>
    <h1 class="font-display text-4xl font-bold">{{ $commande->statut->libelle() }}</h1>

    <div class="mt-6 border-y-2 border-marine py-4">
        <p class="text-gris">Code de livraison</p>
        <p class="font-display text-6xl font-bold tracking-[0.3em]">{{ $commande->code_livraison }}</p>
        <p class="mt-2 text-base text-gris">Donnez ce code au livreur à la remise. Ne le donnez à personne d'autre.</p>
    </div>

    @if ($suiviActif)
        <div class="mt-6">
            @if ($eta)
                <p class="mb-3 font-display text-3xl font-bold">{{ $eta['libelle'] }} : environ {{ $eta['minutes'] }} min
                </p>
            @endif
            <section wire:ignore class="flex flex-col gap-2" x-data="carteSuivi({ canal: 'commande.{{ $commande->id }}', position: @js($position), defaut: @js(array_values(config('bls.centre_defaut'))) })">
                <h2 class="font-display text-2xl font-bold">Votre livreur</h2>

                <p class="text-base" :class="perdu ? 'text-alerte' : 'text-gris'" role="status" x-text="texteAge"></p>
                <p x-show="!connecte" x-cloak class="text-base text-gris" role="status">Connexion en direct perdue,
                    reconnexion…</p>
                <p x-show="indisponible" x-cloak class="text-base text-gris">La carte ne charge pas. Le statut reste à
                    jour ci-dessus.</p>

                <div x-show="!indisponible" x-ref="carte" class="h-72 w-full rounded-lg border-2 border-trait"></div>

                <button type="button" x-show="!suivre && position" x-cloak @click="recentrer()"
                    class="min-h-11 self-start font-semibold text-marine underline underline-offset-4">
                    Suivre le livreur
                </button>
            </section>
        </div>
    @endif

    <dl class="mt-6 flex flex-col gap-3 text-lg">
        <div>
            <dt class="text-gris">Livraison</dt>
            <dd class="whitespace-nowrap font-semibold">{{ number_format($commande->frais_livraison, 0, ',', ' ') }}
                FCFA</dd>
        </div>

        @if ($commande->montant_articles > 0)
            <div>
                <dt class="text-gris">Repas avancé par le livreur</dt>
                <dd class="whitespace-nowrap font-semibold">
                    {{ number_format($commande->montant_articles, 0, ',', ' ') }} FCFA</dd>
            </div>
            <div>
                <dt class="text-gris">Total à payer</dt>
                <dd class="whitespace-nowrap font-semibold">{{ number_format($commande->montant_total, 0, ',', ' ') }}
                    FCFA</dd>
            </div>
        @endif
    </dl>

    <a href="{{ route('client.accueil') }}"
        class="mt-8 inline-block font-semibold text-marine underline underline-offset-4">
        Retour à l'accueil
    </a>
</div>
