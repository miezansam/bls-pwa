@props(['surFond' => 'clair'])

@php
    $fichier = collect(['svg', 'png', 'webp'])->first(fn (string $extension) => file_exists(public_path("images/logo.{$extension}")));
@endphp

@if ($fichier)
    {{-- Sur fond sombre, le logo est posé sur une plaque blanche pour rester lisible --}}
    <span @class(['inline-block', 'rounded-sm bg-white p-2' => $surFond === 'sombre'])>
        <img src="{{ asset("images/logo.{$fichier}") }}" alt="BLS Livraison" {{ $attributes->class(['w-auto']) }}>
    </span>
@else
    <span {{ $attributes->class(['font-display font-bold leading-none tracking-tight', 'text-white' => $surFond === 'sombre', 'text-encre' => $surFond !== 'sombre']) }}>
        BLS Livraison
    </span>
@endif