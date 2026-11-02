@props(['cible' => null, 'type' => 'submit', 'variante' => 'principal'])

<button type="{{ $type }}" wire:loading.attr="disabled" @if ($cible) wire:target="{{ $cible }}" @endif
    {{ $attributes->class([
        'inline-flex w-full items-center justify-center gap-3 rounded-lg px-5 py-4 text-lg font-semibold disabled:opacity-60',
        'bg-marque text-white active:bg-marque-fonce' => $variante === 'principal',
        'border-2 border-encre bg-white text-encre active:bg-fond' => $variante === 'secondaire',
    ]) }}>
    @if ($cible)
        <svg wire:loading wire:target="{{ $cible }}" class="size-5 animate-spin motion-reduce:animate-none"
             viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity=".25" />
            <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
        </svg>
    @endif

    {{ $slot }}
</button>