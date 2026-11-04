<div wire:poll.60s>
    <p class="text-gris">Commande {{ $commande->reference }}</p>
    <h1 class="font-display text-4xl font-bold">{{ $commande->statut->libelle() }}</h1>
    <div class="mt-6 border-y-2 border-marine py-4">
        <p class="text-gris">Code de livraison</p>
        <p class="font-display text-6xl font-bold tracking-[0.3em]">{{ $commande->code_livraison }}</p>
        <p class="mt-2 text-base text-gris">Donnez ce code au livreur à la remise. Ne le donnez à personne d'autre.</p>
    </div>

    <div>
        <dt class="text-gris">Livraison</dt>
        <dd class="whitespace-nowrap font-semibold">{{ number_format($commande->frais_livraison, 0, ',', ' ') }} FCFA
        </dd>
    </div>
    @if ($commande->montant_articles > 0)
        <div>
            <dt class="text-gris">Repas avancé par le livreur</dt>
            <dd class="whitespace-nowrap font-semibold">{{ number_format($commande->montant_articles, 0, ',', ' ') }}
                FCFA</dd>
        </div>
        <div>
            <dt class="text-gris">Total à payer</dt>
            <dd class="whitespace-nowrap font-semibold">{{ number_format($commande->montant_total, 0, ',', ' ') }} FCFA
            </dd>
        </div>
    @endif

    <a href="{{ route('client.accueil') }}"
        class="mt-8 inline-block font-semibold text-marine underline underline-offset-4">
        Retour à l'accueil
    </a>
</div>
