<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('partials.entete-html')
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-white lg:grid lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
    <aside class="hidden bg-marine p-12 text-white lg:flex lg:flex-col lg:justify-between">
        <x-logo variante="inverse" :largeur="200" />

        <p class="max-w-md font-display text-5xl font-bold leading-tight">
            Colis et repas livrés à Adzopé et dans les villages voisins.
        </p>

        <x-apercu-tarifs />
    </aside>

    <div class="flex min-h-dvh flex-col">
        <header class="border-b-[3px] border-ambre bg-marine px-5 py-4 lg:hidden">
            <x-logo variante="inverse" :largeur="130" />
        </header>

        <main class="mx-auto flex w-full max-w-md flex-1 flex-col lg:justify-center lg:py-12">
            {{ $slot }}
        </main>
    </div>
</body>
</html>