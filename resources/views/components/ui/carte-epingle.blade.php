@props(['lieu', 'centres'])

<div x-data="carteEpingle({ lieu: '{{ $lieu }}', centres: @js($centres), defaut: @js(array_values(config('bls.centre_defaut'))) })"
     class="flex flex-col gap-3">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
        <button type="button" @click="basculer()"
                class="min-h-11 text-left font-semibold text-marine underline underline-offset-4">
            <span x-text="ouverte ? 'Masquer la carte' : (placee ? 'Modifier l\'épingle' : 'Placer sur la carte (facultatif)')"></span>
        </button>
        <span x-show="placee" x-cloak class="text-base text-gris">Position enregistrée</span>
        <span x-show="chargement" x-cloak class="text-base text-gris">Chargement de la carte…</span>
    </div>

    <p x-show="indisponible" x-cloak class="text-base text-gris" role="status">
        La carte ne charge pas. Pas de souci, le repère suffit.
    </p>

    <div x-show="ouverte && !indisponible" x-cloak class="flex flex-col gap-3">
        <div wire:ignore x-ref="carte" class="h-72 w-full rounded-lg border-2 border-trait"></div>

        <p class="text-base text-gris">Touchez la carte pour poser l'épingle, puis déplacez-la si besoin.</p>

        <div class="flex flex-wrap gap-x-4 gap-y-2">
            <button type="button" @click="utiliserMaPosition()"
                    class="min-h-11 font-semibold text-marine underline underline-offset-4">
                Utiliser ma position
            </button>
            <button type="button" x-show="placee" x-cloak @click="retirer()"
                    class="min-h-11 font-semibold text-marine underline underline-offset-4">
                Retirer l'épingle
            </button>
        </div>

        <p x-show="messagePosition" x-cloak x-text="messagePosition" class="text-base text-alerte" role="alert"></p>
    </div>

    @php($erreurPosition = $errors->first($lieu.'Latitude') ?: $errors->first($lieu.'Longitude'))
    @if ($erreurPosition)
        <p class="text-base text-alerte" role="alert">{{ $erreurPosition }}</p>
    @endif
</div>