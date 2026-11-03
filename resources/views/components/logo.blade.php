@props(['variante' => 'inverse', 'largeur' => 130])

@php
    $fichier = match ($variante) {
        'inverse' => 'logo-inverse.png',
        'compact-inverse' => 'logo-compact-inverse.png',
        'compact' => 'logo-compact.png',
        default => 'logo.png',
    };
@endphp

<img
    src="{{ asset('images/'.$fichier) }}"
    alt="Béni Livraison Service"
    width="{{ $largeur }}"
    {{ $attributes->class(['h-auto max-w-full']) }}
>