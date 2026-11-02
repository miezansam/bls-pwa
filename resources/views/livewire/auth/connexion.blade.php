<div>
    @if ($etape === 'telephone')
        <form wire:submit="demanderCode" class="space-y-5">
            <div>
                <h1 class="text-2xl font-bold">Connexion</h1>
                <p class="mt-1 text-gray-600">Entrez votre numéro : nous vous envoyons un code par SMS.</p>
            </div>

            <div>
                <label for="telephone" class="block text-base font-semibold">Votre numéro de téléphone</label>
                <input id="telephone" type="tel" inputmode="tel" autocomplete="tel" wire:model="telephone"
                       placeholder="07 00 00 00 00" autofocus
                       class="mt-2 block w-full rounded-xl border-2 border-gray-300 px-4 py-4 text-xl focus:border-green-600 focus:outline-none">
                @error('telephone')
                    <p class="mt-2 text-base font-medium text-red-700" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled"
                    class="w-full rounded-xl bg-green-700 px-4 py-4 text-lg font-semibold text-white active:bg-green-800 disabled:opacity-60">
                <span wire:loading.remove wire:target="demanderCode">Recevoir mon code</span>
                <span wire:loading wire:target="demanderCode">Envoi en cours…</span>
            </button>

            <p class="text-center text-base text-gray-600">
                Pas encore de compte ?
                <a href="{{ route('inscription') }}" class="font-semibold text-green-800 underline">Créer mon compte</a>
            </p>
        </form>
    @else
        @include('livewire.auth.saisie-code')
    @endif
</div>