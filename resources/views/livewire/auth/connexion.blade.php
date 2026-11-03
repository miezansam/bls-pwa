<div class="flex flex-1 flex-col lg:flex-none">
    @if ($etape === 'telephone')
        <form wire:submit="demanderCode" class="flex flex-1 flex-col lg:flex-none" novalidate>
            <div class="flex-1 space-y-6 px-5 pt-8 lg:flex-none lg:px-0 lg:pt-0">
                <div>
                    <h1 class="font-display text-4xl font-bold leading-none">Connexion</h1>
                    <p class="mt-3 text-lg text-gris">Entrez votre numéro. Nous vous envoyons un code par SMS.</p>
                </div>

                <x-ui.champ nom="telephone" label="Votre numéro de téléphone" prefixe="+225"
                            type="tel" inputmode="tel" autocomplete="tel" placeholder="07 00 00 00 00"
                            wire:model="telephone" :erreur="$errors->first('telephone')" autofocus />
            </div>

            <x-ui.barre-action>
                <x-ui.bouton cible="demanderCode">Recevoir mon code</x-ui.bouton>

                <p class="mt-4 text-center text-base text-gris">
                    Pas encore de compte ?
                    <a href="{{ route('inscription') }}" class="font-semibold text-marine underline underline-offset-4">Créer mon compte</a>
                </p>
            </x-ui.barre-action>
        </form>
    @else
        @include('livewire.auth.saisie-code')
    @endif
</div>