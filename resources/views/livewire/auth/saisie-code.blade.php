<form wire:submit="verifierCode" class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold">Entrez le code</h1>
        <p class="mt-1 text-gray-600">
            Code envoyé par SMS au {{ \App\Support\Telephone::formater($telephone) }}.
            <button type="button" wire:click="modifierNumero" class="font-semibold text-green-800 underline">Modifier</button>
        </p>
    </div>

    @if (app()->environment('local') && ($smsDev = \Illuminate\Support\Facades\Cache::get('sms-dev:' . $telephone)))
        <p class="rounded-xl bg-amber-100 px-4 py-3 text-sm text-amber-900">
            Mode développement, SMS : {{ $smsDev }}
        </p>
    @endif

    <div>
        <label for="code" class="block text-base font-semibold">Code reçu par SMS</label>
        <input id="code" type="text" inputmode="numeric" autocomplete="one-time-code" wire:model="code"
               placeholder="123456" autofocus
               class="mt-2 block w-full rounded-xl border-2 border-gray-300 px-4 py-4 text-center text-2xl tracking-widest focus:border-green-600 focus:outline-none">
        @error('code')
            <p class="mt-2 text-base font-medium text-red-700" role="alert">{{ $message }}</p>
        @enderror
        @error('telephone')
            <p class="mt-2 text-base font-medium text-red-700" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <button type="submit" wire:loading.attr="disabled"
            class="w-full rounded-xl bg-green-700 px-4 py-4 text-lg font-semibold text-white active:bg-green-800 disabled:opacity-60">
        <span wire:loading.remove wire:target="verifierCode">Valider</span>
        <span wire:loading wire:target="verifierCode">Vérification…</span>
    </button>

    <div wire:key="renvoi-{{ $envois }}"
         x-data="{ restant: {{ $delaiRenvoi }} }"
         x-init="let t = setInterval(() => { if (restant > 0) { restant-- } else { clearInterval(t) } }, 1000)"
         class="text-center">
        <p x-show="restant > 0" x-cloak class="text-base text-gray-600">
            Vous pourrez demander un nouveau code dans <span x-text="restant"></span> s
        </p>
        <button type="button" x-show="restant <= 0" x-cloak wire:click="renvoyerCode"
                class="text-base font-semibold text-green-800 underline">
            Renvoyer le code
        </button>
    </div>
</form>