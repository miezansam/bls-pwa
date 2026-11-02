@if ($prixMinimum)
    <div class="border-t-2 border-white/30 pt-6">
        <p class="font-display text-7xl font-bold leading-none">
            {{ number_format($prixMinimum, 0, ',', ' ') }}
            <span class="text-3xl font-semibold">FCFA</span>
        </p>
        <p class="mt-3 max-w-xs text-lg text-white/90">
            C'est le prix le plus bas d'une livraison à Adzopé. Le prix exact s'affiche avant que vous validiez votre commande.
        </p>
    </div>
@endif