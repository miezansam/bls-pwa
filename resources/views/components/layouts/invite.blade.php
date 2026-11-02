<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#ffc20e">
    <title>{{ $title ?? 'BLS Livraison' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-white lg:grid lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
    <aside class="hidden bg-marque p-12 text-white lg:flex lg:flex-col lg:justify-between">
        <x-logo surFond="sombre" class="h-12 text-4xl" />

        <p class="max-w-md font-display text-5xl font-bold leading-tight">
            Colis et repas livrés à Adzopé et dans les villages voisins.
        </p>

        <x-apercu-tarifs />
    </aside>

    <div class="flex min-h-dvh flex-col">
        <header class="bg-signal px-5 py-4 lg:hidden">
            <x-logo class="h-9 text-3xl" />
        </header>

        <main class="mx-auto flex w-full max-w-md flex-1 flex-col lg:justify-center lg:py-12">
            {{ $slot }}
        </main>
    </div>
</body>
</html>