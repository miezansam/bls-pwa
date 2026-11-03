<div class="flex flex-1 flex-col lg:flex-none">
    @if ($etape === 'telephone')
        <form wire:submit="demanderCode" class="flex flex-1 flex-col lg:flex-none" novalidate>
            <div class="flex-1 space-y-6 px-5 pt-8 lg:flex-none lg:px-0 lg:pt-0">
                <div>
                    <h1 class="font-display text-4xl font-bold leading-none">Créer mon compte</h1>
                    <p class="mt-3 text-lg text-gris">Entrez votre nom et votre numéro. Nous vous envoyons un code par SMS pour confirmer.</p>
                </div>

                <x-ui.champ nom="nom" label="Votre nom" type="text" autocomplete="name" placeholder="Aya Kouassi"
                            wire:model="nom" :erreur="$errors->first('nom')" autofocus />

                <x-ui.champ nom="telephone" label="Votre numéro de téléphone" prefixe="+225"
                            type="tel" inputmode="tel" autocomplete="tel" placeholder="07 00 00 00 00"
                            wire:model="telephone" :erreur="$errors->first('telephone')" />
            </div>

            <x-ui.barre-action>
                <x-ui.bouton cible="demanderCode">Recevoir mon code</x-ui.bouton>

                <p class="mt-4 text-center text-base text-gris">
                    Vous avez déjà un compte ?
                    <a href="{{ route('login') }}" class="font-semibold text-marine underline underline-offset-4">Me connecter</a>
                </p>
            </x-ui.barre-action>
        </form>
    @else
        @include('livewire.auth.saisie-code')
    @endif
</div>