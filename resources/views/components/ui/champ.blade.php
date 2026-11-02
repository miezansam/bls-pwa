@props(['nom', 'label', 'erreur' => null, 'aide' => null, 'prefixe' => null, 'variante' => 'normal'])

<div>
    <label for="{{ $nom }}" class="block text-base font-semibold">{{ $label }}</label>

    <div @class([
        'mt-2 flex overflow-hidden rounded-lg border-2 bg-white',
        'border-encre/30 focus-within:border-encre' => ! $erreur,
        'border-alerte' => $erreur,
    ])>
        @if ($prefixe)
            <span class="flex items-center border-r-2 border-trait bg-fond px-4 text-lg font-medium text-gris">{{ $prefixe }}</span>
        @endif

        <input id="{{ $nom }}" name="{{ $nom }}"
               @if ($erreur) aria-invalid="true" aria-describedby="{{ $nom }}-erreur" @endif
               {{ $attributes->class([
                   'block w-full min-w-0 bg-transparent px-4 py-4 outline-none placeholder:text-gris/60',
                   'text-xl' => $variante !== 'code',
                   'text-center font-display text-3xl font-semibold tracking-[0.4em]' => $variante === 'code',
               ]) }}>
    </div>

    @if ($aide && ! $erreur)
        <p class="mt-2 text-base text-gris">{{ $aide }}</p>
    @endif

    @if ($erreur)
        <p id="{{ $nom }}-erreur" class="mt-2 text-base font-semibold text-alerte" role="alert">{{ $erreur }}</p>
    @endif
</div>