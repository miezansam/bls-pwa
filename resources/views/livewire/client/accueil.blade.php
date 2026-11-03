<div class="space-y-6">
    <div class="space-y-3">
        <h1 class="font-display text-4xl font-bold leading-none">Bonjour {{ auth()->user()->nom }}</h1>
        <p class="text-lg text-gris">Colis et repas livrés à Adzopé et dans les villages voisins.</p>
    </div>

    <a href="{{ route('client.commander') }}"
       class="inline-flex w-full items-center justify-center rounded-lg bg-ambre px-5 py-4 text-lg font-bold text-marine active:brightness-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marine">
        Nouvelle commande
    </a>

    @if ($this->commandeEnCours)
        <a href="{{ route('client.commande', ['commande' => $this->commandeEnCours->reference]) }}"
           class="block border-y-2 border-marine py-4">
            <span class="block text-gris">Commande en cours</span>
            <span class="block text-lg font-semibold">{{ $this->commandeEnCours->reference }}</span>
            <span class="mt-1 block font-semibold text-marine underline underline-offset-4">Suivre ma commande</span>
        </a>
    @endif
</div>