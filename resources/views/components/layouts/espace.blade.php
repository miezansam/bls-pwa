<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#15803d">
    <title>{{ $title ?? 'BLS Livraison' }}</title>
    <style>[x-cloak] { display: none !important; }</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    <header class="flex items-center justify-between bg-green-700 px-4 py-3 text-white">
        <span class="text-lg font-bold">BLS Livraison</span>

        <form method="POST" action="{{ route('deconnexion') }}">
            @csrf
            <button type="submit" class="rounded-lg bg-white/15 px-3 py-2 text-sm font-semibold active:bg-white/25">
                Déconnexion
            </button>
        </form>
    </header>

    <main class="mx-auto w-full max-w-md px-4 py-6">
        {{ $slot }}
    </main>
</body>
</html>