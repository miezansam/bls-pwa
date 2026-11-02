<form wire:submit="verifierCode" class="flex flex-1 flex-col lg:flex-none" novalidate>
    <div class="flex-1 space-y-6 px-5 pt-8 lg:flex-none lg:px-0 lg:pt-0">
        <div>
            <h1 class="font-display text-4xl font-bold leading-none">Entrez le code</h1>
            <p class="mt-3 text-lg text-gris">
                Nous l'avons envoyé par SMS au
                <span class="whitespace-nowrap font-semibold text-encre">{{ \App\Support\Telephone::formater($telephone) }}</span>.
                <button type="button" wire:click="modifierNumero" class="font-semibold text-marque underline underline-offset-4">
                    Changer de numéro
                </button>
            </p>
        </div>

        @if (app()->environment('local') && ($smsDev = \Illuminate\Support\Facades\Cache::get('sms-dev:' . $telephone)))
            <div class="rounded-lg border-2 border-signal bg-signal/20 px-4 py-3 text-base">
                <p class="font-semibold">Mode développement</p>
                <p>{{ $smsDev }}</p>
            </div>
        @endif

        <x-ui.champ nom="code" label="Code reçu par SMS" variante="code" type="text" inputmode="numeric"
                    autocomplete="one-time-code" placeholder="000000" wire:model="code"
                    :erreur="$errors->first('code') ?: $errors->first('telephone')" autofocus />
    </div>

    <x-ui.barre-action>
        <x-ui.bouton cible="verifierCode">Valider mon code</x-ui.bouton>

        <div wire:key="renvoi-{{ $envois }}"
             x-data="{ restant: {{ $delaiRenvoi }} }"
             x-init="let t = setInterval(() => { if (restant > 0) { restant-- } else { clearInterval(t) } }, 1000)"
             class="mt-4 text-center text-base">
            <p x-show="restant > 0" x-cloak class="text-gris">
                Vous pourrez demander un nouveau code dans <span x-text="restant" class="font-semibold text-encre"></span> secondes.
            </p>
            <button type="button" x-show="restant <= 0" x-cloak wire:click="renvoyerCode"
                    class="font-semibold text-marque underline underline-offset-4">
                Recevoir un nouveau code
            </button>
        </div>
    </x-ui.barre-action>
</form>